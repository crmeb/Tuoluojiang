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

namespace App\Http\Controller\AdminApi\Frame;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\FrameRequest;
use App\Http\Service\Frame\FrameService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 组织架构
 * Class FrameController.
 */
#[Prefix('ent/config/frame')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取组织结构列表接口',
    'create'  => '获取组织结构创建接口',
    'store'   => '保存组织结构接口',
    'edit'    => '获取修改组织结构表单接口',
    'update'  => '修改组织结构接口',
    'destroy' => '删除组织结构接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class FrameController extends AuthController
{
    public function __construct(FrameService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取部门列表(树形).
     */
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['is_show', 1],
            ['entid', 1],
        ]);
        $field = ['pid', 'path', 'id as value', 'name as label', 'user_count', 'user_single_count'];
        $sort  = ['level', 'sort'];
        return $this->success($this->service->getDepartmentTreeList($where, $field, $sort));
    }

    /**
     * 获取tree型组织架构数据.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('tree', '获取tree型组织架构数据')]
    public function getTreeFrame()
    {
        $withRole = (bool) $this->request->get('role', 0);
        $isScope  = (bool) $this->request->get('scope', 0);
        return $this->success($this->service->getTree(auth('admin')->id(), $withRole, $isScope));
    }

    /**
     * 获取tree型组织架构数据.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('user', '组织架构tree型人员')]
    public function getTreeUser()
    {
        [$withRole, $leave] = $this->request->getMore([
            ['role', 0],
            ['leave', 0],
        ], true);
        return $this->success($this->service->getUserTree($this->uuid, $this->entId, (bool) $withRole, (bool) $leave));
    }

    /**
     * 获取创建组织架构数据.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function create()
    {
        return $this->success($this->service->getInfo());
    }

    /**
     * 创建组织部门.
     * @throws ValidationException
     */
    public function store(FrameRequest $request): mixed
    {
        $request->scene('create')->check();
        $data = $this->request->postMore([
            ['pid', 0],
            ['name', ''],
            ['path', []],
            ['introduce', ''],
            ['sort', 0],
            ['role_id', 0],
            ['entid', 1],
        ]);
        $this->service->createDepartment($data);
        return $this->success('保存成功');
    }

    /**
     * 获取创建组织架构数据.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function edit($id)
    {
        return $this->success($this->service->getInfo((int) $id));
    }

    /**
     * 创建组织部门.
     * @param mixed $id
     * @throws ValidationException
     */
    public function update(FrameRequest $request, $id): mixed
    {
        $request->scene('update')->check();
        $data = $this->request->postMore([
            ['pid', 0],
            ['name', ''],
            ['path', []],
            ['introduce', ''],
            ['sort', 0],
            ['role_id', 0],
            ['entid', 1],
        ]);
        $this->service->updateDepartment(['id' => $id, 'entid' => $this->entId], $data);
        return $this->success('修改成功');
    }

    /**
     * 获取组织部门信息.
     * @param mixed $id
     * @return mixed
     */
    public function info($id)
    {
        $where = [
            'id'    => $id,
            'entid' => $this->entId,
        ];
        return $this->success($this->service->getDepartmentInfo($where));
    }

    /**
     * 删除部门.
     * @param mixed $id
     * @return mixed
     */
    public function destroy($id)
    {
        $this->service->deleteDepartment((int) $id);
        return $this->success('删除成功');
    }

    /**
     * 获取部门人员列表.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('users/{frameId}', '获取部门人员列表')]
    public function getFrameUser($frameId)
    {
        return $this->success($this->service->getFrameUser($frameId, $this->entId));
    }

    /**
     * 获取当前部门及管理范围部门.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('scope', '获取当前部门及管理范围部门')]
    public function scopeFrames(): mixed
    {
        return $this->success($this->service->getAllSubFrames($this->uuid, $this->entId, false, true));
    }
}
