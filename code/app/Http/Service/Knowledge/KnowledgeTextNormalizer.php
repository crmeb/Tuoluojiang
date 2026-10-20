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

/**
 * 知识库文本规范化.
 *
 * 压缩 OCR / PDF 解析产生的多余空行、乱码与「单字空格」拉丁行，便于分块与向量化.
 */
final class KnowledgeTextNormalizer
{
    /**
     * 规范化换行与空白.
     */
    public static function normalize(string $content): string
    {
        $content = self::stripNoiseChars($content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines   = explode("\n", $content);
        $lines   = array_map(static function (string $line): string {
            $line = trim($line);
            if ($line === '') {
                return '';
            }

            return self::collapseSpacedLatinLine($line);
        }, $lines);
        $lines = array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));

        $content = implode("\n", $lines);

        return trim((string) preg_replace('/[ \t]+/u', ' ', $content));
    }

    /**
     * 去除 UTF-8 替换符及 OCR/PDF 常见孤立乱码.
     */
    private static function stripNoiseChars(string $content): string
    {
        $content = (string) preg_replace('/\x{FFFD}/u', '', $content);
        // 行内孤立 ?（编码失败残留，且前后非英文问号语境）.
        $content = (string) preg_replace('/(?<![?.!?\)]\s)\?(?!\s*[?.!?\)]|$)/u', '', $content);

        return $content;
    }

    /**
     * 合并 pdftotext 产生的「Pr o d u c t」类单字空格行.
     */
    private static function collapseSpacedLatinLine(string $line): string
    {
        if (! preg_match('/[A-Za-z0-9]/', $line)) {
            return $line;
        }

        $parts = preg_split('/\s+/u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 4) {
            return $line;
        }

        $singleCharCount = 0;
        foreach ($parts as $part) {
            if (mb_strlen($part) === 1) {
                ++$singleCharCount;
            }
        }

        if ($singleCharCount / count($parts) >= 0.6) {
            return preg_replace('/\s+/u', '', $line) ?? $line;
        }

        return $line;
    }
}
