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

namespace App\Http\Service\System;

use App\Constants\CacheEnum;
use App\Constants\DataPermissionLevelEnum;
use App\Constants\ModuleEnum;
use App\Http\Context\DataPermissionContext;
use App\Http\Contract\System\ModulePermissionInterface;
use App\Http\Dao\Auth\RoleDao;
use App\Http\Dao\Auth\RoleUserDao;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\Cache;

/**
 * 模块级数据权限服务实现
 * Class ModulePermissionService.
 */
class ModulePermissionService extends BaseService implements ModulePermissionInterface
{
    protected RoleDao $roleDao;

    protected RoleUserDao $roleUserDao;

    protected FrameService $frameService;

    protected FrameAssistService $frameAssistService;

    public function __construct(
        RoleDao $roleDao,
        RoleUserDao $roleUserDao,
        FrameService $frameService,
        FrameAssistService $frameAssistService,
    ) {
        $this->roleDao            = $roleDao;
        $this->roleUserDao        = $roleUserDao;
        $this->frameService       = $frameService;
        $this->frameAssistService = $frameAssistService;
    }

    /**
     * 获取用户在指定模块的最终权限级别（多角色取最高）.
     */
    public function getUserModuleLevel(int $userId, string $module): int
    {
        $permission = $this->getUserModulePermission($userId, $module);
        return $permission['data_level'];
    }

    /**
     * 获取用户在指定模块的权限详情（多角色合并）.
     */
    public function getUserModulePermission(int $userId, string $module): array
    {
        $cacheKey = "user_module_perm:{$userId}:{$module}";
        return Cache::tags([CacheEnum::TAG_ROLE])->remember($cacheKey, 300, function () use ($userId, $module) {
            if (app(AdminService::class)->value($userId, 'is_admin')) {
                return [
                    'data_level' => DataPermissionLevelEnum::ALL,
                    'frame_id'   => [],
                    'directly'   => 1,
                ];
            }

            $empty = [
                'data_level' => DataPermissionLevelEnum::NONE,
                'frame_id'   => [],
                'directly'   => 0,
            ];
            $roleIds = $this->roleUserDao->column(['user_id' => $userId, 'status' => 1], 'role_id');
            if (empty($roleIds)) {
                return $empty;
            }
            $roles = $this->roleDao->select(['ids' => $roleIds, 'status' => 1], ['module_permissions']);
            if ($roles->isEmpty()) {
                return $empty;
            }
            $rolesPerms = collect($roles)->map(function ($role) use ($module) {
                $modulePermissions = $role['module_permissions'] ?? [];
                if (isset($modulePermissions[$module])) {
                    return [
                        'data_level' => (int) ($modulePermissions[$module]['data_level'] ?? DataPermissionLevelEnum::SELF),
                        'frame_id'   => $modulePermissions[$module]['frame_id'] ?? [],
                        'directly'   => (int) ($modulePermissions[$module]['directly'] ?? 0),
                    ];
                }
                return null;
            })->filter();
            if ($rolesPerms->isEmpty()) {
                return $empty;
            }

            $maxLevel = $rolesPerms->max(fn ($perm) => $perm['data_level']);

            $mergedFrameIds = $rolesPerms->filter(fn ($perm) => $perm['data_level'] >= $maxLevel && ! empty($perm['frame_id']))
                ->flatMap(fn ($perm) => $perm['frame_id'])->unique()->values()->all();
            $mergedDirectly = $rolesPerms->contains(fn ($perm) => $perm['directly']) ? 1 : 0;
            return [
                'data_level' => $maxLevel,
                'frame_id'   => $mergedFrameIds,
                'directly'   => $mergedDirectly,
            ];
        });
    }

    /**
     * 获取用户在所有模块的权限.
     */
    public function getUserAllPermissions(int $userId): array
    {
        $is_admin = app(AdminService::class)->value($userId, 'is_admin');
        if ($is_admin) {
            return array_map(function () {
                return [
                    'data_level' => DataPermissionLevelEnum::ALL,
                    'frame_id'   => [],
                    'directly'   => 1,
                ];
            }, ModuleEnum::all());
        }
        $result = [];
        foreach (ModuleEnum::all() as $module => $name) {
            $result[$module] = $this->getUserModulePermission($userId, $module);
        }
        return $result;
    }

