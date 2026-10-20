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

namespace App\Jobs\Attend;

use App\Http\Service\Attendance\AttendanceApplyRecordService;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 审批考勤计算 CronJob
 * 每天01:00计算审批考勤时间
 * Class AttendCalcApplyRecordCronJob.
 */
class AttendCalcApplyRecordCronJob extends CronJob
{
    /**
     * 频率：动态计算，距离次日01:00的毫秒数
     */
    public function interval(): int
    {
        $tz = config('app.timezone');
        $tomorrow = Carbon::tomorrow($tz)->addHours(1);

        return (int) Carbon::now($tz)->diffInMilliseconds($tomorrow);
    }

    public function run(): void
    {
        $this->executeTask();

        // 设置次日01:00再次执行
        $this->scheduleNextRun();
    }

    /**
     * 执行审批考勤计算任务
     */
    private function executeTask(): void
    {
        $tz = config('app.timezone');
        $date = Carbon::now($tz)->toDateString();

        try {
            app()->get(AttendanceApplyRecordService::class)->calcApplyRecordTime($date);
        } catch (\Throwable $e) {
            Log::error('审批考勤更新失败：' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * 设置下次执行时间
     */
    private function scheduleNextRun(): void
    {
        $tz = config('app.timezone');
        $tomorrow = Carbon::tomorrow($tz)->addHours(1);
        $delay = (int) Carbon::now($tz)->diffInMilliseconds($tomorrow);

        \Swoole\Timer::after($delay, function () {
            $this->run();
        });
    }
}
