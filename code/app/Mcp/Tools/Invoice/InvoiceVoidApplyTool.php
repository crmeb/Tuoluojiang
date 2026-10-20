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

namespace App\Mcp\Tools\Invoice;

use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesApproveDrivenCreate;

/**
 * 作废/冲红发票（审批模式）：customer_biz_form_get → 本工具.
 */
class InvoiceVoidApplyTool extends BaseTool
{
    use ResolvesApproveDrivenCreate;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '作废/冲红发票（仅审批模式）：customer_biz_form_get → 本工具（form + process）';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['form'],
            'properties' => [
                'eid'         => ['type' => 'integer', 'description' => '客户 ID（预填上下文，可选）'],
                'cid'         => ['type' => 'integer', 'description' => '订单 ID（预填上下文，可选）'],
                'customer_id' => ['type' => 'integer', 'description' => '客户 ID（同 eid）'],
                'contract_id' => ['type' => 'integer', 'description' => '订单 ID（同 cid）'],
                'invoice_id'  => ['type' => 'integer', 'description' => '发票 ID（预填上下文）'],
                'bill_id'     => [
                    'type'        => 'array',
                    'items'       => ['type' => 'integer'],
                    'description' => '关联付款单 ID（可选）',
                ],
                'form'        => [
                    'type'        => 'object',
                    'description' => '审批表单（customer_biz_form_get.fields，键为 field）',
                ],
                'process'     => [
                    'type'        => 'array',
                    'description' => '审批流程：approve_process_get 返回的 list，自选节点需填 users',
                ],
                'link_id'     => ['type' => 'integer', 'description' => '关联业务 ID'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        return $this->executeApprovalDrivenCreate(
            'void_invoice_switch',
            $arguments,
            $this->buildBillApprovalContext($arguments)
        );
    }
}
