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

use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesCustomerBizApproveFormGet;

/**
 * 客户域业务审批表单统一入口：biz=payment|renewal|expense|contract_sign|invoice_issue|invoice_void.
 */
class CustomerBizFormGetTool extends BaseTool
{
    use ResolvesCustomerBizApproveFormGet;

    /** @var array<string, array{config: string, label: string, write: string, preview_only?: bool}> */
    private const BIZ_MAP = [
        'payment' => [
            'config' => 'contract_refund_switch',
            'label'  => '订单回款',
            'write'  => 'payment_create',
        ],
        'renewal' => [
            'config' => 'contract_renew_switch',
            'label'  => '订单续费',
            'write'  => 'renewal_create',
        ],
        'expense' => [
            'config' => 'contract_disburse_switch',
            'label'  => '订单支出',
            'write'  => 'expense_create',
        ],
        'contract_sign' => [
            'config'       => 'contract_sign_switch',
            'label'        => '合同签约',
            'write'        => '',
            'preview_only' => true,
        ],
        'invoice_issue' => [
            'config' => 'invoicing_switch',
            'label'  => '开票申请',
            'write'  => 'invoice_apply',
        ],
        'invoice_void' => [
            'config' => 'void_invoice_switch',
            'label'  => '发票作废',
            'write'  => 'invoice_void_apply',
        ],
    ];

    /** @var string */
    private $activeBiz = 'payment';

    public function getDescription(): string
    {
        return '【客户业务审批表单】按 biz 读取 sys_config 解析审批模板并返回 fields。'
            . 'biz=payment|renewal|expense|contract_sign|invoice_issue|invoice_void（expense=客户订单支出，不是员工报销）。'
            . '员工报销请用 approve_type_list(name=报销)。禁止再调 approve_type_list 处理客户域业务。'
            . 'payment→payment_create；renewal→renewal_create；expense→expense_create；'
            . 'invoice_issue→invoice_apply；invoice_void→invoice_void_apply；'
            . 'contract_sign 仅表单预览，完整签约须在管理端发起。自选审批人时先 approve_process_get。';
    }

    public function getInputSchema(): array
    {
        $schema = $this->customerBizApproveFormInputSchema();
        $schema['required'] = array_values(array_unique(array_merge(
            ['biz'],
            (array) ($schema['required'] ?? [])
        )));
        $schema['properties'] = array_merge([
            'biz' => [
                'type'        => 'string',
                'enum'        => array_keys(self::BIZ_MAP),
                'description' => '业务类型：payment=回款，renewal=续费，expense=支出，'
                    . 'contract_sign=签约预览，invoice_issue=开票，invoice_void=作废/冲红',
            ],
        ], $schema['properties'] ?? []);

        return $schema;
    }

    public function execute(array $arguments): array
    {
        $biz = strtolower(trim((string) ($arguments['biz'] ?? '')));
        if ($biz === '' || ! isset(self::BIZ_MAP[$biz])) {
            return [
                'error'   => true,
                'message' => '请指定 biz：' . implode('|', array_keys(self::BIZ_MAP)),
                'field'   => 'biz',
            ];
        }
        $this->activeBiz = $biz;
        $payload         = $this->executeCustomerBizApproveFormGet($arguments);
        if (! empty($payload['error'])) {
            return $payload;
        }
        if (! empty(self::BIZ_MAP[$biz]['preview_only'])) {
            return array_merge($payload, [
                'write_tool'      => '',
                'write_supported' => false,
                'hint'            => sprintf(
                    '已加载「合同签约」审批表单预览（approve_id=%d）。'
                    . '完整签约请在管理端合同模块发起；本工具不支持 Agent 一键签约写入。',
                    (int) ($payload['approve_id'] ?? 0)
                ),
            ]);
        }

        return $payload;
    }

    protected function configKey(): string
    {
        return self::BIZ_MAP[$this->activeBiz]['config'];
    }

    protected function bizLabel(): string
    {
        return self::BIZ_MAP[$this->activeBiz]['label'];
    }

    protected function writeToolName(): string
    {
        return self::BIZ_MAP[$this->activeBiz]['write'];
    }
}
