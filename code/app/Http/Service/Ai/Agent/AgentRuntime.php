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
use App\Constants\Ai\AgentQuotaEnum;
use App\Http\Model\Ai\AgentRun;
use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiConversation;
use App\Http\Service\Ai\Agent\Decision\McpToolAllowlist;
use App\Http\Service\Ai\Agent\Decision\ThinDecisionBuilder;
use App\Http\Service\Ai\Agent\MdSkills;
use App\Http\Service\Ai\Agent\Trace\AgentTracer;
use App\Http\Service\Ai\AiMcpService;
use App\Http\Service\Ai\AiStreamService;
use App\Http\Service\Ai\AiToolCallService;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;

/**
 * Agent 运行时入口：Decision + Retrieval 编排门面，并委托 Executor 执行.
 *
 * 职责：配置解析、thin-react 决策、Prefetch、MCP 发现、Run 追踪与执行委托.
 * 会话层（AiConversationService）只保留鉴权、配额与落库，不再内嵌 prepareStreamContext.
 */
class AgentRuntime
{
    private AgentTracer $tracer;

    private AgentExecutor $executor;

    public function __construct(
        AgentTracer $tracer,
        AgentExecutor $executor
    ) {
        $this->tracer   = $tracer;
        $this->executor = $executor;
    }

    /**
     * 从 AI 应用解析 Agent 编排配置.
     *
     * @param AiApplication $app 当前 AI 应用
     *
     * @return AgentConfig Agent 调参对象
     */
    public function config(AiApplication $app): AgentConfig
    {
        return AgentConfig::fromApplication($app);
    }

    /**
     * 启动 Agent 会话：解析配置、挂载 thin-react 中性意图并可选创建 Run 记录.
     *
     * @param AiApplication $app 当前 AI 应用
     * @param AiConversation $conversation 当前会话
     * @param string $messageUuid 本轮消息 UUID
     * @param array|null $intent 可选预计算意图，为空时使用 thin-react 中性意图
     *
     * @return array{config: AgentConfig, intent: array, run: ?\App\Http\Model\Ai\AgentRun}
     */
    public function bootstrap(
        AiApplication $app,
        AiConversation $conversation,
        string $messageUuid,
        ?array $intent = null
    ): array {
        $config = $this->config($app);
        $intent ??= ThinDecisionBuilder::neutralIntent();
        $run    = null;

        if ($this->tracer->isEnabled()) {
            $run = $this->tracer->startRun(
                (int) $conversation->id,
                $messageUuid,
                (int) $app->id,
                (int) $conversation->uid,
                $intent,
                $config
            );
        }

        return ['config' => $config, 'intent' => $intent, 'run' => $run];
    }

