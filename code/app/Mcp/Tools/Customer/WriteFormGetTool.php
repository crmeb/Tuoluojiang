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

use App\Http\Service\Ai\Agent\AgentWriteFlowDriver;
use App\Mcp\ToolExecutor;
use App\Mcp\Tools\Abstract\BaseTool;

class WriteFormGetTool extends BaseTool
{
    public function getDescription(): string
    {
        return '获取任意 MCP 写工具的必填/可填字段清单 fields，供 Agent 交互式收集参数后提交';
    }

    public function getInputSchema(): array
    {
        $registry = app(AgentWriteFlowDriver::class);
        $enum     = $registry->writeToolNames();
        $toolProp = [
            'type'        => 'string',
            'description' => '写工具名称',
        ];
        if ($enum !== []) {
            $toolProp['enum'] = $enum;
        }

        return [
            'type'       => 'object',
            'required'   => ['tool'],
            'properties' => [
                'tool' => $toolProp,
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $toolName = (string) ($arguments['tool'] ?? '');
        if ($toolName === '') {
            return ['error' => true, 'message' => '缺少 tool 参数'];
        }

        $registry = app(AgentWriteFlowDriver::class);
        $config   = $registry->resolveByToolName($toolName);
        if ($config === null) {
            return ['error' => true, 'message' => '未知写工具：' . $toolName];
        }

        return match ($config['type'] ?? 'schema') {
            'customer_approval' => $this->resolveCustomerApprovalForm($config),
            'personnel_approve' => $this->resolvePersonnelApproveHint(),
            default             => app(AgentWriteFlowDriver::class)->resolveWriteToolSchema($toolName),
        };
    }

    /**
     * 客户审批写工具 → 对应独立业务表单工具（sys_config → 表单）.
     *
     * @var array<string, string>
     */
    /** @var array<string, string> config_key → customer_biz_form_get.biz */
    private const CUSTOMER_APPROVAL_FORM_BIZ = [
        'contract_refund_switch'   => 'payment',
        'contract_renew_switch'    => 'renewal',
        'contract_disburse_switch' => 'expense',
        'invoicing_switch'         => 'invoice_issue',
        'void_invoice_switch'      => 'invoice_void',
        'contract_sign_switch'     => 'contract_sign',
    ];

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function resolveCustomerApprovalForm(array $config): array
    {
        $configKey = (string) ($config['config_key'] ?? '');
        $biz       = self::CUSTOMER_APPROVAL_FORM_BIZ[$configKey] ?? '';
        if ($biz === '') {
            return [
                'error'   => true,
                'message' => '未配置对应审批表单工具',
                'hint'    => '请先配置 ' . $configKey,
            ];
        }

        return app(ToolExecutor::class)->execute('customer_biz_form_get', ['biz' => $biz]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePersonnelApproveHint(): array
    {
        return [
            'error'   => true,
            'message' => '人事审批请使用 approve_type_list → approve_form_get 流程',
            'hint'    => 'approve_type_list → approve_holiday_type_list（请假）→ approve_form_get → approve_process_get → approve_create',
        ];
    }
}
