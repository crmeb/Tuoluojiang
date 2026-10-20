<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\traits\service;

use App\Constants\MenuEnum;
use App\Http\Service\System\RolesService;

trait RolesTrait
{
    public function saveMenuRole(array $menuInfo = [], array $oldInfo = [])
    {
        try {
            if ($menuInfo && $oldInfo) {
                $this->processApi($oldInfo, $menuInfo);
            } elseif ($menuInfo) {
                $this->handleNewMenuRole($menuInfo);
            } else {
                $this->handleDelMenuRole($oldInfo);
            }
        } catch (\Exception $e) {
            // 记录日志或回滚操作
            throw new \RuntimeException('Failed to save menu role: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getRolesByPermission($object, $action)
    {
        // 获取所有策略
        $policies = app('enforcer')->getPolicy();
        $roles    = [];
        foreach ($policies as $policy) {
            // 假设策略格式为 [角色, 对象, 操作]
            if ($policy[1] === $object && $policy[2] === $action) {
                $roles[] = $policy[0];
            }
        }
        // 去除重复的角色
        return array_unique($roles);
    }

    private function processApi(array $oldInfo, array $menuInfo, array $roles = [])
    {
        app('enforcer')->deletePermission($oldInfo['uniqued']);
        $roleIds = app()->get(RolesService::class)->column(['rule_api' => $menuInfo['id']], 'id');
        foreach ($roleIds as $roleId) {
            $roles[] = 'role_' . $roleId;
        }
        if ($menuInfo['type'] == MenuEnum::TYPE_MENU) {
            app('enforcer')->addPermissionForUser('role_all', $menuInfo['uniqued'], $menuInfo['type']);
            if ($roles) {
                foreach ($roles as $role) {
                    app('enforcer')->addPermissionForUser($role, $menuInfo['uniqued'], $menuInfo['type']);
                }
            }
        } elseif ($menuInfo['type'] == MenuEnum::TYPE_API) {
            app('enforcer')->addPermissionForUser('role_all', $menuInfo['api'], $menuInfo['methods']);
            if ($roles) {
                foreach ($roles as $role) {
                    app('enforcer')->addPermissionForUser($role, $menuInfo['api'], $menuInfo['methods']);
                }
            }
        } else {
            app('enforcer')->addPermissionForUser('role_all', $menuInfo['uniqued'], $menuInfo['unique_auth']);
            if ($roles) {
                foreach ($roles as $role) {
                    app('enforcer')->addPermissionForUser($role, $menuInfo['uniqued'], $menuInfo['unique_auth']);
                }
            }
        }
    }

    private function handleNewMenuRole(array $menuInfo)
    {
        if ($menuInfo['type'] == MenuEnum::TYPE_MENU) {
            app('enforcer')->addPermissionForUser('role_all', $menuInfo['uniqued'], $menuInfo['type']);
        } elseif ($menuInfo['type'] == MenuEnum::TYPE_API) {
            app('enforcer')->addPermissionForUser('role_all', $menuInfo['api'], $menuInfo['methods']);
        } else {
            app('enforcer')->addPermissionForUser('role_all', $menuInfo['uniqued'], $menuInfo['unique_auth']);
        }
    }

    private function handleDelMenuRole(array $oldInfo)
    {
        if ($oldInfo['type'] == MenuEnum::TYPE_MENU) {
            app('enforcer')->deletePermission($oldInfo['uniqued']);
        } elseif ($oldInfo['type'] == MenuEnum::TYPE_API) {
            app('enforcer')->deletePermission($oldInfo['api']);
        } else {
            app('enforcer')->deletePermission($oldInfo['uniqued']);
        }
    }
}