    /**
     * 计算用户在指定模块可访问的用户ID列表
     * 使用Redis缓存提升性能.
     */
    public function getAccessibleUserIds(int $userId, string $module, bool $normal = true): array
    {
        $permission = $this->getUserModulePermission($userId, $module);

        if ($permission['data_level'] === DataPermissionLevelEnum::NONE) {
            return [];
        }

        if ($permission['data_level'] === DataPermissionLevelEnum::SELF) {
            return [$userId];
        }

        if ($permission['data_level'] === DataPermissionLevelEnum::ALL) {
            $userIds = $normal ? $this->getNormalUserIds($module) : app()->get(AdminService::class)->column([], 'id');
            return $this->withSelfUserId($userIds, $userId);
        }

        // 获取角色ID用于缓存key
        $roleIds = $this->roleUserDao->column(['user_id' => $userId, 'status' => 1], 'role_id');
        if (empty($roleIds)) {
            return [$userId];
        }

        // 使用第一个角色ID作为缓存key的一部分
        $primaryRoleId = (int) $roleIds[0];

        // 尝试从缓存获取
        try {
            $cacheService = app()->get(DataPermissionCacheService::class);
            $cached       = $normal && $module !== ModuleEnum::REPORT ? $cacheService->getAccessibleUserIds($userId, $primaryRoleId, $module) : null;

            if ($cached !== null) {
                return $this->withSelfUserId($cached, $userId);
            }
        } catch (\Exception $e) {
            // 缓存失败，继续正常计算
        }

        // 根据权限级别计算
        switch ($permission['data_level']) {
            case DataPermissionLevelEnum::DEPARTMENT:
                $uids = $this->getDepartmentUserIds($userId, $normal);
                break;
            case DataPermissionLevelEnum::DIRECT_SUBORDINATE:
                $uids = $this->frameAssistService->getSubUid($userId, $normal);
                break;
            case DataPermissionLevelEnum::CUSTOM_DEPARTMENT:
                $uids = $this->frameAssistService->getUserIdsByFrameIds($permission['frame_id'], ! $normal);
                break;
            default:
                $uids = [$userId];
        }

        $uids = array_unique(array_filter($uids));
        if ($normal) {
            $activeUserIds = $this->getNormalUserIds($module);
            $uids          = array_intersect($uids, $activeUserIds);
        }
        $uids = $this->withSelfUserId($uids, $userId);

        // 设置缓存
        try {
            $cacheService = app()->get(DataPermissionCacheService::class);
            if ($normal) {
                $cacheService->setAccessibleUserIds($userId, $primaryRoleId, $module, $uids);
            }
        } catch (\Exception $e) {
            // 缓存失败，不影响正常流程
        }

        return $uids;
    }

    /**
     * 获取用户本部门权限范围内的用户ID.
     *
     * 部门负责人沿用原管理范围逻辑：本部门及下级部门；
     * 普通成员沿用原同部门逻辑：主部门内非负责人用户。
     */
    protected function getDepartmentUserIds(int $userId, bool $normal = true): array
    {
        $info = $this->frameAssistService->setTrashed(! $normal)->get(['user_id' => $userId, 'is_mastart' => 1], ['frame_id', 'is_admin', 'entid']);
        if (! $info) {
            return [$userId];
        }

        if ($info['is_admin']) {
            return $this->frameService->scopeUser((int) $info['frame_id'], $normal);
        }

        return $this->frameAssistService->setTrashed(! $normal)->column(['frame_id' => $info['frame_id'], 'is_mastart' => 1, 'is_admin' => 0], 'user_id');
    }

    /**
     * 可访问数据范围统一包含当前用户本人；NONE 权限在调用处直接返回空，不走这里。
     */
    protected function withSelfUserId(array $uids, int $userId): array
    {
        $uids[] = $userId;
        return array_values(array_unique(array_filter(array_map('intval', $uids))));
    }

    /**
     * {@inheritDoc}
     *
     * 内置模块标识（ModuleEnum）与 CRUD 的 table_name_en 不是同一套；
     * 必须通过本服务计算 uid，不能走 RolesService::getDataUids 的 CRUD 分支，否则会报「无效的模块」.
     */
    public function hydrateDataPermissionContext(int $userId, string $module): void
    {
        $permission = $this->getUserModulePermission($userId, $module);
        $uids       = [];
        if ($permission['data_level'] !== DataPermissionLevelEnum::ALL
            && $permission['data_level'] !== DataPermissionLevelEnum::NONE) {
            $uids = $this->getAccessibleUserIds($userId, $module);
        }

        DataPermissionContext::set(
            $module,
            $uids,
            $permission['data_level'],
            $permission['frame_id'],
            $permission['directly']
        );
    }

