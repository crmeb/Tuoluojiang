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

use App\Http\Service\Approve\ApproveProcessService;

trait ResolvesApproveProcess
{
    /**
     * 根据表单计算审批流程，并补充 MCP 自选人员元数据.
     *
     * @return array{list: array<int, array<string, mixed>>, rules: array<string, mixed>, needs_selection: bool, selection_nodes: array<int, array<string, mixed>>, hint: string}|array{error: true, message: string, hint?: string}
     */
    protected function fetchMcpApproveProcess(int $approveId, array $form, int $uid): array
    {
        try {
            $verifyResult = app(ApproveProcessService::class)->verifyForm($form, $approveId, $uid);
        } catch (\Throwable $e) {
            return [
                'error'   => true,
                'message' => $e->getMessage(),
                'hint'    => '请确认已登录 MCP 且当前用户对该审批模板有发起权限',
            ];
        }

        return $this->buildMcpProcessPayload(is_array($verifyResult) ? $verifyResult : []);
    }

    /**
     * 提交前解析/校验 process；存在未选人员的自选节点时返回待选信息.
     *
     * @return array{process?: array<int, array<string, mixed>>, error?: true, message?: string, needs_selection?: true, selection_nodes?: array<int, array<string, mixed>>, list?: array<int, array<string, mixed>>, hint?: string}
     */
    protected function resolveApproveProcessForSubmit(int $approveId, array $form, int $uid, array $processArg = []): array
    {
        $payload = $this->fetchMcpApproveProcess($approveId, $form, $uid);
        if (isset($payload['error'])) {
            return $payload;
        }

        $baseList = (array) ($payload['list'] ?? []);
        if ($baseList === []) {
            return ['process' => []];
        }

        $process = $processArg === []
            ? $baseList
            : $this->mergeProcessUserSelections($baseList, $processArg);

        if ($error = $this->validateProcessUserSelections($process)) {
            if ($processArg === [] && ! empty($error['needs_selection'])) {
                $error['list']            = $baseList;
                $error['selection_nodes'] = $payload['selection_nodes'] ?? [];
            }

            return $error;
        }

        return ['process' => $process];
    }

