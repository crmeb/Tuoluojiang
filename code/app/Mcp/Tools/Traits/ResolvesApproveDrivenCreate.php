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

use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveService;

trait ResolvesApproveDrivenCreate
{
    use ResolvesApproveFormSchema;
    use ResolvesApproveProcess;

    protected function resolveApproveId(string $configKey): int
    {
        // sys_config 非 0 → 绑定模板；为 0 → 无需审批，回落 types+examine=0 模板.
        [$approveId] = app(ApproveService::class)->getApproveConfig($configKey);

        return (int) $approveId;
    }

    /**
     * 审批驱动创建：校验表单、解析流程（含自选审批人/抄送人）后提交.
     *
     * 配置为 0（无需审批）时仍走审批提交，使用 examine=0 模板；不得当作「未开启」拒绝.
     */
    protected function executeApprovalDrivenCreate(string $configKey, array $arguments, array $approvalContext = []): array
    {
        $form = (array) ($arguments['form'] ?? []);
        if ($form === []) {
            return $this->missingApprovalFormMessage($configKey);
        }

        try {
            $approveId = $this->resolveApproveId($configKey);
        } catch (\Throwable $e) {
            return [
                'error'   => true,
                'message' => $e->getMessage(),
                'hint'    => '请确认已配置 ' . $configKey . ' 对应审批模板（含无需审批模板），再调用对应业务表单工具（如 customer_biz_form_get）后提交',
            ];
        }

        $uid         = $this->getUserDbId();
        $formPayload = $this->fetchMcpApproveForm($approveId, $uid, $approvalContext);
        $form        = $this->normalizeApproveFormInput($form, $approveId, $formPayload);
        // 顶层 contract_id/cid/order_id 灌入订单编号等 disabled 预填字段，避免「缺订单编号」死循环.
        $form = $this->fillMissingApproveFormFromContext($form, $formPayload, $arguments);

        if ($error = $this->validateFormAgainstUniques($form, $approveId, $formPayload)) {
            return $error;
        }

        $processResolved = $this->resolveApproveProcessForSubmit(
            $approveId,
            $form,
            $uid,
            (array) ($arguments['process'] ?? [])
        );
        if (isset($processResolved['error'])) {
            return array_merge($processResolved, [
                'approve_id' => $approveId,
                'form'       => $form,
            ]);
        }

        try {
            return $this->submitApproval(
                $form,
                (array) ($processResolved['process'] ?? []),
                $approveId,
                $uid,
                $this->getEntId(),
                (int) ($arguments['link_id'] ?? 0)
            );
        } catch (\Throwable $e) {
            return [
                'error'      => true,
                'message'    => $e->getMessage(),
                'approve_id' => $approveId,
            ];
        }
    }

    /**
     * 客户域账单/开票审批表单预填上下文.
     */
    protected function buildBillApprovalContext(array $arguments): array
    {
        return [
            'customer_id' => (int) ($arguments['eid'] ?? $arguments['customer_id'] ?? 0),
            'contract_id' => (int) ($arguments['cid'] ?? $arguments['contract_id'] ?? $arguments['order_id'] ?? 0),
            'bill_id'     => (array) ($arguments['bill_id'] ?? []),
            'invoice_id'  => (int) ($arguments['invoice_id'] ?? 0),
        ];
    }

    /**
     * 用 approve_form_get 预填值与顶层业务 ID 补齐 form 中空的必填项（如订单编号）.
     *
     * @param array<string, mixed> $form
     * @param array<string, mixed> $formPayload
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    protected function fillMissingApproveFormFromContext(array $form, array $formPayload, array $arguments): array
    {
        $fields     = $this->flattenApproveFormFields($formPayload);
        $contractId = (int) ($arguments['contract_id'] ?? $arguments['cid'] ?? $arguments['order_id'] ?? $arguments['link_id'] ?? 0);
        $customerId = (int) ($arguments['customer_id'] ?? $arguments['eid'] ?? 0);

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = (string) ($field['field'] ?? '');
            if ($key === '') {
                continue;
            }
            $current = $form[$key] ?? null;
            if ($current !== null && $current !== '') {
                continue;
            }
            if (array_key_exists('value', $field) && $field['value'] !== null && $field['value'] !== '') {
                $form[$key] = $field['value'];
                continue;
            }
            $symbol = (string) ($field['symbol'] ?? '');
            if (in_array($symbol, ['contractList', 'contract', 'order', 'orderList'], true) && $contractId > 0) {
                $form[$key] = $contractId;
                continue;
            }
            if (in_array($symbol, ['customer', 'customerList', 'eid'], true) && $customerId > 0) {
                $form[$key] = $customerId;
            }
        }

        return $form;
    }

    protected function submitApproval(array $form, array $process, int $approveId, int $userId, int $entId, int $linkId = 0): array
    {
        $id = app(ApproveApplyService::class)->saveForm(
            $form,
            $process,
            $approveId,
            0,
            $userId,
            $entId,
            $linkId
        );

        return ['id' => (int) $id, 'success' => true, 'approve_id' => $approveId];
    }

    protected function missingApprovalFormMessage(string $configKey): array
    {
        return [
            'error'   => true,
            'message' => '缺少审批表单 form，请按审批流程获取并填写后再提交',
            'hint'    => '流程：先调用对应业务表单工具（如 customer_biz_form_get，配置键 '
                . $configKey . '）填写 form → 自选节点时 approve_process_get → 本工具提交 form + process',
        ];
    }

    /**
     * 客户域账单类 MCP 工具入参（仅审批模式）.
     */
    protected function billApprovalInputSchema(): array
    {
        return [
            'eid'         => ['type' => 'integer', 'description' => '客户 ID（approve_form_get 预填上下文，可选）'],
            'cid'         => ['type' => 'integer', 'description' => '订单 ID（approve_form_get 预填上下文，可选）'],
            'customer_id' => ['type' => 'integer', 'description' => '客户 ID（同 eid）'],
            'contract_id' => ['type' => 'integer', 'description' => '订单 ID（同 cid）'],
            'bill_id'     => [
                'type'        => 'array',
                'items'       => ['type' => 'integer'],
                'description' => '付款单 ID 列表（approve_form_get 预填上下文，可选）',
            ],
            'form'        => [
                'type'        => 'object',
                'description' => '审批表单（approve_form_get.fields，键为 field）',
            ],
            'process'     => [
                'type'        => 'array',
                'description' => '审批流程：approve_process_get 返回的 list，自选节点需填 users',
            ],
            'link_id'     => ['type' => 'integer', 'description' => '关联业务 ID'],
        ];
    }
}
