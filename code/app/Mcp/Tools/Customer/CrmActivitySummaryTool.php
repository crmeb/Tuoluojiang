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

namespace App\Mcp\Tools\Customer;

use App\Http\Service\Customer\CrmActivitySummaryService;
use App\Mcp\Tools\Abstract\BaseTool;

/**
 * CRM 活动汇总 MCP 读工具：按日/周/月聚合客户模块全业务动态，供 AI 组装汇报.
 */
class CrmActivitySummaryTool extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return '按日/周/月汇总我的客户模块动态：客户、线索、商机、订单、合同、发票、回款、续费、支出、联系人及跟进。'
            . '用于组装日报/周报/月报。period 必填：day|week|month；无客户权限时返回空数据并标注 skipped_modules。';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['period'],
            'properties' => [
                'period' => [
                    'type'        => 'string',
                    'description' => '汇总周期：day=日，week=周，month=月',
                    'enum'        => ['day', 'week', 'month'],
                ],
                'date' => [
                    'type'        => 'string',
                    'description' => '锚点日期 Y-m-d，默认今天',
                ],
            ],
        ];
    }

    /**
     * 汇总当前 MCP 用户的 CRM 动态.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array
    {
        $period = strtolower(trim((string) ($arguments['period'] ?? '')));
        if (! in_array($period, ['day', 'week', 'month'], true)) {
            return [
                'error'   => true,
                'message' => 'period 必须为 day、week 或 month',
                'field'   => 'period',
            ];
        }

        $date = trim((string) ($arguments['date'] ?? ''));

        return app(CrmActivitySummaryService::class)->summarize(
            $this->getUserDbId(),
            $period,
            $date === '' ? null : $date
        );
    }
}
