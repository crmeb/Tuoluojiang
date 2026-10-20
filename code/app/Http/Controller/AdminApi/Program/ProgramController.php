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

namespace App\Http\Controller\AdminApi\Program;

use App\Constants\ModuleEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\program\ProgramRequest;
use App\Http\Service\Program\ProgramService;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 项目.
 * Class ProgramController.
 */
#[Prefix('ent/program')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '项目列表',
    'store'   => '项目保存',
    'update'  => '项目修改',
    'destroy' => '项目删除',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ProgramController extends AuthController
{
    use SearchTrait;

    public function __construct(ProgramService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function index(): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::PROGRAM);
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
            ['types', ''],
            ['uid', []],
            ['eid', ''],
            ['cid', ''],
            ['admins', '', 'admin_uids'],
            ['status', ''],
            ['admin_uid', auth('admin')->id()],
        ]);

        $field = ['id', 'name', 'ident', 'uid', 'eid', 'cid', 'creator_uid', 'start_date', 'end_date', 'status', 'created_at'];
        return $this->success($this->service->getList($where, $field));
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('select', '项目下拉列表')]
    public function select(): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::PROGRAM);
        $where = $this->request->getMore([
            ['types', ''],
            ['uid', []],
            ['admin_uid', auth('admin')->id()],
        ]);

        return $this->success($this->service->getSelect($where));
    }

    /**
     * 成员列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('members', '项目成员列表')]
    public function members(): mixed
    {
        $where = $this->request->getMore([
            ['program_id', ''],
        ]);
        return $this->success($this->service->getMemberList($where));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function store(ProgramRequest $request): mixed
    {
        $res = $this->service->saveProgram($request->postMore($this->getRequestFields()));
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function update($id, ProgramRequest $request): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $this->service->updateProgram($request->postMore($this->getRequestFields()), (int) $id);
        return $this->success('common.update.succ');
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '项目详情')]
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        return $this->success($this->service->getInfo((int) $id));
    }

    /**
     * 删除.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $this->service->deleteProgram((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['uid', 0],
            ['eid', 0],
            ['cid', 0],
            ['status', 0],
            ['describe', ''],
            ['members', []],
            ['start_date', null],
            ['end_date', null],
        ];
    }
}
