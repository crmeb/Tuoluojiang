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

namespace App\Jobs\Crud;

use App\Http\Service\Crud\SystemCrudEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrudCronActionJob implements ShouldQueue
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
    public function __construct(protected array $item, protected int $dataId = 0, protected int $lastId = 0) {}

    public function handle()
    {
        $service = app()->get(SystemCrudEventService::class);

        try {
            if (! $service->isTimerEventEnabled((int) ($this->item['id'] ?? 0))) {
                return;
            }

            if ($this->dataId > 0) {
                $result = $service->timerActionData($this->item, $this->dataId);
                $this->disableTimerEventWhenFailed($service, $result);
                return;
            }

            $result = $service->timerAction($this->item, $this->lastId);
            $this->disableTimerEventWhenFailed($service, $result);
        } catch (\Throwable $e) {
            $this->disableTimerEventWhenFailed($service, ['result' => false]);
            Log::error('实体触发器执行定时任务报错action：' . $e->getMessage(), $this->logContext($e));
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->disableTimerEventWhenFailed(app()->get(SystemCrudEventService::class), ['result' => false]);
        Log::error('实体触发器定时任务执行失败：' . $e->getMessage(), $this->logContext($e));
    }

    protected function disableTimerEventWhenFailed(SystemCrudEventService $service, mixed $result): void
    {
        if (! is_array($result) || ($result['result'] ?? true)) {
            return;
        }

        try {
            $service->disableTimerEvent((int) ($this->item['id'] ?? 0));
        } catch (\Throwable $e) {
            Log::error('实体触发器定时任务停用失败：' . $e->getMessage(), $this->logContext($e));
        }
    }

    protected function logContext(\Throwable $e): array
    {
        return [
            'event_id' => $this->item['id'] ?? 0,
            'crud_id'  => $this->item['crud_id'] ?? 0,
            'event'    => $this->item['event'] ?? '',
            'data_id'  => $this->dataId,
            'last_id'  => $this->lastId,
            'file'     => $e->getFile(),
            'line'     => $e->getLine(),
        ];
    }
}
