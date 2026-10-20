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

namespace App\Http\Service\Ai\Agent\Trace;

use App\Constants\Ai\AgentEnum;
use App\Constants\Ai\AiConversationEnum;
use App\Http\Dao\Ai\AgentRunDao;
use App\Http\Dao\Ai\AgentStepDao;
use App\Http\Dao\Ai\AiConversationDao;
use App\Http\Model\Ai\AgentRun;
use App\Http\Service\Ai\Agent\AgentConfig;
use App\Http\Service\Ai\AiStreamService;
use Illuminate\Support\Facades\Schema;

/**
 * Agent 运行追踪：持久化 run / step，供审计与后续可观测性扩展.
 *
 * 职责：创建 Run、记录各阶段 Step、同步工具轨迹与终态状态；并提供 Run 列表/详情/统计只读查询.
 * 在 Agent 流程中的位置：Bootstrap 时启动、Executor 各关键节点写入；管理端 Observability API 读取.
 */
class AgentTracer
{
    private AgentRunDao $runDao;

    private AgentStepDao $stepDao;

    private AiConversationDao $conversationDao;

    public function __construct(AgentRunDao $runDao, AgentStepDao $stepDao, AiConversationDao $conversationDao)
    {
        $this->runDao          = $runDao;
        $this->stepDao         = $stepDao;
        $this->conversationDao = $conversationDao;
    }

    /**
     * 判断 agent_runs / agent_steps 表是否可用.
     *
     * @return bool 表存在且可追踪时为 true
     */
    public function isEnabled(): bool
    {
        return Schema::hasTable('agent_runs') && Schema::hasTable('agent_steps');
    }

    /**
     * 创建新的 Agent Run 并记录初始思考步骤.
     *
     * @param int $conversationId 会话 ID
     * @param string $messageUuid 消息 UUID
     * @param int $appId AI 应用 ID
     * @param int $uid 用户 ID
     * @param array $intent 路由得到的意图
     * @param AgentConfig $config Agent 配置
     *
     * @return AgentRun|null 新建的 Run，追踪未启用时返回 null
     */
    public function startRun(
        int $conversationId,
        string $messageUuid,
        int $appId,
        int $uid,
        array $intent,
        AgentConfig $config
    ): ?AgentRun {
        if (! $this->isEnabled()) {
            return null;
        }

        /** @var AgentRun $run */
        $run = $this->runDao->create([
            'conversation_id' => $conversationId,
            'message_uuid'    => $messageUuid,
            'app_id'          => $appId,
            'uid'             => $uid,
            'status'          => AgentEnum::RUN_STATUS_EXECUTING,
            'intent'          => $intent,
            'max_steps'       => $config->maxSteps,
            'current_step'    => 0,
            'token_usage'     => [],
        ]);

        $this->recordStep($run, AgentEnum::STEP_TYPE_THINK, [
            'intent' => $intent,
        ], [
            'status' => AgentEnum::RUN_STATUS_EXECUTING,
        ]);

        return $run;
    }

