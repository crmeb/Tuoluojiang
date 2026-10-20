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
use App\Http\Requests\enterprise\attendance\AttendanceShiftRequest;
use App\Http\Service\Attendance\AttendanceShiftService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 考勤班次
 * Class AttendanceShiftController.
 */
#[Prefix('ent/attendance/shift')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '考勤班次列表',
    'store'   => '考勤班次保存',
    'update'  => '考勤班次修改',
    'destroy' => '考勤班次删除',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AttendanceShiftController extends AuthController
{
    /**
     * AttendanceShiftController constructor.
     * @throws \Throwable
     */
    public function __construct(AttendanceShiftService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取列表.
     */
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
            ['group_id', ''],
        ]);
        return $this->success($this->service->getList($where));
    }

    /**
     * 下拉数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('select', '考勤班次下拉数据')]
    public function select(): mixed
    {
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
            ['group_id', ''],
        ]);
        return $this->success($this->service->getSelectList($where));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     */
    public function store(AttendanceShiftRequest $request): mixed
    {
        $data = $request->postMore($this->getRequestFields());
        $res  = $this->service->saveShift($data, auth('admin')->id());
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function update($id, AttendanceShiftRequest $request): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $data = $request->postMore($this->getRequestFields());
        $this->service->updateShift((int) $id, $data);
        return $this->success('common.operation.succ');
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

        $this->service->deleteShift((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 详情.
     */
    #[Get('info/{id}', '考勤班次详情')]
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        return $this->success($this->service->getInfo((int) $id));
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['number', 1],
            ['rest_time', 0],
            ['rest_start', ''],
            ['rest_end', ''],
            ['overtime', ''],
            ['work_time', ''],
            ['color', ''],
            ['number1', []],
            ['number2', []],
            ['rest_start_after', 0],
            ['rest_end_after', 0],
        ];
    }
}
