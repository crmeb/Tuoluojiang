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

use App\Constants\ApproveEnum;
use App\Http\Service\Approve\ApproveApplyService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesApproveFormSchema;
use App\Mcp\Tools\Traits\ResolvesApproveProcess;
use App\Mcp\Tools\Traits\ResolvesApproveTemplate;

class ApproveCreateTool extends BaseTool
{
    use ResolvesApproveFormSchema;
    use ResolvesApproveProcess;
    use ResolvesApproveTemplate;

    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '发起人事审批或自定义审批（含员工报销）。流程：approve_type_list → approve_form_get → approve_process_get → 本工具。'
            . '员工报销：approve_type_list(name=报销) → 本链路；禁止改走客户订单支出 expense_create。'
            . '客户回款/开票/续费/订单支出禁止用本工具，应走 customer_biz_form_get → payment_create 等；合同签约可用 customer_biz_form_get → 本工具。'
            . 'form 键用 field；process 来自 approve_process_get.list 并填好 users。';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['form'],
            'properties' => [
                'approve_id' => ['type' => 'integer', 'description' => '审批模板 ID（来自 approve_type_list；存在多个同类模板时必填）'],
                'types'      => [
                    'type'        => 'integer',
                    'description' => '未传 approve_id 时按业务类型解析模板；若存在多个同类模板会返回 candidates 待选',
                    'enum'        => [
                        ApproveEnum::PERSONNEL_HOLIDAY,
                        ApproveEnum::PERSONNEL_SIGN,
                        ApproveEnum::PERSONNEL_OVERTIME,
                        ApproveEnum::PERSONNEL_OUT,
                        ApproveEnum::PERSONNEL_TRIP,
                    ],
                ],
                'name'       => ['type' => 'string', 'description' => '配合 types 按名称关键词筛选审批模板'],
                'form'       => [
                    'type'        => 'object',
                    'description' => '审批表单：键=approve_form_get.fields[].field；假期类型值=假期ID；请假时长值为对象 {timeType,dateStart,dateEnd,timeStart,timeEnd,duration}',
                ],
                'process'    => [
                    'type'        => 'array',
                    'description' => '审批流程节点：来自 approve_process_get.list；自选节点需将 users 填入选定人员（id/name）',
                ],
                'link_id'    => ['type' => 'integer', 'description' => '关联业务 ID'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $uid       = $this->getUserDbId();
        $approveId = (int) ($arguments['approve_id'] ?? 0);
        $types     = array_key_exists('types', $arguments) ? (int) $arguments['types'] : null;
        $name      = trim((string) ($arguments['name'] ?? ''));

        if ($approveId <= 0 && $types === null) {
            return [
                'error'   => true,
                'message' => '缺少 approve_id，请先调用 approve_type_list 选择审批模板',
                'hint'    => '若已知业务 types，也可传 types 参数；存在多个同类模板时会返回 candidates',
            ];
        }

        $resolved = $this->resolveApproveTemplateId($uid, $approveId, $types, $name);
        if (isset($resolved['error'])) {
            return $resolved;
        }
        $approveId = (int) $resolved['approve_id'];

        $formPayload = $this->fetchMcpApproveForm($approveId, $this->getUserDbId());
        $form        = $this->normalizeApproveFormInput((array) ($arguments['form'] ?? []), $approveId, $formPayload);
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
            return $processResolved;
        }

        try {
            $id = app(ApproveApplyService::class)->saveForm(
                $form,
                (array) ($processResolved['process'] ?? []),
                $approveId,
                0,
                $this->getUserDbId(),
                $this->getEntId(),
                (int) ($arguments['link_id'] ?? 0)
            );
        } catch (\Throwable $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }

        return ['id' => (int) $id, 'success' => true];
    }
}
