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

use App\Constants\NoticeEnum;
use App\Http\Service\Attendance\AttendanceRemindService;
use App\Http\Service\Message\MessageService;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 个人考勤推送 CronJob
 * 支持周报(每周一)、月报(每月1号)
 * Class AttendPersonalPushCronJob.
 */
class AttendPersonalPushCronJob extends CronJob
{
    /**
     * 频率：每分钟检查是否需要执行
     */
    public function interval(): int
    {
        return 60000;
    }

    public function run(): void
    {
        $this->checkAndExecute();
    }

    /**
     * 检查并执行推送任务
     */
    private function checkAndExecute(): void
    {
        $tz = config('app.timezone');
        $now = Carbon::now($tz);
        $dateTime = $now->toDateTimeString();

        // 1. 检查周报推送（周一）
        if ($now->dayOfWeek === Carbon::MONDAY) {
            $this->checkWeeklyPush($tz, $dateTime, $now);
        }

        // 2. 检查月报推送（每月1号）
        if ($now->startOfMonth()->day === 1) {
            $this->checkMonthlyPush($tz, $dateTime, $now);
        }
    }

    /**
     * 检查周报推送
     */
    private function checkWeeklyPush(string $tz, string $dateTime, Carbon $now): void
    {
        try {
            $msgService = app()->get(MessageService::class);
            $weeklyMessage = $msgService->getMessageContent(1, NoticeEnum::PERSONAL_WEEKLY_REMIND);

            if ($weeklyMessage && ! empty($weeklyMessage['remind_time'])) {
                $targetDateTime = $now->format("Y-m-d {$weeklyMessage['remind_time']}:00");
                if ($dateTime === $targetDateTime) {
                    $dateObj = Carbon::now($tz)->subWeek();
                    $date = $dateObj->startOfWeek()->format('Y/m/d 00:00:00') . '-' .
                            $dateObj->endOfWeek()->format('Y/m/d 23:59:59');
                    app()->get(AttendanceRemindService::class)->sendPersonalPush(
                        $date,
                        NoticeEnum::PERSONAL_WEEKLY_REMIND,
                        'time'
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('个人周报推送失败：' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * 检查月报推送
     */
    private function checkMonthlyPush(string $tz, string $dateTime, Carbon $now): void
    {
        try {
            $msgService = app()->get(MessageService::class);
            $monthlyMessage = $msgService->getMessageContent(1, NoticeEnum::PERSONAL_MONTHLY_REMIND);

            if ($monthlyMessage && ! empty($monthlyMessage['remind_time'])) {
                $targetDateTime = $now->format("Y-m-d {$monthlyMessage['remind_time']}:00");
                if ($dateTime === $targetDateTime) {
                    app()->get(AttendanceRemindService::class)->sendPersonalPush(
                        $now->subMonth()->format('Y-m'),
                        NoticeEnum::PERSONAL_MONTHLY_REMIND,
                        'month'
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('个人月报推送失败：' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