    /**
     * Decision + Retrieval：瘦身 ReAct 渐进工具路径（唯一路径）.
     *
     * @return array{
     *     rag: array<string, mixed>,
     *     toolService: AiToolCallService,
     *     externalTools: array<int, array<string, mixed>>,
     *     decision: array<string, mixed>,
     *     bootstrap: array{config: AgentConfig, intent: array, run: ?\App\Http\Model\Ai\AgentRun},
     *     memoryPrompt: string,
     *     skillPrompt: string,
     *     availableTools: array<int, string>
     * }
     */
    public function prepareStream(
        AiApplication $app,
        AiConversation $conversation,
        string $question,
        string $messageUuid,
        AiStreamService $streamService,
        AgentConfig $agentConfig,
        bool $isGuest = false,
        ?AiToolCallService $toolService = null
    ): array {
        $toolService ??= app(AiToolCallService::class);
        $prepareStarted = microtime(true);
        $decision       = ThinDecisionBuilder::buildDecision($app, $isGuest);
        $intent         = ThinDecisionBuilder::neutralIntent();
        $availableTools = app(AiMcpService::class)->localToolNamesForApp($app);
        $catalogPrompt  = ThinDecisionBuilder::catalogPrompt((array) ($decision['module_catalog'] ?? []));

        $streamService->status('agent_think', '准备工具目录，交由模型决策', [
            'intent'     => $intent['type'],
            'confidence' => $intent['confidence'],
            'thin_react' => true,
            'need_rag'   => false,
            'profile'    => (string) ($decision['profile'] ?? ThinDecisionBuilder::PROFILE_EMPLOYEE),
            'prefetch'   => (array) ($decision['prefetch'] ?? []),
        ]);
        $streamService->ping();
        $streamService->status('rag_skip', '知识库改为按需 search_knowledge');
        $streamService->ping();

        $uid            = (int) $conversation->uid;
        $appId          = (int) $app->id;
        $conversationId = (int) $conversation->id;
        $memoryManager  = app(MemoryManager::class);
        $needMemory     = ! $isGuest && in_array('memory', (array) ($decision['prefetch'] ?? []), true);
        $needIdentity   = ! $isGuest && in_array('identity', (array) ($decision['prefetch'] ?? []), true);

        $parallelTasks = [];
        if ($needMemory) {
            $includeSessionSummary = (bool) config('ai.context.inject_session_summary', false);
            $parallelTasks['memory'] = fn (): string => $memoryManager->promptSection(
                $uid,
                $appId,
                $conversationId,
                $agentConfig,
                $question,
                $availableTools,
                $includeSessionSummary
            );
        }
        if ($needIdentity) {
            $parallelTasks['identity'] = fn (): string => $toolService->identityContext();
        }

        $prefetchStarted = microtime(true);
        $parallel        = $parallelTasks === []
            ? []
            : $streamService->runParallelWithHeartbeat($parallelTasks);
        $prefetchMs   = (int) round((microtime(true) - $prefetchStarted) * 1000);
        $memoryPrompt = (string) ($parallel['memory'] ?? '');
        if ($needIdentity) {
            $toolService->setPrefetchedIdentity((string) ($parallel['identity'] ?? ''));
        }

        $rag = ['prompt' => '', 'references' => [], 'images' => []];

        $bootstrap = $this->bootstrap($app, $conversation, $messageUuid, $intent);

        if ($bootstrap['run']) {
            $streamService->status('agent_think', '瘦身决策就绪', [
                'intent'     => $bootstrap['intent']['type'],
                'confidence' => $bootstrap['intent']['confidence'],
                'run_id'     => (int) $bootstrap['run']->id,
                'thin_react' => true,
            ]);
            $this->tracer->recordStep($bootstrap['run'], AgentEnum::STEP_TYPE_THINK, [
                'phase' => 'thin_react',
            ], [
                'prefetch_ms' => $prefetchMs,
                'profile'     => (string) ($decision['profile'] ?? ''),
                'prefetch'    => (array) ($decision['prefetch'] ?? []),
                'catalog'     => (array) ($decision['module_catalog'] ?? []),
            ], '', '', $prefetchMs);
        }

        // 模块目录 + 可选图表格式 Skill；不做关键词路由.
        $skillParts = array_filter([$catalogPrompt]);
        if ((bool) config('ai.agent.skills.enabled', true) && ($decision['mcp'] ?? false)) {
            $matcher      = app(MdSkills::class);
            $formatSkills = $matcher->matchByNames(['gpt-vis'], $availableTools);
            $built        = $matcher->buildPrompt($formatSkills);
            if ($built !== '') {
                $skillParts[] = $built;
            }
        }
        $skillPrompt = trim(implode("\n\n", $skillParts));

        $orchestrateMs = (int) round((microtime(true) - $prepareStarted) * 1000);
        $streamService->setRequestMeta([
            'intent_type'         => (string) ($bootstrap['intent']['type'] ?? ''),
            'intent_confidence'   => 1.0,
            'intent_source'       => 'thin_react',
            'intent_operation'    => '',
            'intent_modules'      => [],
            'intent_skills'       => [],
            'mcp_allowlist_count' => count((array) ($decision['mcp_allowlist'] ?? [])),
            'inject_org_tools'    => false,
            'max_tool_rounds'     => (int) ($decision['max_tool_rounds'] ?? 0),
            'answer_policy'       => (string) ($decision['answer_policy'] ?? ''),
            'prefetch_ms'         => $prefetchMs,
            'orchestrate_ms'      => $orchestrateMs,
            'thin_react'          => true,
        ]);

        $streamService->status('rag_done', '已跳过知识库预取', [
            'hit_count'       => 0,
            'skipped'         => true,
            'answer_policy'   => (string) ($decision['answer_policy'] ?? ''),
            'plan_rag'        => false,
            'plan_mcp'        => (bool) ($decision['mcp'] ?? false),
            'plan_sql'        => (bool) ($decision['sql'] ?? false),
            'max_tool_rounds' => (int) ($decision['max_tool_rounds'] ?? 0),
            'prefetch_ms'     => $prefetchMs,
            'orchestrate_ms'  => $orchestrateMs,
            'thin_react'      => true,
        ]);
        $streamService->ping();

        $externalTools = [];
        $mcpDiscoverMs = 0;
        if ($decision['mcp']) {
            $streamService->status('mcp_prepare', '正在发现可用 MCP 工具');
            $mcpStarted = microtime(true);
            $allowlist  = McpToolAllowlist::discoveryNames((array) ($decision['mcp_allowlist'] ?? []));
            $externalTools = $streamService->runWithHeartbeat(
                fn (): array => app(AiMcpService::class)->toolsForApp($app, $allowlist)
            );
            $mcpDiscoverMs = (int) round((microtime(true) - $mcpStarted) * 1000);
            $streamService->ping();
        }
        $streamService->setRequestMeta(['mcp_discover_ms' => $mcpDiscoverMs]);

        if ($isGuest) {
            $decision = $this->sanitizeGuestDecision($decision, $externalTools);
            // 访客仍可按需检索知识库（若应用绑定）.
            if (! empty($decision['knowledge_tool'])) {
                $decision['mcp'] = true;
                $decision['mcp_allowlist'] = array_values(array_unique(array_merge(
                    (array) ($decision['mcp_allowlist'] ?? []),
                    ['search_knowledge']
                )));
            }
        }

        if ($bootstrap['run']) {
            $this->tracer->recordStep(
                $bootstrap['run'],
                AgentEnum::STEP_TYPE_THINK,
                ['phase' => 'orchestrate_thin'],
                [
                    'orchestrate_ms'  => $orchestrateMs,
                    'mcp_discover_ms' => $mcpDiscoverMs,
                    'decision'        => [
                        'mcp'        => $decision['mcp'],
                        'sql'        => $decision['sql'],
                        'thin_react' => true,
                    ],
                ],
                '',
                '',
                $orchestrateMs + $mcpDiscoverMs
            );
        }

        return [
            'rag'            => $rag,
            'toolService'    => $toolService,
            'externalTools'  => $externalTools,
            'decision'       => $decision,
            'bootstrap'      => $bootstrap,
            'memoryPrompt'   => $memoryPrompt,
            'skillPrompt'    => $skillPrompt,
            'availableTools' => $availableTools,
        ];
    }

