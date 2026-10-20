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

use App\Http\Service\Attendance\AttendanceRemindService;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 班次提醒 CronJob
 * 每天00:30生成班次提醒数据
 * Class AttendShiftRemindCronJob.
 */
class AttendShiftRemindCronJob extends CronJob
{
    /**
     * 频率：动态计算，距离次日00:30的毫秒数
     */
    public function interval(): int
    {
        $tz = config('app.timezone');
        $tomorrow = Carbon::tomorrow($tz)->addMinutes(30);

        return Carbon::now($tz)->diffInMilliseconds($tomorrow);
    }

    public function run(): void
    {
        $this->executeTask();

        // 设置次日00:30再次执行
        $this->scheduleNextRun();
    }

    /**
     * 执行班次提醒生成任务
     */
    private function executeTask(): void
    {
        $tz = config('app.timezone');

        try {
            app()->get(AttendanceRemindService::class)->generateShiftRemind(Carbon::now($tz));
        } catch (\Throwable $e) {
            Log::error('考勤推送数据创建失败：' . $e->getMessage(), [
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
        $tomorrow = Carbon::tomorrow($tz)->addMinutes(30);
        $delay = (int) Carbon::now($tz)->diffInMilliseconds($tomorrow);

        \Swoole\Timer::after($delay, function () {
            $this->run();
        });
    }
}
