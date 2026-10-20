<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Task\crud;

use App\Constants\Crud\CrudEventEnum;
use Tuoluojiang\Open\exceptions\ApiException;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 *  触发器任务处理.
 */
class TriggerTask extends Task
{
    /**
     * @param int $crudId 实体ID
     * @param string $action 触发动作
     */
    public function __construct(
        protected int $crudId,
        protected string $action,// 当前执行的动作
        protected array $eventIds,
        protected array $event,
        protected int $dataId,
        protected array $data,
        protected array $scheduleData = []
    ) {}

    public function handle()
    {
        try {
            $taskClass = $this->getTask();
            Task::deliver(new $taskClass($this->crudId, $this->action, $this->event, $this->eventIds, $this->dataId, $this->data, $this->scheduleData));
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    /**
     * 获取执行的操作.
     */
    protected function getTask()
    {
        $className = '';
        switch ($this->event['event']) {
            case CrudEventEnum::EVENT_AUTO_CREATE:
                $className = TriggerAutoCreateTask::class;
                break;
            case CrudEventEnum::EVENT_SEND_NOTICE:
                $className = TriggerSendMsgTask::class;
                break;
            case CrudEventEnum::EVENT_DATA_CHECK:
                $className = TriggerDataCheckTask::class;
                break;
            case CrudEventEnum::EVENT_GROUP_AGGREGATE:
                $className = TriggerGroupAggregationTask::class;
                break;
            case CrudEventEnum::EVENT_FIELD_AGGREGATE:
                $className = TriggerFieldAggregationTask::class;
                break;
            case CrudEventEnum::EVENT_FIELD_UPDATE:
                $className = TriggerFieldUpdateTask::class;
                break;
            case CrudEventEnum::EVENT_AUTH_APPROVE:
                $className = TriggerAutoApproveTask::class;
                break;
            case CrudEventEnum::EVENT_AUTO_REVOKE_APPROVE:
                $className = TriggerAutoRevokeTask::class;
                break;
        }

        if ($className) {
            return $className;
        }

        throw new ApiException('未找到触发器操作执行类' . $className);
    }
}
