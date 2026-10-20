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

namespace App\Http\Service\Approve;

use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Crud\SystemCrudApproveRuleService;

/**
 * 审批异常处理.
 *
 * 发起时：部门主管/连续多级找不到负责人。
 * 审批中：待审人已离职或账号停用。
 * 节点配置「为空直接跳过」仅作用于发起时空主管节点。
 * abnormal=0 自动同意；abnormal>0 转交指定在职人员（无效则回退自动同意）。
 */
class ApproveAbnormalService
{
    public const ACTION_AUTO_AGREE = 'auto_agree';

    public const ACTION_TRANSFER = 'transfer';

    /**
     * 读取异常处理配置及可用的指定处理人.
     *
     * @return array{abnormal: int, handler: array<string, int|string>|null}
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function loadContext(int $approveId, bool $isCrud = false): array
    {
        $ruleService = $isCrud
            ? app()->get(SystemCrudApproveRuleService::class)
            : app()->get(ApproveRuleService::class);
        $abnormal = (int) ($ruleService->value(['approve_id' => $approveId], 'abnormal') ?? 0);

        return [
            'abnormal' => $abnormal,
            'handler'  => $abnormal > 0 ? $this->activeAdmin($abnormal) : null,
        ];
    }

    /**
     * 预览流程：空的异常节点补上指定处理人，自动同意仍保留空节点供前端展示.
     *
     * @param array<int, array<string, mixed>> $process
     * @param array<string, int|string>|null $handler
     * @return array<int, array<string, mixed>>
     */
    public function preview(array $process, int $abnormal, ?array $handler): array
    {
        foreach ($process as $index => $node) {
            if (! is_array($node)) {
                continue;
            }
            $users = $this->sanitizeUsers($node['users'] ?? []);
            if ($this->shouldTransferEmptySupervisor($node, $users, $abnormal, $handler)) {
                $users = [$handler];
            }
            $process[$index]['users'] = $users;
        }

        return $process;
    }

    /**
     * 保存前整理流程：转交写入指定人，自动同意或明确跳过的空节点删除，保证层级连续.
     *
     * @param array<int, array<string, mixed>> $process
     * @param array<string, int|string>|null $handler
     * @return array<int, array<string, mixed>>
     */
    public function prepareForSave(array $process, int $abnormal, ?array $handler): array
    {
        $prepared = [];
        foreach (array_values($process) as $node) {
            if (! is_array($node)) {
                continue;
            }
            $users = $this->sanitizeUsers($node['users'] ?? []);
            if ($this->shouldTransferEmptySupervisor($node, $users, $abnormal, $handler)) {
                $node['users'] = [$handler];
                $prepared[]    = $node;
                continue;
            }
            if ($this->shouldSkipEmptyNode($node, $users)) {
                continue;
            }
            $node['users'] = $users;
            $prepared[]    = $node;
        }

        return $prepared;
    }

    /**
     * 待审人失效时的处置：转交或自动同意.
     *
     * @param array<string, int|string>|null $handler
     * @param array<int, int> $nodePendingUserIds 同节点其余仍待审的 user_id
     */
    public function decideInactiveAction(int $abnormal, ?array $handler, int $inactiveUserId, array $nodePendingUserIds = []): string
    {
        if ($abnormal <= 0 || $handler === null) {
            return self::ACTION_AUTO_AGREE;
        }
        $handlerId = (int) ($handler['id'] ?? 0);
        if ($handlerId <= 0 || $handlerId === $inactiveUserId) {
            return self::ACTION_AUTO_AGREE;
        }
        if (in_array($handlerId, $nodePendingUserIds, true)) {
            return self::ACTION_AUTO_AGREE;
        }

        return self::ACTION_TRANSFER;
    }

    /**
     * 员工是否仍可作为审批人（在职且未离职类型）.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function isActiveApprover(int $adminId): bool
    {
        if ($adminId <= 0) {
            return false;
        }
        $admin = app()->get(AdminService::class)->get($adminId, ['id', 'status']);
        if (! $admin || (int) $admin->status !== 1) {
            return false;
        }
        $type = (int) (app()->get(AdminInfoService::class)->value($adminId, 'type') ?? 0);

        return $type !== 4;
    }

    /**
     * @return array{id: int, uid: string, name: string, avatar: string}|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function activeAdmin(int $adminId): ?array
    {
        if (! $this->isActiveApprover($adminId)) {
            return null;
        }
        $admin = app()->get(AdminService::class)->get($adminId, ['id', 'uid', 'name', 'avatar']);
        if (! $admin) {
            return null;
        }
        $row = $admin->toArray();

        return [
            'id'     => (int) $row['id'],
            'uid'    => (string) ($row['uid'] ?? ''),
            'name'   => (string) ($row['name'] ?? ''),
            'avatar' => (string) ($row['avatar'] ?? ''),
        ];
    }

    /**
     * 去掉空值，以及前端误塞进来的整条审批规则.
     *
     * @param mixed $users
     * @return array<int, array<string, mixed>>
     */
    public function sanitizeUsers(mixed $users): array
    {
        if (! is_array($users)) {
            return [];
        }
        $valid = [];
        foreach ($users as $user) {
            if (! is_array($user) || $this->isRulePlaceholder($user)) {
                continue;
            }
            $id = isset($user['value']) ? (int) $user['value'] : (int) ($user['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $valid[] = $user;
        }

        return $valid;
    }

    /**
     * @param array<string, mixed> $node
     * @param array<int, array<string, mixed>> $users
     * @param array<string, int|string>|null $handler
     */
    private function shouldTransferEmptySupervisor(array $node, array $users, int $abnormal, ?array $handler): bool
    {
        if ($users !== [] || $abnormal <= 0 || $handler === null || ! $this->isSupervisorNode($node)) {
            return false;
        }

        return (int) ($node['no_hander'] ?? 0) !== 2;
    }

    /**
     * @param array<string, mixed> $node
     * @param array<int, array<string, mixed>> $users
     */
    private function shouldSkipEmptyNode(array $node, array $users): bool
    {
        if ($users !== []) {
            return false;
        }
        $types = (int) ($node['types'] ?? 0);
        if ($types === 2) {
            return true;
        }
        if ($types !== 1) {
            return false;
        }
        // 申请人自选允许空着交给前端校验；其余空审批节点直接跳过，避免发起后卡在无人节点
        return (int) ($node['settype'] ?? 0) !== 4;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function isSupervisorNode(array $node): bool
    {
        return (int) ($node['types'] ?? 0) === 1
            && in_array((int) ($node['settype'] ?? 0), [2, 7], true);
    }

    /**
     * 规则对象带 abnormal、approve_id，且没有员工 uid.
     *
     * @param array<string, mixed> $user
     */
    private function isRulePlaceholder(array $user): bool
    {
        return array_key_exists('abnormal', $user)
            && array_key_exists('approve_id', $user)
            && ! array_key_exists('uid', $user);
    }
}
