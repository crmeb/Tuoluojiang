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

use App\Constants\Ai\AgentEnum;
use App\Constants\Ai\AgentMemoryEnum;
use App\Http\Dao\Ai\AgentMemoryDao;
use App\Http\Model\Ai\AgentRun;
use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiConversation;
use App\Http\Service\Ai\AiConversationMessageService;
use Illuminate\Support\Facades\Schema;

/**
 * Agent 记忆管理：会话摘要 + 跨会话长期记忆.
 *
 * 职责：生成记忆 prompt 片段、回合结束后捕获偏好与结论并 prune 超限条目.
 * 注入时按相关性与字符预算裁剪，避免无关长期记忆撑大上下文导致响应变慢.
 * 在 Agent 流程中的位置：Bootstrap 前注入 promptSection，Turn 结束后 captureAfterTurn.
 */
class MemoryManager
{
    private AgentMemoryDao $dao;

    public function __construct(AgentMemoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 判断 agent_memories 表是否可用.
     *
     * @return bool 表存在时为 true
     */
    public function isEnabled(): bool
    {
        return Schema::hasTable('agent_memories');
    }

    /**
     * 构建可注入 system 消息的记忆上下文片段.
     *
     * @param int $uid 用户 ID
     * @param int $appId AI 应用 ID
     * @param int $conversationId 会话 ID
     * @param AgentConfig $config Agent 配置
     * @param string $question 当前用户问题，用于避免把无关结论整包注入
     * @param null|array<int, string> $availableTools 当前应用可见 MCP 工具；null 表示不做能力过滤
     * @param null|bool $includeSessionSummary 是否注入会话摘要；null 时读 config.ai.context.inject_session_summary
     *
     * @return string Markdown 记忆片段，无记忆时返回空串
     */
    public function promptSection(
        int $uid,
        int $appId,
        int $conversationId,
        AgentConfig $config,
        string $question = '',
        ?array $availableTools = null,
        ?bool $includeSessionSummary = null
    ): string {
        if (! $config->memoryEnabled || ! $this->isEnabled()) {
            return '';
        }

        $budget               = max(200, (int) config('ai.context.memory_prompt_budget', 1200));
        $itemMax              = max(40, (int) config('ai.context.memory_item_max_chars', 160));
        $summaryMax           = max(80, (int) config('ai.context.session_summary_max_chars', 400));
        $injectLimit          = max(1, (int) config('ai.context.memory_inject_limit', 5));
        $includeSessionSummary ??= (bool) config('ai.context.inject_session_summary', false);

        $lines = ['# Agent 记忆上下文'];
        $used  = mb_strlen($lines[0]);

        if ($includeSessionSummary) {
            $session = $this->findSessionSummary($uid, $appId, $conversationId);
            if ($session !== '' && ! $this->shouldSkipMemory($session, $question, $availableTools)) {
                $session = $this->truncateText($session, $summaryMax);
                $block   = "## 当前会话摘要\n" . $session;
                if ($used + mb_strlen($block) + 2 <= $budget) {
                    $lines[] = '## 当前会话摘要';
                    $lines[] = $session;
                    $used += mb_strlen($block) + 2;
                }
            }
        }

        $candidates = $this->listUserMemoryRows($uid, $appId, $config->maxMemories);
        $ranked     = $this->rankMemoriesForQuestion($candidates, $question, $availableTools, $injectLimit);

        $kept = [];
        foreach ($ranked as $item) {
            $text = $this->truncateText($item['content'], $itemMax);
            if ($text === '') {
                continue;
            }
            $line = '- ' . $text;
            if ($used + mb_strlen($line) + 40 > $budget && $kept !== []) {
                break;
            }
            $kept[] = $line;
            $used += mb_strlen($line) + 1;
        }

        if ($kept !== []) {
            $lines[] = '## 跨会话记忆（用户偏好与历史结论）';
            foreach ($kept as $line) {
                $lines[] = $line;
            }
        }

        if (count($lines) <= 1) {
            return '';
        }

        $footer = '请结合上述记忆理解用户当前问题，避免重复询问已知信息。';
        if ($used + mb_strlen($footer) + 2 <= $budget + 80) {
            $lines[] = $footer;
        }

        return implode("\n", $lines);
    }

    /**
     * 单轮对话结束后捕获会话摘要、用户偏好与业务结论.
     *
     * @param AiConversation $conversation 当前会话
     * @param int $appId AI 应用 ID
     * @param string $messageUuid 消息 UUID
     * @param string $question 用户问题
     * @param string $answer Agent 回答
     * @param array $intent 路由意图
     * @param null|AgentRun $run 关联 Run
     * @param AgentConfig $config Agent 配置
     * @param bool $hasToolEvidence 是否存在本轮工具结果，可支撑业务结论
     */
    public function captureAfterTurn(
        AiConversation $conversation,
        int $appId,
        string $messageUuid,
        string $question,
        string $answer,
        array $intent,
        ?AgentRun $run,
        AgentConfig $config,
        bool $hasToolEvidence = false,
        bool $pendingWrite = false
    ): void {
        if (! $config->memoryEnabled || ! $this->isEnabled() || trim($answer) === '') {
            return;
        }

        $uid            = (int) ($conversation->uid ?? $conversation->user_id ?? 0);
        $conversationId = (int) $conversation->id;
        $runId          = (int) ($run->id ?? 0);

        $this->upsertSessionSummary($uid, $appId, $conversationId, $config->sessionTurns, $runId, $messageUuid);

        if ($preference = $this->extractPreference($question)) {
            $this->storeUserMemory(
                $uid,
                $appId,
                AgentMemoryEnum::TYPE_PREFERENCE,
                $preference,
                ['intent' => $intent['type']],
                $runId,
                $messageUuid
            );
        }

        // 写操作还在等待用户确认时结论未定，此时落库会让「数据尚未写入」变成长期记忆；
        // 待 AiConversationService::confirmAgentAction 确认后再由 syncAfterWriteConfirmed 补写.
        if ($hasToolEvidence && ! $pendingWrite && $this->shouldStoreConclusion($intent, $answer)) {
            $conclusion = $this->formatConclusion($question, $answer);
            $this->storeUserMemory(
                $uid,
                $appId,
                AgentMemoryEnum::TYPE_CONCLUSION,
                $conclusion,
                ['intent' => $intent['type']],
                $runId,
                $messageUuid
            );
        }

        $this->pruneUserMemories($uid, $appId, $config->maxMemories);
    }

    /**
     * 写操作确认（执行或取消）后同步记忆，避免记忆停留在「等待确认、数据尚未写入」.
     *
     * 消息 answer_text 已被 saveConfirmedAction 改写为最终结果，故必须在其之后调用。
     *
     * @param AiConversation $conversation 会话模型
     * @param int $appId AI 应用 ID
     * @param string $messageUuid 被确认的消息 UUID
     * @param string $question 该轮用户问题
     * @param string $answer 确认后的最终结果文案
     * @param int $runId 关联的 Agent Run ID
     */
    public function syncAfterWriteConfirmed(
        AiConversation $conversation,
        int $appId,
        string $messageUuid,
        string $question,
        string $answer,
        int $runId = 0
    ): void {
        if (! $this->isEnabled() || trim($answer) === '') {
            return;
        }

        $app = AiApplication::find($appId);
        if (! $app) {
            return;
        }
        $config = AgentConfig::fromApplication($app);
        if (! $config->memoryEnabled) {
            return;
        }

        $uid = (int) ($conversation->uid ?? $conversation->user_id ?? 0);

        // 会话摘要按最新消息重建，覆盖掉确认前的「数据尚未写入」表述.
        $this->upsertSessionSummary($uid, $appId, (int) $conversation->id, $config->sessionTurns, $runId, $messageUuid);

        $conclusion = $this->formatConclusion($question, $answer);
        $rows       = $this->dao->search([
            'uid'         => $uid,
            'app_id'      => $appId,
            'scope'       => AgentMemoryEnum::SCOPE_USER,
            'memory_type' => AgentMemoryEnum::TYPE_CONCLUSION,
        ])->where('source_message_uuid', $messageUuid)->get();

        if ($rows->isEmpty()) {
            $this->storeUserMemory(
                $uid,
                $appId,
                AgentMemoryEnum::TYPE_CONCLUSION,
                $conclusion,
                ['intent' => AgentEnum::INTENT_ACTION_REQUEST, 'write_confirmed' => true],
                $runId,
                $messageUuid
            );

            return;
        }

        foreach ($rows as $row) {
            $this->dao->update((int) $row->id, [
                'content'    => $conclusion,
                'metadata'   => array_merge((array) ($row->metadata ?? []), ['write_confirmed' => true]),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** 读取当前会话的摘要记忆. */
    private function findSessionSummary(int $uid, int $appId, int $conversationId): string
    {
        $row = $this->dao->get([
            'uid'             => $uid,
            'app_id'          => $appId,
            'conversation_id' => $conversationId,
            'memory_type'     => AgentMemoryEnum::TYPE_SESSION_SUMMARY,
            'scope'           => AgentMemoryEnum::SCOPE_SESSION,
        ]);

        return trim((string) ($row->content ?? ''));
    }

    /**
     * 列出用户跨会话长期记忆行（含类型，便于注入时优先偏好）.
     *
     * @return array<int, array{content: string, memory_type: string}>
     */
    private function listUserMemoryRows(int $uid, int $appId, int $limit): array
    {
        $rows = $this->dao->search([
            'uid'    => $uid,
            'app_id' => $appId,
            'scope'  => AgentMemoryEnum::SCOPE_USER,
        ])
            ->whereIn('memory_type', [
                AgentMemoryEnum::TYPE_PREFERENCE,
                AgentMemoryEnum::TYPE_CONCLUSION,
                AgentMemoryEnum::TYPE_FACT,
            ])
            ->orderByDesc('updated_at')
            ->limit(max(1, $limit))
            ->get(['content', 'memory_type']);

        $items = [];
        foreach ($rows as $row) {
            $content = trim((string) ($row->content ?? ''));
            if ($content === '') {
                continue;
            }
            $items[] = [
                'content'     => $content,
                'memory_type' => (string) ($row->memory_type ?? ''),
            ];
        }

        return $items;
    }

    /**
     * 按与当前问题的相关性筛选并截断长期记忆.
     *
     * 偏好始终优先保留；结论/事实无关键词重叠时不注入，避免无关历史撑爆上下文.
     *
     * @param array<int, array{content: string, memory_type: string}> $candidates
     * @param null|array<int, string> $availableTools
     *
     * @return array<int, array{content: string, memory_type: string, score: int}>
     */
    private function rankMemoriesForQuestion(
        array $candidates,
        string $question,
        ?array $availableTools,
        int $injectLimit
    ): array {
        $qTokens = $this->tokens($question);
        $scored  = [];

        foreach ($candidates as $item) {
            $content = $item['content'];
            if ($this->shouldSkipMemory($content, $question, $availableTools)) {
                continue;
            }

            $type  = $item['memory_type'];
            $score = $this->relevanceScore($content, $qTokens);
            if ($type === AgentMemoryEnum::TYPE_PREFERENCE) {
                // 用户显式偏好跨话题保留，略抬权重.
                $score = max($score, 1) + 10;
            } elseif ($qTokens !== [] && $score <= 0) {
                continue;
            }

            $scored[] = [
                'content'     => $content,
                'memory_type' => $type,
                'score'       => $score,
            ];
        }

        usort($scored, static function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return 0;
        });

        return array_slice($scored, 0, $injectLimit);
    }

    /**
     * 问题与记忆文本的粗粒度词重叠分.
     *
     * @param array<int, string> $questionTokens
     */
    private function relevanceScore(string $content, array $questionTokens): int
    {
        if ($questionTokens === []) {
            return 0;
        }

        $memoryTokens = $this->tokens($content);
        if ($memoryTokens === []) {
            return 0;
        }

        $memorySet = array_fill_keys($memoryTokens, true);
        $score     = 0;
        foreach ($questionTokens as $token) {
            if (isset($memorySet[$token])) {
                $score += mb_strlen((string) $token) >= 3 ? 2 : 1;
                continue;
            }
            // 子串兜底：短中文问句与结论标题部分匹配.
            $token = (string) $token;
            if (mb_strlen($token) >= 2 && mb_strpos($content, $token) !== false) {
                $score += 1;
            }
        }

        return $score;
    }

    /**
     * 抽取可用于相关性匹配的词元（中文 2/3 字窗 + 英文数字标识）.
     *
     * @return array<int, string>
     */
    private function tokens(string $text): array
    {
        $text = mb_strtolower(trim($text));
        if ($text === '') {
            return [];
        }

        $stop = [
            '什么', '怎么', '如何', '是否', '可以', '一下', '这个', '那个', '我们', '你们', '他们',
            '一个', '没有', '不是', '确定', '请问', '帮忙', '告诉', '以及', '或者', '还有',
            '需要', '希望', '进行', '处理', '查看', '告诉我', '帮我', '曾处理', '本月', '今天',
            '明天', '昨天', '后天', '哪些', '多少', '有没有', '一下',
        ];

        $out = [];
        preg_match_all('/[a-z0-9_]{2,}/u', $text, $latin);
        foreach ($latin[0] ?? [] as $token) {
            $token = trim((string) $token);
            if ($token !== '') {
                $out[$token] = true;
            }
        }

        preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $text, $runs);
        foreach ($runs[0] ?? [] as $run) {
            $len = mb_strlen((string) $run);
            for ($n = 2; $n <= 3; $n++) {
                if ($len < $n) {
                    continue;
                }
                for ($i = 0; $i <= $len - $n; $i++) {
                    $gram = mb_substr((string) $run, $i, $n);
                    if (in_array($gram, $stop, true)) {
                        continue;
                    }
                    $out[$gram] = true;
                }
            }
        }

        // PHP 会把纯数字字符串键收成 int（如 "1200" → 1200），
        // 后续 mb_strlen/mb_strpos 在 strict_types 下会 TypeError。
        return array_map(static fn (int|string $key): string => (string) $key, array_keys($out));
    }

    /** 按字符预算截断文本，超长追加省略号. */
    private function truncateText(string $text, int $maxChars): string
    {
        $text = trim($text);
        if ($text === '' || $maxChars <= 0 || mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, max(1, $maxChars - 1)) . '…';
    }

    /** 更新或创建当前会话摘要记忆. */
    private function upsertSessionSummary(
        int $uid,
        int $appId,
        int $conversationId,
        int $sessionTurns,
        int $runId,
        string $messageUuid
    ): void {
        $summary = $this->buildSessionSummary($conversationId, $sessionTurns);
        if ($summary === '') {
            return;
        }

        $existing = $this->dao->get([
            'uid'             => $uid,
            'app_id'          => $appId,
            'conversation_id' => $conversationId,
            'memory_type'     => AgentMemoryEnum::TYPE_SESSION_SUMMARY,
            'scope'           => AgentMemoryEnum::SCOPE_SESSION,
        ]);

        $payload = [
            'content'             => $summary,
            'metadata'            => ['turns' => $sessionTurns],
            'source_run_id'       => $runId,
            'source_message_uuid' => $messageUuid,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->dao->update((int) $existing->id, $payload);

            return;
        }

        $this->dao->create(array_merge($payload, [
            'uid'             => $uid,
            'app_id'          => $appId,
            'conversation_id' => $conversationId,
            'memory_type'     => AgentMemoryEnum::TYPE_SESSION_SUMMARY,
            'scope'           => AgentMemoryEnum::SCOPE_SESSION,
            'created_at'      => date('Y-m-d H:i:s'),
        ]));
    }

    /** 从最近 N 轮对话历史拼接会话摘要文本. */
    private function buildSessionSummary(int $conversationId, int $sessionTurns): string
    {
        $messages = app(AiConversationMessageService::class)->history($conversationId, max(1, $sessionTurns));
        if ($messages === []) {
            return '';
        }

        $lines = [];
        for ($i = 0, $count = count($messages); $i < $count; $i += 2) {
            $q = mb_substr(trim((string) ($messages[$i]['content'] ?? '')), 0, 80);
            if ($q === '') {
                continue;
            }
            $a = isset($messages[$i + 1])
                ? mb_substr(trim((string) ($messages[$i + 1]['content'] ?? '')), 0, 80)
                : '';
            $lines[] = $a !== '' ? "问：{$q}；答：{$a}" : "问：{$q}";
        }

        return implode("\n", $lines);
    }

    /** 从问题文本提取用户偏好表述（「以后/默认/请记住」等）. */
    private function extractPreference(string $question): string
    {
        if (preg_match('/(?:以后|今后|默认|总是|优先|请记住).{2,80}/u', $question, $matches)) {
            return trim((string) ($matches[0] ?? ''));
        }

        return '';
    }

    /** 判断当前意图与回答是否值得存储为跨会话结论. */
    private function shouldStoreConclusion(array $intent, string $answer): bool
    {
        if (trim($answer) === '') {
            return false;
        }

        return in_array($intent['type'], [
            AgentEnum::INTENT_DATA_QUERY,
            AgentEnum::INTENT_ACTION_REQUEST,
            AgentEnum::INTENT_MULTI_STEP,
        ], true);
    }

    /** 格式化问答对为结论记忆文本. */
    private function formatConclusion(string $question, string $answer): string
    {
        $q = mb_substr(trim($question), 0, 80);
        $a = mb_substr(trim($answer), 0, 160);

        return "曾处理「{$q}」→ {$a}";
    }

    /** 写入或更新用户级长期记忆，相同 content 去重. */
    private function storeUserMemory(
        int $uid,
        int $appId,
        string $type,
        string $content,
        array $metadata,
        int $runId,
        string $messageUuid
    ): void {
        $content = trim($content);
        if ($content === '') {
            return;
        }

        $existing = $this->dao->search([
            'uid'         => $uid,
            'app_id'      => $appId,
            'scope'       => AgentMemoryEnum::SCOPE_USER,
            'memory_type' => $type,
        ])->where('content', $content)->first();

        if ($existing) {
            $this->dao->update((int) $existing->id, [
                'metadata'            => $metadata,
                'source_run_id'       => $runId,
                'source_message_uuid' => $messageUuid,
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);

            return;
        }

        $this->dao->create([
            'uid'                 => $uid,
            'app_id'              => $appId,
            'conversation_id'     => 0,
            'memory_type'         => $type,
            'scope'               => AgentMemoryEnum::SCOPE_USER,
            'content'             => $content,
            'metadata'            => $metadata,
            'source_run_id'       => $runId,
            'source_message_uuid' => $messageUuid,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    /** 超出 maxMemories 时删除最旧的用户级记忆. */
    private function pruneUserMemories(int $uid, int $appId, int $maxMemories): void
    {
        $rows = $this->dao->search([
            'uid'    => $uid,
            'app_id' => $appId,
            'scope'  => AgentMemoryEnum::SCOPE_USER,
        ])
            ->orderByDesc('updated_at')
            ->get(['id']);

        $overflow = $rows->count() - max(1, $maxMemories);
        if ($overflow <= 0) {
            return;
        }

        foreach ($rows->slice($maxMemories) as $row) {
            $this->dao->delete((int) $row->id);
        }
    }

    /**
     * 无对应写工具或不相关的请假审批结论不注入，避免无审批 MCP 时直接命中历史办理记忆.
     *
     * @param null|array<int, string> $availableTools
     */
    private function shouldSkipMemory(string $content, string $question, ?array $availableTools): bool
    {
        if (! $this->mentionsPersonnelApprove($content)) {
            return false;
        }
        if ($availableTools !== null && ! in_array(\App\Http\Service\Ai\AiMcpService::PERSONNEL_APPROVE_WRITE_TOOL, $availableTools, true)) {
            return true;
        }

        return $question !== '' && ! $this->mentionsPersonnelApprove($question);
    }

    /** 判断记忆是否描述人事请假/审批办理结论. */
    private function mentionsPersonnelApprove(string $content): bool
    {
        return (bool) preg_match(
            '/请假|假期类型|approve_create|approve_holiday|approve_form_get|approve_process_get|事假|年假|调休|补卡|加班|出差|外出/u',
            $content
        );
    }
}
