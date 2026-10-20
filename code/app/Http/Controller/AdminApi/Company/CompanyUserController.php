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

namespace App\Http\Controller\AdminApi\Company;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\user\EnterpriseUserCardRequest;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 企业用户.
 */
#[Prefix('ent/user')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CompanyUserController extends AuthController
{
    public function __construct(AdminService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取企业用户列表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Get('list', '组织架构人员列表')]
    public function index()
    {
        $where = $this->request->getMore([
            ['pid', '', 'frame_id'],
            ['name', ''],
            ['status', 1],
            ['types', [1, 2, 3]],
        ]);
        $with = [
            'job'    => fn ($query) => $query->select(['id', 'name']),
            'frames' => fn ($query) => $query->orderBy('frame_assist.is_admin', 'desc')
                ->orderByDesc('frame_assist.is_mastart')
                ->select(['frame.id', 'frame.name', 'frame_assist.is_mastart', 'frame_assist.is_admin']),
        ];
        return $this->success($this->service->getListOrderAdmin($where, ['is_admin', 'frame_assist.is_admin', 'id' => 'asc'], $with));
    }

    /**
     * 组织架构成员信息.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('card/{id}', '组织架构成员信息')]
    public function editUser($id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        return $this->success($this->service->editAdminFrame((int) $id));
    }

    /**
     * 修改组织架构成员.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidationException
     * @throws \ReflectionException
     */
    #[Put('card/{id}', '修改组织架构成员')]
    public function updateUser(EnterpriseUserCardRequest $request, $id)
    {
        $request->scene('edit')->check();
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $data = $request->postMore([
            ['frame_id', []],
            ['mastart_id', 0],
            ['name', ''],
            ['position', ''],
            ['phone', ''],
            ['is_admin', 0],
            ['superior_uid', 0],
            ['frames', []],
            ['manage_frame', [], 'manage_frames'],
            ['cards', []],
        ]);
        $this->service->saveAdminFrame((int) $id, $data);
        return $this->success('common.update.succ');
    }

    /**
     * 获取通讯录组织架构.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('add_book/tree', '通讯录tree型数据')]
    public function getFrameTree(FrameService $services)
    {
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
        ]);
        return $this->success($services->tree($where));
    }

    /**
     * 通讯录用户列表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Get('add_book/list', '通讯录用户列表')]
    public function addressBook()
    {
        $where = $this->request->getMore([
            ['frame_id', 0],
            ['entid', 1],
            ['time', ''],
            ['sex', ''],
            ['types', [1, 2, 3]],
            ['field', ''],
            ['status', ''],
            ['search', '', 'name'],
        ]);
        $with = [
            'frames' => fn ($query) => $query->select(['frame.id', 'frame.name', 'frame_assist.is_mastart', 'frame_assist.is_admin']),
            'job'    => fn ($query) => $query->select(['id', 'name']),
            'info'   => fn ($query) => $query->select(['uid', 'email']),
        ];
        $data = $this->service->getListOrderAdmin($where, ['is_admin', 'frame_assist.is_admin', 'id' => 'asc'], $with);
        return $this->success($data);
    }
}
