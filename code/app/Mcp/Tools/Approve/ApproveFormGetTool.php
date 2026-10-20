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

namespace App\Mcp\Tools\Approve;

use App\Constants\CommonEnum;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesApproveFormSchema;

class ApproveFormGetTool extends BaseTool
{
    use ResolvesApproveFormSchema;

    public function getDescription(): string
    {
        return '获取审批动态表单并返回可填字段清单 fields（含 field/symbol/options/示例）。'
            . '用于人事与自定义审批（含报销）取表单；客户回款/开票/续费/订单支出/签约/作废请直接调 customer_biz_form_get。'
            . '人事请假：approve_type_list(types) → approve_holiday_type_list → 本工具 → approve_create。'
            . 'form 的键必须用返回的 field，不是 symbol。';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['approve_id'],
            'properties' => [
                'approve_id'  => ['type' => 'integer', 'description' => '审批模板 ID：人事来自 approve_type_list；客户域请用 customer_biz_form_get 等业务表单工具'],
                'customer_id' => ['type' => 'integer', 'description' => '客户 ID（预填上下文）'],
                'contract_id' => ['type' => 'integer', 'description' => '订单/合同 ID（预填上下文）'],
                'bill_id'     => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '付款单 ID 列表'],
                'invoice_id'  => ['type' => 'integer', 'description' => '发票 ID（预填上下文）'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $approveId = (int) ($arguments['approve_id'] ?? 0);
        if ($approveId <= 0) {
            return ['error' => true, 'message' => '缺少审批类型 ID'];
        }

        $payload = $this->fetchMcpApproveForm(
            $approveId,
            $this->getUserDbId(),
            $this->buildApprovalContextFromArguments($arguments),
            CommonEnum::ORIGIN_WEB
        );

        return array_merge($payload, [
            'approve_id' => $approveId,
            'hint'       => $payload['hint'] ?? '将 fields[].field 作为 form 的键提交给 approve_create。请假示例：holidayType=事假ID；leaveDuration={timeType,dateStart,dateEnd,timeStart,timeEnd,duration}',
        ]);
    }
}