    /**
     * 委托 AgentExecutor 执行完整 ReAct 循环.
     *
     * @param array<string, mixed> $request 单次 Agent 执行请求
     *
     * @return array 工具循环执行结果
     */
    public function execute(array $request): array
    {
        return $this->executor->execute($request);
    }

    /**
     * 将 Run 标记为失败并记录错误步骤.
     *
     * @param AgentRun|null $run Agent 运行记录，为空时跳过
     * @param string $message 失败原因说明
     */
    public function failRun(?AgentRun $run, string $message): void
    {
        $this->tracer->failRun($run, $message);
    }

    /**
     * 访客模式下裁剪插件决策（双保险）：禁用 SQL，MCP 仅保留外部 HTTP 工具.
     *
     * @param array<string, mixed> $decision
     * @param array<int, array<string, mixed>> $externalTools
     *
     * @return array<string, mixed>
     */
    public function sanitizeGuestDecision(array $decision, array $externalTools): array
    {
        $hasExternalHttp = false;
        foreach ($externalTools as $tool) {
            if (empty($tool['config']['local'])) {
                $hasExternalHttp = true;
                break;
            }
        }

        $decision['mcp'] = $hasExternalHttp && (bool) ($decision['mcp'] ?? false);
        $decision['sql'] = false;

        return $decision;
    }

    /**
     * 校验用户在指定应用下是否仍可发起 Agent 调用，超限则抛业务异常.
     *
     * 配额按「应用 + 用户 + 日」隔离，不同应用互不影响.
     *
     * @param int $appId 应用 ID
     * @param int $uid 用户 ID
     * @param AgentConfig $config Agent 配置（含 quota 子配置）
     *
     * @return bool 是否占用了并发槽（true 时调用方必须在 finally 中 releaseConcurrent）
     *
     * @throws \Throwable 配额超限时
     */
    public function assertAllowed(int $appId, int $uid, AgentConfig $config): bool
    {
        if (! $config->quota->enabled) {
            return false;
        }

        $quota = $config->quota;
        $date  = date('Y-m-d');

        if ($quota->dailyRunsPerUser > 0) {
            $runs = (int) Cache::get($this->quotaRunsKey($appId, $uid, $date), 0);
            if ($runs >= $quota->dailyRunsPerUser) {
                throw new \RuntimeException('今日 Agent 调用次数已达上限，请明日再试或联系管理员');
            }
        }

        if ($quota->dailyTokensPerUser > 0) {
            $tokens = (int) Cache::get($this->quotaTokensKey($appId, $uid, $date), 0);
            if ($tokens >= $quota->dailyTokensPerUser) {
                throw new \RuntimeException('今日 Agent Token 配额已用尽，请明日再试或联系管理员');
            }
        }

        $acquired = false;
        if ($quota->maxConcurrentRuns > 0) {
            if (! $this->tryAcquireQuotaConcurrent($appId, $uid, $quota->maxConcurrentRuns)) {
                throw new \RuntimeException('当前 Agent 并发请求过多，请稍后再试');
            }
            $acquired = true;
        }

        if ($quota->dailyRunsPerUser > 0) {
            $this->incrementQuotaDailyRuns($appId, $uid, $date);
        }

        return $acquired;
    }

