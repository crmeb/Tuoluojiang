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

use App\Mcp\ToolRegistry;

/**
 * MCP 工具白名单辅助：身份/元工具常量、发现过滤、循环中收窄与硬顶裁剪.
 *
 * thin-react 首轮 allowlist 由 ThinDecisionBuilder 固定为身份+元工具；
 * 业务工具经 load_tools 按模块核心短名单补挂，不再做意图词面路由.
 */
class McpToolAllowlist
{
    /** @var array<int, string> */
    public const IDENTITY_TOOLS = ['who_am_i', 'my_data_scope', 'check_data_permission'];

    /** 运行时元工具（非 MCP 业务工具）：focus / cap 裁剪时与身份工具一样常驻. */
    public const META_TOOLS = ['load_tools', 'search_knowledge'];

    /**
     * 客户域复合写桥接：自选审批人仍需 approve_process_get（在 approve 模块）.
     *
     * @var array<int, string>
     */
    private const CUSTOMER_APPROVE_BRIDGE_TOOLS = [
        'approve_process_get',
    ];

    /**
     * 客户域业务审批表单工具（customer 模块，sys_config→表单）.
     *
     * @var array<int, string>
     */
    private const CUSTOMER_BIZ_APPROVE_FORM_TOOLS = [
        'customer_biz_form_get',
    ];

    /**
     * 反向桥接：若仍误调 approve_form_get/process_get，保留客户写工具与业务表单工具.
     *
     * @var array<int, string>
     */
    private const APPROVE_CUSTOMER_WRITE_BRIDGE_TOOLS = [
        'write_form_get',
        'customer_biz_form_get',
        'payment_create',
        'renewal_create',
        'expense_create',
        'invoice_apply',
        'invoice_void_apply',
    ];

    /**
     * @return array<int, string>
     */
    public static function identityTools(): array
    {
        return self::IDENTITY_TOOLS;
    }

    /**
     * 供 toolsForApp 使用的发现过滤名单：永远返回数组，空 = 发现零业务工具.
     *
     * @param array<int, string|mixed> $allowlist
     *
     * @return array<int, string>
     */
    public static function discoveryNames(array $allowlist): array
    {
        return array_values(array_unique(array_filter(array_map('strval', $allowlist))));
    }