    /**
     * @param array{list?: array<int, array<string, mixed>>, rules?: array<string, mixed>}|array<int, mixed> $verifyResult
     * @return array{list: array<int, array<string, mixed>>, rules: array<string, mixed>, needs_selection: bool, selection_nodes: array<int, array<string, mixed>>, hint: string}
     */
    protected function buildMcpProcessPayload(array $verifyResult): array
    {
        if ($verifyResult === [] || ! isset($verifyResult['list'])) {
            return [
                'list'             => [],
                'rules'            => (array) ($verifyResult['rules'] ?? []),
                'needs_selection'  => false,
                'selection_nodes'  => [],
                'hint'             => '当前审批无需配置流程或已免审',
            ];
        }

        $selectionNodes = [];
        $list           = [];
        foreach ((array) $verifyResult['list'] as $node) {
            if (! is_array($node)) {
                continue;
            }
            $normalized = $this->normalizeMcpProcessNode($node);
            $list[]     = $normalized;
            if (! empty($normalized['needs_selection'])) {
                $selectionNodes[] = $normalized;
            }
        }

        $needsSelection = $selectionNodes !== [];
        $hint           = $needsSelection
            ? '存在需自选审批人/抄送人的节点：查看 selection_nodes[].selection.options 中的人员 id，写入对应 uniqued 节点的 users 后作为 process 提交 approve_create'
            : '流程节点人员已自动确定，可直接将 list 作为 process 提交 approve_create';

        if ($needsSelection) {
            $hint .= '；若候选人员列表为空，请先搜索人员获取 id 再填入';
        }

        return [
            'list'            => $list,
            'rules'           => (array) ($verifyResult['rules'] ?? []),
            'needs_selection' => $needsSelection,
            'selection_nodes' => $selectionNodes,
            'hint'            => $hint,
        ];
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    protected function normalizeMcpProcessNode(array $node): array
    {
        $selectionMeta = $this->describeProcessNodeSelection($node);
        $normalized    = array_merge($node, [
            'types_label' => $this->getProcessNodeTypesLabel((int) ($node['types'] ?? 0)),
            'users'       => $this->normalizeProcessUsersForSubmit($node['users'] ?? [], $node['options'] ?? []),
        ]);

        if ($selectionMeta['needs_selection']) {
            $normalized['needs_selection'] = true;
            $normalized['selection']       = $selectionMeta;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $node
     * @return array{needs_selection: bool, required: bool, mode: string, select_mode: int, select_mode_label: string, select_range: int, range_label: string, options: array<int, array<string, mixed>>, preset_users: array<int, array<string, mixed>>, hint: string, title: string, uniqued: string}
     */
    protected function describeProcessNodeSelection(array $node): array
    {
        $types       = (int) ($node['types'] ?? 0);
        $settype     = (int) ($node['settype'] ?? 0);
        $selfSelect  = (int) ($node['self_select'] ?? 0);
        $selectMode  = (int) ($node['select_mode'] ?? 1);
        $selectRange = (int) ($node['select_range'] ?? 0);
        $title       = (string) ($node['title'] ?? '');
        $uniqued     = (string) ($node['uniqued'] ?? '');
        $options     = $this->normalizeProcessUsersForSubmit($node['options'] ?? [], $node['options'] ?? []);
        $presetUsers = $this->normalizeProcessUsersForSubmit($node['users'] ?? [], $options);
        $base        = [
            'needs_selection'    => false,
            'required'           => false,
            'mode'               => '',
            'select_mode'        => $selectMode,
            'select_mode_label'  => $selectMode === 2 ? '多选' : '单选',
            'select_range'       => $selectRange,
            'range_label'        => $selectRange > 1 ? '指定范围' : '全公司',
            'options'            => $options,
            'preset_users'       => $presetUsers,
            'hint'               => '',
            'title'              => $title,
            'uniqued'            => $uniqued,
        ];

        if ($types === 1 && $settype === 4) {
            $base['needs_selection'] = true;
            $base['required']        = $presetUsers === [];
            $base['mode']            = 'approver_self_select';
            $base['hint']            = $options === []
                ? '审批人需自选（' . ($selectMode === 2 ? '可多选' : '单选') . '），候选为空时请先搜索人员再填入 id'
                : '审批人需从候选人员中选择 id（' . ($selectMode === 2 ? '可多选' : '仅单选') . '）';

            return $base;
        }

        if ($types === 2 && $selfSelect === 1) {
            $base['needs_selection'] = true;
            $base['required']        = $presetUsers === [];
            $base['mode']            = 'cc_self_select';
            $base['hint']            = $presetUsers === []
                ? '抄送人需自选（' . ($selectMode === 2 ? '可多选' : '单选') . '），候选为空时请先搜索人员再填入 id'
                : '可在已有抄送人基础上追加（' . ($selectMode === 2 ? '可多选' : '单选') . '）';

            return $base;
        }

        return $base;
    }

    /**
     * @param array<int, array<string, mixed>> $baseList
     * @param array<int, array<string, mixed>> $processArg
     * @return array<int, array<string, mixed>>
     */
    protected function mergeProcessUserSelections(array $baseList, array $processArg): array
    {
        $incomingByUniqued = [];
        foreach ($processArg as $node) {
            if (! is_array($node)) {
                continue;
            }
            $uniqued = (string) ($node['uniqued'] ?? '');
            if ($uniqued !== '') {
                $incomingByUniqued[$uniqued] = $node;
            }
        }

        $merged = [];
        foreach ($baseList as $node) {
            $uniqued = (string) ($node['uniqued'] ?? '');
            if ($uniqued !== '' && isset($incomingByUniqued[$uniqued])) {
                $incoming = $incomingByUniqued[$uniqued];
                if (array_key_exists('users', $incoming)) {
                    $node['users'] = $this->normalizeProcessUsersForSubmit(
                        (array) $incoming['users'],
                        (array) ($node['options'] ?? [])
                    );
                }
            }
            $merged[] = $node;
        }

        return $merged;
    }

    /**
     * @param array<int, array<string, mixed>> $process
     * @return array{error: true, message: string, needs_selection?: true, selection_nodes?: array<int, array<string, mixed>>, hint?: string}|null
     */
    protected function validateProcessUserSelections(array $process): ?array
    {
        $missingNodes = [];
        foreach ($process as $node) {
            if (! is_array($node)) {
                continue;
            }
            $meta  = $this->describeProcessNodeSelection($node);
            $users = $this->normalizeProcessUsersForSubmit($node['users'] ?? [], $node['options'] ?? []);

            if (! $meta['needs_selection'] || ! $meta['required']) {
                continue;
            }
            if ($users === []) {
                $missingNodes[] = [
                    'uniqued'   => $meta['uniqued'],
                    'title'     => $meta['title'],
                    'mode'      => $meta['mode'],
                    'selection' => $meta,
                ];
                continue;
            }
            if ($meta['select_mode'] === 1 && count($users) > 1) {
                return [
                    'error'   => true,
                    'message' => '节点「' . $meta['title'] . '」为单选，只能选 1 名人员',
                    'hint'    => '请仅保留 1 个 users 元素，uniqued=' . $meta['uniqued'],
                ];
            }
        }

        if ($missingNodes === []) {
            return null;
        }

        return [
            'error'           => true,
            'needs_selection' => true,
            'message'         => '存在未选择人员的审批/抄送节点，请先确认后再提交',
            'selection_nodes' => $missingNodes,
            'hint'            => '调用 approve_process_get(form=...) 获取候选人员，选定后将 users 写入 process 对应 uniqued 节点',
        ];
    }

    /**
     * @param array<int, mixed> $users
     * @param array<int, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeProcessUsersForSubmit(array $users, array $options = []): array
    {
        $optionMap = [];
        foreach ($options as $option) {
            $normalized = $this->normalizeMcpProcessUser($option);
            if ($normalized !== []) {
                $optionMap[(int) $normalized['id']] = $normalized;
            }
        }

        $normalizedUsers = [];
        foreach ($users as $user) {
            if (is_numeric($user)) {
                $user = ['id' => (int) $user];
            }
            if (! is_array($user)) {
                continue;
            }

            $item = $this->normalizeMcpProcessUser($user);
            if ($item === []) {
                continue;
            }

            $id = (int) $item['id'];
            if (isset($optionMap[$id])) {
                $item = array_merge(
                    $optionMap[$id],
                    array_filter($item, static fn ($value): bool => $value !== '' && $value !== null)
                );
            }

            $normalizedUsers[$id] = [
                'id'      => $id,
                'user_id' => $id,
                'card_id' => $id,
                'name'    => (string) ($item['name'] ?? ''),
                'uid'     => (string) ($item['uid'] ?? ''),
                'card'    => [
                    'id'     => $id,
                    'uid'    => (string) ($item['uid'] ?? ''),
                    'name'   => (string) ($item['name'] ?? ''),
                    'avatar' => (string) ($item['avatar'] ?? ''),
                ],
            ];
        }

        return array_values($normalizedUsers);
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeMcpProcessUser(mixed $user): array
    {
        if (! is_array($user)) {
            return [];
        }

        $card = is_array($user['card'] ?? null) ? $user['card'] : [];
        $id   = (int) ($user['id'] ?? $user['value'] ?? $user['user_id'] ?? $user['card_id'] ?? $card['id'] ?? 0);
        if ($id <= 0) {
            return [];
        }

        return [
            'id'     => $id,
            'user_id'=> $id,
            'name'   => (string) ($user['name'] ?? $card['name'] ?? ''),
            'uid'    => (string) ($user['uid'] ?? $card['uid'] ?? ''),
            'avatar' => (string) ($user['avatar'] ?? $card['avatar'] ?? ''),
        ];
    }

    protected function getProcessNodeTypesLabel(int $types): string
    {
        return match ($types) {
            1       => '审批人',
            2       => '抄送人',
            default => '流程节点',
        };
    }
}
