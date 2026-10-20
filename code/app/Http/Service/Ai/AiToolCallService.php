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

namespace App\Http\Service\Ai;

use App\Constants\Ai\AiConversationEnum;
use App\Http\Model\Ai\AiApplication;
use App\Http\Service\Ai\Agent\AgentWriteFlowDriver;
use App\Http\Service\Ai\Agent\AnswerSanitizePipeline;
use App\Http\Service\Ai\Agent\Meta\LoadToolsHandler;
use App\Http\Service\Ai\Agent\Meta\SearchKnowledgeHandler;
use App\Http\Service\Ai\Agent\SchemaSlotFiller;
use App\Http\Service\Ai\AiConversationMessageService;
use App\Http\Service\Ai\Agent\Decision\McpToolAllowlist;
use App\Mcp\ExternalMcpClient;
use App\Mcp\ToolExecutor;
use App\Mcp\ToolRegistry;
use Tuoluojiang\Open\services\ai\BaseOption;
use Illuminate\Support\Facades\Log;

/**
 * AI MCP 工具调用服务：按应用白名单注入工具、执行调用并回填结果。
 *
 * 调用链：AiConversationService 流式对话 → 本服务 → ToolExecutor/AiSqlQueryService/AiMcpService。
 */
class AiToolCallService
{
    private const TOOL_NAME_ALIASES = [
        'mysql_query'         => AiSqlQueryService::TOOL_NAME,
        'sql_query'           => AiSqlQueryService::TOOL_NAME,
        'execute_sql'         => AiSqlQueryService::TOOL_NAME,
        'run_sql'             => AiSqlQueryService::TOOL_NAME,
        'query_sql'           => AiSqlQueryService::TOOL_NAME,
        'database_query'      => AiSqlQueryService::TOOL_NAME,
        'query_business_data' => AiSqlQueryService::TOOL_NAME,
    ];

    /** 运行时元工具：按业务模块补挂 MCP 工具（不进 ToolRegistry，不对外暴露为 MCP 工具）. */
    public const LOAD_TOOLS_NAME = 'load_tools';

    /** 运行时元工具：按需检索应用绑定知识库（不预取）. */
    public const SEARCH_KNOWLEDGE_NAME = 'search_knowledge';

    /** 结构化统计行必须保留的业务指标字段，避免被误压缩为目录项. */
    private const TOOL_RESULT_METRIC_FIELDS = [
        // 排行优先：次数/天数/金额/库存等可比指标靠前
        'clock_count', 'actual_days', 'un_followed_days', 'followed',
        'price', 'amount', 'income', 'expend', 'net_amount', 'available_stock',
        'good_stock', 'defect_stock', 'occupy_stock', 'transit_stock',
        'ratio', 'percentage', 'rate', 'total', 'count', 'approved', 'pending',
        'review_income', 'review_expend', 'frame_name', 'avatar',
        'late_card', 'early_card', 'absenteeism', 'required_days',
        'late', 'leave_early', 'work_hours', 'normal_days', 'ot_price',
    ];

    /** @var array<int, string> 视为“已收窄范围”的常见入参名 */
    private const NARROW_ARG_KEYS = [
        'folderId', 'folder_id', 'moduleId', 'module_id', 'branchId', 'branch_id',
        'parentId', 'parent_id', 'keyword', 'search', 'q', 'query', 'filter',
        'limit', 'offset', 'page', 'pageSize', 'page_size', 'cursor',
        'path', 'tag', 'tags', 'selectedFolderIds', 'checkedFolder', 'id',
        'entityId', 'httpApiId', 'apiDetailId',
    ];

    private array $externalToolConfigs = [];

    /** @var array<string, ExternalMcpClient> */
    private array $externalClients = [];

    private bool $externalToolsPrepared = false;

    /** @var array<string, true> */
    private array $addedToolNames = [];

    /** 本轮是否允许执行 SQL 工具（未注入时拒绝文本解析出的 mysql_query）. */
    private bool $sqlToolAllowed = false;

    /** 应用白名单与意图白名单无交集时，外部工具仅允许上下文工具. */
    private bool $restrictExternalTools = false;

    /** @var null|array<int, string> 本轮与意图白名单求交后的工具列表 */
    private ?array $preparedConfiguredTools = null;

    /** 访客模式：禁用 SQL、内部 MCP 与写操作工具注入. */
    private bool $guestMode = false;

    /** @var null|array{runId: int, messageUuid: string, writeConfirmation: bool, approvedPendingIds?: array<int, int>} */
    private ?array $writeContext = null;

    /** 本轮 SQL 预览行数上限（来自 decision.sql_max_rows）. */
    private int $sqlMaxRows = 10;

    /** 本轮预取的 who_am_i 上下文；null 表示未预取. */
    private ?string $prefetchedIdentity = null;

    /** 本轮是否常驻精简组织工具（与 inject_org_tools 决策对齐）. */
    private bool $injectOrgTools = false;

    /** @var array<int, string> 本轮 load_tools 可补挂的本地业务模块（应用已绑定）；空表示不提供 load_tools */
    private array $loadableModules = [];

    /** @var array<int, string> 应用权限内可见的本地业务工具（AiMcpService::localToolNamesForApp）；load_tools 只能从中补挂，write_form_get 枚举按此收窄 */
    private array $loadableToolNames = [];

    /** @var array<int, array<string, mixed>> load_tools 已放行、待 flushLoadedTools 注入 option 的工具元数据 */
    private array $pendingLoadedTools = [];

    /** 本轮 load_tools 已放行的工具数（不含首轮注入），受 load_tools_total_max 约束. */
    private int $loadedToolCount = 0;

    /** 本轮是否注入 search_knowledge. */
    private bool $knowledgeToolAllowed = false;

    /** @var array<int, array<string, mixed>> search_knowledge 累积的引用，供会话落库/citation */
    private array $knowledgeReferences = [];

    /** @var array<int, array<string, mixed>> search_knowledge 累积的挂图 */
    private array $knowledgeImages = [];

    /**
     * 开启/关闭访客沙箱模式（须在 stream 结束 finally 中重置为 false）。
     *
     * @param bool $guestMode 是否启用访客沙箱
     */
    public function setGuestMode(bool $guestMode): void
    {
        $this->guestMode = $guestMode;
    }

    /**
     * 绑定 Agent 写操作确认上下文，供 execute 时拦截需确认的写工具。
     *
     * @param null|array{runId: int, messageUuid: string, writeConfirmation: bool, approvedPendingIds?: array<int, int>} $context 写操作上下文
     */
    public function bindWriteContext(?array $context): void
    {
        $this->writeContext = $context;
    }

    /**
     * 写入本轮已预取的身份上下文（仅当编排 prefetch 含 identity 时调用）.
     *
     * @param null|string $identity Markdown 片段；null 表示清除
     */
    public function setPrefetchedIdentity(?string $identity): void
    {
        $this->prefetchedIdentity = $identity;
    }

    /**
     * 按插件决策向模型选项注入 MCP/SQL 工具定义。
     *
     * @param BaseOption $option 模型请求参数
     * @param AiApplication $app 应用模型
     * @param null|array<int, array<string, mixed>> $externalTools null 表示内部自行发现，[] 表示已发现且无外部工具
     * @param null|array<string, mixed> $decision 插件启用决策（含 sql_max_rows 等）
     */
    /**
     * 取出并清空本轮 search_knowledge 累积的引用与挂图.
     *
     * @return array{references: array<int, array<string, mixed>>, images: array<int, array<string, mixed>>}
     */
    public function drainKnowledgeHits(): array
    {
        $hits = [
            'references' => $this->knowledgeReferences,
            'images'     => $this->knowledgeImages,
        ];
        $this->knowledgeReferences = [];
        $this->knowledgeImages     = [];

        return $hits;
    }

    /**
     * 窥视本轮已累积的知识库引用（不清空）.
     *
     * @return array<int, array<string, mixed>>
     */
    public function knowledgeReferences(): array
    {
        return $this->knowledgeReferences;
    }

