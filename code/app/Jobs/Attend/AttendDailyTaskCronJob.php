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

use App\Http\Service\Attendance\AttendanceStatisticsService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 考勤每日统计 CronJob
 * 每天00:01生成考勤统计数据
 * 同时处理跨天打卡场景，创建昨日和今日的统计记录
 * Class AttendDailyTaskCronJob.
 */
class AttendDailyTaskCronJob extends CronJob
{
    /**
     * 频率：动态计算，距离次日00:01的毫秒数
     */
    public function interval(): int
    {
        $tz = config('app.timezone');
        $tomorrow = Carbon::tomorrow($tz)->addMinute();

        return (int) Carbon::now($tz)->diffInMilliseconds($tomorrow);
    }

    public function run(): void
    {
        $this->executeTask();

        // 设置次日00:01再次执行
        $this->scheduleNextRun();
    }

    /**
     * 执行考勤统计生成任务
     * 同时创建昨日和今日的统计记录，确保跨天打卡场景正确处理
     */
    private function executeTask(): void
    {
        $tz = config('app.timezone');

        // 昨日日期（处理跨天打卡场景）
        $yesterday = Carbon::now($tz)->subDay()->toDateString();
        // 今日日期
        $today = Carbon::now($tz)->toDateString();

        $this->generateStatisticsForDate($yesterday);
        $this->generateStatisticsForDate($today);
    }

    /**
     * 生成指定日期的考勤统计
     */
    private function generateStatisticsForDate(string $date): void
    {
        try {
            app()->get(AttendanceStatisticsService::class)->generateMemberStatistics($date);
        } catch (\Throwable $e) {
            Log::error("考勤数据创建失败[日期：{$date}]：" . $e->getMessage(), [
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
        $tomorrow = Carbon::tomorrow($tz)->addMinute();
        $delay = (int) Carbon::now($tz)->diffInMilliseconds($tomorrow);

        \Swoole\Timer::after($delay, function () {
            $this->run();
        });
    }

    /**
     * 重新生成指定日期之后的考勤统计数据（排班变更后调用）
     * 静态方法，可由其他服务调用
     */
    public static function regenerateAfter(string $date): void
    {
        $tz = config('app.timezone');
        $startDate = Carbon::parse($date, $tz)->toDateString();
        $today = Carbon::now($tz)->toDateString();

        $service = app()->get(AttendanceStatisticsService::class);

        while ($startDate <= $today) {
            try {
                $service->generateMemberStatistics($startDate);
            } catch (\Throwable $e) {
                Log::error("重新生成考勤数据失败[日期：{$startDate}]：" . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
            $startDate = Carbon::parse($startDate, $tz)->addDay()->toDateString();
        }
    }
}
