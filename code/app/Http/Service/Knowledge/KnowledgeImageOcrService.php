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
 * 图片 OCR 服务（Tesseract）.
 *
 * 供 PDF 解析与分块 Markdown 内嵌图片复用；未安装 tesseract 或语言包缺失时自动跳过.
 * 识别前尽量做灰度/对比度预处理，并过滤「短拉丁碎片」类乱码行，提升中文 Banner 准确率.
 */
final class KnowledgeImageOcrService
{
    /** @var bool|null */
    private static ?bool $availabilityCache = null;

    /**
     * 对多张图片 OCR 并合并进 base 文本.
     *
     * @param list<string> $imagePaths 本地可读图片路径
     */
    public function enrichWithImages(string $baseText, array $imagePaths): string
    {
        try {
            if (! $this->shouldRun() || $imagePaths === []) {
                return $baseText;
            }

            $ocrParts = [];
            foreach ($imagePaths as $path) {
                if (! is_readable($path)) {
                    continue;
                }
                $text = $this->recognize($path);
                if ($text !== '') {
                    $ocrParts[] = $text;
                }
            }

            if ($ocrParts === []) {
                return $baseText;
            }

            return $this->mergeTexts($baseText, implode("\n", $ocrParts));
        } catch (\Throwable $e) {
            Log::warning('知识库图片 OCR 批次软失败，保留原文本', [
                'error' => $e->getMessage(),
            ]);

            return $baseText;
        }
    }

    /**
     * OCR 单张图片.
     *
     * 只识别一次（优先预处理图），避免双跑 tesseract 撑爆 30s 队列任务.
     */
    public function recognize(string $imagePath): string
    {
        if (! $this->shouldRun() || ! is_readable($imagePath)) {
            return '';
        }

        try {
            $preprocessed = $this->preprocessImage($imagePath);
            $target       = $preprocessed ?? $imagePath;
            try {
                $raw = $this->runTesseract($target);
            } finally {
                if ($preprocessed !== null) {
                    @unlink($preprocessed);
                }
            }

            return KnowledgeTextNormalizer::normalize($this->cleanOcrText($raw));
        } catch (\Throwable $e) {
            Log::warning('知识库图片 OCR 识别软失败', [
                'path'  => $imagePath,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * 检测 tesseract 及配置语言包是否可用.
     */
    public function isAvailable(): bool
    {
        if (self::$availabilityCache !== null) {
            return self::$availabilityCache;
        }

        if (! $this->commandExists('tesseract')) {
            return self::$availabilityCache = false;
        }

        $langsOutput = [];
        $code        = 0;
        exec('tesseract --list-langs 2>/dev/null', $langsOutput, $code);
        if ($code !== 0) {
            return self::$availabilityCache = false;
        }

        $langs = array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $langsOutput
        ), static fn (string $line): bool => $line !== '' && ! str_starts_with($line, 'List of available languages')));

        foreach ($this->requiredLangs() as $lang) {
            if (! in_array($lang, $langs, true)) {
                return self::$availabilityCache = false;
            }
        }

        return self::$availabilityCache = true;
    }

    /**
     * 合并正文与 OCR 文本，跳过重复行.
     */
    public function mergeTexts(string $baseText, string $ocrText): string
    {
        $baseText = trim($baseText);
        $ocrText  = trim($ocrText);
        if ($ocrText === '') {
            return $baseText;
        }
        if ($baseText === '') {
            return KnowledgeTextNormalizer::normalize($ocrText);
        }

        $baseNormalized = $this->normalizeForCompare($baseText);
        $uniqueLines    = [];
        foreach (preg_split("/\r\n|\r|\n/u", $ocrText) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '' || mb_strlen($line) < 2) {
                continue;
            }
            $key = $this->normalizeForCompare($line);
            if ($key === '' || str_contains($baseNormalized, $key)) {
                continue;
            }
            $uniqueLines[] = $line;
        }

        if ($uniqueLines === []) {
            return KnowledgeTextNormalizer::normalize($baseText);
        }

        return KnowledgeTextNormalizer::normalize($baseText . "\n" . implode("\n", $uniqueLines));
    }

