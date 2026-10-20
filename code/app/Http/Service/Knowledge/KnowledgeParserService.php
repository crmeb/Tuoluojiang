<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Knowledge;

use App\Constants\KnowledgeEnum;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * 知识库文档解析服务.
 */
class KnowledgeParserService extends BaseService
{
    /**
     * 解析文档文本.
     */
    public function parse(string $path, string $type, bool $withPdfOcr = true): string
    {
        return $this->parseWithImages($path, $type, $withPdfOcr)['text'];
    }

    /**
     * 解析文档正文并提取可 OCR 的图片路径。
     *
     * @return array{text:string,image_paths:list<string>}
     */
    public function parseWithImages(string $path, string $type, bool $withPdfOcr = true): array
    {
        $type = strtolower($type);
        if (! in_array($type, KnowledgeEnum::PARSEABLE_TYPES, true)) {
            throw $this->exception('当前仅支持 txt、md、docx、xlsx、pptx、pdf 文档解析');
        }

        $detectedType = $this->detectType($path);
        if ($detectedType !== null && $detectedType !== $type) {
            throw $this->exception('文件实际格式与扩展名不一致，请重新上传正确格式的文档');
        }

        $imagePaths = [];
        $content = match ($type) {
            'txt'      => $this->parseText($path),
            'md'       => $this->parseMarkdown($path, $imagePaths),
            'docx'     => $this->parseOffice($path, 'word', $imagePaths),
            'xlsx'     => $this->parseOffice($path, 'xl', $imagePaths),
            'pptx'     => $this->parseOffice($path, 'ppt', $imagePaths),
            'pdf'       => $this->parsePdf($path, $withPdfOcr),
        };

        // 有文本层的 PDF 也会含产品截图；抽内嵌图交给分片 OCR 策略（避免演示数据污染需靠后续 caption_only）.
        if ($type === 'pdf') {
            $imagePaths = array_merge($imagePaths, $this->extractPdfEmbeddedImages($path));
        }

        return [
            'text'        => $this->finalizeText($content, $type, ($type === 'pdf' && ! $withPdfOcr) || $imagePaths !== []),
            'image_paths' => array_values(array_unique($imagePaths)),
        ];
    }

    /**
     * 根据文件内容识别类型.
     */
    public function detectType(string $path): ?string
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        $header = (string) fread($handle, 8192);
        fclose($handle);

        if ($header === '') {
            return null;
        }

        if (str_starts_with($header, '%PDF')) {
            return 'pdf';
        }

        if (str_starts_with($header, "PK\x03\x04")) {
            return $this->detectOfficeOpenXmlType($path);
        }

        if ($this->looksLikePlainText($header)) {
            return 'txt';
        }

