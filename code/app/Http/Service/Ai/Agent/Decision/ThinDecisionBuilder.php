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

namespace App\Http\Service\Ai\Agent\Decision;

use App\Constants\Ai\AgentEnum;
use App\Constants\Ai\AiConversationEnum;
use App\Http\Model\Ai\AiApplication;
use App\Http\Service\Ai\AiMcpService;
use App\Http\Service\Ai\AiSqlQueryService;
use App\Mcp\ToolRegistry;

/**
 * 瘦身 ReAct 决策：不做本地意图/词面路由，只产出权限剖面 + 渐进工具骨架.
 *
 * 首轮业务工具为空，由模型经 load_tools / search_knowledge 按需挂载；
 * 写确认与补参闸门仍由执行层处理。
 */
final class ThinDecisionBuilder
{
    /** 企业内部员工会话. */
    public const PROFILE_EMPLOYEE = 'employee';

    /** 外部访客会话（禁 SQL / 本地写 MCP）. */
    public const PROFILE_GUEST = 'guest';

    /**
     * 兼容下游的中性意图：不表达业务语义，仅标记来源为 thin_react.
     *
     * @return array{type: string, confidence: float, meta: array<string, mixed>}
     */
    public static function neutralIntent(): array
    {
        return self::makeIntent(AgentEnum::INTENT_MULTI_STEP, 1.0, [
            'source'    => 'thin_react',
            'modules'   => [],
            'operation' => '',
            'skills'    => [],
            'reason'    => 'thin_react',
        ]);
    }

    /**
     * 组装 intent 观测快照（写确认续跑等；不做路由）.
     *
     * @param array<string, mixed> $meta
     *
     * @return array{type: string, confidence: float, meta: array<string, mixed>}
     */
    public static function makeIntent(string $type, float $confidence = 1.0, array $meta = []): array
    {
        return [
            'type'       => $type,
            'confidence' => min(1.0, max(0.0, $confidence)),
            'meta'       => $meta,
        ];
    }

    /**
     * 按应用绑定与访客画像组装 decision（供 prepare / ToolLoop 使用）.
     *
     * @return array<string, mixed>
     */
    public static function buildDecision(AiApplication $app, bool $isGuest = false): array
    {
        $profile = $isGuest ? self::PROFILE_GUEST : self::PROFILE_EMPLOYEE;
        $mask    = $app->sourceTypeMask();
        $mcpBound = AiConversationEnum::hasSource($mask, AiConversationEnum::SOURCE_MCP)
            && app(AiMcpService::class)->mcpEnabledForApp($app);
        $knowledgeBound = AiConversationEnum::hasSource($mask, AiConversationEnum::SOURCE_KNOWLEDGE);
        $sqlBound       = ! $isGuest
            && AiConversationEnum::hasSource($mask, AiConversationEnum::SOURCE_SQL)
            && app(AiSqlQueryService::class)->isEnabled($app);

        $readMax  = max(1, (int) config('ai.plugins.tool_loop.read_max_rounds', 3));
        $writeMax = max(1, (int) config('ai.plugins.tool_loop.write_max_rounds', 5));
        // 渐进加载至少需要额外一轮 load_tools；读/写分预算，不做全局绝对硬顶.
        $maxToolRounds = max($writeMax + 2, $readMax + 2, 6);

        $prefetch = [];
        if (! $isGuest && $mcpBound) {
            $prefetch[] = 'memory';
            $prefetch[] = 'identity';
        }

        // 首轮 allowlist 仅身份 + 元工具；业务工具一律经 load_tools 补挂.
        $allowlist = array_values(array_unique(array_merge(
            McpToolAllowlist::IDENTITY_TOOLS,
            McpToolAllowlist::META_TOOLS
        )));

        $plan = [
            'profile'               => self::normalizeProfile($profile),
            'rag_enabled'           => false,
            'rag_top_k'             => (int) config('ai.plugins.rag.max_chunks', 5),
            'rag_token_budget'      => (int) config('ai.plugins.rag.token_budget', 2000),
            'mcp_enabled'           => $mcpBound || $knowledgeBound,
            'mcp_allowlist'         => $mcpBound ? $allowlist : (
                $knowledgeBound ? ['search_knowledge'] : []
            ),
            'mcp_parallel_readonly' => true,
            'sql_enabled'           => $sqlBound,
            'sql_tables'            => [],
            'sql_max_rows'          => (int) config('ai.plugins.sql.max_rows', 10),
            'prefetch'              => $prefetch,
            'max_tool_rounds'       => $mcpBound || $sqlBound || $knowledgeBound ? $maxToolRounds : 0,
            'answer_policy'         => 'tool_first',
            'rag_skip_reason'       => 'thin_react_on_demand',
            'inject_org_tools'      => false,
            'thin_react'            => true,
            'knowledge_tool'        => $knowledgeBound,
            'module_catalog'        => $mcpBound
                ? self::moduleCatalog(app(AiMcpService::class)->modulesForApp($app))
                : [],
        ];

        $decision = self::planToDecisionArray($plan);
        $decision['thin_react']     = true;
        $decision['knowledge_tool'] = $knowledgeBound;
        $decision['module_catalog'] = $plan['module_catalog'];
        $decision['rag_enabled']    = false;

        if ($isGuest) {
            $decision['sql'] = false;
        }

        return $decision;
    }