    /**
     * 释放指定应用下该用户的并发 Run 计数（请求结束或异常时调用）.
     *
     * @param int $appId 应用 ID
     * @param int $uid 用户 ID
     * @param AgentConfig $config Agent 配置（含 quota 子配置）
     */
    public function releaseConcurrent(int $appId, int $uid, AgentConfig $config): void
    {
        if (! $config->quota->enabled || $config->quota->maxConcurrentRuns <= 0) {
            return;
        }

        $this->decrementQuotaConcurrent($appId, $uid);
    }

    /**
     * 累计本次 Run 消耗的 Token 到该应用下用户日配额.
     *
     * @param int $appId 应用 ID
     * @param int $uid 用户 ID
     * @param int $tokens 本轮消耗 Token 数
     * @param AgentConfig $config Agent 配置（含 quota 子配置）
     */
    public function recordRun(int $appId, int $uid, int $tokens, AgentConfig $config): void
    {
        if (! $config->quota->enabled) {
            return;
        }

        $quota = $config->quota;
        $date  = date('Y-m-d');
        $ttl   = AgentQuotaEnum::CACHE_TTL_SECONDS;

        if ($quota->dailyTokensPerUser > 0 && $tokens > 0) {
            $key = $this->quotaTokensKey($appId, $uid, $date);
            if (! Cache::has($key)) {
                Cache::put($key, $tokens, $ttl);
            } else {
                Cache::increment($key, $tokens);
            }
        }
    }

    private function tryAcquireQuotaConcurrent(int $appId, int $uid, int $max): bool
    {
        $key = $this->quotaConcurrentKey($appId, $uid);
        $ttl = AgentQuotaEnum::CONCURRENT_TTL_SECONDS;
        $store = Cache::getStore();

        if ($store instanceof RedisStore) {
            $prefixed = $store->getPrefix() . $key;
            $result   = $store->connection()->eval(
                "local current = tonumber(redis.call('GET', KEYS[1]) or '0')\n" .
                "if current >= tonumber(ARGV[1]) then return -1 end\n" .
                "local next = redis.call('INCR', KEYS[1])\n" .
                "redis.call('EXPIRE', KEYS[1], tonumber(ARGV[2]))\n" .
                'return next',
                1,
                $prefixed,
                (string) $max,
                (string) $ttl
            );

            return (int) $result > 0;
        }

        $current = (int) Cache::get($key, 0);
        if ($current >= $max) {
            return false;
        }

        Cache::put($key, $current + 1, $ttl);

        return true;
    }

    private function decrementQuotaConcurrent(int $appId, int $uid): void
    {
        $key   = $this->quotaConcurrentKey($appId, $uid);
        $store = Cache::getStore();

        if ($store instanceof RedisStore) {
            $prefixed = $store->getPrefix() . $key;
            $store->connection()->eval(
                "local current = tonumber(redis.call('GET', KEYS[1]) or '0')\n" .
                "if current <= 1 then\n" .
                "  redis.call('DEL', KEYS[1])\n" .
                "  return 0\n" .
                "end\n" .
                'return redis.call(\'DECR\', KEYS[1])',
                1,
                $prefixed
            );

            return;
        }

        $current = (int) Cache::get($key, 0);
        if ($current <= 1) {
            Cache::forget($key);

            return;
        }

        Cache::put($key, $current - 1, AgentQuotaEnum::CONCURRENT_TTL_SECONDS);
    }

    private function incrementQuotaDailyRuns(int $appId, int $uid, string $date): void
    {
        $key = $this->quotaRunsKey($appId, $uid, $date);
        if (! Cache::has($key)) {
            Cache::put($key, 1, AgentQuotaEnum::CACHE_TTL_SECONDS);

            return;
        }

        Cache::increment($key);
    }

    private function quotaRunsKey(int $appId, int $uid, string $date): string
    {
        return "agent_quota:runs:{$appId}:{$uid}:{$date}";
    }

    private function quotaTokensKey(int $appId, int $uid, string $date): string
    {
        return "agent_quota:tokens:{$appId}:{$uid}:{$date}";
    }

    private function quotaConcurrentKey(int $appId, int $uid): string
    {
        return "agent_quota:concurrent:{$appId}:{$uid}";
    }
}
