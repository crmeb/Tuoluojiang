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

namespace App\Listeners\Todo;

use App\Events\BusinessDataChangeEvent;
use App\Http\Service\Todo\TodoItemService;
use Illuminate\Support\Facades\Log;

/**
 * 业务数据变更监听器，同步创建、更新或删除对应待办.
 */
class TodoChangeListener
{
    public function handle(BusinessDataChangeEvent $event): void
    {
        try {
            $service = app()->get(TodoItemService::class);

            match ($event->action) {
                'create'   => $service->createBySourceId($event->type, $event->sourceId, $event->userIds),
                'delete'   => $service->deleteBySourceId($event->type, $event->sourceId, $event->userIds),
                'complete' => $service->completeBySourceId($event->type, $event->sourceId, $event->userIds),
                default    => $service->updateBySourceId($event->type, $event->sourceId, $event->userIds),
            };
        } catch (\Throwable $e) {
            Log::error('TodoChangeListener sync failed', [
                'type'     => $event->type,
                'sourceId' => $event->sourceId,
                'action'   => $event->action,
                'message'  => $e->getMessage(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
            ]);
        }
    }
}