    public function prepare(BaseOption $option, AiApplication $app, ?array $externalTools = null, ?array $decision = null): void
    {
        $this->externalToolConfigs  = [];
        $this->externalClients      = [];
        $this->externalToolsPrepared = false;
        $this->sqlToolAllowed       = false;
        $this->restrictExternalTools = false;
        $this->preparedConfiguredTools = null;
        $this->addedToolNames       = [];
        $this->loadableModules      = [];
        $this->loadableToolNames    = [];
        $this->pendingLoadedTools   = [];
        $this->loadedToolCount      = 0;
        $this->knowledgeToolAllowed = false;
        $this->knowledgeReferences  = [];
        $this->knowledgeImages      = [];
        // 续跑路径会 restoreOption 带回旧 tools；setTool 只 append，必须先清空否则翻倍推送.
        $option->tools = [];
        $decision ??= ['mcp' => $this->enabled($app), 'sql' => false];
        $this->knowledgeToolAllowed = ! empty($decision['knowledge_tool'])
            || in_array(self::SEARCH_KNOWLEDGE_NAME, (array) ($decision['mcp_allowlist'] ?? []), true);
        $this->injectOrgTools = (bool) ($decision['inject_org_tools'] ?? false);
        $this->sqlMaxRows = max(1, (int) ($decision['sql_max_rows'] ?? config('ai.plugins.sql.max_rows', 10)));
        $withMcp = (bool) ($decision['mcp'] ?? false);
        $withSql = (bool) ($decision['sql'] ?? false);
        $allowlist = McpToolAllowlist::discoveryNames(
            (array) ($decision['mcp_allowlist'] ?? [])
        );

        if ($withMcp && ($this->enabled($app) || $this->knowledgeToolAllowed)) {
            $configured = $this->configured($app);
            // 编排层 allowlist 为权威名单；应用 mcp_json.tools 仅作进一步求交，二者皆空则不挂业务工具.
            $resolvedAllowlist = $this->resolveEffectiveToolNames($allowlist, $configured);
            $metaSet = array_merge(McpToolAllowlist::IDENTITY_TOOLS, McpToolAllowlist::META_TOOLS);
            // 首轮：仅身份/元工具；续跑：allowlist 中已 load 的业务名一并注入.
            $resumeBusiness = array_values(array_diff($resolvedAllowlist, $metaSet));
            $effective      = array_values(array_unique(array_merge(
                array_values(array_intersect(
                    $resolvedAllowlist !== [] ? $resolvedAllowlist : $metaSet,
                    $metaSet
                )),
                $resumeBusiness,
                $this->knowledgeToolAllowed ? [self::SEARCH_KNOWLEDGE_NAME] : []
            )));
            $this->restrictExternalTools    = true;
            $this->preparedConfiguredTools = $effective;

            if (! $this->guestMode) {
                // 默认仅 common_tools；inject_org_tools 时常驻精简组织工具，不挂全量 context_tools.
                $this->injectContextTools($option, $this->injectOrgTools);

                $mcpService = app(AiMcpService::class);
                $modules    = $mcpService->modulesForApp($app);
                $this->loadableToolNames = $mcpService->localToolNamesForApp($app);
                $injectNames = $resumeBusiness;
                if ($injectNames !== []) {
                    $localTools = $modules !== []
                        ? $mcpService->localBusinessToolsMetaForModules($modules, $injectNames)
                        : ToolRegistry::getToolsMeta(null, false, $injectNames);
                    foreach ($localTools as $tool) {
                        $name = (string) ($tool['name'] ?? '');
                        if (! $this->isConfiguredToolAllowed($name, $injectNames)) {
                            continue;
                        }
                        $this->addTool($option, $tool);
                    }
                }

                // 渐进披露主路径：有绑定模块即提供 load_tools.
                if ($modules !== [] && (bool) config('ai.plugins.tools.load_tools_enabled', true)) {
                    $this->loadableModules = $modules;
                    $this->addTool($option, $this->loadToolsDefinition($modules));
                }
            }

            if ($this->knowledgeToolAllowed) {
                $this->addTool($option, $this->searchKnowledgeDefinition());
            }

            // 禁止 toolsForApp($app) 无过滤兜底（空 allowlist 曾被翻成全量 listTools）.
            $discoverNames = array_values(array_unique(array_merge($metaSet, $resumeBusiness)));
            $this->externalToolConfigs = $externalTools ?? app(AiMcpService::class)->toolsForApp($app, $discoverNames);
            $this->externalToolsPrepared = true;
            foreach ($this->externalToolConfigs as $tool) {
                if ($this->guestMode && ! empty($tool['config']['local'])) {
                    continue;
                }
                $name = (string) ($tool['name'] ?? '');
                if (empty($tool['config']['local']) && $resumeBusiness === [] && ! $this->guestMode) {
                    // 员工首轮不挂外部业务 schema；访客仍可挂 HTTP 工具.
                    continue;
                }
                if (! $this->guestMode && ! in_array($name, $resumeBusiness, true)) {
                    continue;
                }
                if ($discoverNames === [] || ! $this->isConfiguredToolAllowed($name, $discoverNames)) {
                    continue;
                }
                $this->addTool($option, $tool);
            }
        } elseif (! $withMcp) {
            // 本轮未启用 MCP 时，阻止幻觉工具名触发兜底发现和执行.
            $this->preparedConfiguredTools = [];
            $this->externalToolsPrepared = true;
            $this->injectOrgTools        = false;
        } else {
            // 调用方误传 mcp=true 但应用未启用 MCP 时，也不允许兜底发现外部工具.
            $this->preparedConfiguredTools = [];
            $this->externalToolsPrepared = true;
            $this->injectOrgTools        = false;
        }

        if ($withSql && ! $this->guestMode && app(AiSqlQueryService::class)->isEnabled($app)) {
            $this->sqlToolAllowed = true;
            $this->addTool($option, app(AiSqlQueryService::class)->definition());
        }

        $maxTools = max(0, (int) config('ai.plugins.tools.max_count', 24));
        if ($maxTools > 0 && is_array($option->tools) && count($option->tools) > $maxTools) {
            $option->tools = McpToolAllowlist::capToolDefinitions($option->tools, $maxTools);
        }
    }

    /**
     * 合并编排白名单与应用配置 tools：编排优先；配置非空时求交，交空则用编排名单.
     *
     * @param array<int, string> $allowlist
     * @param array<int, string> $configured
     *
     * @return array<int, string>
     */
    private function resolveEffectiveToolNames(array $allowlist, array $configured): array
    {
        if ($allowlist !== [] && $configured !== []) {
            $hit = array_values(array_intersect($configured, $allowlist));

            return $hit !== [] ? $hit : $allowlist;
        }
        if ($allowlist !== []) {
            return $allowlist;
        }

        return $configured;
    }

    /**
     * 生成 MCP/SQL 运行时上下文（身份预取、模块绑定、本轮 SQL 开关）；编排优先级见 AgentOrchestrationPrompt.
     *
     * @param AiApplication $app 应用模型
     * @param string $question 用户问题
     * @param bool $hasRagReferences 是否已有知识库命中
     * @param bool $sqlEnabledInRound 本轮是否启用 SQL
     * @param bool $mcpEnabledInRound 本轮是否启用 MCP
     *
     * @return string 系统提示片段，无能力时为空串
     */
    public function context(AiApplication $app, string $question, bool $hasRagReferences = false, bool $sqlEnabledInRound = false, bool $mcpEnabledInRound = false): string
    {
        if ((! $this->enabled($app) || ! $mcpEnabledInRound) && ! $sqlEnabledInRound) {
            return '';
        }

        $lines = [];
        if ($this->enabled($app) && $mcpEnabledInRound) {
            $modules = app(AiMcpService::class)->modulesForApp($app);
            if ($modules !== []) {
                $lines[] = '已绑定 MCP 模块：' . implode('、', $modules)
                    . '；优先调用对应模块工具；跨模块选人用已开放的 personnel_search / org_department_users；'
                    . '组织树与数据范围优先 resources/read（oa://org/tree、oa://user/data-scope）。';
            }
            $lines[] = '可通过 MCP resources/read 读取 oa://user/me、oa://user/data-scope、oa://org/tree、oa://modules/permissions，减少重复 who_am_i / org_tree 调用。';
            if ($this->prefetchedIdentity !== null && $this->prefetchedIdentity !== '') {
                $lines[] = $this->prefetchedIdentity;
            }
        }
        if ($sqlEnabledInRound) {
            $lines[] = '本轮已启用数据库只读查询：通过 query_business_data 提交 SELECT；禁止编造 mysql_query 等名称。';
        } else {
            $lines[] = '本轮未启用数据库插件，禁止调用 query_business_data / mysql_query。';
        }

        return $lines === [] ? '' : implode("\n", $lines);
    }

    /**
     * 预取当前登录用户身份，供模型在业务取数前锚定「我是谁」。
     *
     * @return string 身份上下文 Markdown 片段
     */
    public function identityContext(): string
    {
        try {
            $result = app(ToolExecutor::class)->execute('who_am_i', []);
            if (! empty($result['error'])) {
                return '# 当前用户身份' . "\n"
                    . '预取 who_am_i 失败，取数前必须先调用 who_am_i 确认身份，再调用业务 MCP 工具。';
            }

            // 只注入摘要字段，避免完整 who_am_i JSON 占满 system 预算.
            $summary = $this->summarizeIdentityResult($result);

            return '# 当前用户身份（已预取 who_am_i）' . "\n"
                . (json_encode($summary, JSON_UNESCAPED_UNICODE) ?: '{}') . "\n"
                . '后续业务取数请基于上述身份及其数据权限；仅在身份信息不足时再调用 who_am_i。';
        } catch (\Throwable $e) {
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->warning('AI who_am_i prefetch failed', [
                'error' => $e->getMessage(),
            ]);

            return '# 当前用户身份' . "\n"
                . '预取 who_am_i 失败，取数前必须先调用 who_am_i 确认身份，再调用业务 MCP 工具。';
        }
    }

