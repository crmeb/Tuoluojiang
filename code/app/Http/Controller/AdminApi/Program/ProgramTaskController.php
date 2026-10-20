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
use App\Http\Requests\enterprise\program\ProgramTaskRequest;
use App\Http\Service\Program\ProgramTaskService;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 项目任务.
 * Class ProgramTaskController.
 */
#[Prefix('ent/program_task')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '项目任务列表',
    'store'   => '项目任务保存',
    'update'  => '项目任务修改',
    'destroy' => '项目任务删除',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ProgramTaskController extends AuthController
{
    use SearchTrait;

    public function __construct(ProgramTaskService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function index()
    {
        [$types] = $this->request->getMore([
            ['types', ''],
        ], true);

        if ($types < 1) {
            $this->withScopeFrame(module: ModuleEnum::PROGRAM);
        }

        $where = $this->request->getMore([
            ['types', ''],
            ['name', '', 'name_like'],
            ['program_id', ''],
            ['version_id', ''],
            ['uid', ''],
            ['time', ''],
            ['time_field', 'date'],
            ['status', ''],
            ['priority', ''],
            ['admins', ''],
            ['members', ''],
            ['admin_uid', auth('admin')->id()],
        ]);

        if (empty($where['time_field'])) {
            $where['time'] = '';
        }
        $field = [
            'id', 'name', 'program_id', 'version_id', 'ident', 'level', 'pid', 'creator_uid', 'uid', 'creator_uid',
            'status', 'priority', 'plan_start', 'plan_end', 'sort', 'created_at', 'updated_at',
        ];

        $with = ['admins', 'members', 'program', 'version', 'creator'];
        return $this->success($this->service->getList($where, $field, 'sort', $with));
    }

    /**
     * 保存.
     */
    public function store(ProgramTaskRequest $request): mixed
    {
        $res = $this->service->saveTask($request->postMore($this->getRequestFields()), auth('admin')->id());
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 保存下级任务
     */
    #[Post('subordinate', '项目任务保存下级')]
    public function subordinateStore(ProgramTaskRequest $request): mixed
    {
        $res = $this->service->saveSubordinateTask($request->postMore([
            ['pid', 0],
            ['name', ''],
        ]), auth('admin')->id());
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function update($id, ProgramTaskRequest $request): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $data = $request->postMore($this->getRequestFields());
        $this->service->updateTask($data, $request->post('field', ''), (int) $id);
        return $this->success('common.update.succ', tips: 0);
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '项目任务详情')]
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $this->service->deleteTask((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 获取下拉列表.
     */
    #[Get('select', '项目任务下拉列表')]
    public function select(): mixed
    {
        $where = $this->request->getMore([
            ['program_id', ''],
            ['pid', ''],
        ]);
        return $this->success($this->service->getSelectList($where, auth('admin')->id()));
    }

    /**
     * 批量更新.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('batch', '项目任务批量更新')]
    public function batchUpdate(): mixed
    {
        $data = $this->request->postMore([
            ['program_id', ''],
            ['version_id', ''],
            ['pid', ''],
            ['uid', ''],
            ['status', ''],
            ['start_date', ''],
            ['end_date', ''],
            ['data', []],
        ]);

        $this->service->batchUpdate($data, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 批量删除.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('batch_del', '项目任务批量删除')]
    public function batchDel(): mixed
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);

        $this->service->batchDel(array_filter(array_unique($data)), true);
        return $this->success('common.operation.succ');
    }

    /**
     * 排序.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('sort', '项目任务排序')]
    public function sort(): mixed
    {
        [$currentId, $targetId] = $this->request->postMore([
            ['current', 0],
            ['target', 0],
        ], true);

        $this->service->sort((int) $currentId, (int) $targetId);
        return $this->success('common.operation.succ');
    }

    /**
     * 分享详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('share/{ident}', '项目任务分享')]
    public function share($ident): mixed
    {
        if (! $ident) {
            return $this->fail(__('common.empty.attrs'));
        }

        return $this->success($this->service->getShareInfo((string) $ident));
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['pid', 0],
            ['path', []],
            ['name', ''],
            ['uid', 0],
            ['status', 0],
            ['priority', 0],
            ['describe', ''],
            ['members', []],
            ['program_id', 0],
            ['version_id', 0],
            ['plan_start', null],
            ['plan_end', null],
        ];
    }
}
