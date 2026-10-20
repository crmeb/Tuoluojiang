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

namespace App\Task\crud;

use App\Http\Service\Crud\SystemCrudEventLogService;
use App\Http\Service\Crud\SystemCrudEventService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

/**
 * Trait TriggerTrait.
 */
trait TriggerTrait
{
    /**
     * 执行任务
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/15
     */
    public function handle()
    {
        $eventLogService = app()->get(SystemCrudEventLogService::class);

        try {
            if (in_array($this->action, $this->event['action'])) {
                $this->runHandle();

                $eventLogService->saveLog(
                    crudId: $this->crudId,
                    eventId: $this->event['id'],
                    action: $this->action,
                    result: '执行成功:',
                    parameter: [
                        'eventId'      => $this->eventIds,
                        'data'         => $this->data,
                        'scheduleData' => $this->scheduleData,
                    ],
                    log: []
                );
            }
        } catch (\Throwable $e) {
            $eventLogService->saveLog(
                crudId: $this->crudId,
                eventId: $this->event['id'],
                action: $this->action,
                result: '执行失败:' . $e->getMessage(),
                parameter: [
                    'eventId'      => $this->eventIds,
                    'data'         => $this->data,
                    'scheduleData' => $this->scheduleData,
                ],
                log: [
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'trace' => $e->getTrace(),
                ]
            );
        }

        $this->nextTask();
    }

    abstract protected function runHandle();

    /**
     * 执行下一个任务
     * @return false
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/15
     */
    protected function nextTask()
    {
        $eventService = app()->get(SystemCrudEventService::class);
        // 执行下一个任务
        if ($this->eventIds) {
            $eventId = array_shift($this->eventIds);
            $event   = $eventService->get($eventId);
            if (! $event) {
                Log::error('下一个任务查询失败，事件ID：' . $eventId);

                return false;
            }
            Task::deliver(new TriggerTask($this->crudId, $this->action, $this->eventIds, $event->toArray(), $this->dataId, $this->data, $this->scheduleData));
        }
    }
}