        return null;
    }

    /**
     * 解析扩展名与真实格式，优先信任文件内容.
     */
    public function resolveType(string $path, string $extension): string
    {
        $extension = strtolower(trim($extension));
        $detected  = $this->detectType($path);

        if ($detected !== null) {
            if ($extension !== '' && $extension !== $detected && ! $this->isTextAlias($extension, $detected)) {
                if ($detected === 'txt' && in_array($extension, ['pdf', 'docx', 'xlsx', 'pptx'], true)) {
                    throw $this->exception('附件文件读取失败，请确认附件已上传成功');
                }

                throw $this->exception('文件实际格式与扩展名不一致，请检查后重新上传');
            }

            return $detected;
        }

        if (! in_array($extension, KnowledgeEnum::PARSEABLE_TYPES, true)) {
            throw $this->exception('仅支持 txt、md、docx、xlsx、pptx、pdf 文档');
        }

        return $extension;
    }

    private function parseText(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw $this->exception('读取文档失败');
        }

        if ($this->isBinaryContent($content)) {
            throw $this->exception('文档内容不是有效文本，请确认文件类型是否正确');
        }

        return $content;
    }

    /**
     * @param list<string> $imagePaths
     */
    private function parseMarkdown(string $path, array &$imagePaths): string
    {
        $content = $this->parseText($path);
        $contentService = app(KnowledgeChunkContentService::class);
        $imagePaths = $contentService->resolveImagePaths($contentService->extractImageUrls($content));

        return $content;
    }

    /**
     * @param list<string> $imagePaths
     */
    private function parseOffice(string $path, string $mediaPrefix, array &$imagePaths): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw $this->exception('Office 文档读取失败');
        }

        $tempDir = storage_path('app/temp/knowledge/document_images/' . md5($path));
        if (! is_dir($tempDir) && ! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            $zip->close();
            throw $this->exception('无法创建文档图片临时目录');
        }
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = (string) $zip->getNameIndex($i);
            if (! str_starts_with($name, $mediaPrefix . '/media/') || str_ends_with($name, '/')) {
                continue;
            }
            $target = $tempDir . '/' . basename($name);
            if (@file_put_contents($target, $zip->getFromName($name)) !== false && is_readable($target)) {
                $imagePaths[] = $target;
            }
        }
        $zip->close();

        return match ($mediaPrefix) {
            'word' => $this->parseWord($path),
            'xl'   => $this->parseSpreadsheet($path),
            default => $this->parsePresentation($path),
        };
    }

    private function parseWord(string $path): string
    {
        $document = WordIOFactory::load($path);
        $lines    = [];
        foreach ($document->getSections() as $section) {
            $this->collectWordElements($section->getElements(), $lines);
        }

        return implode("\n", $lines);
    }

    private function collectWordElements(array $elements, array &$lines): void
    {
        foreach ($elements as $element) {
            if ($element instanceof TextRun) {
                $text = trim($element->getText());
                if ($text !== '') {
                    $lines[] = $text;
                }
                continue;
            }

            if (method_exists($element, 'getText')) {
                $text = $this->normalizeWordText($element->getText());
                if ($text !== '') {
                    $lines[] = $text;
                }
                continue;
            }

            if (method_exists($element, 'getElements')) {
                $this->collectWordElements($element->getElements(), $lines);
            }
            if (method_exists($element, 'getRows')) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $this->collectWordElements($cell->getElements(), $lines);
                    }
                }
            }
        }
    }

    /**
     * 将 PhpWord 元素文本统一转为字符串.
     * Title、Field 等元素的 getText() 可能返回 TextRun 对象.
     */
    private function normalizeWordText(mixed $text): string
    {
        if ($text instanceof TextRun) {
            return trim($text->getText());
        }
        if (is_array($text)) {
            return trim(implode('', array_filter($text, 'is_string')));
        }
        if (is_string($text)) {
            return trim($text);
        }

        return '';
    }

    private function parseSpreadsheet(string $path): string
    {
        $spreadsheet = SpreadsheetIOFactory::load($path);
        $lines       = [];
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $lines[] = '[' . $worksheet->getTitle() . ']';
            foreach ($worksheet->toArray(null, true, true, true) as $row) {
                $line = trim(implode("\t", array_map(static fn ($value) => trim((string) $value), $row)));
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        return implode("\n", $lines);
    }

    private function parsePresentation(string $path): string
    {
        $presentation = PresentationIOFactory::load($path);
        $lines        = [];
        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if (method_exists($shape, 'getPlainText')) {
                    $text = trim((string) $shape->getPlainText());
                    if ($text !== '') {
                        $lines[] = $text;
                    }
                } elseif (method_exists($shape, 'getText')) {
                    $text = trim((string) $shape->getText());
                    if ($text !== '') {
                        $lines[] = $text;
                    }
                }
            }
        }

        return implode("\n", $lines);
    }

    private function parsePdf(string $path, bool $withOcr): string
    {
        $process = new Process(['pdftotext', '-enc', 'UTF-8', $path, '-']);
        $process->setTimeout(max(1, (int) config('knowledge.pdf_ocr.parse_timeout', 600)));
        $process->run();
        if (! $process->isSuccessful()) {
            throw $this->exception('PDF 文档解析失败，请确认服务器已安装 pdftotext');
        }

        $text = $process->getOutput();

        return $withOcr ? app(KnowledgePdfOcrService::class)->enrich($path, $text) : $text;
    }

    private function finalizeText(string $content, string $type, bool $allowEmpty = false): string
    {
        $content = $this->sanitizeText($content);
        if ($type === 'pdf') {
            $content = $this->stripPdfToolNoise($content);
        }
        $content = KnowledgeTextNormalizer::normalize($content);
        if ($content === '' && ! $allowEmpty) {
            throw $this->exception('未能从文档中提取到有效文本');
        }

        if ($this->isBinaryContent($content)) {
            throw $this->exception('文档解析结果异常，请确认文件格式是否正确');
        }

        return $content;
    }

    /**
     * 剔除 pdftotext/poppler 残留告警（整行及历史 2>&1 嵌入正文的片段）.
     */
    private function stripPdfToolNoise(string $content): string
    {
        $content = preg_replace('/^(?:Syntax\s+(?:Warning|Error)|Warning|Error):\s.*$/mi', '', $content) ?? $content;
        $content = preg_replace(
            '/\?*\s*Syntax\s+Warning:\s*Mismatch between font type and embedded font file\s*\?*/iu',
            '',
            $content
        ) ?? $content;
        $content = preg_replace("/\n{3,}/", "\n\n", $content) ?? $content;

        return $content;
    }

    private function sanitizeText(string $content): string
    {
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $content) ?? '';
    }

    private function isBinaryContent(string $content): bool
    {
        if ($content === '') {
            return false;
        }

        if (str_contains($content, "\0")) {
            return true;
        }

        if (str_starts_with($content, "PK\x03\x04") || str_starts_with($content, '%PDF')) {
            return true;
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            return true;
        }

        $sample = substr($content, 0, 4096);

        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $sample) === 1;
    }

    private function looksLikePlainText(string $header): bool
    {
        if ($this->isBinaryContent($header)) {
            return false;
        }

        return preg_match('//u', $header) === 1;
    }

    private function detectOfficeOpenXmlType(string $path): ?string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        try {
            if ($zip->locateName('word/document.xml') !== false) {
                return 'docx';
            }
            if ($zip->locateName('xl/workbook.xml') !== false) {
                return 'xlsx';
            }
            if ($zip->locateName('ppt/presentation.xml') !== false) {
                return 'pptx';
            }
        } finally {
            $zip->close();
        }

        return null;
    }

    private function isTextAlias(string $extension, string $detected): bool
    {
        return in_array($extension, ['txt', 'md'], true) && $detected === 'txt';
    }

    /**
     * 用 pdfimages 抽取 PDF 内嵌图（产品截图等），过滤过小图标.
     *
     * @return list<string>
     */
    private function extractPdfEmbeddedImages(string $pdfPath): array
    {
        if (! is_readable($pdfPath) || ! $this->commandExists('pdfimages')) {
            return [];
        }

        $tempDir = storage_path('app/temp/knowledge/pdf_images/' . md5($pdfPath . microtime(true)));
        if (! is_dir($tempDir) && ! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            return [];
        }

        try {
            $prefix  = $tempDir . '/img';
            $process = new Process(['pdfimages', '-all', $pdfPath, $prefix]);
            $process->setTimeout(max(30, min(300, (int) config('knowledge.pdf_ocr.parse_timeout', 600))));
            $process->run();
            if (! $process->isSuccessful()) {
                $this->cleanupDir($tempDir);

                return [];
            }

            $files = glob($tempDir . '/img-*') ?: [];
            natsort($files);
            $kept = [];
            foreach ($files as $file) {
                if (! is_readable($file) || filesize($file) < 5 * 1024) {
                    @unlink($file);
                    continue;
                }
                $size = @getimagesize($file);
                $w    = (int) ($size[0] ?? 0);
                $h    = (int) ($size[1] ?? 0);
                // 跳过按钮/图标类碎图，保留界面截图.
                if ($w > 0 && $h > 0 && ($w < 120 || $h < 120)) {
                    @unlink($file);
                    continue;
                }
                $kept[] = $file;
            }

            return array_values($kept);
        } catch (\Throwable $e) {
            Log::warning('知识库 PDF 内嵌图抽取跳过', [
                'path'  => $pdfPath,
                'error' => $e->getMessage(),
            ]);
            $this->cleanupDir($tempDir);

            return [];
        }
    }

    private function commandExists(string $command): bool
    {
        $output = [];
        $code   = 0;
        exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null', $output, $code);

        return $code === 0 && $output !== [];
    }

    private function cleanupDir(string $dir): void
    {
        if ($dir === '' || ! is_dir($dir)) {
            return;
        }
        foreach (glob(rtrim($dir, '/') . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }
}
