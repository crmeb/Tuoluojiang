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

use App\Http\Service\Attendance\AttendanceClockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 考勤导入队列任务
 */
class AttendanceImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(private string $func, private array $data) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $clockService = app()->get(AttendanceClockService::class);
            foreach ($this->data as $record) {
                $clockService->{$this->func}($record);
            }
        } catch (\Throwable $e) {
            Log::error('打卡记录批量导入失败：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'func' => $this->func, 'data' => $this->data]);
        }
    }
}
