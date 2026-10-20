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

namespace App\Http\Controller\AdminApi\Attendance;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\attendance\AttendancePersonRequest;
use App\Http\Service\Attendance\AttendancePersonnelStatisticsService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 考勤统计
 * Class AttendanceStatisticsController.
 */
#[Prefix('ent/attendance')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AttendanceStatisticsController extends AuthController
{
    /**
     * AttendanceArrangeController constructor.
     * @throws \Throwable
     */
    public function __construct(AttendancePersonnelStatisticsService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 每日统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('daily_statistics', '考勤每日统计')]
    public function dailyStatistics(): mixed
    {
        $where = $this->request->getMore([
            ['scope', 1],
            ['status', '', 'personnel_status'],
            ['frame_id', ''],
            ['group_id', ''],
            ['time', ''],
            ['user_id', []],
        ]);
        if (! $where['time']) {
            $where['time'] = 'today';
        }
        return $this->success($this->service->getDailyStatistics(auth('admin')->id(), $where));
    }

    /**
     * 月度统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('monthly_statistics', '考勤月度统计')]
    public function monthlyStatistics(): mixed
    {
        $where = $this->request->getMore([
            ['scope', 1],
            ['status', '', 'personnel_status'],
            ['frame_id', ''],
            ['group_id', ''],
            ['time', '', 'month'],
            ['user_id', []],
        ]);
        if (! $where['month']) {
            $where['time'] = 'month';
        }

        return $this->success($this->service->getMonthlyStatistics(auth('admin')->id(), $where));
    }

    /**
     * 添加处理记录.
     * @throws BindingResolutionException
     */
    #[Put('statistics/{id}', '考勤添加处理记录')]
    public function update($id, AttendancePersonRequest $request): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }

        $data = $request->postMore([
            ['number', 0],
            ['remark', ''],
            ['status', 0],
            ['location_status', 0],
        ]);

        $this->service->saveStatisticsResult($this->uuid, (int) $id, $data);
        return $this->success('common.update.succ');
    }

    /**
     * 处理记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('statistics/{id}', '考勤处理记录')]
    public function recordList($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        return $this->success($this->service->getRecordList($this->uuid, (int) $id));
    }

    /**
     * 出勤统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('attendance_statistics', '考勤出勤统计')]
    public function attendanceStatistics(): mixed
    {
        [$userId, $time] = $this->request->getMore([
            ['user_id', ''],
            ['time', 'month'],
        ], true);
        return $this->success($this->service->getAttendanceStatistics(auth('admin')->id(), (int) ($userId ?: auth('admin')->id()), $time));
    }

    /**
     * 个人统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('individual_statistics', '考勤个人统计')]
    public function individualStatistics(): mixed
    {
        $where = $this->request->getMore([
            ['status', '', 'personnel_status'],
            ['user_id', '', 'uid'],
            ['time', 'month'],
        ]);
        $where['uid'] = (int) ($where['uid'] ?: auth('admin')->id());
        return $this->success($this->service->getUserStatistics(auth('admin')->id(), $where, 1));
    }
}
