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

namespace App\Http\Service\Frame;

use App\Constants\CacheEnum;
use App\Http\Dao\Frame\FrameAssistDao;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\System\RolesService;
use App\Http\Service\System\RoleUserService;
use App\Task\frame\FrameCensusTask;
use App\Http\Service\BaseService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @mixin FrameAssistDao
 */
class FrameAssistService extends BaseService
{
    /**
     * 全部.
     */
    protected string $all = 'all';

    /**
     * 仅本人.
     */
    protected string $self = 'self';

    /**
     * 本部门(含无限下级).
     */
    protected string $department = 'dep';

    /**
     * 直属下级.
     */
    protected string $subordinate = 'sub';

    /**
     * 本人+直属下级.
     */
    protected string $team = 'team';

    public function __construct(FrameAssistDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 批量添加.
     * @param mixed $frameIds
     * @param mixed $superUid
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function batchAdd($frameIds, array $userIds, int $masterId, int $entId = 1, int $isAdmin = 0, $superUid = 0): bool
    {
        if (! is_array($frameIds)) {
            $frameIds = [$frameIds];
        }
        $frameIds = array_values(array_unique(array_filter(array_map('intval', $frameIds))));
        $userIds  = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $masterId = (int) $masterId;
        $isAdmin  = (int) $isAdmin ? 1 : 0;
        if ($masterId && $frameIds && ! in_array($masterId, $frameIds, true)) {
            throw $this->exception('必须选择一个主部门');
        }
        if ($isAdmin && ! $masterId) {
            throw $this->exception('必须选择一个负责部门');
        }
        // 指定新主部门时，清理这些用户的旧主部门标记
        if ($masterId && $userIds) {
            $this->dao->update(['user_id' => $userIds], ['is_mastart' => 0]);
        }
        // 设为负责人时，清理这些用户的旧负责标记，并踢掉目标部门原负责人
        if ($isAdmin && $masterId && $userIds) {
            $this->dao->update(['user_id' => $userIds], ['is_admin' => 0]);
            $this->dao->update(['frame_id' => $masterId, 'is_admin' => 1], ['is_admin' => 0]);
        }
        $data = [];
        foreach ($frameIds as $frameId) {
            foreach ($userIds as $userId) {
                $isMaster = $masterId === $frameId;
                if ($this->dao->exists(['frame_id' => $frameId, 'user_id' => $userId, 'entid' => $entId])) {
                    $this->dao->update(['frame_id' => $frameId, 'user_id' => $userId, 'entid' => $entId], [
                        'is_mastart'   => $isMaster ? 1 : 0,
                        'is_admin'     => $isMaster ? $isAdmin : 0,
                        'superior_uid' => $isAdmin && $isMaster ? (int) $superUid : 0,
                    ]);
                    continue;
                }
                $data[] = [
                    'entid'        => $entId,
                    'frame_id'     => $frameId,
                    'user_id'      => $userId,
                    'is_mastart'   => $isMaster ? 1 : 0,
                    'is_admin'     => $isMaster ? $isAdmin : 0,
                    'superior_uid' => $isAdmin && $isMaster ? (int) $superUid : 0,
                    'created_at'   => now()->toDateTimeString(),
                ];
            }
        }
        return $data ? $this->dao->insert($data) : true;
    }

    /**
     * 设置用户所在部门.
     *
     * 约束：
     * 1. 可加入多个部门，但只能有一个主部门（is_mastart）
     * 2. 开启部门负责人时，只能选择一个负责部门
     * 3. 一个部门同时只能有一个负责人（is_admin）
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setUserFrame(array|int $frameIds, int $userId, int $masterId, bool $isAdmin = false, int $superUid = 0, array|int|string $manageFrameIds = []): bool
    {
        [$frameIds, $masterId, $manageFrameIds] = $this->normalizeUserFrameParams($frameIds, $masterId, $isAdmin, $manageFrameIds);

        $this->dao->forceDelete(['not_frame_id' => $frameIds, 'user_id' => $userId]);
        $this->dao->restore(['frame_id' => $frameIds, 'user_id' => $userId]);
        $res = $this->transaction(function () use ($frameIds, $userId, $masterId, $isAdmin, $superUid, $manageFrameIds) {
            // 先清空该用户旧主部门/负责人标记，避免历史脏数据残留多个主部门
            $this->dao->update(['user_id' => $userId], ['is_mastart' => 0, 'is_admin' => 0]);
            // 负责部门仅允许一个负责人：清掉该部门上其他用户的负责人标记
            if ($isAdmin && $manageFrameIds) {
                $this->dao->update(['frame_id' => $manageFrameIds, 'is_admin' => 1, 'not_user_id' => $userId], ['is_admin' => 0]);
            }
            foreach ($frameIds as $frameId) {
                $this->dao->updateOrCreate(['frame_id' => $frameId, 'user_id' => $userId], [
                    'frame_id'     => $frameId,
                    'user_id'      => $userId,
                    'is_mastart'   => $frameId === $masterId ? 1 : 0,
                    'is_admin'     => in_array($frameId, $manageFrameIds, true) ? 1 : 0,
                    'superior_uid' => $superUid ?: 0,
                ]);
            }
            return true;
        });
        return $res && Task::deliver(new FrameCensusTask());
    }

    /**
     * 规范化并校验用户部门参数.
     *
     * @param array|int $frameIds
     * @param array|int|string $manageFrameIds
     * @return array{0: array<int, int>, 1: int, 2: array<int, int>}
     */
    protected function normalizeUserFrameParams(array|int $frameIds, int $masterId, bool $isAdmin, array|int|string $manageFrameIds): array
    {
        if (! is_array($frameIds)) {
            $frameIds = [$frameIds];
        }
        $frameIds = array_values(array_unique(array_filter(array_map('intval', $frameIds))));
        if (! $frameIds) {
            throw $this->exception('必须选择一个部门');
        }

        $masterId = (int) $masterId;
        if (! $masterId || ! in_array($masterId, $frameIds, true)) {
            throw $this->exception('必须选择一个主部门');
        }

        if (! is_array($manageFrameIds)) {
            $manageFrameIds = $manageFrameIds !== '' && $manageFrameIds !== null ? [(int) $manageFrameIds] : [];
        }
        $manageFrameIds = array_values(array_unique(array_filter(array_map('intval', $manageFrameIds))));

        if ($isAdmin) {
            if (! $manageFrameIds) {
                throw $this->exception('必须选择一个负责部门');
            }
            if (count($manageFrameIds) > 1) {
                throw $this->exception('只能选择一个负责部门');
            }
            if (! in_array($manageFrameIds[0], $frameIds, true)) {
                throw $this->exception('负责部门必须是所属部门之一');
            }
        } else {
            $manageFrameIds = [];
        }

        return [$frameIds, $masterId, $manageFrameIds];
    }

    /**
     * 角色添加部门权限.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function addFrameRole(int $id, int $masterId, int $entId = 1): void
    {
        $frame = app()->get(FrameService::class)->get($masterId, ['id', 'role_id']);
        if (! $frame) {
            throw $this->exception('主部门不存在');
        }
        if ($frame->role_id) {
            $roles = app()->get(RoleUserService::class)->column(['user_id' => $id, 'entid' => $entId], 'role_id');
            if (in_array($frame->role_id, $roles)) {
                return;
            }
            app()->get(RolesService::class)->changeUserRole($entId, $id, array_unique(array_merge($roles, [$frame->role_id])));
        }
    }

    /**
     * 获取某个部门下的所有成员id.
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function frameIdByUserId(array $frameIds, int $entid)
    {
        $userIds = [];
        foreach ($frameIds as $frameId) {
            $userIds = array_merge($userIds, $this->dao->getFrameUserIds($frameId, $entid));
        }
        return array_merge(array_unique($userIds));
    }

    /**
     * 根据部门IDs获取用户IDs（用于数据权限）.
     * @param array $frameIds 部门IDs
     * @param bool $includeResigned 是否包含离职人员
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUserIdsByFrameIds(array $frameIds, bool $includeResigned = false): array
    {
        if (empty($frameIds)) {
            return [];
        }

        $entid = (int) (request()->hasMacro('entId') ? request()->entId() : session('ent_id', 1));
        $userIds = [];
        foreach ($frameIds as $frameId) {
            $userIds = array_merge($userIds, $this->dao->getFrameUserIds($frameId, $entid));
        }
        $userIds = array_unique(array_filter($userIds));

        // 如果不包含离职人员，需要过滤掉离职用户
        if (! $includeResigned && ! empty($userIds)) {
            // 在职人员 type: 1,2,3; 离职人员 type: 4
            $activeTypes = [1, 2, 3];
            $activeUserIds = app(AdminInfoService::class)->column(
                ['id' => $userIds, 'type' => $activeTypes],
                'id'
            );
            $userIds = array_values(array_intersect($userIds, $activeUserIds));
        }

        return array_values($userIds);
    }

    /**
     * 查找当前部门下的所有人数.
     * @param int $frameId 部门ID
     * @param int $entid 企业ID
     * @param mixed $status 状态
     * @param bool $includeResigned 是否包含离职人员
     * @return int
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getUserCount(int $frameId, int $entid = 1, $status = 1, bool $includeResigned = false)
    {
        $frameIds   = app()->get(FrameService::class)->setEntValue($entid)->scopeFrames($frameId);
        $frameIds[] = $frameId;
        $query      = ['entid' => $entid, 'frame_ids' => $frameIds];
        // 根据是否包含离职人员设置类型过滤
        if ($includeResigned) {
            $query['card'] = fn ($q) => $q->whereIn('type', [1, 2, 3, 4])->select(['id', 'type']);
        } else {
            $query['card'] = fn ($q) => $q->whereIn('type', [1, 2, 3])->select(['id', 'type']);
        }
        $uids = $this->dao->select($query, ['*'], [
            'card' => fn ($q) => $q->select(['id', 'type']),
        ])->map(function ($item) {
            return ! is_null($item->card) ? $item['user_id'] : 0;
        })->filter()->all();
        return app()->get(AdminService::class)->count(['ids' => $uids, 'status' => $status]);
    }

    /**
     * 获取用户部门.
     * @param mixed $uuid
     * @param mixed $entid
     * @return null|array|Model
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getUserFrames($uuid, $entid = 1)
    {
        return Cache::tags([CacheEnum::TAG_FRAME])->remember(
            'user_frames' . $entid . '_' . $uuid,
            (int) sys_config('system_cache_ttl', 3600),
            function () use ($uuid) {
                $userId = app()->get(AdminService::class)->value(['uid' => $uuid], 'id');
                return $this->dao->setDefaultSort('is_mastart')->select(['user_id' => $userId], ['*'], [
                    'frame' => fn ($q) => $q->select(['id', 'name']),
                ]);
            }
        );
    }

    /**
     * 获取部门主管
     * @throws BindingResolutionException
     */
    public function getFrameAdminUserId(int $frameId): int
    {
        return intval($this->dao->value(['is_admin' => 1, 'frame_id' => $frameId], 'user_id'));
    }

    /**
     * 获取用户直属下级用户ID.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSubUid(int $uid, bool $normal = true): array
    {
        if ($normal) {
            $dao = $this->dao;
        } else {
            $dao = $this->dao->setTrashed();
        }
        $frameIds = $dao->column(['user_id' => $uid, 'is_admin' => 1], 'frame_id');
        if ($frameIds) {
            $uid1   = $dao->column(['frame_id' => $frameIds, 'is_mastart' => 1, 'is_admin' => 0], 'user_id');
            $notUid = $dao->column(['frame_id' => $frameIds, 'is_mastart' => 1, 'is_admin' => 1, 'not_superior_uid' => $uid], 'user_id');
            $uid1   = array_diff($uid1, $notUid);
        } else {
            $uid1 = [];
        }
        $uid2 = $dao->column(['superior_uid' => $uid], 'user_id');
        $userIds = array_unique(array_merge($uid1, $uid2));
        // 过滤在职用户（type为1、2、3）
        if ($normal) {
            $normalUserIds = app()->get(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id');
            $userIds = array_intersect($userIds, $normalUserIds);
        }
        return $userIds;
    }

    /**
     * 获取权限用户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getScopeUid(int $userId, array|string $scope = '', bool $normal = true): array
    {
        $result = Cache::tags([CacheEnum::TAG_FRAME, CacheEnum::TAG_ROLE])->remember(md5($userId . json_encode($scope) . (int) $normal), (int) sys_config('system_cache_ttl', 3600), function () use ($userId, $scope, $normal) {
            $roleUid = app(RolesService::class)->getDataUids(userId: $userId, normal: $normal);
            switch ($scope) {
                case $this->self:
                    $uid = [$userId];
                    break;
                case $this->department:
                    $info = $this->dao->setTrashed(! $normal)->get(['user_id' => $userId, 'is_mastart' => 1], ['frame_id', 'is_admin', 'entid']);
                    if ($info['is_admin']) {
                        $uid = app(FrameService::class)->scopeUser((int) $info['frame_id'], $normal);
                    } else {
                        $uid = $this->dao->setTrashed(! $normal)->column(['frame_id' => $info['frame_id'], 'is_mastart' => 1, 'is_admin' => 0], 'user_id');
                    }
                    $uid = array_intersect($uid, $roleUid);
                    break;
                case $this->subordinate:
                    $uid = $this->getSubUid($userId, $normal);
                    $uid = array_intersect($uid, $roleUid);
                    break;
                case $this->team:
                    $uid = array_merge($this->getSubUid($userId, $normal), [$userId]);
                    $uid = array_intersect($uid, $roleUid);
                    break;
                case $this->all:
                    $uid = $roleUid;
                    break;
                default:
                    $frameId  = app(FrameService::class)->scopeFrames($scope);
                    $frameUid = $this->dao->setTrashed(! $normal)->column(['frame_id' => $frameId, 'is_mastart' => 1], 'user_id');
                    $uid      = array_intersect($frameUid, $roleUid);
            }
            return json_encode($uid, JSON_UNESCAPED_UNICODE);
        });
        return $result ? json_decode($result, true) : [];
    }
}
