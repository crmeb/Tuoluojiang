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

namespace App\Http\Service\Ai\Agent;

use App\Http\Service\Ai\Guest\AiGuestContentGuard;

/**
 * 面向用户回答的统一净化管道（Delivery 平面）.
 *
 * 终态固定顺序：Chart(GPT-Vis) → Citation → InternalLeak → EntityId；
 * 可选追加 Guest 敏感词过滤。流式路径通过 {@see createStreamState()} 取得各 stage 状态，
 * 由 AiStreamService 按分片能力挂载，避免 stream / resolveAnswer / emitAnswer 顺序不一致。
 *
 * Chart / Citation / InternalLeak / EntityId 均内联于此，Delivery 平面不再拆薄文件.
 */
class AnswerSanitizePipeline
{
    /** 整段回复皆为内部规则时的兜底文案. */
    public const INTERNAL_LEAK_FALLBACK = '暂时无法确认相关信息，请换个问法或补充更多细节。';

    /**
     * 快速预检：无 ID 相关字样则跳过正则，降低开销.
     */
    private const ENTITY_ID_QUICK_HINT = '/(?:\bid\b|ID|cid|uid|pid|eid|sid|_id)/u';

    /**
     * 强特征：命中任意一条即视为出现内部规则泄漏片段.
     *
     * @var list<string>
     */
    private const INTERNAL_LEAK_MARKERS = [
        'AI联调应答规则',
        'Agent编排策略',
        'Agent 编排策略',
        '能力优先级规则',
        '应答参考优先级',
        '知识库 > MCP工具 > 数据库',
        '知识库 > MCP > 数据库',
        '跳过全部工具调用',
        '未启用数据库的场景',
        '尽量避免调用数据库',
        '尽量不要使用数据库',
        '# 能力优先级',
        '# Agent 编排策略',
    ];


    /**
     * 终态净化：stream 结束后落库/推送前统一调用.
     *
     * @param string $content 原始回答
     * @param array{words?: array<int, string>, max_word_length?: int, replacement?: string, buffer?: string}|null $guest 访客敏感词过滤器，非访客传 null
     *
     * @return string 净化后的回答
     */
    public function sanitize(string $content, ?array $guest = null): string
    {
        $answer = $this->sanitizeChartContent($content);
        $answer = $this->sanitizeCitationContent($answer);
        $answer = $this->stripToolCallArtifacts($answer);
        $answer = $this->stripInternalLeak($answer);
        $answer = $this->stripEntityIds($answer);
        $answer = $this->normalizeMarkdownTables($answer);

        if ($guest !== null && $answer !== '') {
            $answer = AiGuestContentGuard::filterOutputChunk($guest, $answer)
                . AiGuestContentGuard::flushOutputFilter($guest);
        }

        return trim($answer);
    }

    /**
     * 确保 GFM 表格与前后段落分隔，避免列表项把表格行吞成普通文本。
     */
    private function normalizeMarkdownTables(string $content): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        if (! is_array($lines) || count($lines) < 2) {
            return $content;
        }

        $normalized = [];
        $count      = count($lines);
        for ($i = 0; $i < $count; ++$i) {
            $line      = (string) $lines[$i];
            $next      = (string) ($lines[$i + 1] ?? '');
            $isHeader  = str_starts_with(ltrim($line), '|') && str_contains($line, '|');
            $isDivider = (bool) preg_match('/^\s*\|?\s*:?-{3,}:?\s*(?:\|\s*:?-{3,}:?\s*)+\|?\s*$/u', $next);
            if ($isHeader && $isDivider && $normalized !== [] && end($normalized) !== '') {
                $normalized[] = '';
            }

            $normalized[] = $line;
            if ($isHeader && $isDivider) {
                $normalized[] = $next;
                $j = $i + 2;
                while ($j < $count && str_starts_with(ltrim((string) $lines[$j]), '|')) {
                    $normalized[] = (string) $lines[$j];
                    ++$j;
                }
                if ($j < $count && $normalized[count($normalized) - 1] !== '' && (string) $lines[$j] !== '') {
                    $normalized[] = '';
                }
                $i = $j - 1;
            }
        }

