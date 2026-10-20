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

namespace App\Http\Contract\System;

/**
 * 企业角色.
 */
interface RolesInterface
{
    /**
     * 获取角色列表分页.
     * @param null $sort
     */
    public function getRolesPageList(array $where, int $page, int $limit, array $field, $sort, array $with): array;

    /**
     * 获取角色列表.
     */
    public function getRolesList(array $where, array $field = ['*'], array|string $sort = 'id', array $with = []): array;

    /**
     * 获取角色信息.
     */
    public function getRoleInfo(int $entId, int $id = 0): array;

    /**
     * 保存角色信息.
     */
    public function saveRole(string $name = '', array $rules = [], array $apis = [], int $status = 1, array $modulePermissions = [], array $crud = []): bool;

    /**
     * 角色启用/禁用.
     */
    public function changeRole(int $entId, int $roleId, int $status): bool;

    /**
     * 修改角色信息.
     */
    public function updateRole(int $id, string $name = '', array $rules = [], array $apis = [], int $status = 1, array $modulePermissions = [], array $crud = []): bool;

    /**
     * 保存角色信息.
     */
    public function saveSystemRole(int $entId, array $rules = [], array $apis = []): bool;

    /**
     * 获取企业超级权限.
     */
    public function getCompanySuperRole(int $entId, bool $withRule = true, bool $withApi = true): array;

    /**
     * 获取初始用户权限.
     * @return mixed
     */
    public function getRolesForInitialUser(bool $withRule = true, bool $withApi = true): array;

    /**
     * 获取初始企业用户权限.
     * @return mixed
     */
    public function getRolesForCompanyUser(bool $withRule = true, bool $withApi = true): array;

    /**
     * 获取企业用户权限.
     * @return mixed
     */
    public function getRolesForUser(string $uuid, int $entId, bool $withRule = true, bool $withApi = true): array;

    /**
     * 删除角色.
     */
    public function deleteRole(int $id, int $entId): bool;

    /**
     * 获取角色用户.
     */
    public function getRoleUser(int $id, int $entId): array;

    /**
     * 获取用户角色.
     */
    public function getUserRole(int $entId, int $userId): array;

    /**
     * 用户修改角色.
     */
    public function changeUserRole(int $entId, int $userId, array $roleIds = []): bool;

    /**
     * 添加角色用户.
     */
    public function addRoleUser(int $entId, int $roleId, array $userIds = [], array $frameIds = []): bool;

    /**
     * 修改角色用户.
     * @param mixed $status
     */
    public function changeRoleUser(int $uid, int $entId, int $roleId, $status): bool;

    /**
     * 删除角色用户.
     */
    public function delRoleUser(int $uid, int $entId, int $roleId): bool;
}
