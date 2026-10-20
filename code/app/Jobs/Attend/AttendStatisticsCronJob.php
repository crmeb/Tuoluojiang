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

namespace App\Jobs\Attend;

use App\Http\Service\Attendance\AttendanceRemindService;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 考勤短提醒 CronJob
 * 每分钟执行，发送缺卡提醒等高频任务
 * Class AttendStatisticsCronJob.
 */
class AttendStatisticsCronJob extends CronJob
{
    /**
     * 频率：每分钟执行.
     */
    public function interval(): int
    {
        return 60000;
    }

    public function run(): void
    {
        $tz = config('app.timezone');

        try {
            app()->get(AttendanceRemindService::class)->sendShortRemindMessage(Carbon::now($tz));
        } catch (\Throwable $e) {
            Log::error('缺卡提醒推送失败：' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
