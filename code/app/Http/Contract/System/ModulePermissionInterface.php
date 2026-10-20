<?php

declare(strict_types=1);

namespace App\Http\Contract\System;

/**
 * 模块级数据权限服务接口
 */
interface ModulePermissionInterface
{
    /**
     * 获取用户在指定模块的最终权限级别
     * @param int $userId 用户ID
     * @param string $module 模块标识
     * @return int 权限级别
     */
    public function getUserModuleLevel(int $userId, string $module): int;

    /**
     * 获取用户在指定模块的权限详情
     * @param int $userId 用户ID
     * @param string $module 模块标识
     * @return array ['level' => int, 'frame_ids' => [], 'directly' => int]
     */
    public function getUserModulePermission(int $userId, string $module): array;

    /**
     * 获取用户在所有模块的权限
     * @param int $userId 用户ID
     * @return array [module => ['level' => int, 'frame_ids' => [], 'directly' => int]]
     */
    public function getUserAllPermissions(int $userId): array;

    /**
     * 计算用户在指定模块可访问的用户ID列表
     * @param int $userId 用户ID
     * @param string $module 模块标识
     * @param bool $normal 是否只返回在职用户
     * @return array 用户ID列表
     */
    public function getAccessibleUserIds(int $userId, string $module, bool $normal = true): array;

    /**
     * 根据用户在指定内置模块的权限，写入数据权限请求上下文（供 BaseDao / 中间件使用）.
     */
    public function hydrateDataPermissionContext(int $userId, string $module): void;

    /**
     * 设置角色的模块权限
     */
    public function setRoleModulePermission(int $roleId, string $module, int $level, array $frameIds = [], int $directly = 0): bool;

    /**
     * 批量设置角色的模块权限
     * @param int $roleId 角色ID
     * @param array $permissions [module => ['level' => int, 'frame_ids' => [], 'directly' => int]]
     * @return bool
     */
    public function setRoleModulePermissions(int $roleId, array $permissions): bool;

    /**
     * 获取角色的模块权限
     * @param int $roleId 角色ID
     * @param string|null $module 模块标识，null表示获取所有
     * @return array
     */
    public function getRoleModulePermissions(int $roleId, ?string $module = null): array;

    /**
     * 删除角色的模块权限
     * @param int $roleId 角色ID
     * @param string|null $module 模块标识，null表示删除所有
     * @return bool
     */
    public function deleteRoleModulePermission(int $roleId, ?string $module = null): bool;
}
