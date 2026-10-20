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
 * 知识库结构感知分块器.
 *
 * 先按 Markdown/纯文本标题与空行切成结构段，再打包到接近 chunk_size，
 * 避免 PDF/表格解析产生的短行各自成块；超长段回退硬切分.
 * 供 KnowledgeChunkService 在 features.semantic_chunk 开启时调用.
 */
final class KnowledgeChunkSplitter
{
    /**
     * 结构感知切分并按目标长度打包.
     *
     * @return list<array{content: string, metadata: array<string, mixed>}>
     */
    public function split(string $content, int $size, int $overlap): array
    {
        $content = $this->normalize($content);
        if ($content === '') {
            return [];
        }

        $size    = max(100, $size);
        $overlap = min(max(0, $overlap), $size - 1);

        if (mb_strlen($content) <= $size) {
            return [$this->makePart($content, 'semantic')];
        }

        $segments = $this->segmentByStructure($content);
        $parts    = $this->packSegments($segments, $size, $overlap);

        return $parts !== [] ? $parts : [$this->makePart($content, 'semantic')];
    }

    /**
     * 强制硬切分（异常回退与单测用）.
     *
     * @return list<array{content: string, metadata: array<string, mixed>}>
     */
    public function splitLegacy(string $content, int $size, int $overlap): array
    {
        $content = $this->normalize($content);
        if ($content === '') {
            return [];
        }

        $size    = max(100, $size);
        $overlap = min(max(0, $overlap), $size - 1);
        $parts   = [];
        foreach ($this->hardSplit($content, $size, $overlap) as $piece) {
            $parts[] = $this->makePart($piece, 'legacy');
        }

        return $parts;
    }

    /**
     * 规范化换行与空白.
     */
    private function normalize(string $content): string
    {
        return KnowledgeTextNormalizer::normalize($content);
    }

    /**
     * 按标题与双换行切段，并跟踪 heading_path.
     *
     * @return list<array{text: string, heading_path: list<string>}>
     */
    private function segmentByStructure(string $content): array
    {
        $lines       = explode("\n", $content);
        $segments    = [];
        $headingPath = [];
        $buffer      = [];
        $tableHeader = null;
        $inTable     = false;

        $flush = function () use (&$segments, &$buffer, &$headingPath): void {
            $text   = trim(implode("\n", $buffer));
            $buffer = [];
            if ($text === '' || $this->isHeadingOnlyText($text)) {
                return;
            }
            $segments[] = [
                'text'         => $text,
                'heading_path' => array_values($headingPath),
            ];
        };

        foreach ($lines as $line) {
            if (preg_match('/^(#{1,6})\s+(.+)$/u', $line, $match) === 1) {
                $flush();
                $inTable     = false;
                $tableHeader = null;
                $level       = strlen($match[1]);
                $title       = trim($match[2]);
                $headingPath = array_slice($headingPath, 0, $level - 1);
                $headingPath[] = $title;
                $buffer[] = $line;
                continue;
            }

            if ($this->isTableSeparator($line)) {
                continue;
            }

            if ($this->isTableRow($line)) {
                if (! $inTable) {
                    $flush();
                    $inTable     = true;
                    $tableHeader = $line;
                    continue;
                }

                // 表体行：表头 + 当前行单独成块，便于检索.
                $segments[] = [
                    'text'         => trim($tableHeader . "\n" . $line),
                    'heading_path' => array_values($headingPath),
                ];
                continue;
            }

            if ($inTable) {
                $inTable     = false;
                $tableHeader = null;
            }

            if (trim($line) === '') {
                // 标题后空行不立即切段，等正文或下一标题.
                if ($this->isHeadingOnlyBuffer($buffer)) {
                    continue;
                }
                $flush();
                continue;
            }

            $buffer[] = $line;
        }

        $flush();

        return $segments;
    }

