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

/**
 * CRM 审批写路径轻量契约：半截写判定与 focus 桥接名单（无词面预置）.
 */
final class ReactPhase
{
    public const CRM_PAYMENT_FORM  = 'customer_biz_form_get';
    public const CRM_PAYMENT_WRITE = 'payment_create';

    /**
     * 客户域业务审批表单（半截写前置；不含裸 approve_form_get）.
     * 合并工具后统一为 customer_biz_form_get，仍用本常量集合做 isWritePrepTool 判定.
     *
     * @var array<int, string>
     */
    public const CRM_BIZ_FORM_TOOLS = [
        'customer_biz_form_get',
    ];

    /**
     * 回款竖切工具面（focus 桥接 / 单测）.
     *
     * @return array<int, string>
     */
    public static function crmPaymentAllowlist(): array
    {
        return array_values(array_unique(array_merge(
            McpToolAllowlist::IDENTITY_TOOLS,
            McpToolAllowlist::META_TOOLS,
            [self::CRM_PAYMENT_FORM, 'approve_process_get', self::CRM_PAYMENT_WRITE]
        )));
    }

    /**
     * 是否半截写前置工具（业务 form / process；不含裸 approve_form_get）.
     */
    public static function isWritePrepTool(string $name): bool
    {
        return $name === 'approve_process_get'
            || $name === 'write_form_get'
            || in_array($name, self::CRM_BIZ_FORM_TOOLS, true);
    }
}