    /**
     * 压缩 who_am_i 预取结果，仅保留编排常用字段.
     *
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function summarizeIdentityResult(array $result): array
    {
        // 对齐 WhoAmITool 真实字段；frames 只保留 id/name/主部门标记，去掉整树膨胀.
        $frames = [];
        foreach ((array) ($result['frames'] ?? []) as $frame) {
            if (! is_array($frame)) {
                continue;
            }
            $frames[] = [
                'id'         => (int) ($frame['id'] ?? 0),
                'name'       => (string) ($frame['name'] ?? ''),
                'is_main'    => (bool) ($frame['is_main'] ?? false),
                'is_manager' => (bool) ($frame['is_manager'] ?? false),
            ];
        }

        $out = array_filter([
            'id'            => $result['id'] ?? null,
            'uid'           => $result['uid'] ?? null,
            'name'          => $result['name'] ?? null,
            'job_id'        => $result['job_id'] ?? null,
            'is_admin'      => $result['is_admin'] ?? null,
            'main_frame_id' => $result['main_frame_id'] ?? null,
            'frame_ids'     => $result['frame_ids'] ?? null,
            'frames'        => $frames !== [] ? $frames : null,
        ], static fn ($v) => $v !== null && $v !== '' && $v !== []);

        // 不注入 phone/avatar，减少 PII 与无关字节.
        return $out !== [] ? $out : ['_summary' => mb_substr(json_encode($result, JSON_UNESCAPED_UNICODE) ?: '{}', 0, 400)];
    }

    /**
     * 批量执行模型返回的工具调用，外部 HTTP 工具可并行。
     *
     * @param AiApplication $app 应用模型
     * @param array<int, array<string, mixed>> $toolCalls 工具调用列表
     * @param null|callable(array<string, mixed>): void $onProgress 进度回调
     *
     * @return array<int, array<string, mixed>> 工具执行结果列表
     */
    public function execute(AiApplication $app, array $toolCalls, ?callable $onProgress = null): array
    {
        $toolCalls = array_values(array_filter(
            $toolCalls,
            static fn (array $call): bool => (string) ($call['function']['name'] ?? '') !== ''
        ));
        if ($toolCalls === []) {
            return [];
        }

        $total   = count($toolCalls);
        $started = microtime(true);

        $parallelExternal = $total > 1
            && $this->canParallelTools()
            && $this->allExternalHttp($app, $toolCalls);
        $reuseExternalSession = ! $parallelExternal;

        $tasks = [];
        foreach ($toolCalls as $index => $toolCall) {
            $tasks[(string) $index] = function () use ($app, $toolCall, $onProgress, $index, $total, $started, $reuseExternalSession) {
                $name = (string) ($toolCall['function']['name'] ?? '');
                if ($onProgress) {
                    $onProgress([
                        'stage'   => 'tool_progress',
                        'name'    => $name,
                        'index'   => $index + 1,
                        'total'   => $total,
                        'elapsed' => round(microtime(true) - $started, 2),
                    ]);
                }

                return $this->executeOne($app, $toolCall, $reuseExternalSession);
            };
        }

        // 仅外部 HTTP MCP 适合并行；本地/SQL 走顺序，避免共享 PDO 协程竞态.
        if ($parallelExternal) {
            $ordered = $this->runParallelTasks($tasks);

            return array_values($ordered);
        }

        $results = [];
        foreach ($tasks as $task) {
            $results[] = $task();
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $toolCall
     *
     * @return array<string, mixed>
     */
    private function executeOne(AiApplication $app, array $toolCall, bool $reuseExternalSession = true): array
    {
        $name      = $this->normalizeToolName((string) ($toolCall['function']['name'] ?? ''));
        $arguments = $toolCall['function']['arguments'] ?? [];
        $args      = is_array($arguments) ? $arguments : json_decode((string) $arguments, true);
        $args      = is_array($args) ? $args : [];
        // 模型偶发把数组参数直接当作顶层 arguments（list），统一抬到 items 便于写工具消费.
        if ($args !== [] && array_keys($args) === range(0, count($args) - 1)) {
            $args = ['items' => $args];
        }
        // 兼容 parameters.sql / sql 顶层字段.
        if (! isset($args['sql']) && isset($args['query']) && is_string($args['query'])) {
            $args['sql'] = $args['query'];
        }
        $executor   = app(ToolExecutor::class);
        $configured = $this->preparedConfiguredTools ?? $this->configured($app);
        $guard      = app(AgentWriteFlowDriver::class);

        if ($name === self::LOAD_TOOLS_NAME) {
            return $this->executeLoadTools($toolCall, $args);
        }
        if ($name === self::SEARCH_KNOWLEDGE_NAME) {
            return $this->executeSearchKnowledge($app, $toolCall, $args);
        }

        try {
            if ($this->guestMode) {
                if ($name === AiSqlQueryService::TOOL_NAME || $guard->isWriteTool($name)) {
                    return $this->guestDeniedResult($toolCall, $name, '外部访客会话不支持该操作');
                }
            }

            if ($this->writeContext && $guard->requiresConfirmation($name, $this->writeContext)) {
                // 写确认前抽槽：合并进 args，标量缺参交给可编辑确认表单，不再逐步追问.
                $filled = $this->prefillWriteArgsFromSlots($app, $guard, $name, $args);
                $args   = $filled['args'];
                $toolCall['function']['arguments'] = $args;
                $appliedSlots = $filled['applied'];

                $missing  = $guard->missingWriteFields($name, $args);
                $blocking = $guard->blockingMissingWriteFields($missing);
                if ($blocking !== []) {
                    return [
                        'id'          => (string) ($toolCall['id'] ?? ''),
                        'name'        => $name,
                        'result'      => ['error' => true, 'needs_input' => true, 'fields' => $blocking],
                        'content'     => json_encode(['needs_input' => true, 'fields' => $blocking], JSON_UNESCAPED_UNICODE) ?: '',
                        'needs_input' => true,
                        'fields'      => $blocking,
                    ];
                }
                // 写确认前解析业务对象（如线索名称多命中）：先弹选择卡，避免确认后才失败.
                $prepared = $this->prepareWriteToolArguments($name, $args);
                if ($prepared !== null && ! empty($prepared['error'])) {
                    $prepared = $this->normalizePrepareWriteError($prepared, $args);

                    return [
                        'id'          => (string) ($toolCall['id'] ?? ''),
                        'name'        => $name,
                        'result'      => $prepared,
                        'content'     => json_encode($prepared, JSON_UNESCAPED_UNICODE) ?: (string) ($prepared['message'] ?? '写入前校验失败'),
                        'needs_input' => ! empty($prepared['needs_input']),
                    ];
                }
                if ($prepared !== null && isset($prepared['arguments']) && is_array($prepared['arguments'])) {
                    $args = $prepared['arguments'];
                    $toolCall['function']['arguments'] = $args;
                }
                $pending = $guard->storePending($this->writeContext, $toolCall);
                if (! $pending) {
                    throw new \RuntimeException('写操作确认不可用，数据未写入，请检查 Agent Run 和待确认表');
                }
                $message = '写操作「' . $name . '」需要用户确认，已暂停执行。';

                return [
                    'id'                   => (string) ($toolCall['id'] ?? ''),
                    'name'                 => $name,
                    'result'               => [
                        'error'                => true,
                        'pending_confirmation' => true,
                        'pending_id'           => (int) ($pending->id ?? 0),
                        'message'              => $message,
                        'applied_slots'        => $appliedSlots,
                    ],
                    'content'              => $message,
                    'pending_confirmation' => true,
                    'pending_id'           => (int) ($pending->id ?? 0),
                    'applied_slots'        => $appliedSlots,
                ];
            }

            $result = null;
            if ($name === AiSqlQueryService::TOOL_NAME) {
                if (! $this->sqlToolAllowed) {
                    return [
                        'id'      => (string) ($toolCall['id'] ?? ''),
                        'name'    => $name,
                        'result'  => [
                            'error'      => true,
                            'error_code' => 'TOOL_UNAVAILABLE',
                            'message'    => '本轮未启用数据库插件，请改用知识库或 MCP 工具',
                        ],
                        'content' => '本轮未启用数据库插件，请改用知识库或 MCP 工具',
                    ];
                }
                $result = app(AiSqlQueryService::class)->run(
                    $app,
                    (string) ($args['sql'] ?? ''),
                    $this->sqlMaxRows
                );
            } elseif (ToolRegistry::getTool($name) && $this->isLocalToolAllowed($name, $configured)) {
                if ($this->guestMode) {
                    return $this->guestDeniedResult($toolCall, $name, '外部访客会话不支持内部 MCP 工具');
                }
                $result = $executor->execute($name, $args);
            } else {
                $external = $this->externalToolsForExecution($app)[$name] ?? null;
                if ($external && $this->isDiscoveredToolAllowed($name, $configured)) {
                    if ($this->guestMode && ! empty($external['config']['local'])) {
                        return $this->guestDeniedResult($toolCall, $name, '外部访客会话不支持内部 MCP 工具');
                    }
                    if (! empty($external['config']['local'])) {
                        $result = $executor->executeWithResult($name, $args);
                    } else {
                        $config = (array) ($external['config'] ?? []);
                        $client = $reuseExternalSession
                            ? $this->externalClient($config)
                            : new ExternalMcpClient(
                                (string) ($config['url'] ?? ''),
                                (array) ($config['headers'] ?? []),
                                max(1, (int) ($config['timeout'] ?? 30))
                            );
                        $result = $client->callTool($name, $args);
                    }
                }
            }
            if ($result === null) {
                throw new \RuntimeException('Tool is not enabled for this AI application');
            }
            $item = [
                'id'      => (string) ($toolCall['id'] ?? ''),
                'name'    => $name,
                'result'  => $result,
                'content' => $this->content($result, $name, $args),
            ];
            if ($name === AiSqlQueryService::TOOL_NAME) {
                $item['type'] = 'sql';
                $item['sql']  = (string) ($result['sql'] ?? '');
                $item['rows'] = (array) ($result['rows'] ?? []);
            }

            return $item;
        } catch (\Throwable $e) {
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->error('AI MCP tool execution error', [
                'tool'  => $name,
                'error' => $e->getMessage(),
            ]);

            return [
                'id'      => (string) ($toolCall['id'] ?? ''),
                'name'    => $name,
                'result'  => [
                    'error'      => true,
                    'error_code' => $e->getMessage() === 'Tool is not enabled for this AI application'
                        ? 'TOOL_UNAVAILABLE'
                        : 'TOOL_EXECUTION_FAILED',
                    'message'    => $e->getMessage(),
                ],
                'content' => $e->getMessage(),
            ];
        }
    }

    /**
     * 将 load_tools 本轮放行的工具定义注入模型选项（须在工具结果回填、focus 裁剪之后调用）.
     *
     * @param BaseOption $option 模型请求参数
     *
     * @return array<int, string> 本次实际注入的工具名；供调用方并入 decision.mcp_allowlist，确认续跑时 prepare 可重建
     */
    public function flushLoadedTools(BaseOption $option): array
    {
        $names = [];
        foreach ($this->pendingLoadedTools as $tool) {
            $name = (string) ($tool['name'] ?? '');
            if ($name === '' || isset($this->addedToolNames[$name])) {
                continue;
            }
            $this->addTool($option, $tool);
            $names[] = $name;
        }
        $this->pendingLoadedTools = [];

        return $names;
    }

    /**
     * search_knowledge 的 function schema.
     *
     * @return array<string, mixed>
     */
    private function searchKnowledgeDefinition(): array
    {
        return SearchKnowledgeHandler::definition();
    }

    /**
     * 执行知识库检索并累积 references 供 citation / 落库.
     *
     * @param array<string, mixed> $toolCall
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    private function executeSearchKnowledge(AiApplication $app, array $toolCall, array $args): array
    {
        $out = SearchKnowledgeHandler::execute($app, $toolCall, $args, [
            'knowledgeToolAllowed' => $this->knowledgeToolAllowed,
            'knowledgeReferences'  => $this->knowledgeReferences,
            'knowledgeImages'      => $this->knowledgeImages,
        ]);
        $state = $out['state'];
        $this->knowledgeReferences = (array) ($state['knowledgeReferences'] ?? $this->knowledgeReferences);
        $this->knowledgeImages     = (array) ($state['knowledgeImages'] ?? $this->knowledgeImages);

        return $out['envelope'];
    }


    /**
     * load_tools 的 function schema：模块枚举限定为应用已绑定的本地模块.
     *
     * @param array<int, string> $modules 应用已绑定模块
     *
     * @return array<string, mixed>
     */
    private function loadToolsDefinition(array $modules): array
    {
        return LoadToolsHandler::definition($modules);
    }

    /**
     * 执行 load_tools：仅挂 moduleCore（无 keyword 不整模块倾倒）.
     *
     * @param array<string, mixed> $toolCall 模型发起的调用
     * @param array<string, mixed> $args 已解析参数
     *
     * @return array<string, mixed> 工具执行结果
     */
    private function executeLoadTools(array $toolCall, array $args): array
    {
        $out = LoadToolsHandler::execute($toolCall, $args, [
            'guestMode'                => $this->guestMode,
            'loadableModules'          => $this->loadableModules,
            'loadableToolNames'        => $this->loadableToolNames,
            'addedToolNames'           => $this->addedToolNames,
            'pendingLoadedTools'       => $this->pendingLoadedTools,
            'loadedToolCount'          => $this->loadedToolCount,
            'preparedConfiguredTools'  => $this->preparedConfiguredTools,
        ]);
        $state = $out['state'];
        $this->pendingLoadedTools = (array) ($state['pendingLoadedTools'] ?? $this->pendingLoadedTools);
        $this->loadedToolCount = (int) ($state['loadedToolCount'] ?? $this->loadedToolCount);
        $this->preparedConfiguredTools = $state['preparedConfiguredTools'] ?? $this->preparedConfiguredTools;

        return $out['envelope'];
    }


    /**
     * @param array<int, array<string, mixed>> $toolCalls
     */
    private function allExternalHttp(AiApplication $app, array $toolCalls): bool
    {
        $configured = $this->preparedConfiguredTools ?? $this->configured($app);
        $externals  = $this->externalToolsForExecution($app);
        foreach ($toolCalls as $toolCall) {
            $name = (string) ($toolCall['function']['name'] ?? '');
            if ($name === '' || $this->normalizeToolName($name) === AiSqlQueryService::TOOL_NAME) {
                return false;
            }
            if (ToolRegistry::getTool($name) && $this->isLocalToolAllowed($name, $configured)) {
                return false;
            }
            $external = $externals[$name] ?? null;
            if (! $external || ! empty($external['config']['local'])) {
                return false;
            }
        }

        return true;
    }

    /** 懒加载或复用已发现的外部工具配置映射。 */
    private function externalToolsForExecution(AiApplication $app): array
    {
        if ($this->externalToolsPrepared) {
            return $this->externalToolConfigs;
        }

        $this->externalToolConfigs  = app(AiMcpService::class)->toolsForApp(
            $app,
            is_array($this->preparedConfiguredTools) ? $this->preparedConfiguredTools : []
        );
        $this->externalToolsPrepared = true;

        return $this->externalToolConfigs;
    }

    /**
     * 复用本轮请求内的 MCP 会话，避免每次工具调用重复 initialize.
     *
     * @param array<string, mixed> $config
     */
    private function externalClient(array $config): ExternalMcpClient
    {
        $key = sha1((string) json_encode([
            'url'     => (string) ($config['url'] ?? ''),
            'headers' => (array) ($config['headers'] ?? []),
            'timeout' => (int) ($config['timeout'] ?? 30),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (! isset($this->externalClients[$key])) {
            $this->externalClients[$key] = new ExternalMcpClient(
                (string) ($config['url'] ?? ''),
                (array) ($config['headers'] ?? []),
                max(1, (int) ($config['timeout'] ?? 30))
            );
        }

        return $this->externalClients[$key];
    }

    /**
     * 访客沙箱拒绝执行时的统一工具结果结构.
     *
     * @param array<string, mixed> $toolCall
     *
     * @return array<string, mixed>
     */
    private function guestDeniedResult(array $toolCall, string $name, string $message): array
    {
        return [
            'id'      => (string) ($toolCall['id'] ?? ''),
            'name'    => $name,
            'result'  => ['error' => true, 'error_code' => 'NO_PERMISSION', 'message' => $message],
            'content' => $message,
        ];
    }

    /** 读取应用 mcp_json/json 中配置的工具白名单。 */
    private function configured(AiApplication $app): array
    {
        $tools = [];
        $mcpJson = (array) ($app->mcp_json ?? []);
        if (isset($mcpJson['tools']) && is_array($mcpJson['tools'])) {
            $tools = $mcpJson['tools'];
        }
        $advanced = (array) ($app->json ?? []);
        if (isset($advanced['tools']) && is_array($advanced['tools'])) {
            $tools = array_merge($tools, $advanced['tools']);
        }

        return array_values(array_filter(array_map(static function ($tool): string {
            if (is_array($tool)) {
                $tool = $tool['name'] ?? '';
            }

            return (string) $tool;
        }, $tools)));
    }

    /**
     * 应用配置了 tools 白名单时，仅自动放行本轮允许的上下文工具（默认 common，不含 org）.
     */
    private function isConfiguredToolAllowed(string $name, array $configured): bool
    {
        if (in_array($name, $this->passthroughContextToolNames(), true)) {
            return true;
        }

        return in_array($name, $configured, true);
    }

    /**
     * 本地 ToolRegistry 工具：白名单命中，或本轮允许的上下文工具.
     */
    private function isLocalToolAllowed(string $name, array $configured): bool
    {
        if (in_array($name, $this->passthroughContextToolNames(), true)) {
            return true;
        }

        return $configured !== [] && in_array($name, $configured, true);
    }

    /**
     * 发现自 MCP 服务的工具：无白名单则全放行；有白名单时仅 common（及按需 org）仍放行.
     */
    private function isDiscoveredToolAllowed(string $name, array $configured): bool
    {
        if ($configured === []) {
            return ! $this->restrictExternalTools;
        }

        return $this->isConfiguredToolAllowed($name, $configured);
    }

    /**
     * 过滤业务/发现工具时允许「附带通过」的上下文工具名（仅常驻精简集）.
     *
     * @return array<int, string>
     */
    private function passthroughContextToolNames(): array
    {
        return self::resolveAlwaysOnContextNames(
            $this->injectOrgTools,
            ToolRegistry::getCommonToolNames(),
            $this->orgToolsAlwaysOn()
        );
    }

    /**
     * 注入上下文工具：默认仅 common_tools；需要选人/组织时再附带精简组织工具.
     */
    private function injectContextTools(BaseOption $option, bool $includeOrgTools = false): void
    {
        $allowed = array_flip(self::resolveAlwaysOnContextNames(
            $includeOrgTools,
            ToolRegistry::getCommonToolNames(),
            $this->orgToolsAlwaysOn()
        ));

        foreach (ToolRegistry::getContextToolsMeta() as $tool) {
            $name = (string) ($tool['name'] ?? '');
            if ($name === '' || ! isset($allowed[$name])) {
                continue;
            }
            $this->addTool($option, $tool);
        }
    }

    /**
     * AI 会话常驻上下文工具名（不含全量 org/personnel context_tools）.
     *
     * @param array<int, string> $common
     * @param array<int, string> $orgAlwaysOn
     *
     * @return array<int, string>
     */
    public static function resolveAlwaysOnContextNames(bool $includeOrgTools, array $common, array $orgAlwaysOn): array
    {
        $names = array_values(array_filter(array_map('strval', $common)));
        if ($includeOrgTools) {
            $names = array_merge($names, array_values(array_filter(array_map('strval', $orgAlwaysOn))));
        }

        return array_values(array_unique($names));
    }

    /**
     * @return array<int, string>
     */
    private function orgToolsAlwaysOn(): array
    {
        $default = ['personnel_search', 'org_department_users'];
        if (! function_exists('config')) {
            return $default;
        }
        try {
            $value = config('ai.context.org_tools_always_on', $default);
            if (! is_array($value)) {
                return $default;
            }
            $names = array_values(array_filter(array_map('strval', $value)));

            return $names === [] ? $default : $names;
        } catch (\Throwable) {
            return $default;
        }
    }

    /** 向模型选项去重追加单个工具定义。 */
    private function addTool(BaseOption $option, array $tool): void
    {
        $name = (string) ($tool['name'] ?? '');
        if ($name === '' || isset($this->addedToolNames[$name])) {
            return;
        }
        $this->addedToolNames[$name] = true;
        $parameters = (array) ($tool['inputSchema'] ?? ['type' => 'object', 'properties' => []]);
        // write_form_get 的 tool 枚举是全系统写工具；只留应用有权限的，避免模型据此去找未开通的工具.
        if ($name === 'write_form_get' && $this->loadableToolNames !== []
            && isset($parameters['properties']['tool']['enum']) && is_array($parameters['properties']['tool']['enum'])
        ) {
            $parameters['properties']['tool']['enum'] = array_values(array_intersect(
                $parameters['properties']['tool']['enum'],
                $this->loadableToolNames
            ));
            if ($parameters['properties']['tool']['enum'] === []) {
                return;
            }
        }
        $option->setTool([
            'name'        => $name,
            'description' => (string) ($tool['description'] ?? ''),
            'parameters'  => $parameters,
        ]);
    }

    /**
     * 将工具执行结果序列化为模型可读的 content 字符串（结构化压缩 + 截断）.
     *
     * @param array<string, mixed> $result 工具原始结果
     * @param string $toolName 工具名
     * @param array<string, mixed> $arguments 本次调用入参
     */
    private function content(array $result, string $toolName = '', array $arguments = []): string
    {
        return $this->normalizeToolResult($result, null, null, $toolName, $arguments);
    }

    /**
     * 将任意工具结果压缩为适合回注 LLM 的字符串（原 ResultNormalizer）.
     *
     * @param array<string, mixed> $result 工具原始结果
     * @param null|int $maxChars 最大字符数
     * @param null|int $maxRows 明细列表最大行数
     * @param string $toolName 工具名（用于续查提示，可空）
     * @param array<string, mixed> $arguments 本次调用入参（用于判断是否已收窄）
     */
    public function normalizeToolResult(
        array $result,
        ?int $maxChars = null,
        ?int $maxRows = null,
        string $toolName = '',
        array $arguments = []
    ): string {
        $maxChars = $maxChars ?? (int) config('ai.plugins.tool_result.max_chars', 2500);
        $maxRows  = $maxRows ?? (int) config('ai.plugins.tool_result.max_rows', 20);
        $compacted = false;
        $result = $this->coerceToolResultArrays($result);

        if (isset($result['rows']) && is_array($result['rows'])) {
            $total = count($result['rows']);
            if ($total > $maxRows) {
                $result['rows']           = array_slice($result['rows'], 0, $maxRows);
                $result['rows_truncated'] = true;
                $result['rows_total']     = $total;
                $compacted                = true;
            }
        }

        if (isset($result['content']) && is_array($result['content'])) {
            $textParts = [];
            foreach ($result['content'] as $item) {
                if (is_array($item) && isset($item['text'])) {
                    $textParts[] = (string) $item['text'];
                }
            }
            if ($textParts !== []) {
                $text = implode("\n", $textParts);
                $compactedText = $this->compactToolResultTextPayload($text, $maxRows, $compacted);
                $decoded       = json_decode($compactedText, true);
                if (is_array($decoded)) {
                    $decoded = $this->withAccuracySummary($decoded);
                    $compactedText = json_encode($decoded, JSON_UNESCAPED_UNICODE) ?: $compactedText;
                }

                return $this->finalizeToolResultText(
                    $compactedText,
                    $maxChars,
                    $compacted,
                    $toolName,
                    $arguments
                );
            }
        }

        // 先基于完整列表生成 summary（含 ranks），再 compact，避免 max_rows 切片后排行失真.
        $result = $this->withAccuracySummary($result);
        $result = $this->compactToolResultObject($result, $maxRows, $compacted);
        // compact 可能改写 list，但已有 summary 时 withAccuracySummary 会原样返回.
        $result = $this->withAccuracySummary($result);

        return $this->finalizeToolResultText(
            json_encode($result, JSON_UNESCAPED_UNICODE) ?: '{}',
            $maxChars,
            $compacted,
            $toolName,
            $arguments
        );
    }

    /**
     * 将 Collection / Arrayable / Model 转为纯数组，便于后续发现 list 与生成 ranks.
     *
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function coerceToolResultArrays(array $result): array
    {
        foreach ($result as $key => $value) {
            $result[$key] = $this->coerceToolResultValue($value);
        }

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function coerceToolResultValue($value)
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->map(fn ($item) => $this->coerceToolResultValue($item))->values()->all();
        }
        if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            return $this->coerceToolResultValue($value->toArray());
        }
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $this->coerceToolResultValue($value->toArray());
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $item) {
                $out[$k] = $this->coerceToolResultValue($item);
            }

            return $out;
        }

        return $value;
    }

    /**
     * 提取总数/口径/截断标记、短样本与通用数值排行，供模型在明细被截断时仍对齐事实.
     *
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function withAccuracySummary(array $result): array
    {
        if (isset($result['summary']) && is_array($result['summary'])) {
            return $result;
        }

        $summary = $this->buildAccuracySummary($result);
        if ($summary === []) {
            return $result;
        }

        return ['summary' => $summary] + $result;
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function buildAccuracySummary(array $result): array
    {
        $summary = [];

        foreach (['total', 'count', 'total_count', 'rows_total'] as $key) {
            if (! array_key_exists($key, $result)) {
                continue;
            }
            $value = $result[$key];
            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                $summary[$key] = is_string($value) ? 0 + $value : $value;
            }
        }

        foreach (['stats', 'statistics', 'paging', 'scope'] as $key) {
            if (! isset($result[$key]) || ! is_array($result[$key])) {
                continue;
            }
            $slim = [];
            foreach ($result[$key] as $subKey => $subValue) {
                if (is_scalar($subValue) || $subValue === null) {
                    $slim[$subKey] = $subValue;
                }
            }
            if ($slim !== []) {
                $summary[$key] = $slim;
            }
        }

        if (! empty($result['rows_truncated'])) {
            $summary['rows_truncated'] = true;
            if (isset($result['rows_total'])) {
                $summary['rows_total'] = $result['rows_total'];
            }
        }

        $truncatedLists = [];
        foreach ($result as $key => $value) {
            if (! is_string($key) || ! str_ends_with($key, '_truncated') || ! $value) {
                continue;
            }
            $base = substr($key, 0, -strlen('_truncated'));
            $truncatedLists[$base] = $result[$base . '_total'] ?? true;
        }
        if ($truncatedLists !== []) {
            $summary['truncated_lists'] = $truncatedLists;
        }

        $discovered = $this->discoverToolResultList($result);
        if ($discovered !== null) {
            [$listKey, $list] = $discovered;
            $listCount                 = count($list);
            $summary['sample_key']     = $listKey;
            $summary['sample_count']   = $listCount;
            // 小列表（≤20，对齐 tool_result.max_rows 默认）全量进 sample，避免「显示全部」只剩前 5 条.
            $sampleLimit               = $listCount <= 20 ? $listCount : min(5, $listCount);
            $summary['sample']         = array_map(
                fn ($item) => $this->slimAccuracySampleItem($item),
                array_slice($list, 0, $sampleLimit)
            );
            $summary['sample_complete'] = $sampleLimit >= $listCount;
            $ranks = $this->buildListRankSummary($list);
            if ($ranks !== []) {
                $summary['ranks'] = $ranks;
                $summary['rank_hint'] = '问「谁最多/最高/最长」：直接依据 summary.ranks 的 metric 与 top[].label 作答；需要详情时用列表中的 id 继续调工具，禁止空转续查。';
            }
        }

        return $summary;
    }

    /**
     * 发现工具结果中的主列表：具名键 list/rows…，或顶层裸数组（客户/线索等）.
     *
     * @param array<string, mixed> $result
     *
     * @return null|array{0: string, 1: array<int, mixed>}
     */
    private function discoverToolResultList(array $result): ?array
    {
        foreach (['rows', 'list', 'items', 'data', 'users', 'employees', 'records'] as $listKey) {
            if (! isset($result[$listKey]) || ! is_array($result[$listKey]) || ! $this->isToolResultList($result[$listKey])) {
                continue;
            }
            if ($result[$listKey] === []) {
                continue;
            }

            return [$listKey, $result[$listKey]];
        }

        $bare = [];
        foreach ($result as $key => $value) {
            if (! is_int($key) && ! (is_string($key) && ctype_digit($key))) {
                continue;
            }
            if (! is_array($value)) {
                continue;
            }
            $bare[(int) $key] = $value;
        }
        if ($bare === []) {
            return null;
        }
        ksort($bare);
        if (array_keys($bare) !== range(0, count($bare) - 1)) {
            return null;
        }
        if (! is_array($bare[0] ?? null)) {
            return null;
        }

        return ['_items', array_values($bare)];
    }

    /**
     * 从完整列表生成通用数值排行：每指标 Top5，最多 3 个指标（按覆盖率×离散度）.
     *
     * @param array<int, mixed> $list
     *
     * @return array<int, array{metric: string, top: array<int, array{label: string, value: int|float}>}>
     */
    private function buildListRankSummary(array $list): array
    {
        $rows = [];
        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = $this->extractToolResultItemLabel($item);
            if ($label === '') {
                continue;
            }
            $metrics = $this->extractToolResultItemMetrics($item);
            if ($metrics === []) {
                continue;
            }
            $rows[] = ['label' => $label, 'metrics' => $metrics];
        }
        if ($rows === []) {
            return [];
        }

        $metricStats = [];
        foreach ($rows as $row) {
            foreach ($row['metrics'] as $metric => $value) {
                if (! isset($metricStats[$metric])) {
                    $metricStats[$metric] = ['values' => [], 'coverage' => 0];
                }
                $metricStats[$metric]['values'][] = $value;
                ++$metricStats[$metric]['coverage'];
            }
        }

        $scored = [];
        $rowCount = count($rows);
        $preferred = array_flip(self::TOOL_RESULT_METRIC_FIELDS);
        foreach ($metricStats as $metric => $stat) {
            $values = $stat['values'];
            $coverage = $stat['coverage'] / max(1, $rowCount);
            if ($coverage < 0.3 && count($values) < 1) {
                continue;
            }
            $min = min($values);
            $max = max($values);
            $spread = abs($max - $min);
            // 全相同也保留：便于模型说明「该指标均为 X」
            if ($spread <= 0.0) {
                $spread = 0.0001;
            }
            $boost = isset($preferred[$metric]) ? 3.0 : 1.0;
            // 白名单越靠前，同分时越优先（clock_count 等可比指标）
            $tie = isset($preferred[$metric]) ? (1000 - (int) $preferred[$metric]) / 100000 : 0.0;
            $scored[$metric] = $coverage * (1.0 + $spread) * $boost + $tie;
        }
        if ($scored === []) {
            return [];
        }
        arsort($scored);
        $selected = array_slice(array_keys($scored), 0, 3);

        $ranks = [];
        foreach ($selected as $metric) {
            $top = [];
            foreach ($rows as $row) {
                if (! array_key_exists($metric, $row['metrics'])) {
                    continue;
                }
                $top[] = [
                    'label' => mb_substr($row['label'], 0, 40),
                    'value' => $row['metrics'][$metric],
                ];
            }
            usort(
                $top,
                static fn (array $left, array $right): int => $right['value'] <=> $left['value']
            );
            $ranks[] = [
                'metric' => $metric,
                'top'    => array_slice($top, 0, 5),
            ];
        }

        return $ranks;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function extractToolResultItemLabel(array $item): string
    {
        foreach (['name', 'title', 'customer_name', 'lead_name', 'user_name', 'real_name', 'nickname', 'label'] as $field) {
            if (! isset($item[$field]) || ! is_scalar($item[$field])) {
                continue;
            }
            $label = trim((string) $item[$field]);
            if ($label !== '') {
                return $label;
            }
        }
        if (isset($item['card']) && is_array($item['card']) && isset($item['card']['name'])) {
            $label = trim((string) $item['card']['name']);
            if ($label !== '') {
                return $label;
            }
        }
        if (isset($item['user']) && is_array($item['user']) && isset($item['user']['name'])) {
            $label = trim((string) $item['user']['name']);
            if ($label !== '') {
                return $label;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, int|float>
     */
    private function extractToolResultItemMetrics(array $item): array
    {
        $metrics = [];
        $preferred = array_flip(self::TOOL_RESULT_METRIC_FIELDS);
        foreach ($item as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            if ($this->isToolResultIdLikeKey($key)) {
                continue;
            }
            if (! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric($value))) {
                continue;
            }
            // 优先白名单；其它纯数字键也收（覆盖 CRM 自定义字段），但排除看起来像状态码的极短键可仍保留
            if (! isset($preferred[$key]) && ! preg_match('/^[a-z][a-z0-9_]{1,40}$/i', $key)) {
                continue;
            }
            $num = is_string($value) ? 0 + $value : $value;
            if (! is_int($num) && ! is_float($num)) {
                continue;
            }
            $metrics[$key] = $num;
        }

        return $metrics;
    }

    private function isToolResultIdLikeKey(string $key): bool
    {
        $lower = strtolower($key);
        if (in_array($lower, ['id', 'uid', 'uuid', 'pid', 'sid'], true)) {
            return true;
        }

        return (bool) preg_match('/(^|_)(id|uid|uuid)$/i', $key);
    }

    /**
     * @param mixed $item
     *
     * @return mixed
     */
    private function slimAccuracySampleItem($item)
    {
        if (! is_array($item)) {
            return is_string($item) ? mb_substr($item, 0, 40) : $item;
        }
        $slim = [];
        $label = $this->extractToolResultItemLabel($item);
        if ($label !== '') {
            $slim['name'] = mb_substr($label, 0, 40);
        }
        foreach ([
            'id', 'name', 'title', 'customer_name', 'status', 'number', 'created_at',
            'actual_days', 'clock_count', 'late_card', 'required_days', 'late', 'leave_early',
            'un_followed_days', 'price', 'amount',
        ] as $field) {
            if (! array_key_exists($field, $item) || array_key_exists($field, $slim)) {
                continue;
            }
            $value = $item[$field];
            $slim[$field] = is_string($value) ? mb_substr($value, 0, 40) : $value;
        }
        if (isset($item['frame']) && is_array($item['frame']) && isset($item['frame']['name'])) {
            $slim['frame_name'] = mb_substr((string) $item['frame']['name'], 0, 40);
        }
        if (isset($item['approve']) && is_array($item['approve']) && isset($item['approve']['name'])) {
            $slim['approve_name'] = mb_substr((string) $item['approve']['name'], 0, 40);
        }

        return $slim !== [] ? $slim : array_slice($item, 0, 3, true);
    }

    /** 尝试将文本当 JSON 结构化压缩；非 JSON 则原样返回. */
    private function compactToolResultTextPayload(string $text, int $maxRows, bool &$compacted): string
    {
        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            return $text;
        }

        $decoded = $this->compactToolResultObject($decoded, $maxRows, $compacted);

        return json_encode($decoded, JSON_UNESCAPED_UNICODE) ?: $text;
    }

    /**
     * 递归压缩对象：目录类列表多留，明细类按 max_rows 切片并打标.
     *
     * @param array<string, mixed> $obj
     *
     * @return array<string, mixed>
     */
    private function compactToolResultObject(array $obj, int $maxRows, bool &$compacted): array
    {
        $keys = array_keys($obj);
        usort($keys, function ($a, $b) use ($obj): int {
            return $this->toolResultCatalogPriority((string) $b, $obj[$b] ?? null)
                <=> $this->toolResultCatalogPriority((string) $a, $obj[$a] ?? null);
        });

        $out = [];
        foreach ($keys as $key) {
            $key   = (string) $key;
            $value = $obj[$key];
            // summary 已在 compact 前基于完整列表生成，禁止再 slim/切片 ranks.
            if ($key === 'summary' && is_array($value)) {
                $out[$key] = $value;
                continue;
            }
            if (is_array($value) && $this->isToolResultList($value)) {
                $limit   = $this->toolResultListLimit($key, $value, $maxRows);
                $slimmed = [];
                foreach ($value as $item) {
                    if (! is_array($item)) {
                        $slimmed[] = $item;
                        continue;
                    }
                    if ($this->isToolResultCatalogItem($key, $item) && ! $this->isToolResultScheduleListKey($key)) {
                        $slimmed[] = $this->slimToolResultCatalogItem($item);
                    } else {
                        $slimmed[] = $this->slimToolResultDataItem($this->compactToolResultObject($item, $maxRows, $compacted));
                    }
                }
                $total = count($slimmed);
                if ($total > $limit) {
                    $compacted                 = true;
                    $out[$key]                 = array_values(array_slice($slimmed, 0, $limit));
                    $out[$key . '_truncated'] = true;
                    $out[$key . '_total']     = $total;
                } else {
                    $out[$key] = $slimmed;
                }
                continue;
            }
            if (is_array($value)) {
                $out[$key] = $this->compactToolResultObject($value, $maxRows, $compacted);
                continue;
            }
            if (is_string($value) && mb_strlen($value) > 800) {
                $compacted = true;
                $out[$key] = mb_substr($value, 0, 800) . '...';
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * @param array<int, mixed> $value
     */
    private function isToolResultList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    /** 目录/索引类 key 优先编码，避免明细占满字符预算后丢失 id. */
    private function toolResultCatalogPriority(string $key, mixed $value): int
    {
        if (in_array($key, ['statistics', 'agentHints', 'scope', 'total', 'stats', 'paging'], true)) {
            return 120;
        }
        if (preg_match('/folder|module|branch|categor|director|tree|menu|nav|schema/i', $key)) {
            return 100;
        }
        if (is_array($value) && $this->isToolResultList($value) && $value !== [] && is_array($value[0])
            && $this->isToolResultCatalogItem($key, $value[0])
        ) {
            return 80;
        }

        return 0;
    }

    /**
     * @param array<int, mixed> $list
     */
    private function toolResultListLimit(string $key, array $list, int $maxRows): int
    {
        $sample = $list[0] ?? null;
        if ($this->isToolResultScheduleListKey($key)) {
            return max($maxRows, min(50, $maxRows + 10));
        }
        if (is_array($sample) && $this->isToolResultCatalogItem($key, $sample)) {
            return max($maxRows, 200);
        }
        if (preg_match('/folder|module|branch|categor|director|tree|menu|nav/i', $key)) {
            return max($maxRows, 200);
        }

        return max(1, $maxRows);
    }

    /** 是否为日程汇总中的待办/完成明细列表 key. */
    private function isToolResultScheduleListKey(string $key): bool
    {
        return (bool) preg_match('/^(pending|completed|schedules?)$/i', $key);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function isToolResultCatalogItem(string $key, array $item): bool
    {
        if (preg_match('/folder|module|branch|categor|director|tree|menu|nav/i', $key)) {
            return true;
        }
        if (array_intersect(self::TOOL_RESULT_METRIC_FIELDS, array_keys($item)) !== []) {
            return false;
        }
        $hasId   = isset($item['id']) || isset($item['folderId']) || isset($item['moduleId']);
        $hasName = isset($item['name']) || isset($item['title']);
        $looksEndpoint = isset($item['path']) || isset($item['method']) || isset($item['entityId']);

        return $hasId && $hasName && ! $looksEndpoint;
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function slimToolResultCatalogItem(array $item): array
    {
        $keep = [];
        // code/address 供同名条目（如重名仓库）消歧.
        foreach (['id', 'folderId', 'moduleId', 'branchId', 'parentId', 'name', 'title', 'type', 'updatedAt', 'code', 'address'] as $field) {
            if (array_key_exists($field, $item)) {
                $keep[$field] = $item[$field];
            }
        }

        return $keep !== [] ? $keep : $item;
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function slimToolResultDataItem(array $item): array
    {
        $keep = [];
        foreach ([
            'id', 'entityId', 'name', 'title', 'customer_name', 'lead_name', 'user_name', 'method', 'path', 'status', 'type',
            'moduleId', 'folderId', 'branchId', 'parentId', 'code', 'key',
            'start', 'end', 'start_time', 'end_time', 'finish',
            'price', 'amount', 'income', 'expend', 'net_amount', 'ratio',
            'percentage', 'rate', 'total', 'count', 'approved', 'pending',
            'review_income', 'review_expend', 'frame_name', 'avatar',
            // 产品/仓库：组装订单明细所需的定位键与库存口径，裁掉会让模型只剩产品名.
            'product_id', 'sku_id', 'unique', 'sku', 'skus', 'unit_name',
            'category', 'bar_code', 'ot_price', 'warehouse_id', 'permissions', 'address',
            'good_stock', 'defect_stock', 'occupy_stock', 'transit_stock', 'available_stock',
            // 考勤等：数值排行依赖；姓名常嵌在 card.name.
            'actual_days', 'clock_count', 'late_card', 'early_card', 'absenteeism', 'required_days',
            'late', 'leave_early', 'normal_days', 'work_hours', 'un_followed_days', 'followed',
        ] as $field) {
            if (array_key_exists($field, $item)) {
                $keep[$field] = $item[$field];
            }
        }
        if (isset($item['card']) && is_array($item['card']) && isset($item['card']['name'])) {
            $keep['name'] = $item['card']['name'];
        }
        if (! isset($keep['name'])) {
            $label = $this->extractToolResultItemLabel($item);
            if ($label !== '') {
                $keep['name'] = $label;
            }
        }
        if (isset($item['frame']) && is_array($item['frame']) && isset($item['frame']['name'])) {
            $keep['frame_name'] = $item['frame']['name'];
        }
        if (isset($item['approve']) && is_array($item['approve']) && isset($item['approve']['name'])) {
            $keep['approve_name'] = $item['approve']['name'];
        }
        foreach (['number', 'created_at'] as $field) {
            if (array_key_exists($field, $item) && ! array_key_exists($field, $keep)) {
                $keep[$field] = $item[$field];
            }
        }

        return $keep !== [] ? $keep : array_slice($item, 0, 8, true);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function finalizeToolResultText(
        string $text,
        int $maxChars,
        bool $compacted,
        string $toolName,
        array $arguments
    ): string {
        $needHint = $compacted || ($maxChars > 0 && mb_strlen($text) > $maxChars);
        if ($needHint && ! str_contains($text, 'next_action')) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                $text = json_encode([
                    'truncated'    => true,
                    'next_action' => $this->toolResultNextActionMessage($toolName, $arguments),
                ] + $decoded, JSON_UNESCAPED_UNICODE) ?: $text;
            } else {
                $text = json_encode([
                    'truncated'    => true,
                    'next_action' => $this->toolResultNextActionMessage($toolName, $arguments),
                    'payload'     => $text,
                ], JSON_UNESCAPED_UNICODE) ?: $text;
            }
        }

        return $this->truncateToolResultText($text, $maxChars);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function toolResultNextActionMessage(string $toolName, array $arguments): string
    {
        $tool = $toolName !== '' ? "工具「{$toolName}」" : '当前工具';
        $narrowed = $this->hasToolResultNarrowArgs($arguments)
            ? '已有部分过滤条件但仍过大：请继续用子目录/更短关键词/分页(offset/limit)细分。'
            : '请先取目录/索引类结果，再按目标范围用 folderId/moduleId/parentId/keyword/limit/offset 等收窄后重查。';

        return $tool . '返回结果过大已结构化截断。'
            . $narrowed
            . '禁止向用户复述下一步工具计划；必须继续调用工具，直到拿到完整目标子集后再作答。';
    }

    /**
     * 字数截断后仅剩 summary 时的续作提示：全量 sample 优先直接展示，避免 ranks 误导分页空转.
     *
     * @param array<string, mixed> $summaryPart
     */
    private function truncatedSummaryNextAction(array $summaryPart, string $fallback): string
    {
        $sample = $summaryPart['sample'] ?? null;
        $sampleCount = is_array($sample) ? count($sample) : 0;
        $total = (int) ($summaryPart['count'] ?? $summaryPart['total'] ?? $summaryPart['sample_count'] ?? 0);
        $complete = $sampleCount > 0 && $total > 0 && $sampleCount >= $total;

        if ($complete) {
            return 'summary.sample 已含本批全量精简清单，请直接完整展示给用户；禁止再分页/按人拆查空转。';
        }
        if ($sampleCount > 0 && $total > $sampleCount) {
            return '列表已截断：请直接展示已有 summary.sample，并说明还有 '
                . ($total - $sampleCount)
                . ' 条未展示；需要剩余时用 page/limit 续查，禁止无进展空转。';
        }
        $hasRank = isset($summaryPart['ranks']) && is_array($summaryPart['ranks']) && $summaryPart['ranks'] !== [];
        if ($hasRank && $sampleCount === 0) {
            return '结果已含列表排行摘要(summary.ranks)，请直接据此回答谁最多/最高；勿再空转续查。';
        }

        return $fallback !== ''
            ? $fallback
            : '结果过长：请仅依据 summary 陈述事实；需要明细时用更窄条件重查。';
    }

    /**
     * 将 sample 压到展示必备字段，便于在 max_chars 预算内保留全量清单.
     *
     * @param array<int, mixed> $sample
     * @param bool $minimal 仅保留 id/姓名/类型/状态，进一步压缩字数
     *
     * @return array<int, mixed>
     */
    private function compactAccuracySampleForBudget(array $sample, bool $minimal = false): array
    {
        $fields = $minimal
            ? ['id', 'name', 'approve_name', 'status']
            : ['id', 'name', 'approve_name', 'status', 'frame_name', 'number', 'created_at'];
        $out = [];
        foreach ($sample as $item) {
            if (! is_array($item)) {
                $out[] = $item;
                continue;
            }
            $row = [];
            foreach ($fields as $field) {
                if (! array_key_exists($field, $item) || $item[$field] === '' || $item[$field] === null) {
                    continue;
                }
                $value = $item[$field];
                $row[$field] = is_string($value) ? mb_substr($value, 0, 40) : $value;
            }
            $out[] = $row !== [] ? $row : $item;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function hasToolResultNarrowArgs(array $arguments): bool
    {
        foreach (self::NARROW_ARG_KEYS as $key) {
            if (! array_key_exists($key, $arguments)) {
                continue;
            }
            $value = $arguments[$key];
            if ($value === null || $value === '' || $value === [] || $value === false) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function truncateToolResultText(string $text, int $maxChars): string
    {
        if ($maxChars <= 0 || mb_strlen($text) <= $maxChars) {
            return $text;
        }

        // 超预算时优先保留 summary（含 ranks），避免总数/口径被盲截断冲掉.
        $decoded = json_decode($text, true);
        if (is_array($decoded) && isset($decoded['summary']) && is_array($decoded['summary'])) {
            $summary = $decoded['summary'];
            $compactRanks = null;
            if (isset($summary['ranks']) && is_array($summary['ranks'])) {
                $compactRanks = [];
                foreach (array_slice($summary['ranks'], 0, 3) as $rank) {
                    if (! is_array($rank)) {
                        continue;
                    }
                    $compactRanks[] = [
                        'metric' => (string) ($rank['metric'] ?? ''),
                        'top'    => array_slice(is_array($rank['top'] ?? null) ? $rank['top'] : [], 0, 3),
                    ];
                }
            }
            // 优先保留全量/大批 sample（「显示全部」），再退到 ranks；避免 sample 被丢掉后只剩排行误导分页空转.
            $baseMeta = array_intersect_key($summary, array_flip([
                'total', 'count', 'total_count', 'rows_total', 'stats', 'statistics', 'paging', 'scope',
                'rows_truncated', 'truncated_lists', 'sample_key', 'sample_count', 'sample_complete',
            ]));
            $rawSample = isset($summary['sample']) && is_array($summary['sample']) ? $summary['sample'] : [];
            $compactSample = $this->compactAccuracySampleForBudget($rawSample);
            $minimalSample = $this->compactAccuracySampleForBudget($rawSample, true);
            $attempts = [
                $baseMeta + ['sample' => $rawSample],
                $baseMeta + ['sample' => $compactSample],
                $baseMeta + ['sample' => $minimalSample],
                array_diff_key($summary, ['ranks' => true, 'rank_hint' => true]) + ['sample' => $minimalSample],
                array_diff_key($summary, ['ranks' => true, 'rank_hint' => true]),
                $baseMeta + ['sample' => $minimalSample] + (
                    isset($summary['ranks']) ? ['ranks' => $compactRanks, 'rank_hint' => $summary['rank_hint'] ?? null] : []
                ),
                $summary,
                array_intersect_key($summary, array_flip([
                    'total', 'count', 'total_count', 'rows_total', 'stats', 'statistics', 'paging', 'scope',
                    'rows_truncated', 'truncated_lists', 'ranks', 'rank_hint', 'sample', 'sample_count', 'sample_complete',
                ])),
                array_diff_key($summary, ['sample' => true]),
                array_intersect_key($summary, array_flip([
                    'total', 'count', 'total_count', 'rows_total', 'stats', 'statistics', 'paging', 'scope',
                    'rows_truncated', 'truncated_lists', 'ranks', 'rank_hint',
                ])),
                // 极紧预算：人数 + ranks 短名单
                array_filter([
                    'count'     => $summary['count'] ?? ($summary['total'] ?? null),
                    'ranks'     => $compactRanks,
                    'rank_hint' => $summary['rank_hint'] ?? null,
                ], static fn ($v) => $v !== null && $v !== []),
                array_intersect_key($summary, array_flip(['total', 'count', 'total_count', 'rows_total', 'stats', 'statistics', 'paging', 'scope', 'rows_truncated', 'truncated_lists'])),
            ];
            foreach ($attempts as $summaryPart) {
                if ($summaryPart === []) {
                    continue;
                }
                $summaryOnly = [
                    'summary'     => $summaryPart,
                    'truncated'   => true,
                    'next_action' => $this->truncatedSummaryNextAction($summaryPart, (string) ($decoded['next_action'] ?? '')),
                ];
                $encoded = json_encode($summaryOnly, JSON_UNESCAPED_UNICODE) ?: '';
                if ($encoded !== '' && mb_strlen($encoded) <= $maxChars) {
                    return $encoded;
                }
            }
        }

        // 最终兜底仍保证可解析 JSON，避免模型吃到半截字符串.
        $fallback = json_encode([
            'truncated'   => true,
            'summary'     => ['note' => '结果过长已截断，请收窄条件重查'],
            'next_action' => '请用更窄条件重新调用工具后再回答。',
        ], JSON_UNESCAPED_UNICODE) ?: '{"truncated":true}';

        return mb_strlen($fallback) <= $maxChars
            ? $fallback
            : mb_substr($text, 0, $maxChars) . "\n...[truncated]";
    }

    /**
     * 写确认前按 schema/fields 从用户话术抽槽并合并进参数.
     *
     * @param array<string, mixed> $args
     *
     * @return array{args: array<string, mixed>, applied: array<string, scalar>}
     */
    private function prefillWriteArgsFromSlots(
        AiApplication $app,
        AgentWriteFlowDriver $guard,
        string $toolName,
        array $args
    ): array {
        $fields = $guard->resolveEditableSchemaFields($toolName, $args);
        if ($fields === []) {
            return ['args' => $args, 'applied' => []];
        }

        $texts = $this->collectSlotTexts();
        if ($texts === []) {
            return ['args' => $args, 'applied' => []];
        }

        $model = null;
        try {
            $model = $app->model;
        } catch (\Throwable) {
            $model = null;
        }

        $filled = app(SchemaSlotFiller::class)->fill($fields, $texts, $toolName, $model);

        return $guard->mergeSlotsIntoArgs($toolName, $args, (array) ($filled['slots'] ?? []));
    }

    /**
     * 收集抽槽文本：当前句 + 近几轮 user 原文.
     *
     * @return array<int, string>
     */
    private function collectSlotTexts(): array
    {
        $texts = [];
        $question = trim((string) ($this->writeContext['question'] ?? ''));
        if ($question !== '') {
            $texts[] = $question;
        }

        $conversationId = (int) ($this->writeContext['conversation_id'] ?? 0);
        if ($conversationId > 0) {
            try {
                $history = app(AiConversationMessageService::class)->history($conversationId, 4);
                foreach (array_reverse($history) as $message) {
                    if (($message['role'] ?? '') !== AiConversationEnum::ROLE_USER) {
                        continue;
                    }
                    $content = trim((string) ($message['content'] ?? ''));
                    if ($content === '' || in_array($content, $texts, true)) {
                        continue;
                    }
                    $texts[] = $content;
                    if (count($texts) >= 4) {
                        break;
                    }
                }
            } catch (\Throwable) {
                // 历史不可用时仅用当前句.
            }
        }

        return $texts;
    }

    /**
     * 调用本地写工具的 prepareWriteArguments（若实现）：确认前解析名称/消歧.
     *
     * @param array<string, mixed> $args
     *
     * @return null|array<string, mixed> null=工具无此钩子；否则为 prepare 返回值
     */
    private function prepareWriteToolArguments(string $toolName, array $args): ?array
    {
        $tool = ToolRegistry::getTool($toolName);
        if ($tool === null || ! method_exists($tool, 'prepareWriteArguments')) {
            return null;
        }

        try {
            $prepared = $tool->prepareWriteArguments($args);
        } catch (\Throwable $e) {
            Log::warning('prepareWriteArguments failed', [
                'tool'    => $toolName,
                'message' => $e->getMessage(),
            ]);

            return [
                'error'   => true,
                'message' => '写入前校验失败：' . $e->getMessage(),
            ];
        }

        return is_array($prepared) ? $prepared : null;
    }

    /**
     * 将 prepareWriteArguments 的 needs_selection/field 错误规范为补参卡可消费结构.
     *
     * @param array<string, mixed> $prepared
     * @param array<string, mixed> $originalArgs 模型原始写参，供续跑合并
     *
     * @return array<string, mixed>
     */
    private function normalizePrepareWriteError(array $prepared, array $originalArgs): array
    {
        $prepared['original_arguments'] = $originalArgs;

        if (! empty($prepared['needs_selection'])) {
            $fieldKey   = (string) ($prepared['field_key'] ?? 'entity_name');
            $fieldLabel = (string) ($prepared['field_label'] ?? '选项');
            $message    = (string) ($prepared['message'] ?? ('请选择' . $fieldLabel));
            $options    = [];
            foreach ((array) ($prepared['candidates'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $value = $item['value'] ?? $item['id'] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                $options[] = [
                    'value' => $value,
                    'label' => (string) ($item['label'] ?? $item['name'] ?? $value),
                ];
            }
            $prepared['needs_input'] = true;
            if ($options !== [] && empty($prepared['fields'])) {
                $prepared['fields'] = [[
                    'field_key'   => $fieldKey,
                    'field_label' => $fieldLabel,
                    'input_type'  => 'select',
                    'options'     => $options,
                    'message'     => $message,
                ]];
            }
        } elseif (! empty($prepared['field']) && empty($prepared['fields'])) {
            $fieldKey = (string) $prepared['field'];
            $prepared['needs_input'] = true;
            $prepared['fields']      = [[
                'field_key'   => $fieldKey,
                'field_label' => $fieldKey === 'entity_name' ? '名称' : $fieldKey,
                'input_type'  => 'text',
                'options'     => [],
                'message'     => (string) ($prepared['message'] ?? ('请填写' . $fieldKey)),
            ]];
        }

        return $prepared;
    }

    /** 判断应用是否启用了 MCP 数据源。 */
    private function enabled(AiApplication $app): bool
    {
        return AiConversationEnum::hasSource($app->sourceTypeMask(), AiConversationEnum::SOURCE_MCP);
    }

    /**
     * 必须已在协程内才能并行；禁止 Coroutine\run 新建调度器（原 AiParallelRunner）.
     */
    private function canParallelTools(): bool
    {
        return extension_loaded('swoole')
            && class_exists(\Swoole\Coroutine\WaitGroup::class)
            && class_exists(\Swoole\Coroutine::class)
            && \Swoole\Coroutine::getCid() > 0;
    }

    /**
     * @param array<string, callable(): mixed> $tasks
     *
     * @return array<string, mixed>
     */
    private function runParallelTasks(array $tasks): array
    {
        if ($tasks === [] || count($tasks) === 1 || ! $this->canParallelTools()) {
            $results = [];
            foreach ($tasks as $key => $task) {
                $results[$key] = $task();
            }

            return $results;
        }

        $results = [];
        $errors  = [];
        $wg      = new \Swoole\Coroutine\WaitGroup();
        foreach ($tasks as $key => $task) {
            $wg->add();
            \Swoole\Coroutine::create(function () use ($wg, $key, $task, &$results, &$errors): void {
                try {
                    $results[$key] = $task();
                } catch (\Throwable $e) {
                    $errors[$key] = $e;
                } finally {
                    $wg->done();
                }
            });
        }
        $wg->wait();

        if ($errors !== []) {
            throw reset($errors);
        }

        $ordered = [];
        foreach (array_keys($tasks) as $key) {
            $ordered[$key] = $results[$key] ?? null;
        }

        return $ordered;
    }

    /**
     * 从正文中提取文本型工具调用并返回净化后的内容与标准 tool_calls。
     *
     * @param string $text 模型原始回答
     * @param null|bool $allowParse 为 null 时读配置 ai.plugins.tools.allow_text_tool_parse
     *
     * @return array{content: string, tool_calls: array<int, array<string, mixed>>} 净化结果
     */
    public function parseToolCallText(string $text, ?bool $allowParse = null): array
    {
        // T-2：默认只剥离泄露标记，不把正文解析成可执行 tool_calls（弱模型可开 allow_text_tool_parse）.
        if (! ($allowParse ?? $this->allowTextToolParse())) {
            $pipeline = class_exists(AnswerSanitizePipeline::class)
                ? (function_exists('app') ? app(AnswerSanitizePipeline::class) : new AnswerSanitizePipeline())
                : new AnswerSanitizePipeline();
            $cleaned = $pipeline->stripToolCallArtifacts($text);
            $cleaned = trim(preg_replace("/\n{3,}/", "\n\n", $cleaned) ?? $cleaned);

            return [
                'content'    => $cleaned,
                'tool_calls' => [],
            ];
        }

        $toolCalls = [];
        $content   = $text;

        $patterns = [
            '/<\|FunctionCallBegin\|>(.*?)<\|FunctionCallEnd\|>/s',
            '/<\|tool_call_begin\|>(.*?)<\|tool_call_end\|>/si',
            '/<tool_call>(.*?)<\/tool_call>/s',
            '/```(?:tool_call|json)\s*(\{.*?\})\s*```/s',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, function (array $matches) use (&$toolCalls): string {
                foreach ($this->extractTextToolCalls((string) ($matches[1] ?? '')) as $call) {
                    $toolCalls[] = $call;
                }

                return '';
            }, $content) ?? $content;
        }

        // 兜底：全文就是一段 JSON 工具调用数组/对象.
        if ($toolCalls === [] && $this->looksLikeToolJson($content)) {
            foreach ($this->extractTextToolCalls(trim($content)) as $call) {
                $toolCalls[] = $call;
            }
            if ($toolCalls !== []) {
                $content = '';
            }
        }

        // 兼容模型输出未闭合或混入中文的畸形 tool_call 标签，只保留标签内可读文本。
        $content = preg_replace_callback(
            '/<tool_call([^>]*)>/iu',
            static fn (array $matches): string => trim((string) ($matches[1] ?? '')),
            $content
        ) ?? $content;
        $content = preg_replace('/<\/tool_call>/iu', '', $content) ?? $content;

        // 未配对 FunctionCall / tool_call 特殊标记整段剔除（含残缺 <|FunctionCallBegin|）.
        $content = app(AnswerSanitizePipeline::class)->stripToolCallArtifacts($content);

        return [
            'content'    => trim(preg_replace("/\n{3,}/", "\n\n", $content) ?? $content),
            'tool_calls' => $toolCalls,
        ];
    }

    /**
     * 判断文本是否含有 FunctionCall 或 tool_call 标记。
     *
     * @param string $text 待检测文本
     */
    public function containsToolCallMarker(string $text): bool
    {
        return (bool) preg_match(
            '/<\|FunctionCall(?:Begin|End)?\|>?|<\|tool_calls?(?:_section)?_(?:begin|end)\|>?|<\|tool_call|<tool_call(?=[^a-z0-9_]|$)|```tool_call/i',
            $text
        );
    }

    /**
     * 是否允许从正文回吸工具调用（配置 ai.plugins.tools.allow_text_tool_parse）.
     */
    public function allowTextToolParse(): bool
    {
        return (bool) config('ai.plugins.tools.allow_text_tool_parse', false);
    }

    /**
     * 将模型幻觉工具名映射为系统标准工具名。
     *
     * @param string $name 原始工具名
     *
     * @return string 归一化后的工具名
     */
    public function normalizeToolName(string $name): string
    {
        $key = strtolower(trim($name));

        return self::TOOL_NAME_ALIASES[$key] ?? $name;
    }

    /**
     * 将 tool_call.function.arguments 规范为 JSON object 字符串。
     *
     * OpenAI 兼容接口要求 arguments 解析后必须是对象；PHP `json_encode([])` 会得到 `"[]"`，
     * 多轮把该 tool_call 回传给模型时会报：expected a JSON object, but got list。
     *
     * @param mixed $arguments 原始 arguments（字符串、数组或标量）
     */
    public static function encodeFunctionArguments(mixed $arguments): string
    {
        if (is_string($arguments)) {
            $trim = trim($arguments);
            if ($trim === '' || strcasecmp($trim, 'null') === 0) {
                return '{}';
            }
            $decoded = json_decode($trim, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // 非法 JSON（含半截 object）统一包一层，避免历史消息回传时被上游拒收.
                return json_encode(['input' => $arguments], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
            }
            if (! is_array($decoded)) {
                return json_encode(['value' => $decoded], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
            }
            $arguments = $decoded;
        } elseif ($arguments === null) {
            return '{}';
        } elseif (! is_array($arguments)) {
            return json_encode(['value' => $arguments], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if ($arguments === []) {
            return '{}';
        }

        // 数字下标列表：包成 object（兼容模型把 items[] 直接当作顶层 arguments）.
        if (array_keys($arguments) === range(0, count($arguments) - 1)) {
            return json_encode(['items' => $arguments], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        return json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * 规范化单条 tool_call，确保 function.arguments 为 JSON object 字符串.
     *
     * @param array<string, mixed> $toolCall
     *
     * @return array<string, mixed>
     */
    public static function normalizeToolCallArguments(array $toolCall): array
    {
        if (! isset($toolCall['function']) || ! is_array($toolCall['function'])) {
            return $toolCall;
        }
        $toolCall['function']['arguments'] = self::encodeFunctionArguments(
            $toolCall['function']['arguments'] ?? '{}'
        );

        return $toolCall;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractTextToolCalls(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $json = json_decode($raw, true);
        if (! is_array($json)) {
            return [];
        }

        $items = $this->isToolCallList($json) ? $json : [$json];
        $calls = [];
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = (string) ($item['name'] ?? $item['function']['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $params = $item['parameters'] ?? $item['arguments'] ?? $item['function']['arguments'] ?? [];
            if (is_string($params)) {
                $decoded = json_decode($params, true);
                $params  = is_array($decoded) ? $decoded : ['input' => $params];
            }
            if (! is_array($params)) {
                $params = [];
            }
            $calls[] = self::normalizeToolCallArguments([
                'id'       => (string) ($item['id'] ?? ('call_text_' . $index)),
                'type'     => 'function',
                'function' => [
                    'name'      => $this->normalizeToolName($name),
                    'arguments' => $params,
                ],
            ]);
        }

        return $calls;
    }

    /** 判断文本是否为独立的 JSON 工具调用结构。 */
    private function looksLikeToolJson(string $text): bool
    {
        $trim = trim($text);
        if ($trim === '' || ($trim[0] !== '{' && $trim[0] !== '[')) {
            return false;
        }

        return (bool) preg_match('/"name"\s*:\s*"[^"]+"/u', $trim)
            && (bool) preg_match('/"(parameters|arguments)"\s*:/u', $trim);
    }

    private function isToolCallList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

}