    /**
     * 重置可用性缓存（单测用）.
     */
    public static function resetAvailabilityCache(): void
    {
        self::$availabilityCache = null;
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
    private function requiredLangs(): array
    {
        $raw = 'chi_sim+eng';
        try {
            if (app()->bound('config')) {
                $raw = (string) config('knowledge.pdf_ocr.lang', 'chi_sim+eng');
            }
        } catch (\Throwable) {
        }

        return array_values(array_filter(array_map('trim', explode('+', $raw))));
    }

    private function runTesseract(string $imagePath): string
    {
        $lang   = implode('+', $this->requiredLangs());
        $psm    = $this->pageSegMode();
        $command = ['tesseract', $imagePath, 'stdout', '-l', $lang];
        if ($psm > 0) {
            $command[] = '--psm';
            $command[] = (string) $psm;
        }

        $process = new Process($command);
        $process->setTimeout($this->commandTimeout());
        try {
            $process->run();
        } catch (\Throwable $e) {
            if ($process->isRunning()) {
                $process->stop(0);
            }
            Log::warning('分块图片 OCR 失败', [
                'path'      => $imagePath,
                'timed_out' => true,
                'error'     => $e->getMessage(),
            ]);

            return '';
        }
        if (! $process->isSuccessful()) {
            Log::warning('分块图片 OCR 失败', [
                'path'      => $imagePath,
                'code'      => $process->getExitCode(),
                'timed_out' => $process->isTimedOut(),
            ]);

            return '';
        }

        return trim($process->getOutput());
    }

    /**
     * ImageMagick 灰度标准化预处理；不可用时返回 null.
     */
    private function preprocessImage(string $imagePath): ?string
    {
        if (! $this->preprocessEnabled() || ! $this->commandExists('convert')) {
            return null;
        }

        $tempDir = storage_path('app/temp/knowledge/images');
        if (! is_dir($tempDir) && ! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            return null;
        }

        $out     = $tempDir . '/ocr_prep_' . uniqid('', true) . '.png';
        $process = new Process([
            'convert', $imagePath, '-colorspace', 'Gray', '-normalize', '-sharpen', '0x0.8', $out,
        ]);
        $process->setTimeout($this->commandTimeout());
        try {
            $process->run();
        } catch (\Throwable $e) {
            if ($process->isRunning()) {
                $process->stop(0);
            }
            @unlink($out);

            return null;
        }
        if (! $process->isSuccessful() || ! is_file($out) || filesize($out) <= 0) {
            @unlink($out);

            return null;
        }

        return $out;
    }

    /**
     * 剔除 OCR 拉丁乱码行与过短噪声.
     */
    private function cleanOcrText(string $text): string
    {
        $lines = [];
        foreach (preg_split("/\r\n|\r|\n/u", $text) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '' || mb_strlen($line) < 2) {
                continue;
            }
            if ($this->isGarbageLatinLine($line)) {
                continue;
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * 评分：中文优先，惩罚纯拉丁碎片与过短结果.
     */
    private function scoreOcrText(string $text): int
    {
        if ($text === '') {
            return -1;
        }

        $chinese = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text) ?: 0;
        $latin   = preg_match_all('/[A-Za-z]/', $text) ?: 0;
        $len     = mb_strlen($text);

        return $chinese * 8 + $len - $latin * 2;
    }

    /**
     * 识别「ZS SUE ea See」类艺术字误识拉丁碎片.
     */
    private function isGarbageLatinLine(string $line): bool
    {
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $line) === 1) {
            return false;
        }
        if (preg_match('/[A-Za-z]/', $line) !== 1) {
            return false;
        }

        $parts = preg_split('/\s+/u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            // 单段短拉丁也视为噪声（如 logo 旁的 "Ga"）.
            return mb_strlen($line) <= 6 && (bool) preg_match('/^[A-Za-z.]+$/', $line);
        }

        $shortLatin = 0;
        foreach ($parts as $part) {
            if (preg_match('/^[A-Za-z]{1,4}$/', $part) === 1) {
                ++$shortLatin;
            }
        }

        return ($shortLatin / count($parts)) >= 0.6;
    }

    private function preprocessEnabled(): bool
    {
        try {
            return ! app()->bound('config') || (bool) config('knowledge.chunk_ocr.preprocess', true);
        } catch (\Throwable) {
            return true;
        }
    }

    private function pageSegMode(): int
    {
        try {
            if (app()->bound('config')) {
                return max(0, (int) config('knowledge.chunk_ocr.psm', 3));
            }
        } catch (\Throwable) {
        }

        return 3;
    }

    private function commandTimeout(): int
    {
        try {
            if (app()->bound('config')) {
                return max(1, min(30, (int) config('knowledge.pdf_ocr.command_timeout', 30)));
            }
        } catch (\Throwable) {
        }

        return 30;
    }

    private function commandExists(string $command): bool
    {
        $output = [];
        $code   = 0;
        exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null', $output, $code);

        return $code === 0 && $output !== [];
    }

    private function normalizeForCompare(string $text): string
    {
        $text = mb_strtolower($text);
        $text = (string) preg_replace('/\s+/u', '', $text);

        return $text;
    }
}
