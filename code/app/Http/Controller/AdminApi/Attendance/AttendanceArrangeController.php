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
use App\Http\Requests\enterprise\attendance\AttendanceArrangeRequest;
use App\Http\Service\Attendance\AttendanceArrangeService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 考勤排班
 * Class AttendanceArrangeController.
 */
#[Prefix('ent/attendance/arrange')]
#[Resource('/', false, except: ['show', 'create', 'edit', 'destroy'], names: [
    'index'  => '考勤排班列表',
    'store'  => '考勤排班保存',
    'update' => '考勤排班修改',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AttendanceArrangeController extends AuthController
{
    public function __construct(AttendanceArrangeService $services)
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
            ['name', ''],
            ['time', '', 'attend_date'],
        ]);

        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws ValidationException
     * @throws \ReflectionException
     */
    public function store(AttendanceArrangeRequest $request): mixed
    {
        $request->scene('__FUNCTION__')->check();
        $data = $request->postMore($this->getRequestFields());
        $this->service->saveArrange($data, auth('admin')->id());
        return $this->success('common.insert.succ');
    }

    /**
     * 修改.
     * @param mixed $groupId
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function update($groupId): mixed
    {
        if (! $groupId) {
            return $this->fail(__('common.empty.attrs'));
        }

        [$date, $data] = $this->request->postMore([
            ['date', ''],
            ['data', []],
        ], true);
        $this->service->updateArrange((int) $groupId, $date, $data, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 排班详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('info/{group_id}', '考勤排班详情')]
    public function info($groupId): mixed
    {
        if (! $groupId) {
            return $this->fail(__('common.empty.attrs'));
        }

        [$name, $date] = $this->request->getMore([
            ['name', ''],
            ['date', ''],
        ], true);

        return $this->success($this->service->getInfo((int) $groupId, $name, $date));
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['date', ''],
            ['groups', []],
        ];
    }
}
