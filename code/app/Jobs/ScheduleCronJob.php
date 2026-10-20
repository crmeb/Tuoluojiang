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

namespace App\Jobs;

use App\Constants\CacheEnum;
use App\Http\Service\Schedule\ScheduleService;
use App\Task\system\ScheduleTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 日程提醒定时器
 * Class ScheduleCronJob.
 */
class ScheduleCronJob extends CronJob
{
    protected int $limit = 100;

    /**
     * 频率：每1s运行一次
     * @return int
     */
    public function interval()
    {
        return 1000;
    }

    public function run()
    {
        try {
            foreach ([
                ['period' => 0, 'is_remind' => 0, 'end_time_not' => 1], // 一次性提醒
                ['period_not' => 0, 'end_time_not' => 1], // 企业提醒多次
            ] as $where) {
                $sumCount = Cache::tags([CacheEnum::TAG_SCHEDULE])->remember(
                    md5('schedule_ent_count_' . json_encode($where)),
                    (int) sys_config('system_cache_ttl', 3600),
                    fn () => app()->get(ScheduleService::class)->remindDao->count($where)
                );
                $sumPage = ceil($sumCount / $this->limit);
                for ($i = 1; $i <= (int) $sumPage; ++$i) {
                    Task::deliver(new ScheduleTask($where, $i, $this->limit));
                }
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