    /**
     * 将结构段打包到接近 chunk_size，避免短段各自成块.
     *
     * @param list<array{text: string, heading_path: list<string>}> $segments
     *
     * @return list<array{content: string, metadata: array<string, mixed>}>
     */
    private function packSegments(array $segments, int $size, int $overlap): array
    {
        $parts         = [];
        $buffer        = '';
        $bufferHeading = [];

        $flush = function () use (&$parts, &$buffer, &$bufferHeading): void {
            $text          = trim($buffer);
            $buffer        = '';
            $heading       = $bufferHeading;
            $bufferHeading = [];
            if ($text === '') {
                return;
            }
            $parts[] = $this->makePart($text, 'semantic', $heading);
        };

        foreach ($segments as $segment) {
            $text        = trim((string) ($segment['text'] ?? ''));
            $headingPath = array_values((array) ($segment['heading_path'] ?? []));
            if ($text === '') {
                continue;
            }

            if (mb_strlen($text) > $size) {
                $flush();
                foreach ($this->hardSplit($text, $size, $overlap) as $piece) {
                    $parts[] = $this->makePart($piece, 'semantic', $headingPath);
                }
                continue;
            }

            // 同级以上标题切换且缓冲区已有一定内容时，优先在章节边界切块.
            if ($buffer !== ''
                && $headingPath !== []
                && $headingPath !== $bufferHeading
                && mb_strlen($buffer) >= (int) max(80, floor($size * 0.3))
            ) {
                $flush();
            }

            if ($buffer === '') {
                $buffer        = $text;
                $bufferHeading = $headingPath;
                continue;
            }

            $joined = $buffer . "\n" . $text;
            if (mb_strlen($joined) <= $size) {
                $buffer = $joined;
                continue;
            }

            $flush();
            $buffer        = $text;
            $bufferHeading = $headingPath;
        }

        $flush();

        return $this->coalesceTinyParts($parts, $size);
    }

    /**
     * 合并过短分块到相邻块，消除边界残留的单字/短行块.
     *
     * @param list<array{content: string, metadata: array<string, mixed>}> $parts
     *
     * @return list<array{content: string, metadata: array<string, mixed>}>
     */
    private function coalesceTinyParts(array $parts, int $size): array
    {
        if (count($parts) <= 1) {
            return $parts;
        }

        $minLen    = min(100, max(40, (int) floor($size * 0.08)));
        $maxMerged = (int) max($size, floor($size * 1.25));
        $result    = [];

        foreach ($parts as $part) {
            if ($result === []) {
                $result[] = $part;
                continue;
            }

            $prevIdx = count($result) - 1;
            $prev    = $result[$prevIdx];
            $prevLen = mb_strlen($prev['content']);
            $curLen  = mb_strlen($part['content']);

            if ($prevLen < $minLen || $curLen < $minLen) {
                $merged = $prev['content'] . "\n" . $part['content'];
                if (mb_strlen($merged) <= $maxMerged) {
                    $heading          = (array) ($prev['metadata']['heading_path'] ?? []);
                    $result[$prevIdx] = $this->makePart($merged, 'semantic', array_values($heading));
                    continue;
                }
            }

            $result[] = $part;
        }

        return $result;
    }

    /**
     * @param list<string> $buffer
     */
    private function isHeadingOnlyBuffer(array $buffer): bool
    {
        return $this->isHeadingOnlyText(trim(implode("\n", $buffer)));
    }

    private function isHeadingOnlyText(string $text): bool
    {
        return $text !== '' && (bool) preg_match('/^#{1,6}\s+.+$/u', $text);
    }

    /**
     * 固定长度硬切分，保留 overlap.
     *
     * @return list<string>
     */
    private function hardSplit(string $content, int $size, int $overlap): array
    {
        $chunks = [];
        $offset = 0;
        $length = mb_strlen($content);

        while ($offset < $length) {
            $chunk = trim(mb_substr($content, $offset, $size));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
            if ($offset + $size >= $length) {
                break;
            }
            $offset += $size - $overlap;
        }

        return $chunks;
    }

    /**
     * @param list<string> $headingPath
     *
     * @return array{content: string, metadata: array<string, mixed>}
     */
    private function makePart(string $content, string $strategy, array $headingPath = []): array
    {
        $metadata = ['chunk_strategy' => $strategy];
        if ($headingPath !== []) {
            $metadata['heading_path'] = $headingPath;
        }

        return [
            'content'  => $content,
            'metadata' => $metadata,
        ];
    }

    private function isTableRow(string $line): bool
    {
        $trim = trim($line);

        return $trim !== '' && str_starts_with($trim, '|') && str_ends_with($trim, '|');
    }

    private function isTableSeparator(string $line): bool
    {
        $trim = trim($line);
        if ($trim === '' || ! str_starts_with($trim, '|')) {
            return false;
        }

        return (bool) preg_match('/^\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?$/', $trim);
    }
}