    /**
     * 追加一条 Agent 执行步骤记录.
     *
     * @param AgentRun|null $run 关联 Run，为空时跳过
     * @param string $type 步骤类型（think/tool_call/reflect 等）
     * @param array<string, mixed> $input 步骤输入快照
     * @param array<string, mixed> $output 步骤输出快照
     * @param string $skillName 关联 Skill 名称
     * @param string $toolName 关联工具名称
     * @param int $latencyMs 耗时毫秒
     * @param string $status 步骤状态
     */
    public function recordStep(
        ?AgentRun $run,
        string $type,
        array $input = [],
        array $output = [],
        string $skillName = '',
        string $toolName = '',
        int $latencyMs = 0,
        string $status = AgentEnum::STEP_STATUS_DONE
    ): void {
        if (! $run || ! $this->isEnabled()) {
            return;
        }

        $stepNo = (int) $run->current_step + 1;
        $this->stepDao->create([
            'run_id'     => (int) $run->id,
            'step_no'    => $stepNo,
            'type'       => $type,
            'input'      => $input,
            'output'     => $output,
            'skill_name' => $skillName,
            'tool_name'  => $toolName,
            'status'     => $status,
            'latency_ms' => $latencyMs,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->runDao->update((int) $run->id, ['current_step' => $stepNo]);
        $run->current_step = $stepNo;
    }

    /**
     * 将内存中的 tool_loop 轨迹批量落库为 Step.
     *
     * @param AgentRun|null $run 关联 Run
     * @param array<int, array<string, mixed>> $agentTrace ToolLoop 内存轨迹
     */
    public function persistToolTrace(?AgentRun $run, array $agentTrace): void
    {
        if (! $run) {
            return;
        }

        foreach ($agentTrace as $item) {
            if (($item['type'] ?? '') !== 'tool_loop') {
                continue;
            }
            $this->recordStep(
                $run,
                AgentEnum::STEP_TYPE_TOOL_CALL,
                ['tool_names' => (array) ($item['tool_names'] ?? [])],
                ['results' => (array) ($item['results'] ?? [])],
                '',
                implode(',', (array) ($item['tool_names'] ?? [])),
                (int) ($item['latency_ms'] ?? 0)
            );
        }
    }

    /**
     * 标记 Run 完成并写入最终回答预览步骤.
     *
     * @param AgentRun|null $run 关联 Run
     * @param array $result 工具循环最终结果
     */
    public function completeRun(?AgentRun $run, array $result): void
    {
        if (! $run || ! $this->isEnabled()) {
            return;
        }

        $this->recordStep($run, AgentEnum::STEP_TYPE_ANSWER, [], [
            'answer_preview' => mb_substr($result['answer'], 0, 200),
            'tool_count'     => count($result['tool_calls']),
        ]);

        $status = AgentEnum::RUN_STATUS_DONE;
        if ($result['pending_input']) {
            $status = AgentEnum::RUN_STATUS_AWAITING_INPUT;
        } elseif ($result['pending_confirmation']) {
            $status = AgentEnum::RUN_STATUS_AWAITING_CONFIRMATION;
        } elseif ((int) $result['status'] === AiConversationEnum::MESSAGE_INTERRUPTED) {
            $status = AgentEnum::RUN_STATUS_INTERRUPTED;
        } elseif ((int) $result['status'] === AiConversationEnum::MESSAGE_FAILED) {
            $status = AgentEnum::RUN_STATUS_FAILED;
        }

        $this->runDao->update((int) $run->id, [
            'status'       => $status,
            'token_usage'  => AiStreamService::normalizeUsage((array) ($result['usage'] ?? [])),
            'current_step' => (int) $run->current_step,
        ]);
    }

    /**
     * 标记 Run 失败并记录错误步骤.
     *
     * @param AgentRun|null $run 关联 Run
     * @param string $message 失败原因
     */
    public function failRun(?AgentRun $run, string $message): void
    {
        if (! $run || ! $this->isEnabled()) {
            return;
        }

        $this->recordStep(
            $run,
            AgentEnum::STEP_TYPE_ANSWER,
            [],
            ['error' => $message],
            '',
            '',
            0,
            AgentEnum::STEP_STATUS_FAILED
        );

        $this->runDao->update((int) $run->id, [
            'status' => AgentEnum::RUN_STATUS_FAILED,
        ]);
    }

    /**
     * 将 Run 状态更新为等待写操作确认.
     *
     * @param AgentRun|null $run 关联 Run
     */
    public function markAwaitingConfirmation(?AgentRun $run): void
    {
        if (! $run || ! $this->isEnabled()) {
            return;
        }

        $this->runDao->update((int) $run->id, [
            'status' => AgentEnum::RUN_STATUS_AWAITING_CONFIRMATION,
        ]);
    }

    /**
     * 将 Run 状态更新为等待用户补充信息.
     *
     * @param AgentRun|null $run 关联 Run
     */
    public function markAwaitingInput(?AgentRun $run): void
    {
        if (! $run || ! $this->isEnabled()) {
            return;
        }

        $this->runDao->update((int) $run->id, [
            'status' => AgentEnum::RUN_STATUS_AWAITING_INPUT,
        ]);
    }

    /**
     * 创建内存 trace 状态（原 AgentTraceRecorder）.
     *
     * @return array{steps: array<int, array<string, mixed>>, started_at: float}
     */
    public static function newMemoryTrace(): array
    {
        return ['steps' => [], 'started_at' => microtime(true)];
    }

    /**
     * @param array{steps: array<int, array<string, mixed>>, started_at: float} $trace
     * @param array<int, array<string, mixed>> $toolCalls
     * @param array<int, array<string, mixed>> $toolResults
     */
    public static function memoryRecordRound(array &$trace, int $round, array $toolCalls, array $toolResults, int $latencyMs): void
    {
        $toolNames = array_values(array_filter(array_map(
            static fn (array $call): string => (string) ($call['function']['name'] ?? ''),
            $toolCalls
        )));

        $trace['steps'][] = [
            'round'       => $round,
            'type'        => 'tool_loop',
            'tool_names'  => $toolNames,
            'tool_count'  => count($toolCalls),
            'latency_ms'  => $latencyMs,
            'results'     => array_map(static function (array $result): array {
                return [
                    'name'    => (string) ($result['name'] ?? ''),
                    'type'    => (string) ($result['type'] ?? 'mcp'),
                    'success' => empty($result['result']['error']),
                ];
            }, $toolResults),
        ];
    }

    /**
     * @param array{steps: array<int, array<string, mixed>>, started_at: float} $trace
     * @param array<string, mixed> $meta
     */
    public static function memoryRecordPhase(array &$trace, string $phase, int $latencyMs, array $meta = []): void
    {
        $trace['steps'][] = [
            'type'       => 'phase',
            'phase'      => $phase,
            'latency_ms' => $latencyMs,
            'meta'       => $meta,
        ];
    }

    /**
     * @param array{steps: array<int, array<string, mixed>>, started_at: float} $trace
     */
    public static function memoryRecordFinalAnswer(array &$trace, int $totalRounds): void
    {
        $trace['steps'][] = [
            'type'          => 'answer',
            'total_rounds'  => $totalRounds,
            'total_elapsed' => (int) round((microtime(true) - (float) ($trace['started_at'] ?? microtime(true))) * 1000),
        ];
    }

    /**
     * @param array{steps?: array<int, array<string, mixed>>, started_at?: float}|array<int, array<string, mixed>> $trace
     *
     * @return array<int, array<string, mixed>>
     */
    public static function memoryTraceSteps(array $trace): array
    {
        if (isset($trace['steps']) && is_array($trace['steps'])) {
            return $trace['steps'];
        }

        // 兼容已落库的纯 steps 列表.
        return array_values(array_filter($trace, static fn ($row): bool => is_array($row)));
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     *
     * @return array{steps: array<int, array<string, mixed>>, started_at: float}
     */
    public static function memoryTraceFromSteps(array $steps): array
    {
        return ['steps' => array_values($steps), 'started_at' => microtime(true)];
    }

    /**
     * 分页列出指定会话下的 Agent Run 摘要.
     *
     * @param int $conversationId 会话 ID
     * @param int $uid 用户 ID
     * @param int $page 页码
     * @param int $limit 每页条数
     *
     * @return array{list: array<int, array<string, mixed>>, count: int} 列表与总数
     */
    public function listByConversation(int $conversationId, int $uid, int $page = 1, int $limit = 20): array
    {
        $this->assertConversationAccess($conversationId, $uid);
        if (! $this->isEnabled()) {
            return ['list' => [], 'count' => 0];
        }

        $page  = max(1, $page);
        $limit = max(1, min(100, $limit));
        $query = $this->runDao->search(['conversation_id' => $conversationId, 'uid' => $uid])
            ->orderByDesc('id');

        $count = (int) $query->count();
        $list  = $query->forPage($page, $limit)->get()->map(fn (AgentRun $run): array => $this->runSummary($run))->all();

        return ['list' => $list, 'count' => $count];
    }

    /**
     * 获取 Run 详情含步骤列表与时间线.
     *
     * @param int $runId Run ID
     * @param int $uid 用户 ID
     *
     * @return array<string, mixed> 含 run、steps、timeline
     *
     * @throws \RuntimeException Run 不存在或无权限
     */
    public function detail(int $runId, int $uid): array
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('Agent 追踪未启用');
        }

        /** @var null|AgentRun $run */
        $run = $this->runDao->get(['id' => $runId, 'uid' => $uid]);
        if (! $run) {
            throw new \RuntimeException('运行记录不存在或无权限访问');
        }

        $steps = $this->stepDao->search(['run_id' => $runId])
            ->orderBy('step_no')
            ->get()
            ->map(fn ($step): array => [
                'id'         => (int) $step->id,
                'step_no'    => (int) $step->step_no,
                'type'       => (string) $step->type,
                'status'     => (string) $step->status,
                'skill_name' => (string) $step->skill_name,
                'tool_name'  => (string) $step->tool_name,
                'latency_ms' => (int) $step->latency_ms,
                'input'      => (array) ($step->input ?? []),
                'output'     => (array) ($step->output ?? []),
                'created_at' => (string) $step->created_at,
            ])
            ->all();

        return [
            'run'      => $this->runSummary($run, true),
            'steps'    => $steps,
            'timeline' => $this->buildTimeline($steps),
        ];
    }

    /**
     * 统计指定应用下当前用户的 Agent 运行指标.
     *
     * @param int $appId AI 应用 ID
     * @param int $uid 用户 ID
     *
     * @param int $days 统计最近多少天，0 表示不限制
     *
     * @return array<string, mixed> 含运行、Skill、工具、延迟和 Token 指标
     */
    public function appStats(int $appId, int $uid, int $days = 30): array
    {
        if (! $this->isEnabled()) {
            return $this->emptyStats();
        }

        $days = max(0, min(3650, $days));
        $runs = $this->runDao->search(['app_id' => $appId, 'uid' => $uid])
            ->when($days > 0, fn ($query) => $query->where('created_at', '>=', now()->subDays($days)))
            ->with('steps')
            ->get();
        if ($runs->isEmpty()) {
            return array_merge($this->emptyStats(), ['window_days' => $days]);
        }

        $statusCounts = [];
        $toolCounts   = [];
        $skillCounts  = [];
        $totalTokens  = 0;
        $totalSteps   = 0;
        $totalLatency = 0;
        $latencySteps = 0;
        foreach ($runs as $run) {
            $status = (string) $run->status;
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            $usage = (array) ($run->token_usage ?? []);
            $totalTokens += (int) ($usage['total_tokens'] ?? $usage['tokens'] ?? 0);
            $totalSteps += (int) $run->current_step;
            foreach ($run->steps as $step) {
                $totalLatency += (int) $step->latency_ms;
                $latencySteps++;
                $skillName = trim((string) $step->skill_name);
                if ($skillName !== '') {
                    $skillCounts[$skillName] = ($skillCounts[$skillName] ?? 0) + 1;
                }
                foreach (array_filter(array_map('trim', explode(',', (string) $step->tool_name))) as $toolName) {
                    $toolCounts[$toolName] = ($toolCounts[$toolName] ?? 0) + 1;
                }
            }
        }

        $total = $runs->count();
        arsort($toolCounts);
        arsort($skillCounts);

        return [
            'total_runs'    => $total,
            'status_counts' => $statusCounts,
            'done_rate'     => round(($statusCounts[AgentEnum::RUN_STATUS_DONE] ?? 0) / max(1, $total), 4),
            'total_tokens'  => $totalTokens,
            'avg_steps'     => round($totalSteps / max(1, $total), 2),
            'window_days'   => $days,
            'tool_counts'   => $toolCounts,
            'skill_counts'  => $skillCounts,
            'avg_latency_ms'=> $latencySteps > 0 ? round($totalLatency / $latencySteps, 2) : 0,
        ];
    }

    /** 格式化 Run 为列表/详情摘要结构. */
    private function runSummary(AgentRun $run, bool $detailed = false): array
    {
        $usage = (array) ($run->token_usage ?? []);
        $summary = [
            'id'              => (int) $run->id,
            'conversation_id' => (int) $run->conversation_id,
            'message_uuid'    => (string) $run->message_uuid,
            'app_id'          => (int) $run->app_id,
            'status'          => (string) $run->status,
            'intent'          => (array) ($run->intent ?? []),
            'max_steps'       => (int) $run->max_steps,
            'current_step'    => (int) $run->current_step,
            'token_usage'     => $usage,
            'total_tokens'    => (int) ($usage['total_tokens'] ?? $usage['tokens'] ?? 0),
            'created_at'      => (string) $run->created_at,
            'updated_at'      => (string) $run->updated_at,
        ];

        if ($detailed) {
            $summary['uid'] = (int) $run->uid;
        }

        return $summary;
    }

    /** 将步骤列表转换为前端时间线展示结构. */
    private function buildTimeline(array $steps): array
    {
        return array_map(static fn (array $step): array => [
            'step_no'    => $step['step_no'],
            'type'       => $step['type'],
            'label'      => self::stepLabel((string) $step['type'], (string) ($step['tool_name'] ?? '')),
            'status'     => $step['status'],
            'latency_ms' => $step['latency_ms'],
            'created_at' => $step['created_at'],
        ], $steps);
    }

    /** 将步骤 type 映射为中文展示标签. */
    private static function stepLabel(string $type, string $toolName): string
    {
        return match ($type) {
            AgentEnum::STEP_TYPE_THINK     => '分析意图',
            AgentEnum::STEP_TYPE_TOOL_CALL => $toolName !== '' ? '调用工具 ' . $toolName : '工具调用',
            AgentEnum::STEP_TYPE_REFLECT   => '校验结果',
            AgentEnum::STEP_TYPE_ANSWER    => '生成回答',
            default                        => $type,
        };
    }

    /** 校验用户对会话的访问权限. */
    private function assertConversationAccess(int $conversationId, int $uid): void
    {
        $conversation = $this->conversationDao->get(['id' => $conversationId, 'uid' => $uid]);
        if (! $conversation) {
            throw new \RuntimeException('会话不存在或无权限访问');
        }
    }

    /** 返回空统计结构的默认值. */
    private function emptyStats(): array
    {
        return [
            'total_runs'    => 0,
            'status_counts' => [],
            'done_rate'     => 0,
            'total_tokens'  => 0,
            'avg_steps'     => 0,
            'window_days'   => 30,
            'tool_counts'   => [],
            'skill_counts'  => [],
            'avg_latency_ms'=> 0,
        ];
    }
}