    /**
     * 工具循环中轮：按已调用工具收窄 schema，保留同模块续查能力；不裁剪 messages.
     *
     * @param array<int, array<string, mixed>> $tools BaseOption.tools
     * @param array<int, string> $calledNames 本轮已调用工具名
     * @param null|callable(string): (?string) $toolModule
     *
     * @return array<int, array<string, mixed>>
     */
    public static function focusToolDefinitions(array $tools, array $calledNames, ?callable $toolModule = null): array
    {
        $called = array_values(array_unique(array_filter(array_map('strval', $calledNames))));
        if ($called === [] || $tools === []) {
            return $tools;
        }

        $toolModule ??= static function (string $name): ?string {
            try {
                return ToolRegistry::getToolModule($name);
            } catch (\Throwable) {
                return null;
            }
        };

        $keepModules = [];
        foreach ($called as $name) {
            $module = $toolModule($name);
            if ($module !== null && $module !== '' && ! in_array($module, ['common', 'context'], true)) {
                $keepModules[$module] = true;
            }
        }
        // 尚未落到业务模块时不强行裁剪，避免误伤续查.
        if ($keepModules === []) {
            return $tools;
        }

        $identity  = array_fill_keys(array_merge(self::IDENTITY_TOOLS, self::META_TOOLS), true);
        $calledSet = array_fill_keys($called, true);
        $bridge    = self::resolveFocusBridgeNames($keepModules, $called);
        $focused   = [];
        $seen      = [];
        foreach ($tools as $tool) {
            if (! is_array($tool)) {
                continue;
            }
            $name = (string) ($tool['function']['name'] ?? $tool['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $keep = isset($identity[$name])
                || isset($calledSet[$name])
                || isset($bridge[$name]);
            if (! $keep) {
                $module = $toolModule($name);
                $keep   = $module !== null && isset($keepModules[$module]);
            }
            if (! $keep || isset($seen[$name])) {
                continue;
            }
            $focused[]   = $tool;
            $seen[$name] = true;
        }

        $focused = $focused !== [] ? $focused : $tools;
        // customer 等大模块同名工具面仍可达 40+；按核心短名单再收一轮.
        $maxFocus = 20;
        try {
            $maxFocus = max(0, (int) config('ai.plugins.tools.focus_max_count', 20));
        } catch (\Throwable) {
            // 单测无容器时用默认上限.
        }
        if ($maxFocus > 0 && count($focused) > $maxFocus) {
            $focused = self::shrinkFocusedTools($focused, $called, array_keys($keepModules), $maxFocus, $toolModule);
        }

        return $focused;
    }

    /**
     * 客户↔审批复合写双向桥接名单（focus 裁剪时强制保留）.
     *
     * @param array<string, true> $keepModules
     * @param array<int, string> $called
     *
     * @return array<string, true>
     */
    private static function resolveFocusBridgeNames(array $keepModules, array $called): array
    {
        $bridge = [];
        if (isset($keepModules['customer'])) {
            foreach (self::CUSTOMER_APPROVE_BRIDGE_TOOLS as $name) {
                $bridge[$name] = true;
            }
        }
        // CRM 路径以业务 form + process 为主；仅 approve_process_get / 业务 form 触发反向桥接.
        // 裸 approve_form_get 不再塞入全部客户写工具，避免工具面膨胀.
        $hitCrmBridge = false;
        foreach ($called as $name) {
            if ($name === 'approve_process_get'
                || in_array($name, self::CUSTOMER_BIZ_APPROVE_FORM_TOOLS, true)
            ) {
                $hitCrmBridge = true;
                break;
            }
        }
        if ($hitCrmBridge) {
            foreach (self::CUSTOMER_APPROVE_BRIDGE_TOOLS as $name) {
                $bridge[$name] = true;
            }
            foreach (self::APPROVE_CUSTOMER_WRITE_BRIDGE_TOOLS as $name) {
                $bridge[$name] = true;
            }
        }

        return $bridge;
    }

    /**
     * CRM 回款写阶段白名单（供 ReactPhase / 单测）.
     *
     * @return array<int, string>
     */
    public static function crmPaymentPhaseAllowlist(): array
    {
        return ReactPhase::crmPaymentAllowlist();
    }

    /**
     * 半截写纠偏：把客户审批写工具从全量快照补回当前 option.tools.
     *
     * @param array<int, array<string, mixed>> $currentTools
     * @param array<int, array<string, mixed>> $fullTools
     *
     * @return array<int, array<string, mixed>>
     */
    public static function restoreCustomerApproveWriteBridge(array $currentTools, array $fullTools): array
    {
        $need = array_fill_keys(array_merge(
            self::CUSTOMER_APPROVE_BRIDGE_TOOLS,
            self::CUSTOMER_BIZ_APPROVE_FORM_TOOLS,
            self::APPROVE_CUSTOMER_WRITE_BRIDGE_TOOLS
        ), true);
        $have = [];
        foreach ($currentTools as $tool) {
            if (! is_array($tool)) {
                continue;
            }
            $name = (string) ($tool['function']['name'] ?? $tool['name'] ?? '');
            if ($name !== '') {
                $have[$name] = true;
            }
        }
        foreach ($fullTools as $tool) {
            if (! is_array($tool)) {
                continue;
            }
            $name = (string) ($tool['function']['name'] ?? $tool['name'] ?? '');
            if ($name === '' || ! isset($need[$name]) || isset($have[$name])) {
                continue;
            }
            $currentTools[] = $tool;
            $have[$name]    = true;
        }

        return array_values($currentTools);
    }

    /**
     * 硬顶裁剪工具 schema：优先保留身份工具与靠前项（通常为 allowlist 顺序）.
     *
     * @param array<int, array<string, mixed>> $tools
     *
     * @return array<int, array<string, mixed>>
     */
    public static function capToolDefinitions(array $tools, int $maxCount): array
    {
        if ($maxCount <= 0 || count($tools) <= $maxCount) {
            return $tools;
        }

        $identity = array_fill_keys(array_merge(self::IDENTITY_TOOLS, self::META_TOOLS), true);
        $kept     = [];
        $rest     = [];
        foreach ($tools as $tool) {
            if (! is_array($tool)) {
                continue;
            }
            $name = (string) ($tool['function']['name'] ?? $tool['name'] ?? '');
            if ($name !== '' && isset($identity[$name])) {
                $kept[] = $tool;
            } else {
                $rest[] = $tool;
            }
        }
        $room = max(0, $maxCount - count($kept));

        return array_values(array_merge($kept, array_slice($rest, 0, $room)));
    }

    /**
     * @param array<int, array<string, mixed>> $tools
     * @param array<int, string> $called
     * @param array<int, string> $modules
     * @param callable(string): (?string) $toolModule
     *
     * @return array<int, array<string, mixed>>
     */
    private static function shrinkFocusedTools(
        array $tools,
        array $called,
        array $modules,
        int $maxCount,
        callable $toolModule
    ): array {
        $preferred = array_fill_keys($called, true);
        foreach ($modules as $module) {
            foreach (self::moduleCoreFallback((string) $module, true, []) as $name) {
                $preferred[$name] = true;
            }
            foreach (self::writeExtras((string) $module, true) as $name) {
                $preferred[$name] = true;
            }
        }
        // shrink 时优先保留客户业务表单/写工具与 process 桥接.
        if (in_array('approve', $modules, true) || in_array('customer', $modules, true)) {
            foreach (array_merge(
                self::CUSTOMER_APPROVE_BRIDGE_TOOLS,
                self::CUSTOMER_BIZ_APPROVE_FORM_TOOLS,
                self::APPROVE_CUSTOMER_WRITE_BRIDGE_TOOLS
            ) as $name) {
                $preferred[$name] = true;
            }
        }
        foreach (self::IDENTITY_TOOLS as $name) {
            $preferred[$name] = true;
        }

        $primary   = [];
        $secondary = [];
        foreach ($tools as $tool) {
            $name = (string) ($tool['function']['name'] ?? $tool['name'] ?? '');
            if ($name !== '' && isset($preferred[$name])) {
                $primary[] = $tool;
            } else {
                $secondary[] = $tool;
            }
        }
        $merged = array_merge($primary, $secondary);

        return self::capToolDefinitions($merged, $maxCount);
    }

    /**
     * 模块能力未命中时的安全短名单（与 registry 求交）；宁少勿倾倒.
     *
     * @param array<int, string> $availableModuleTools
     *
     * @return array<int, string>
     */
    public static function moduleCoreFallback(string $module, bool $isWrite, array $availableModuleTools): array
    {
        $preferred = match ($module) {
            'customer' => $isWrite
                ? [
                    'customer_list', 'customer_detail', 'customer_create', 'contact_create', 'lead_create',
                    'opportunity_create', 'opportunity_list', 'order_create', 'order_list',
                    'warehouse_list', 'product_list', 'bill_statistics',
                    'customer_biz_form_get', 'approve_process_get',
                    'payment_create', 'renewal_create', 'expense_create', 'invoice_apply', 'invoice_void_apply',
                    'follow_up_create',
                ]
                : [
                    'customer_list', 'customer_detail', 'order_list', 'order_statistics',
                    'bill_list', 'bill_statistics', 'contract_list', 'finance_trend', 'finance_rank_analysis',
                ],
            'schedule' => [
                'schedule_list', 'schedule_type_list', 'schedule_work_summary',
                'schedule_create', 'schedule_batch_create', 'schedule_complete',
            ],
            'approve' => [
                'approve_my_list', 'approve_pending_list', 'approve_type_list',
                'approve_holiday_type_list', 'approve_form_get', 'approve_process_get', 'approve_create',
            ],
            'attendance' => [
                'attendance_statistics', 'attendance_summary', 'attendance_clock_list',
                'attendance_schedule_list', 'attendance_apply_list',
            ],
            'report' => [
                'report_list', 'report_statistics', 'report_create', 'report_update', 'report_detail',
                'report_submit_statistics', 'schedule_work_summary', 'crm_activity_summary',
            ],
            'assess' => [
                'assess_list', 'assess_detail', 'assess_statistics', 'assess_trend', 'assess_self_eval',
            ],
            default => [],
        };

        if ($availableModuleTools === []) {
            return $preferred;
        }

        $available = array_fill_keys($availableModuleTools, true);
        $hit       = [];
        foreach ($preferred as $name) {
            if (isset($available[$name])) {
                $hit[] = $name;
            }
        }
        if ($hit !== []) {
            return $hit;
        }

        // 命名对不上时最多取 12 个，绝不全集.
        return array_slice($availableModuleTools, 0, 12);
    }

    /**
     * @return array<int, string>
     */
    private static function writeExtras(string $module, bool $isWrite): array
    {
        if (! $isWrite) {
            return [];
        }
        if ($module === 'schedule') {
            return [
                'schedule_create', 'schedule_batch_create', 'schedule_type_list',
                'schedule_list', 'schedule_work_summary', 'write_form_get',
            ];
        }
        if ($module === 'approve' || $module === 'attendance') {
            return [
                'approve_type_list', 'approve_holiday_type_list', 'approve_form_get',
                'approve_process_get', 'approve_create', 'write_form_get',
            ];
        }
        if ($module === 'report') {
            return ['report_create', 'schedule_work_summary', 'crm_activity_summary', 'write_form_get'];
        }
        if ($module === 'customer') {
            return [
                'write_form_get', 'custom_form_get',
                'customer_create', 'contact_create', 'lead_create', 'opportunity_create', 'order_create',
                'customer_list', 'customer_detail', 'opportunity_list', 'warehouse_list', 'product_list',
                'customer_biz_form_get', 'approve_process_get',
                'payment_create', 'renewal_create', 'expense_create', 'invoice_apply', 'invoice_void_apply',
                'follow_up_create', 'follow_up_update',
            ];
        }
        if ($module === 'assess') {
            return [
                'assess_list', 'assess_detail', 'assess_self_eval',
                'schedule_batch_create', 'schedule_work_summary',
            ];
        }

        return [];
    }
}
