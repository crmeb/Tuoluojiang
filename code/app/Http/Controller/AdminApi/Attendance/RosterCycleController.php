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

namespace App\Http\Controller\AdminApi\Attendance;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\attendance\RosterCycleRequest;
use App\Http\Service\Attendance\RosterCycleService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 排班周期
 * Class RosterCycleController.
 */
#[Prefix('ent/attendance/cycle')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '考勤周期列表',
    'store'   => '考勤周期保存',
    'update'  => '考勤周期修改',
    'destroy' => '考勤周期删除',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class RosterCycleController extends AuthController
{
    /**
     * RosterCycleController constructor.
     * @throws \Throwable
     */
    public function __construct(RosterCycleService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取列表.
     * @param mixed $groupId
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function index($groupId): mixed
    {
        if (! $groupId) {
            return $this->fail(__('common.empty.attrs'));
        }
        $data = $this->service->getList(['group_id' => $groupId]);
        return $this->success($data);
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function store(RosterCycleRequest $request): mixed
    {
        $data = $request->postMore($this->getRequestFields());
        $res  = $this->service->saveCycle($data, auth('admin')->id());
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function update($id, RosterCycleRequest $request): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $data = $request->postMore($this->getRequestFields());
        $this->service->updateCycle((int) $id, $data, auth('admin')->id());
        return $this->success('保存成功');
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

        $this->service->deleteCycle((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('info/{group_id}/{id}', '考勤周期详情')]
    public function info($groupId, $id): mixed
    {
        if (! $groupId || ! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        return $this->success($this->service->getInfo((int) $groupId, (int) $id));
    }

    /**
     * 获取排班列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('list/{group_id}', '考勤周期排班列表')]
    public function arrangeRosterCycle($groupId): mixed
    {
        if (! $groupId) {
            return $this->fail(__('common.empty.attrs'));
        }

        $data = $this->service->getArrangeRosterCycleList(['group_id' => $groupId]);
        return $this->success($data);
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['group_id', 0],
            ['name', ''],
            ['cycle', 0],
            ['shifts', []],
        ];
    }
}