    /**
     * 设置角色的模块权限.
     */
    public function setRoleModulePermission(
        int $roleId,
        string $module,
        int $level,
        array $frameIds = [],
        int $directly = 0
    ): bool {
        $role = $this->roleDao->get($roleId);

        if (! $role) {
            throw $this->exception('角色不存在');
        }

        $modulePermissions = $role->module_permissions ?? [];

        $modulePermissions[$module] = [
            'data_level' => $level,
            'frame_id'   => $frameIds,
            'directly'   => $directly,
        ];

        $this->roleDao->update($roleId, ['module_permissions' => $modulePermissions]);

        $this->clearUserPermissionCache($roleId);

        return true;
    }

    /**
     * 批量设置角色的模块权限.
     */
    public function setRoleModulePermissions(int $roleId, array $permissions): bool
    {
        $role = $this->roleDao->get($roleId);

        if (! $role) {
            throw $this->exception('角色不存在');
        }

        $modulePermissions = $role->module_permissions ?? [];
        foreach ($permissions as $module => $perm) {
            $modulePermissions[$module] = [
                'data_level' => $perm['data_level'] ?? DataPermissionLevelEnum::SELF,
                'frame_id'   => $perm['frame_id'] ?? [],
                'directly'   => ! empty($perm['directly']) ? 1 : 0,
            ];
        }

        $this->roleDao->update($roleId, ['module_permissions' => $modulePermissions]);

        $this->clearUserPermissionCache($roleId);

        return true;
    }

    /**
     * 获取角色的模块权限.
     */
    public function getRoleModulePermissions(int $roleId, ?string $module = null): array
    {
        $role = $this->roleDao->get($roleId);

        if (! $role) {
            return [];
        }

        $modulePermissions = $role->module_permissions ?? [];
        $permissions       = collect();
        $modules           = ModuleEnum::all();
        collect(ModuleEnum::getModuleFieldConfig())->each(function ($item, $key) use (&$permissions, $modules, $modulePermissions) {
            $permissions->put($key, [
                'data_level'  => $modulePermissions[$key]['data_level'] ?? DataPermissionLevelEnum::SELF,
                'directly'    => $modulePermissions[$key]['directly'] ?? 0,
                'frame_id'    => $modulePermissions[$key]['frame_id'] ?? [],
                'module_name' => $modules[$key],
            ]);
        });
        if ($module !== null) {
            return $permissions[$module] ?? [
                'data_level' => DataPermissionLevelEnum::SELF,
                'frame_id'   => [],
                'directly'   => 0,
            ];
        }

        return $permissions->all();
    }

    /**
     * 删除角色的模块权限.
     */
    public function deleteRoleModulePermission(int $roleId, ?string $module = null): bool
    {
        $role = $this->roleDao->get($roleId);

        if (! $role) {
            throw $this->exception('角色不存在');
        }

        $modulePermissions = $role->module_permissions ?? [];

        if ($module === null) {
            $modulePermissions = [];
        } else {
            unset($modulePermissions[$module]);
        }

        $this->roleDao->update($roleId, ['module_permissions' => $modulePermissions]);
        $this->clearUserPermissionCache($roleId);

        return true;
    }

    /**
     * 清除角色下所有用户的权限缓存.
     */
    protected function clearUserPermissionCache(int $roleId): void
    {
        $userIds = $this->roleUserDao->column(['role_id' => $roleId], 'user_id');

        foreach ($userIds as $userId) {
            foreach (ModuleEnum::all() as $module => $name) {
                Cache::tags([CacheEnum::TAG_ROLE])->forget("user_module_perm:{$userId}:{$module}");
            }
        }

        // 清除数据权限缓存
        try {
            $cacheService = app()->get(DataPermissionCacheService::class);
            $cacheService->clearRoleCache($roleId);
        } catch (\Exception $e) {
            // 忽略缓存清除错误
        }
    }

    /**
     * 获取当前操作人ID.
     */
    protected function getOperatorId(): int
    {
        return (int) request()->input('user_id', 0);
    }

    /**
     * 获取当前操作人姓名.
     */
    protected function getOperatorName(): string
    {
        return request()->input('user_name', 'system');
    }

    private function getNormalUserIds(string $module): array
    {
        if ($module === ModuleEnum::REPORT) {
            return app()->get(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id');
        }

        return app()->get(AdminService::class)->column(['status' => 1], 'id');
    }
}
