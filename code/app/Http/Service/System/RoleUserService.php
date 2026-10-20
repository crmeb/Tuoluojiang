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

use App\Http\Dao\Auth\RoleUserDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Dao\BaseDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 角色用户
 * Class RoleUserService.
 * @method BaseDao setDefaultWhere(array $where) 设置默认条件
 * @method RoleUserDao getRoleIds(array $where) 获取角色ID
 */
class RoleUserService extends BaseService
{
    /**
     * RoleUserService constructor.
     */
    public function __construct(RoleUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 角色用户列表.
     * @throws BindingResolutionException
     */
    public function getUserList(array $where): array
    {
        [$page, $limit]    = $this->getPageValue();
        $where['user_ids'] = app()->get(AdminService::class)->column(['status' => 1], 'id');
        $list              = $this->dao->getList($where, ['*'], $page, $limit, 'id', [
            'user' => function ($query) {
                $query->with([
                    'card' => function ($query) {
                        $query->select(['name', 'id', 'position', 'uid', 'status']);
                    }, 'frame', ])->select(['card_id', 'id']);
            }, ]);
        foreach ($list as &$item) {
            if (isset($item['user'])) {
                $item['name']       = $item['user']['card']['name'] ?? '';
                $item['position']   = $item['user']['card']['position'] ?? '';
                $item['frame_name'] = $item['user']['frame']['name'] ?? '';
                unset($item['user']);
            } else {
                $item['name']       = '';
                $item['position']   = '';
                $item['frame_name'] = '';
            }
        }
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 删除管理员成员.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function deleteUser(int $id)
    {
        $user = $this->dao->get($id);
        if (! $user) {
            throw $this->exception('删除的管理员成员不存在!');
        }
        $roleServices = app()->get(RolesService::class);
        $adminService = app()->get(AdminService::class);
        return $this->transaction(function () use ($roleServices, $user, $adminService) {
            $roleServices->dec($user->role_id, 1, 'user_count');
            $roles = $adminService->value($user->user_id, 'roles');
            $roles = is_string($roles) ? json_decode($roles, true) : $roles;
            $roles = array_values(array_filter((array) $roles, fn ($roleId) => (int) $roleId !== (int) $user->role_id));
            $adminService->update($user->user_id, ['roles' => $roles]);
            return $user->delete();
        });
    }
}
