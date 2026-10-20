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

namespace App\Task\system;

use App\Http\Contract\Schedule\ScheduleInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;

/**
 * 日程队列任务
 */
class ScheduleTask extends Task
{
    /**
     * @var Application|(Application&ScheduleInterface)|mixed|ScheduleInterface
     */
    private ScheduleInterface $schedule;

    /**
     * Create a new job instance.
     */
    public function __construct(private array $where, private int $i, private int $limit = 500)
    {
        $this->schedule = app(ScheduleInterface::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->schedule->scheduleTimer($this->where, $this->i, $this->limit);
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
