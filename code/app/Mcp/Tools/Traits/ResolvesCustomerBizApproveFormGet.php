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

namespace App\Mcp\Tools\Traits;

use App\Constants\CommonEnum;
use App\Http\Service\Approve\ApproveService;

/**
 * 客户域业务审批表单：sys_config → approve_id → 返回审批表单.
 *
 * 供订单回款/续费/支出、合同签约、开票/冲红等独立 MCP 表单工具复用.
 */
trait ResolvesCustomerBizApproveFormGet
{
    use ResolvesApproveFormSchema;

    abstract protected function configKey(): string;

    abstract protected function bizLabel(): string;

    abstract protected function writeToolName(): string;

    protected function describeCustomerBizApproveForm(): string
    {
        return sprintf(
            '【%s】读取 sys_config.%s 解析审批模板 ID，直接返回可填审批表单 fields（含 field/options）。'
            . '禁止再调 approve_type_list / approve_form_get。'
            . '填完后调用 %s 提交 form（自选审批人时先 approve_process_get）。form 键必须用 fields[].field。',
            $this->bizLabel(),
            $this->configKey(),
            $this->writeToolName()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerBizApproveFormInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'customer_id' => ['type' => 'integer', 'description' => '客户 ID（预填上下文，可选）'],
                'eid'         => ['type' => 'integer', 'description' => '客户 ID（同 customer_id）'],
                'contract_id' => ['type' => 'integer', 'description' => '订单/合同 ID（预填上下文，可选）'],
                'cid'         => ['type' => 'integer', 'description' => '订单 ID（同 contract_id）'],
                'order_id'    => ['type' => 'integer', 'description' => '订单 ID（同 contract_id）'],
                'bill_id'     => [
                    'type'        => 'array',
                    'items'       => ['type' => 'integer'],
                    'description' => '付款单 ID 列表（预填上下文，可选）',
                ],
                'invoice_id'  => ['type' => 'integer', 'description' => '发票 ID（冲红等预填，可选）'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    protected function executeCustomerBizApproveFormGet(array $arguments): array
    {
        $configKey = $this->configKey();
        try {
            [$approveId] = app(ApproveService::class)->getApproveConfig($configKey);
            $approveId   = (int) $approveId;
        } catch (\Throwable $e) {
            return [
                'error'      => true,
                'message'    => $e->getMessage(),
                'config_key' => $configKey,
                'label'      => $this->bizLabel(),
                'hint'       => '请确认已在系统配置中绑定「' . $this->bizLabel() . '」审批模板（含无需审批模板）',
            ];
        }

        if ($approveId <= 0) {
            return [
                'error'      => true,
                'message'    => '未配置「' . $this->bizLabel() . '」审批模板',
                'config_key' => $configKey,
                'label'      => $this->bizLabel(),
                'hint'       => '请在系统配置中设置 ' . $configKey,
            ];
        }

        $context = [
            'customer_id' => (int) ($arguments['customer_id'] ?? $arguments['eid'] ?? 0),
            'contract_id' => (int) ($arguments['contract_id'] ?? $arguments['cid'] ?? $arguments['order_id'] ?? 0),
            'bill_id'     => (array) ($arguments['bill_id'] ?? []),
            'invoice_id'  => (int) ($arguments['invoice_id'] ?? 0),
        ];

        $payload   = $this->fetchMcpApproveForm(
            $approveId,
            $this->getUserDbId(),
            $context,
            CommonEnum::ORIGIN_WEB
        );
        $writeTool = $this->writeToolName();

        return array_merge($payload, [
            'approve_id' => $approveId,
            'config_key' => $configKey,
            'label'      => $this->bizLabel(),
            'write_tool' => $writeTool,
            'hint'       => sprintf(
                '已加载「%s」审批表单（approve_id=%d）。将 fields[].field 作为 form 键填写后调用 %s 提交；'
                . '若审批流含自选节点，先 approve_process_get(approve_id=%d, form) 再提交。',
                $this->bizLabel(),
                $approveId,
                $writeTool,
                $approveId
            ),
        ]);
    }
}
