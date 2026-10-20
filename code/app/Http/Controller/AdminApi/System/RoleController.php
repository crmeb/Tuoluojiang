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

namespace App\Http\Controller\AdminApi\System;

use App\Constants\RuleEnum;
use App\Http\Contract\System\RolesInterface;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Middleware\CheckRuleCompany;
use App\Http\Requests\system\SystemAdminRequest;
use App\Http\Requests\system\SystemRoleRequest;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\System\RolesService;
use App\Http\Service\System\MenusService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 角色管理
 * Class RoleController.
 */
#[Prefix('ent/system/roles')]
#[Resource('/', false, except: ['destroy'], names: [
    'index'  => '角色列表',
    'create' => '创建角色表单',
    'store'  => '保存角色',
    'edit'   => '修改角色表单',
    'show'   => '修改角色状态',
    'update' => '修改角色',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class RoleController extends AuthController
{
    protected ModulePermissionService $permissionService;

    public function __construct(RolesInterface $services, ModulePermissionService $permissionService)
    {
        parent::__construct();
        $this->service           = $services;
        $this->permissionService = $permissionService;
        $this->middleware([AuthAdmin::class, AuthEnterprise::class, CheckRuleCompany::class]);
    }

    /**
     * 获取当前企业下的角色.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['role_name', ''],
            ['entid', 1],
        ]);
        $role = $this->service->getRolesList($where, ['id', 'role_name', 'user_count', 'status', 'module_permissions']);
        return $this->success($role);
    }

    /**
     * 创建获取信息.
     * @return mixed
     */
    public function create()
    {
        return $this->success($this->service->getRoleInfo($this->entId));
    }

    /**
     * 保存数据.
     * @throws ValidationException
     */
    public function store(SystemRoleRequest $request): mixed
    {
        $request->check();
        [$role_name, $rules, $apis, $status, $crud, $modulePermission] = $request->postMore([
            ['role_name', ''],
            ['rules', []],
            ['apis', []],
            ['status', 0],
            ['crud', []],
            ['module_permission', []],
        ], true);
        $roleId = $this->service->saveRole($role_name, $rules, $apis, (int) $status, $modulePermission, $crud);
        if (! $roleId) {
            return $this->fail('common.insert.fail');
        }
        return $this->success('common.insert.succ');
    }

    /**
     * 角色启用/禁用.
     * @param mixed $id
     * @return mixed
     */
    public function show($id)
    {
        if ($this->service->changeRole($this->entId, (int) $id, (int) $this->request->get('status', 1))) {
            return $this->success('common.update.succ');
        }
        return $this->fail('common.update.fail');
    }

    /**
     * 修改获取数据.
     * @param mixed $id
     * @return mixed
     */
    public function edit($id)
    {
        if (! $id) {
            return $this->fail('角色ID不能为空');
        }
        $roleInfo = $this->service->getRoleInfo($this->entId, (int) $id);
        return $this->success($roleInfo);
    }

    /**
     * 修改数据.
     * @param mixed $id
     * @return mixed
     * @throws ValidationException
     */
    public function update(SystemRoleRequest $request, $id)
    {
        $request->check();
        [$role_name, $rules, $apis, $status, $crud, $modulePermission] = $request->postMore([
            ['role_name', ''],
            ['rules', []],
            ['apis', []],
            ['status', 0],
            ['crud', []],
            ['module_permission', []],
        ], true);
        if (! $id) {
            return $this->fail('修改的角色ID不能为空');
        }
        $this->service->updateRole((int) $id, $role_name, $rules, $apis, (int) $status, $modulePermission, $crud);
        return $this->success('common.update.succ');
    }

    /**
     * 删除.
     * @param mixed $id
     */
    #[Delete('{id}', '删除角色')]
    public function delete($id): mixed
    {
        if (! $id) {
            return $this->fail('删除的角色ID不存在');
        }

        $this->service->deleteRole((int) $id, $this->entId);

        return $this->success('删除成功');
    }

    /**
     * 修改超级角色.
     */
    public function updateSuperRole(RolesService $services): mixed
    {
        $data = $this->request->postMore([
            ['rules', []],
            ['apis', []],
        ]);
        if (! $data['rules']) {
            return $this->fail('至少选择一个权限');
        }
        $services->roleDao->update(['entid' => $this->entId, 'level' => 0, 'type' => RuleEnum::ENTERPRISE_TYPE], $data);
        return $this->success('common.update.succ');
    }

    /**
     * 获取超级角色权限.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSuperRole(MenusService $services): mixed
    {
        [$name] = $this->request->getMore([
            ['menu_name', ''],
        ], true);
        return $this->success($services->getSuperMenus($name, $this->entId));
    }

    /**
     * 获取某个菜单下的权限.
     * @param mixed $pid
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMenusRule(MenusService $services, $pid): mixed
    {
        if (! $pid) {
            return $this->fail('上级id必须填写');
        }
        return $this->success($services->getPidMenusRule((int) $pid));
    }

    /**
     * 获取角色成员.
     */
    #[Get('user/{id}', '获取角色用户列表')]
    public function getRoleUser($id): mixed
    {
        if (! $id) {
            return $this->fail('缺少角色ID');
        }
        return $this->success($this->service->getRoleUser((int) $id, $this->entId));
    }

    /**
     * 获取用户角色.
     * @param mixed $uid
     */
    #[Get('role/{uid}', '获取用户角色列表')]
    public function getUserRole($uid): mixed
    {
        if (! $uid) {
            return $this->fail('缺少用户ID');
        }
        $res = $this->service->getUserRole($this->entId, (int) $uid);
        return $this->success($res);
    }

    /**
     * 修改用户角色.
     */
    #[Post('user', '修改用户角色')]
    public function updateUserRole(): mixed
    {
        [$userId, $roleIds] = $this->request->postMore([
            ['user_id', 0],
            ['role_id', []],
        ], true);
        $res = $this->service->changeUserRole($this->entId, (int) $userId, $roleIds);
        return $res ? $this->success('修改成功') : $this->fail('修改失败');
    }

    /**
     * 添加角色成员.
     * @throws BindingResolutionException
     */
    #[Post('add_user', '角色新增用户')]
    public function addUser(): mixed
    {
        [$userIds, $frameId, $roleId] = $this->request->postMore([
            ['user_id', []],
            ['frame_id', []],
            ['role_id', 0],
        ], true);

        if (! $userIds && ! $frameId) {
            return $this->fail('至少选择一个部门或者一个用户');
        }

        if ($this->service->addRoleUser($this->entId, (int) $roleId, $userIds, $frameId)) {
            return $this->success('添加成员成功');
        }
        return $this->fail('common.insert.fail');
    }

    /**
     * 修改角色成员状态
     */
    #[Post('show_user', '修改角色成员状态')]
    public function showUser(): mixed
    {
        [$userId, $status, $roleId] = $this->request->postMore([
            ['uid', 0],
            ['status', 1],
            ['role_id', 0],
        ], true);
        if ($this->service->changeRoleUser((int) $userId, $this->entId, (int) $roleId, (int) $status)) {
            return $this->success('common.update.succ');
        }
        return $this->fail('common.update.fail');
    }

    /**
     * 删除角色成员.
     */
    #[Post('del_user', '删除角色成员')]
    public function deleteUser(): mixed
    {
        [$uid, $roleId] = $this->request->postMore([
            ['uid', 0],
            ['role_id', 0],
        ], true);
        if (! $uid) {
            return $this->fail('缺少删除角色成员ID');
        }
        if ($this->service->delRoleUser((int) $uid, $this->entId, (int) $roleId)) {
            return $this->success('删除成功');
        }
        return $this->fail('删除失败');
    }

    /**
     * 修改用户密码
     * @return mixed
     * @throws BindingResolutionException
     * @throws ValidationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('pwd', '修改用户密码')]
    public function updateUserPassword(SystemAdminRequest $request, AdminService $userService)
    {
        $request->scene('password')->check();
        [$password, $uid] = $request->postMore([
            ['password', ''],
            ['uid', ''],
        ], true);
        $userService->updatePasswordFromUid((string) $uid, $password);
        return $this->success('修改成功');
    }

    /**
     * 获取用户在指定模块的权限.
     */
    #[Get('user/{uid}/module/{module}', '获取用户指定模块权限')]
    public function getUserModulePermission(int $uid, string $module): mixed
    {
        $permission = $this->permissionService->getUserModulePermission($uid, $module);

        return $this->success($permission);
    }
}