    /**
     * 模块短目录（注入 system，非整份 tools schema）.
     *
     * @param array<int, string> $modules
     *
     * @return array<int, array{module: string, name: string, summary: string}>
     */
    public static function moduleCatalog(array $modules): array
    {
        $catalog = [];
        foreach (array_values(array_unique(array_filter(array_map('strval', $modules)))) as $module) {
            $config  = ToolRegistry::getModuleConfig($module);
            $desc    = (string) ($config['description'] ?? '');
            $summary = preg_split('/[。；]/u', $desc)[0] ?? $desc;
            $catalog[] = [
                'module'  => $module,
                'name'    => (string) ($config['name'] ?? $module),
                'summary' => mb_substr(trim((string) $summary), 0, 80),
            ];
        }

        return $catalog;
    }

    /**
     * 将模块目录格式化为可注入 system 的短文案.
     *
     * @param array<int, array{module?: string, name?: string, summary?: string}> $catalog
     */
    public static function catalogPrompt(array $catalog): string
    {
        if ($catalog === []) {
            return '';
        }
        $lines = ['# 可用业务模块（按需 load_tools）'];
        foreach ($catalog as $item) {
            $module  = (string) ($item['module'] ?? '');
            $name    = (string) ($item['name'] ?? $module);
            $summary = (string) ($item['summary'] ?? '');
            if ($module === '') {
                continue;
            }
            $lines[] = '- ' . $module . '（' . $name . '）' . ($summary !== '' ? '：' . $summary : '');
        }
        $lines[] = '当前未挂载业务工具时，先调用 load_tools 加载对应模块，再调用具体工具；禁止编造未加载的工具名。';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $plan
     *
     * @return array<string, mixed>
     */
    public static function planToDecisionArray(array $plan): array
    {
        return [
            'mcp'              => (bool) ($plan['mcp_enabled'] ?? false),
            'sql'              => (bool) ($plan['sql_enabled'] ?? false),
            'answer_policy'    => (string) ($plan['answer_policy'] ?? 'chitchat'),
            'mcp_allowlist'    => (array) ($plan['mcp_allowlist'] ?? []),
            'inject_org_tools' => (bool) ($plan['inject_org_tools'] ?? false),
            'profile'          => (string) ($plan['profile'] ?? self::PROFILE_EMPLOYEE),
            'sql_max_rows'     => (int) ($plan['sql_max_rows'] ?? 10),
            'max_tool_rounds'  => (int) ($plan['max_tool_rounds'] ?? 10),
            'prefetch'         => (array) ($plan['prefetch'] ?? []),
            'rag_top_k'        => (int) ($plan['rag_top_k'] ?? 5),
            'rag_token_budget' => (int) ($plan['rag_token_budget'] ?? 3000),
        ];
    }

    /**
     * 规范化会话画像.
     */
    public static function normalizeProfile(string $profile): string
    {
        return $profile === self::PROFILE_GUEST ? self::PROFILE_GUEST : self::PROFILE_EMPLOYEE;
    }
}