        return implode("\n", $normalized);
    }

    /**
     * 剔除面向用户正文中的文本型工具调用标记（含未闭合残留）.
     *
     * 部分模型会把 `<|FunctionCallBegin|>` 等写进 content；完整块优先整段删除，
     * 未配对残留从首个标记截断，避免标签泄漏到会话气泡.
     */
    public function stripToolCallArtifacts(string $content): string
    {
        $trimmed = trim($content);
        if ($trimmed === '' || ! $this->mayContainToolCallArtifact($trimmed)) {
            return $content;
        }

        $patterns = [
            '/<\|FunctionCallBegin\|>.*?(?:<\|FunctionCallEnd\|>|$)/s',
            '/<\|tool_call_begin\|>.*?(?:<\|tool_call_end\|>|$)/si',
            '/<\|tool_calls_section_begin\|>.*?(?:<\|tool_calls_section_end\|>|$)/si',
            '/<tool_call\b[^>]*>.*?<\/tool_call>/si',
            '/```tool_call\b.*?```/si',
        ];
        foreach ($patterns as $pattern) {
            $content = (string) preg_replace($pattern, '', $content);
        }

        // 未闭合 / 畸形标签：从首个标记起整段丢弃（其后通常是工具 JSON 载荷）.
        $cut = $this->findToolCallArtifactStart($content);
        if ($cut !== null) {
            $content = mb_substr($content, 0, $cut);
        }

        // 扫尾：孤立 End / 残缺 <|...|> 标记.
        $content = (string) preg_replace('/<\|(?:FunctionCall(?:Begin|End)?|tool_calls?_?(?:section_)?(?:begin|end)?)\|?>?/i', '', $content);
        $content = (string) preg_replace('/<\/?tool_call\b[^>]*>/iu', '', $content);
        $content = (string) preg_replace("/\n{3,}/u", "\n\n", $content);

        return rtrim($content);
    }

    /**
     * 文本是否可能含工具调用特殊标记.
     */
    public function mayContainToolCallArtifact(string $content): bool
    {
        return (bool) preg_match('/<\||<tool_call|```tool_call/i', $content);
    }

    /**
     * 定位工具调用标记起点（字符下标）；未命中返回 null.
     */
    public function findToolCallArtifactStart(string $content): ?int
    {
        if (! preg_match(
            '/<\|FunctionCall(?:Begin|End)?\|>?|<\|tool_calls?(?:_section)?_(?:begin|end)\|>?|<\|tool_call|<tool_call(?=[^a-z0-9_]|$)|```tool_call/i',
            $content,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return null;
        }

        return $this->charOffset($content, (int) $matches[0][1]);
    }

    /**
     * 流式暂扣可能跨分片的工具调用标记前缀，避免 `<|` / `<tool_call` 半截先刷到前端.
     */
    public function toolCallHoldSuffix(string $content): string
    {
        if ($content === '') {
            return '';
        }

        if (preg_match(
            '/(?:<\|[A-Za-z0-9_]{0,40}|<\||<tool_call(?:\b[^>]{0,40})?|```tool_call?|```tool_cal|```tool_ca|```tool_c|```tool_|```tool|```too|```to|```t|```)$/i',
            $content,
            $m
        )) {
            return (string) $m[0];
        }

        return '';
    }

    /**
     * 仅剔除内部编排规则泄漏（供单测与流式截断复用）.
     *
     * @param string $content 原始回答
     */
    public function stripInternalLeak(string $content): string
    {
        $trimmed = trim($content);
        if ($trimmed === '' || ! $this->containsInternalLeak($trimmed)) {
            return $content;
        }

        $cut = $this->findInternalLeakStart($trimmed);
        if ($cut === null) {
            return $content;
        }

        $kept = trim(mb_substr($trimmed, 0, $cut));
        if ($kept === '') {
            return self::INTERNAL_LEAK_FALLBACK;
        }

        return $kept;
    }

    /**
     * 仅剔除业务实体 ID 泄漏（供单测复用）.
     *
     * @param string $content 原始回答
     */
    public function stripEntityIds(string $content): string
    {
        $trimmed = trim($content);
        if ($trimmed === '' || ! preg_match(self::ENTITY_ID_QUICK_HINT, $trimmed)) {
            return $content;
        }

        $patterns = [
            '/"(?:id|[a-z][a-z0-9]*_id|cid|uid|pid|eid|sid)"\s*:\s*"?[\w.-]+"?\s*,?/iu',
            '/[（(]\s*(?:[a-z][a-z0-9]*_)?(?:id|cid|uid|pid|eid|sid)\s*[：:=\-]\s*[\w.-]+\s*[）)]/iu',
            '/(?:日程|客户|线索|商机|订单|合同|发票|审批|员工|人员|用户|跟进|汇报|绩效|考核|联系人|回款|类型)?\s*(?:业务)?(?:主键)?\s*(?:ID|Id|id)(?:\s*[：:=\-]\s*|\s+)[\w.-]+/u',
            '/(?:ID|Id|id)\s*为\s*[\w.-]+/u',
            '/(?<![A-Za-z0-9_])(?:(?:[a-z][a-z0-9]*_)?(?:id)|cid|uid|pid|eid|sid)\s*[：:=\-]\s*[\w.-]+/iu',
        ];

        foreach ($patterns as $pattern) {
            $content = (string) preg_replace($pattern, '', $content);
        }

        $content = (string) preg_replace('/[（(]\s*[）)]/u', '', $content);
        $content = (string) preg_replace('/[ \t]{2,}/u', ' ', $content);
        $content = (string) preg_replace('/[ \t]+([。！？；，、,.!?;:])/u', '$1', $content);
        $content = (string) preg_replace("/\n{3,}/u", "\n\n", $content);
        $content = (string) preg_replace('/^[ \t]*[-•]\s*$/mu', '', $content);

        return rtrim($content);
    }

    /**
     * 创建流式分片净化状态（Citation / Chart / InternalLeak 抑制标记）.
     *
     * @return array{
     *     citation: array{buffer: string, suppress: bool},
     *     chart: array{buffer: string, in_block: bool},
     *     leak_suppress: bool
     * }
     */
    public function createStreamState(): array
    {
        return [
            'citation'      => self::emptyCitationStreamState(),
            'chart'         => self::emptyChartStreamState(),
            'leak_suppress' => false,
        ];
    }

    /**
     * 流式：剔除行内引用后可转发的正文分片.
     *
     * @param string $chunk 当前 SSE 正文分片
     * @param array{buffer: string, suppress: bool} $citationState Citation 缓冲状态
     */
    public function takeCitationForwardable(string $chunk, array &$citationState): string
    {
        return $this->takeCitationForwardableImpl($chunk, $citationState);
    }

    /**
     * 流式：图表门禁可转发分片（GPT-Vis 校验；遗留 infographic 整块丢弃）.
     *
     * @param string $chunk 已过 Citation/Guest 的正文
     * @param array{buffer: string, in_block: bool} $chartState Chart 缓冲状态
     */
    public function takeChartForwardable(string $chunk, array &$chartState): string
    {
        return $this->takeChartForwardableImpl($chunk, $chartState);
    }

    /**
     * 流式结束时冲刷 Citation 缓冲.
     *
     * @param array{buffer: string, suppress: bool} $citationState
     */
    public function flushCitation(array &$citationState): string
    {
        return $this->flushCitationStreamImpl($citationState);
    }

    /**
     * 流式结束时冲刷 Chart 缓冲.
     *
     * @param array{buffer: string, in_block: bool} $chartState
     */
    public function flushChart(array &$chartState): string
    {
        return $this->flushChartStreamImpl($chartState);
    }

    /**
     * 判断累积回答是否含内部编排规则泄漏.
     */
    public function containsInternalLeak(string $answer): bool
    {
        foreach (self::INTERNAL_LEAK_MARKERS as $marker) {
            if (mb_stripos($answer, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 定位内部规则泄漏截断点（字符下标）.
     *
     * @return null|int
     */
    public function findInternalLeakStart(string $content): ?int
    {
        if (! $this->containsInternalLeak($content)) {
            return null;
        }

        if (preg_match(
            '/(?:^|\n)\s*#{1,6}\s*[^\n]{0,40}?(?:联调应答规则|Agent\s*编排策略|能力优先级)\s*/u',
            $content,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return $this->charOffset($content, (int) $matches[0][1]);
        }

        if (preg_match(
            '/(?:^|\n)\s*\d+[\.、]\s*\*?\*?Agent\s*编排策略/u',
            $content,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return $this->charOffset($content, (int) $matches[0][1]);
        }

        $earliest = null;
        foreach (self::INTERNAL_LEAK_MARKERS as $marker) {
            $pos = mb_stripos($content, $marker);
            if ($pos === false) {
                continue;
            }
            $lineStart = $this->lineStartBefore($content, $pos);
            if ($earliest === null || $lineStart < $earliest) {
                $earliest = $lineStart;
            }
        }

        return $earliest;
    }

    /**
     * 将 PCRE 字节偏移转为 mb 字符偏移.
     */
    private function charOffset(string $content, int $byteOffset): int
    {
        if ($byteOffset <= 0) {
            return 0;
        }

        return mb_strlen(substr($content, 0, $byteOffset));
    }

    /**
     * 取特征所在行的行首字符偏移，便于整段剔除.
     */
    private function lineStartBefore(string $content, int $charPos): int
    {
        $prefix = mb_substr($content, 0, $charPos);
        $nl     = mb_strrpos($prefix, "\n");
        if ($nl === false) {
            return 0;
        }

        return $nl + 1;
    }
    // --- Chart / GPT-Vis（原 Infographic，已弃用 ```infographic）---

    /** 遗留 AntV Infographic 围栏（整块丢弃）. */
    private const LEGACY_INFOGRAPHIC_FENCE = '```infographic';

    /** GPT-Vis 围栏语言标记（大小写不敏感）. */
    private const GPT_VIS_FENCE = '```GPT-Vis';

    /**
     * 创建空的流式图表缓冲状态。
     *
     * @return array{buffer: string, in_block: bool} 初始状态
     */
    public static function emptyChartStreamState(): array
    {
        return ['buffer' => '', 'in_block' => false];
    }

    /**
     * 剔除回答中的遗留 infographic，并过滤无数据的 GPT-Vis 代码块。
     *
     * @param string $content 原始回答
     *
     * @return string 净化后的回答
     */
    public function sanitizeChartContent(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        // 已弃用：遗留 Infographic 整块剔除.
        if (stripos($content, self::LEGACY_INFOGRAPHIC_FENCE) !== false) {
            $content = (string) preg_replace('/```infographic\b.*?```/si', '', $content);
        }

        if (stripos($content, '```GPT-Vis') === false && stripos($content, '```gpt-vis') === false) {
            return trim((string) preg_replace("/\n{3,}/", "\n\n", $content));
        }

        $cleaned = preg_replace_callback(
            '/```GPT-Vis\b.*?```/si',
            function (array $matches): string {
                $block = (string) $matches[0];

                return $this->isGptVisWorthKeeping($block) ? $block : '';
            },
            $content
        );

        return trim((string) preg_replace("/\n{3,}/", "\n\n", (string) $cleaned));
    }

    /**
     * 流式转发过滤：缓冲未闭合的图表代码块；GPT-Vis 按 sanitize 决定是否放行，infographic 一律丢弃。
     *
     * @param string $chunk 当前 SSE 分片
     * @param array{buffer: string, in_block: bool} $state 流式缓冲状态（引用传递）
     *
     * @return string 可立即转发给前端的内容
     */
    public function takeChartForwardableImpl(string $chunk, array &$state): string
    {
        $state['buffer'] = (string) ($state['buffer'] ?? '') . $chunk;
        $out             = '';

        while ($state['buffer'] !== '') {
            if (empty($state['in_block'])) {
                $fence = $this->findChartFenceStart($state['buffer']);
                if ($fence === null) {
                    $tail = $this->possibleChartFencePrefix($state['buffer']);
                    if ($tail !== '') {
                        $out .= substr($state['buffer'], 0, -strlen($tail));
                        $state['buffer'] = $tail;
                    } else {
                        $out .= $state['buffer'];
                        $state['buffer'] = '';
                    }
                    break;
                }

                $out .= substr($state['buffer'], 0, $fence['pos']);
                $state['buffer']   = substr($state['buffer'], $fence['pos']);
                $state['in_block'] = true;
            }

            if (preg_match('/^```(?:GPT-Vis|gpt-vis|infographic)\b.*?```/si', $state['buffer'], $matches)) {
                $block = (string) $matches[0];
                $kept  = $this->sanitizeChartContent($block);
                if ($kept !== '') {
                    $out .= $kept;
                }
                $state['buffer']   = substr($state['buffer'], strlen($block));
                $state['in_block'] = false;
                if ($kept === '') {
                    // 丢弃无效块后吃掉紧随的一个换行，避免残留空行.
                    $state['buffer'] = preg_replace('/^\r?\n/', '', $state['buffer'], 1) ?? $state['buffer'];
                }
                continue;
            }

            break;
        }

        return $out;
    }

    /**
     * 流结束时刷新剩余缓冲，未闭合的图表块直接丢弃。
     *
     * @param array{buffer: string, in_block: bool} $state 流式缓冲状态（引用传递）
     *
     * @return string 可追加转发的尾部内容
     */
    public function flushChartStreamImpl(array &$state): string
    {
        $remain = (string) ($state['buffer'] ?? '');
        $state  = self::emptyChartStreamState();
        if ($remain === '') {
            return '';
        }

        // 未闭合代码块直接丢弃，避免把半截骨架图刷到前端.
        if ($this->findChartFenceStart($remain) !== null) {
            return '';
        }

        return $remain;
    }

    /**
     * 判断 GPT-Vis 代码块是否值得保留（有合法 type 与可用数据）.
     */
    private function isGptVisWorthKeeping(string $block): bool
    {
        if (! preg_match('/```GPT-Vis\s*(.*?)```/si', $block, $matches)) {
            return false;
        }
        $body = trim((string) $matches[1]);
        if ($body === '') {
            return false;
        }

        // summary：不以 vis 开头，需有 T8 语义标注或足够正文.
        if (! preg_match('/^vis\s+\S+/mi', $body)) {
            return (bool) preg_match('/\]\((?:metric_|delta_|ratio_|contribute_|dim_|time_|trend_)/u', $body)
                || mb_strlen($body) >= 40;
        }

        if (! preg_match('/^vis\s+(\S+)/mi', $body, $typeMatch)) {
            return false;
        }
        $type = strtolower((string) $typeMatch[1]);

        $statTypes = [
            'line', 'area', 'column', 'bar', 'pie', 'scatter', 'dual-axes',
            'histogram', 'boxplot', 'violin', 'radar', 'funnel', 'waterfall',
            'liquid', 'word-cloud', 'table',
        ];
        if (in_array($type, $statTypes, true)) {
            if ($type === 'liquid') {
                return (bool) preg_match('/percent\s+[0-9]*\.?[0-9]+/i', $body);
            }
            if ($type === 'histogram' || $type === 'dual-axes') {
                return (bool) preg_match('/^\s*-\s+-?[0-9]+(?:\.[0-9]+)?\s*$/m', $body)
                    || (bool) preg_match('/value\s+-?[0-9]+(?:\.[0-9]+)?/i', $body);
            }
            if ($type === 'table') {
                return preg_match_all('/^\s*-\s+\S+/mu', $body) >= 1;
            }

            return (bool) preg_match('/value\s+-?[0-9]+(?:\.[0-9]+)?/i', $body);
        }

        $structTypes = [
            'mindmap', 'indented-tree', 'organization-chart', 'fishbone-diagram',
            'flow-diagram', 'network-graph', 'sankey', 'treemap', 'venn',
        ];
        if (in_array($type, $structTypes, true)) {
            return preg_match_all('/(?:^|\n)\s*(?:- )?name\s+\S+/mu', $body) >= 2
                || preg_match_all('/(?:source|target)\s+\S+/iu', $body) >= 2
                || (bool) preg_match('/sets\s+\S+/iu', $body);
        }

        return (bool) preg_match('/\d+/', $body);
    }

    /**
     * 定位缓冲区内最早的图表围栏起点.
     *
     * @return null|array{pos: int, kind: string}
     */
    private function findChartFenceStart(string $buffer): ?array
    {
        $candidates = [];
        foreach (['```GPT-Vis' => 'gpt-vis', '```infographic' => 'infographic'] as $needle => $kind) {
            $pos = stripos($buffer, $needle);
            if ($pos !== false) {
                $candidates[] = ['pos' => $pos, 'kind' => $kind];
            }
        }
        if ($candidates === []) {
            return null;
        }
        usort($candidates, static fn (array $a, array $b): int => $a['pos'] <=> $b['pos']);

        return $candidates[0];
    }

    /** 检测缓冲区末尾是否为未完成的图表围栏前缀。 */
    private function possibleChartFencePrefix(string $text): string
    {
        $needles = [self::GPT_VIS_FENCE, self::LEGACY_INFOGRAPHIC_FENCE];
        $best    = '';
        foreach ($needles as $needle) {
            $len = strlen($text);
            $max = min($len, strlen($needle));
            for ($i = $max; $i >= 1; --$i) {
                $suffix = substr($text, -$i);
                if (strncasecmp($needle, $suffix, $i) === 0) {
                    if (strlen($suffix) > strlen($best)) {
                        $best = $suffix;
                    }
                    break;
                }
            }
        }

        return $best;
    }

    // --- Citation（原 KnowledgeCitationSanitizer）---

    /** 行内资料编号，如 [1]、[12]；保留 Markdown 链接 [1](url). */
    private const CITATION_INLINE = '/\[(\d{1,3})\](?!\()/u';

    /** 文末分隔线后的参考列表. */
    private const CITATION_TRAILING_HR = '/\n{0,2}(?:-{3,}|_{3,}|\*{3,})\s*\n+(?:\[\d{1,3}\][^\n]*)+\s*\z/u';

    /** 文末「参考资料」等标题 + 编号列表. */
    private const CITATION_TRAILING_LABELED = '/\n+(?:参考资料|引用来源|资料来源|参考文献|来源)[：:]\s*\n?(?:\[\d{1,3}\][^\n]*)+\s*\z/u';

    /** 文末仅由 [n] 标题构成的段落（可单行多条）. */
    private const CITATION_TRAILING_LINES = '/\n+(?:\[\d{1,3}\]\s*[^\n\[\]]+)+(?:\n(?:\[\d{1,3}\]\s*[^\n\[\]]+)*)*\s*\z/u';

    /**
     * 流式过滤初始状态.
     *
     * @return array{buffer: string, suppress: bool}
     */
    public static function emptyCitationStreamState(): array
    {
        return ['buffer' => '', 'suppress' => false];
    }

    /**
     * 剔除完整回答中的行内资料编号与文末参考列表.
     *
     * @param string $content 原始回答
     *
     * @return string 净化后的回答
     */
    public function sanitizeCitationContent(string $content): string
    {
        if ($content === '' || ! $this->mayContainCitationMarks($content)) {
            return $content;
        }

        $content = $this->stripCitationTrailingReferenceBlock($content);
        $content = $this->stripCitationInlineMarks($content);
        $content = (string) preg_replace('/[ \t]+([。！？；，、,.!?;])/u', '$1', $content);
        $content = (string) preg_replace("/[ \t]+\n/u", "\n", $content);
        $content = (string) preg_replace("/\n{3,}/u", "\n\n", $content);

        return rtrim($content);
    }

    /**
     * 流式转发过滤：剔除行内编号，并在检测到文末参考区后停止继续放行.
     *
     * @param string $chunk 当前 SSE 分片
     * @param array{buffer: string, suppress: bool} $state 流式缓冲状态（引用传递）
     *
     * @return string 可立即转发给前端的内容
     */
    public function takeCitationForwardableImpl(string $chunk, array &$state): string
    {
        if (! empty($state['suppress'])) {
            $state['buffer'] = '';

            return '';
        }

        if ($chunk === '') {
            return '';
        }

        $state['buffer'] = (string) ($state['buffer'] ?? '') . $chunk;
        $cut             = $this->findCitationReferenceBlockStart((string) $state['buffer']);
        if ($cut !== null) {
            $safe            = mb_substr((string) $state['buffer'], 0, $cut);
            $state['buffer'] = '';
            $state['suppress'] = true;

            return $this->stripCitationInlineMarks($safe);
        }

        $hold = $this->citationHoldSuffix((string) $state['buffer']);
        if ($hold === '') {
            $safe            = (string) $state['buffer'];
            $state['buffer'] = '';

            return $this->stripCitationInlineMarks($safe);
        }

        $releaseLen = mb_strlen((string) $state['buffer']) - mb_strlen($hold);
        if ($releaseLen <= 0) {
            return '';
        }

        $safe            = mb_substr((string) $state['buffer'], 0, $releaseLen);
        $state['buffer'] = $hold;

        return $this->stripCitationInlineMarks($safe);
    }

    /**
     * 流结束时刷新缓冲，并对尾部再做一次完整净化.
     *
     * @param array{buffer: string, suppress: bool} $state 流式缓冲状态（引用传递）
     *
     * @return string 可追加转发的尾部内容
     */
    public function flushCitationStreamImpl(array &$state): string
    {
        if (! empty($state['suppress'])) {
            $state = self::emptyCitationStreamState();

            return '';
        }

        $remain = (string) ($state['buffer'] ?? '');
        $state  = self::emptyCitationStreamState();
        if ($remain === '') {
            return '';
        }

        return $this->sanitizeCitationContent($remain);
    }

    /**
     * 文本是否可能含资料编号或参考列表特征.
     */
    private function mayContainCitationMarks(string $content): bool
    {
        return str_contains($content, '[')
            || preg_match('/参考资料|引用来源|资料来源|参考文献/u', $content) === 1;
    }

    /**
     * 去掉文末参考列表（分隔线 / 标题 / 纯编号行）.
     */
    private function stripCitationTrailingReferenceBlock(string $content): string
    {
        $content = (string) preg_replace(self::CITATION_TRAILING_HR, '', $content);
        $content = (string) preg_replace(self::CITATION_TRAILING_LABELED, '', $content);
        $content = (string) preg_replace(self::CITATION_TRAILING_LINES, '', $content);

        return $content;
    }

    /**
     * 去掉行内 [n] 编号；保留 Markdown 链接与代码块内原文.
     */
    private function stripCitationInlineMarks(string $content): string
    {
        if (! str_contains($content, '[')) {
            return $content;
        }

        $parts = preg_split('/(```[\s\S]*?```|`[^`\n]+`)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return (string) preg_replace(self::CITATION_INLINE, '', $content);
        }

        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                continue;
            }
            $parts[$i] = (string) preg_replace(self::CITATION_INLINE, '', $part);
        }

        return implode('', $parts);
    }

    /**
     * 定位文末参考区起点（字符下标）；未形成完整参考区时返回 null.
     */
    private function findCitationReferenceBlockStart(string $content): ?int
    {
        if (preg_match(
            '/(?:^|\n)(?:-{3,}|_{3,}|\*{3,})\s*\n\s*\[\d{1,3}\]/u',
            $content,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return $this->citationCharOffset($content, (int) $matches[0][1]);
        }

        if (preg_match(
            '/(?:^|\n)(?:参考资料|引用来源|资料来源|参考文献)[：:\s]*\n?\s*\[\d{1,3}\]/u',
            $content,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return $this->citationCharOffset($content, (int) $matches[0][1]);
        }

        return null;
    }

    /**
     * 暂扣可能跨分片的编号或参考区前缀，避免截断误放行.
     */
    private function citationHoldSuffix(string $content): string
    {
        if ($content === '') {
            return '';
        }

        if (preg_match('/(?:\[(?:\d{0,3})?|-{1,2}|_{1,2}|\*{1,2}|(?:参考资料|引用来源|资料来源|参考文献)[：:\s]*|来源[：:])$/u', $content, $m)) {
            return (string) $m[0];
        }

        // 已出现分隔线但尚未看到编号行时整段暂扣，等下一片判定是否进入参考区.
        if (preg_match('/(?:^|\n)((?:-{3,}|_{3,}|\*{3,})\s*(?:\n\s*)?)\z/u', $content, $m)) {
            return (string) $m[1];
        }

        return '';
    }

    /**
     * 将 PCRE 字节偏移转为 mb 字符偏移.
     */
    private function citationCharOffset(string $content, int $byteOffset): int
    {
        if ($byteOffset <= 0) {
            return 0;
        }

        return mb_strlen(substr($content, 0, $byteOffset));
    }

}
