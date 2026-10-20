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

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * PDF 图片 OCR 增强服务.
 *
 * 依赖 poppler-utils（pdftoppm）与 {@see KnowledgeImageOcrService}；不可用时自动跳过.
 */
final class KnowledgePdfOcrService
{
    private KnowledgeImageOcrService $imageOcr;

    public function __construct(KnowledgeImageOcrService $imageOcr)
    {
        $this->imageOcr = $imageOcr;
    }

    /**
     * 在 pdftotext 结果上追加 OCR 文本（可用则执行，否则原样返回）.
     */
    public function enrich(string $pdfPath, string $baseText): string
    {
        if (! $this->shouldRun() || ! is_readable($pdfPath)) {
            return $baseText;
        }

        try {
            for ($page = 1, $pageCount = $this->pageCount($pdfPath); $page <= $pageCount; ++$page) {
                $baseText = $this->enrichPage($pdfPath, $baseText, $page);
            }

            return $baseText;
        } catch (\Throwable $e) {
            Log::warning('知识库 PDF OCR 跳过', [
                'path'  => $pdfPath,
                'error' => $e->getMessage(),
            ]);
            return $baseText;
        }
    }

    /**
     * PDF OCR 是否可处理。
     *
     * @return bool
     */
    public function canProcess(): bool
    {
        return $this->shouldRun();
    }

    /**
     * 读取实际页数并应用配置页数上限。
     *
     * @param string $pdfPath PDF 本地路径
     * @return int
     */
    public function pageCount(string $pdfPath): int
    {
        $process = new Process(['pdfinfo', $pdfPath]);
        $process->setTimeout($this->commandTimeout());
        $process->run();
        if (! $process->isSuccessful()
            || preg_match('/^Pages:\s*(\d+)\s*$/mi', $process->getOutput(), $match) !== 1) {
            throw new \RuntimeException('无法读取 PDF 页数');
        }

        $pages = max(0, (int) $match[1]);
        $limit = (int) config('knowledge.pdf_ocr.max_pages', 0);

        return $limit > 0 ? min($pages, $limit) : $pages;
    }

    /**
     * 将 PDF 各页渲染为临时 PNG，供分片 image_urls 落库；不做 OCR.
     *
     * 返回的路径位于同一临时目录；调用方完成 copy/persist 后应尽快使用，
     * 本方法不负责清理（由进程临时目录或调用方处理）。失败返回空列表.
     *
     * @return list<string>
     */
    public function exportPageImages(string $pdfPath): array
    {
        if (! $this->featureEnabled() || ! is_readable($pdfPath)) {
            return [];
        }
        if (! $this->commandExists('pdftoppm') || ! $this->commandExists('pdfinfo')) {
            return [];
        }

        try {
            $pageCount = $this->pageCount($pdfPath);
            if ($pageCount <= 0) {
                return [];
            }

            $tempDir = $this->createTempDir();
            $prefix  = $tempDir . '/page';
            $dpi     = max(72, (int) config('knowledge.pdf_ocr.dpi', 150));
            $process = new Process([
                'pdftoppm', '-png', '-r', (string) $dpi, '-f', '1', '-l', (string) $pageCount, $pdfPath, $prefix,
            ]);
            // 整份渲染可比单页累加慢，放宽到 parse_timeout 上限但仍有硬顶.
            $process->setTimeout(max(30, min(600, (int) config('knowledge.pdf_ocr.parse_timeout', 600))));
            $process->run();
            if (! $process->isSuccessful()) {
                $this->cleanupTempDir($tempDir);
                throw new \RuntimeException('pdftoppm 批量渲染失败');
            }

            $images = glob($prefix . '-*.png') ?: [];
            if ($images === []) {
                $images = glob($prefix . '-*.ppm') ?: [];
            }
            natsort($images);

            return array_values(array_filter($images, 'is_readable'));
        } catch (\Throwable $e) {
            Log::warning('知识库 PDF 页图导出跳过', [
                'path'  => $pdfPath,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 将指定页面 OCR 文本合并进已解析正文。
     *
     * @param string $pdfPath  PDF 本地路径
     * @param string $baseText 原始解析文本
     * @param int    $page     页码
     *
     * @return string
     */
    public function enrichPage(string $pdfPath, string $baseText, int $page): string
    {
        if (! $this->shouldRun() || ! is_readable($pdfPath) || $page <= 0) {
            return $baseText;
        }

        $tempDir = '';
        try {
            return $this->imageOcr->enrichWithImages($baseText, $this->renderPdfPage($pdfPath, $page, $tempDir));
        } catch (\Throwable $e) {
            Log::warning('知识库 PDF 单页 OCR 软失败', [
                'path'  => $pdfPath,
                'page'  => $page,
                'error' => $e->getMessage(),
            ]);

            return $baseText;
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    /**
     * 检测 PDF OCR 依赖是否可用.
     */
    public function isAvailable(): bool
    {
        return $this->commandExists('pdftoppm')
            && $this->commandExists('pdfinfo')
            && $this->imageOcr->isAvailable();
    }

    /**
     * 重置可用性缓存（单测用）.
     */
    public static function resetAvailabilityCache(): void
    {
        KnowledgeImageOcrService::resetAvailabilityCache();
    }

    private function shouldRun(): bool
    {
        if (! $this->featureEnabled()) {
            return false;
        }

        return $this->isAvailable();
    }

    private function featureEnabled(): bool
    {
        try {
            return app()->bound('config') && (bool) config('knowledge.pdf_ocr.enabled', true);
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * @return list<string>
     */
    private function renderPdfPage(string $pdfPath, int $page, string &$tempDir): array
    {
        $tempDir = $this->createTempDir();
        $prefix  = $tempDir . '/page';
        $dpi     = max(72, (int) config('knowledge.pdf_ocr.dpi', 150));
        $process = new Process([
            'pdftoppm', '-png', '-r', (string) $dpi, '-f', (string) $page, '-l', (string) $page, $pdfPath, $prefix,
        ]);
        $process->setTimeout($this->commandTimeout());
        $process->run();
        if (! $process->isSuccessful()) {
            throw new \RuntimeException('pdftoppm 执行失败');
        }

        $images = glob($prefix . '-*.png') ?: [];
        if ($images === []) {
            $images = glob($prefix . '-*.ppm') ?: [];
        }
        natsort($images);

        return array_values($images);
    }

    private function createTempDir(): string
    {
        $base = storage_path('app/temp/knowledge');
        if (! is_dir($base) && ! @mkdir($base, 0777, true) && ! is_dir($base)) {
            throw new \RuntimeException('无法创建 OCR 临时目录');
        }

        $dir = $base . '/ocr_' . uniqid('', true);
        if (! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new \RuntimeException('无法创建 OCR 工作目录');
        }

        return $dir;
    }

    private function cleanupTempDir(string $tempDir): void
    {
        if ($tempDir === '' || ! is_dir($tempDir)) {
            return;
        }

        foreach (glob($tempDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($tempDir);
    }

    private function commandExists(string $command): bool
    {
        $output = [];
        $code   = 0;
        exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null', $output, $code);

        return $code === 0 && $output !== [];
    }

    private function commandTimeout(): int
    {
        return max(1, min(30, (int) config('knowledge.pdf_ocr.command_timeout', 30)));
    }
}
