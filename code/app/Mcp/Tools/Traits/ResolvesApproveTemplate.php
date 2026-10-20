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

use App\Constants\ApproveEnum;
use App\Http\Service\Approve\ApproveService;

trait ResolvesApproveTemplate
{
    /**
     * 获取当前用户可用的审批模板列表.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function listUserApproveTemplates(int $uid, ?int $types = null, string $nameFilter = ''): array
    {
        $list  = app(ApproveService::class)->getSearchList(0, $uid);
        $list  = is_array($list) ? $list : [];
        $items = [];

        foreach ($list as $item) {
            $row = is_array($item) ? $item : (method_exists($item, 'toArray') ? $item->toArray() : (array) $item);
            $id  = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $rowTypes = (int) ($row['types'] ?? 0);
            $name     = (string) ($row['name'] ?? '');

            if ($types !== null && $rowTypes !== $types) {
                continue;
            }
            if ($nameFilter !== '' && ! str_contains($name, $nameFilter)) {
                continue;
            }

            $items[] = $this->formatApproveTemplateRow($row);
        }

        return $items;
    }

    /**
     * 解析审批模板 ID；多个候选且未指定 approve_id 时返回待选列表.
     *
     * $allowCustomerTypes：指定 approve_id 时是否放行客户业务审批（回款/续费/开票等）。
     * 这类模板不在「发起申请」列表里，但用户在发起范围内即可由业务入口发起；仅只读的流程计算应开启.
     *
     * @return array{approve_id?: int, error?: true, message?: string, needs_selection?: true, candidates?: array<int, array<string, mixed>>, hint?: string}
     */
    protected function resolveApproveTemplateId(
        int $uid,
        int $approveId = 0,
        ?int $types = null,
        string $nameFilter = '',
        bool $allowCustomerTypes = false
    ): array {
        if ($approveId > 0) {
            $matched = $this->findApproveTemplateById($uid, $approveId)
                ?? ($allowCustomerTypes ? $this->findCustomerApproveTemplateById($uid, $approveId) : null);
            if ($matched === null) {
                return [
                    'error'   => true,
                    'message' => '指定的审批模板不可用或无发起权限',
                    'hint'    => '请先调用 approve_type_list 查看当前用户可发起的审批模板',
                ];
            }
            if ($types !== null && (int) ($matched['types'] ?? 0) !== $types) {
                return [
                    'error'   => true,
                    'message' => 'approve_id 与 types 不匹配',
                    'hint'    => '请重新选择 approve_type_list 返回的 approve_id',
                ];
            }

            return ['approve_id' => $approveId];
        }

        $candidates = $this->listUserApproveTemplates($uid, $types, $nameFilter);
        if ($candidates === []) {
            $typesLabel = $types !== null ? $this->getApproveTypesLabel($types) : '审批';

            return [
                'error'      => true,
                'message'    => '当前用户没有可用的' . $typesLabel . '模板',
                'candidates' => [],
                'hint'       => '请在「办公审批」中配置模板并确认当前用户有发起权限',
            ];
        }

        if (count($candidates) === 1) {
            return ['approve_id' => (int) $candidates[0]['id']];
        }

        return $this->buildApproveTemplateSelectionResponse($candidates, $types);
    }

    /**
     * 按 types 分组并标记是否存在多个同类模板.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array{groups: array<int, array<string, mixed>>, duplicate_types: array<int, int>}
     */
    protected function groupApproveTemplatesByTypes(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $types = (int) ($item['types'] ?? 0);
            if (! isset($groups[$types])) {
                $groups[$types] = [
                    'types'       => $types,
                    'types_label' => (string) ($item['types_label'] ?? $this->getApproveTypesLabel($types)),
                    'count'       => 0,
                    'items'       => [],
                ];
            }
            ++$groups[$types]['count'];
            $groups[$types]['items'][] = $item;
        }

        foreach ($groups as &$group) {
            $group['needs_selection'] = $group['count'] > 1;
        }
        unset($group);

        $duplicateTypes = array_values(array_map(
            static fn (array $group): int => (int) $group['types'],
            array_filter($groups, static fn (array $group): bool => $group['count'] > 1)
        ));

        return [
            'groups'          => array_values($groups),
            'duplicate_types' => $duplicateTypes,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function formatApproveTemplateRow(array $row): array
    {
        $types = (int) ($row['types'] ?? 0);

        return [
            'id'          => (int) ($row['id'] ?? 0),
            'name'        => (string) ($row['name'] ?? ''),
            'types'       => $types,
            'types_label' => $this->getApproveTypesLabel($types),
            'info'        => (string) ($row['info'] ?? ''),
            'icon'        => (string) ($row['icon'] ?? ''),
            'color'       => (string) ($row['color'] ?? ''),
            'examine'     => (int) ($row['examine'] ?? 0),
        ];
    }

    protected function getApproveTypesLabel(int $types): string
    {
        return match ($types) {
            0                               => '自定义',
            ApproveEnum::PERSONNEL_HOLIDAY  => '请假',
            ApproveEnum::PERSONNEL_SIGN     => '补卡',
            ApproveEnum::PERSONNEL_OVERTIME => '加班',
            ApproveEnum::PERSONNEL_OUT      => '外出',
            ApproveEnum::PERSONNEL_TRIP     => '出差',
            default                         => '其他',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * 按发起范围查找客户业务审批模板（与后台「添加回款」等入口同口径：已启用且用户在发起人范围内）.
     */
    private function findCustomerApproveTemplateById(int $uid, int $approveId): ?array
    {
        $service = app(ApproveService::class);
        if (! in_array($approveId, array_map('intval', (array) $service->getUsableApprove($uid)), true)) {
            return null;
        }
        $row = $service->get($approveId, ['id', 'name', 'types', 'info', 'icon', 'color', 'examine']);
        $row = $row ? $row->toArray() : [];
        if ($row === [] || ! in_array((int) ($row['types'] ?? 0), ApproveEnum::CUSTOMER_TYPES, true)) {
            return null;
        }

        return $this->formatApproveTemplateRow($row);
    }

    private function findApproveTemplateById(int $uid, int $approveId): ?array
    {
        foreach ($this->listUserApproveTemplates($uid) as $item) {
            if ((int) ($item['id'] ?? 0) === $approveId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @return array{error: true, message: string, needs_selection: true, candidates: array<int, array<string, mixed>>, hint: string, types?: int, types_label?: string}
     */
    private function buildApproveTemplateSelectionResponse(array $candidates, ?int $types): array
    {
        $typesLabel = $types !== null
            ? $this->getApproveTypesLabel($types)
            : ($candidates[0]['types_label'] ?? '审批');

        $response = [
            'error'           => true,
            'message'         => '存在多个' . $typesLabel . '审批模板，请选择要发起的 approve_id',
            'needs_selection' => true,
            'candidates'      => $candidates,
            'hint'            => '先调用 approve_type_list 查看候选列表，确认后将 approve_id 传入 approve_create',
        ];

        if ($types !== null) {
            $response['types']       = $types;
            $response['types_label'] = $typesLabel;
        }

        return $response;
    }
}
