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
use App\Http\Service\Attendance\AttendanceClockService;
use App\Http\Service\Attendance\AttendanceStatisticsService;
use App\Jobs\Work\WorkCheckInJob;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 考勤打卡
 * Class AttendanceClockController.
 */
#[Prefix('ent/attendance')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AttendanceClockController extends AuthController
{
    public function __construct(AttendanceClockService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取列表.
     */
    #[Get('clock_record', '考勤打卡记录')]
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['scope', ''],
            ['frame_id', ''],
            ['group_id', ''],
            ['time', ''],
            ['user_id', '', 'uid'],
        ]);
        if (! $where['time']) {
            $where['time'] = 'today';
        }
        return $this->success($this->service->getList($where));
    }

    /**
     * 获取列表.
     */
    #[Get('work_clock_record', '企微考勤打卡记录')]
    public function work_record(): mixed
    {
        [$date] = $this->request->getMore([
            ['date', []],
        ], true);
        [$start, $end] = $date;
        if (! $date || Carbon::make($end)->diffInDays(Carbon::make($start)) > 30) {
            return $this->fail('请选择正确的时间范围,且跨度不能超过30天');
        }
        WorkCheckInJob::dispatch(Carbon::make($start)->startOfDay(), Carbon::make($end)->endOfDay());
        return $this->success('已加入消息队列，正在异步同步中请稍后查看');
    }

    /**
     * 打卡详情.
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('clock_record/{id}', '考勤打卡详情')]
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }

        return $this->success($this->service->getInfo((int) $id));
    }

    /**
     * 异常日期列表.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('abnormal_date', '考勤异常日期列表')]
    public function abnormalDateList(AttendanceStatisticsService $service): mixed
    {
        $data = $service->getAbnormalDateList(auth('admin')->id());
        return $this->success($data);
    }

    /**
     * 异常记录列表.
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Get('abnormal_record/{id}', '考勤异常记录列表')]
    public function abnormalRecordList($id, AttendanceStatisticsService $service): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }
        $data = $service->getAbnormalRecordList($this->uuid, (int) $id);
        return $this->success($data);
    }

    /**
     * 导入打卡记录.
     */
    #[Post('clock/import_record', '导入打卡记录')]
    public function importRecord(): mixed
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);

        if (empty($data) || ! is_array($data)) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->saveRecord($data);
        return $this->success('导入成功');
    }

    /**
     * 导入三方考勤记录.
     */
    #[Post('clock/import_third', '导入三方打卡记录')]
    public function importThird(): mixed
    {
        [$type, $data] = $this->request->postMore([
            ['type', 0],
            ['data', []],
        ], true);

        $this->service->importThirdParty((int) $type, (array) $data);
        return $this->success('导入成功');
    }
}
