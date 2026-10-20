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

use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesApproveFormSchema;
use App\Mcp\Tools\Traits\ResolvesApproveProcess;
use App\Mcp\Tools\Traits\ResolvesApproveTemplate;

class ApproveProcessGetTool extends BaseTool
{
    use ResolvesApproveFormSchema;
    use ResolvesApproveProcess;
    use ResolvesApproveTemplate;

    public function getDescription(): string
    {
        return '根据已填写的审批表单计算审批流程。若存在自选审批人/抄送人节点，返回 selection_nodes 及候选 options。'
            . '客户域：先 customer_biz_form_get 等取表单，再本工具（自选节点），最后 payment_create/invoice_apply 等；人事用 approve_create。';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['form'],
            'properties' => [
                'approve_id' => ['type' => 'integer', 'description' => '审批模板 ID：客户域来自业务表单工具返回的 approve_id；人事来自 approve_type_list'],
                'types'      => ['type' => 'integer', 'description' => '未传 approve_id 时可传业务 types 解析模板'],
                'name'       => ['type' => 'string', 'description' => '配合 types 按名称筛选模板'],
                'form'       => ['type' => 'object', 'description' => '已填写的审批表单，键为业务表单工具 fields[].field'],
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
            return ['error' => true, 'message' => '缺少 approve_id 或 types'];
        }

        // 只读流程计算：放行客户业务审批，供 payment_create 等审批驱动写工具预取流程.
        $resolved = $this->resolveApproveTemplateId($uid, $approveId, $types, $name, true);
        if (isset($resolved['error'])) {
            return $resolved;
        }
        $approveId = (int) $resolved['approve_id'];

        $form = (array) ($arguments['form'] ?? []);
        if ($form === []) {
            return ['error' => true, 'message' => '缺少审批表单数据'];
        }

        $form    = $this->normalizeApproveFormInput($form, $approveId);
        $payload = $this->fetchMcpApproveProcess($approveId, $form, $uid);
        if (isset($payload['error'])) {
            return $payload;
        }

        return array_merge($payload, [
            'approve_id' => $approveId,
        ]);
    }
}
