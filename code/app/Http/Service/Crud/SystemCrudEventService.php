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

namespace App\Http\Service\Crud;

use App\Constants\Crud\CrudAggregateEnum;
use App\Constants\Crud\CrudEventEnum;
use App\Constants\Crud\CrudFormEnum;
use App\Constants\Crud\CrudOperatorEnum;
use App\Constants\Crud\CrudTriggerEnum;
use App\Constants\Crud\CrudUpdateEnum;
use App\Http\Dao\Crud\CrudModuleDao;
use App\Http\Dao\Crud\SystemCrudEventDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\Message\MessageTemplateService;
use App\Http\Service\Notice\MessageCateService;
use App\Http\Service\Schedule\ScheduleService;
use App\Jobs\Crud\CrudCronActionJob;
use App\Jobs\Crud\CrudEventRunGetDataJob;
use App\Jobs\WebhookJob;
use App\Task\message\NoticeMessageTask;
use App\Task\message\SmsMessageTask;
use App\Http\Dao\BaseDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\expressionLanguage\ExpressionLanguage;
use Tuoluojiang\Open\utils\MessageType;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 实体触发器
 * Class SystemCrudEventService.
 * @email 136327134@qq.com
 * @date 2024/2/29
 */
class SystemCrudEventService extends BaseService
{
    protected const TIMER_ACTION_BATCH_SIZE = 100;

    protected const EVENT_TIMER_CACHE_KEY = 'event_timer_list';

    /**
     * SystemCrudEventService constructor.
     */
    public function __construct(SystemCrudEventDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 触发器查询列表.
     * @param array|string[] $field
     * @param null $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/4/16
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, [
            'crud' => fn ($q) => $q->select(['id', 'table_name']),
        ]);
        $count = $this->dao->count($where);

        $crudIds  = array_column($list, 'crud_id');
        $cateList = app()->get(SystemCrudService::class)->crudIdByCateIds($crudIds);
        $cateIds  = [];
        foreach ($cateList as $item) {
            if ($item['cate_ids']) {
                $cateIds = array_merge($crudIds, $item['cate_ids']);
            }
        }
        if ($cateIds) {
            $cate = app()->get(SystemCrudCateService::class)->idsByNameColumn($cateIds);
            foreach ($list as &$item) {
                $cateItem = [];
                foreach ($cateList as $value) {
                    if ($item['crud_id'] === $value['id'] && $value['cate_ids']) {
                        foreach ($value['cate_ids'] as $cateId) {
                            if (isset($cate[$cateId])) {
                                $cateItem[] = $cate[$cateId];
                            }
                        }
                    }
                }
                $item['cate_item'] = $cateItem;
            }
        }

        return $this->listData($list, $count);
    }

    /**
     * 修改触发器.
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    public function updateEvent(int $id, array $data)
    {
        $notifyType = [];
        if ($data['system_status']) {
            $notifyType[] = '0';
        }
        if ($data['sms_status']) {
            $notifyType[] = '1';
        }
        if ($data['work_webhook_status']) {
            $notifyType[] = '2';
        }
        if ($data['ding_webhook_status']) {
            $notifyType[] = '3';
        }
        if ($data['other_webhook_status']) {
            $notifyType[] = '4';
        }
        $this->dao->update($id, [
            'name'                            => $data['name'], // 触发器名称
            'timer'                           => $data['timer'], // 定时器
            'action'                          => $data['action'], // 出发方式
            'sort'                            => $data['sort'], // 排序
            'target_crud_id'                  => $data['target_crud_id'], // 目标实体
            'crud_approve_id'                 => $data['crud_approve_id'], // 审批ID
            'send_type'                       => $data['send_type'], // 发送类型
            'send_user'                       => $data['send_user'], // 送达人
            'notify_type'                     => $notifyType, // 提醒类型 0 = 通知，1=短信，2=企业微信bot，3=钉钉机器人，4=其他bot
            'sms_template_id'                 => $data['sms_template_id'], // 短信模板id
            'system_status'                   => $data['system_status'], // 系统通知状态
            'sms_status'                      => $data['sms_status'], // 短信状态
            'work_webhook_url'                => $data['work_webhook_url'], // 企业微信bot webhook地址
            'work_webhook_status'             => $data['work_webhook_status'], // 企业微信bot webhook状态
            'ding_webhook_url'                => $data['ding_webhook_url'], // 钉钉机器人webhook地址
            'ding_webhook_status'             => $data['ding_webhook_status'], // 钉钉机器人webhook状态
            'other_webhook_url'               => $data['other_webhook_url'], // 其他bot webhook地址
            'other_webhook_status'            => $data['other_webhook_status'], // 其他bot webhook状态
            'additional_search'               => $data['additional_search'], // 附加条件
            'additional_search_boolean'       => $data['additional_search_boolean'],
            'template'                        => $data['template'], // 模板内容
            'update_field_options'            => $data['update_field_options'], // 更新字段相关数据
            'field_options'                   => $data['field_options'], // 字段相关数据
            'aggregate_target_search'         => $data['aggregate_target_search'], // 聚合目标搜索
            'aggregate_data_search'           => $data['aggregate_data_search'], // 聚合数据搜索
            'aggregate_data_field'            => $data['aggregate_data_field'], // 分组字段关联
            'aggregate_field_rule'            => $data['aggregate_field_rule'], // 聚合规则
            'aggregate_data_search_boolean'   => $data['aggregate_data_search_boolean'],
            'aggregate_target_search_boolean' => $data['aggregate_target_search_boolean'],
            'options'                         => $data['options'], // 其他配置项
            'timer_options'                   => $data['timer_options'], // 定时任务配置
            'curl_id'                         => $data['curl_id'], // 获取数据id
            'timer_type'                      => $data['timer_type'], // 定时任务执行类型
        ]);

        // 记录
        if ($data['event'] === CrudEventEnum::EVENT_SEND_NOTICE) {
            $messageService         = app()->get(MessageService::class);
            $messageCateService     = app()->get(MessageCateService::class);
            $messageTemplateService = app()->get(MessageTemplateService::class);
            $message                = $messageService->get(['crud_id' => $data['crud_id'], 'event_id' => $id], ['*'], ['messageTemplate'])?->toArray();
            $smsTemplate            = $workTemplate = $dingTemplate = $otherTemplate = [];
            if ($message) {
                foreach ($message['message_template'] as $template) {
                    switch ($template['type']) {
                        case MessageType::TYPE_SMS:
                            $smsTemplate = $template;
                            break;
                        case MessageType::TYPE_WORK:
                            $workTemplate = $template;
                            break;
                        case MessageType::TYPE_DING:
                            $dingTemplate = $template;
                            break;
                        case MessageType::TYPE_OTHER:
                            $otherTemplate = $template;
                            break;
                    }
                }
            } else {
                $cateName = app()->get(SystemCrudService::class)->value($data['crud_id'], 'table_name');
                $cateInfo = $messageCateService->get(['eq_cate_name' => $cateName]);
                if (! $cateInfo) {
                    $cateId = $messageCateService->create([
                        'cate_name' => $cateName,
                        'is_show'   => 1,
                        'uni_show'  => 1,
                    ])->id;
                } else {
                    $cateId = $cateInfo->id;
                }
                $message = $messageService->create([
                    'entid'         => 1,
                    'relation_id'   => 0,
                    'event_id'      => $id,
                    'crud_id'       => $data['crud_id'],
                    'cate_id'       => $cateId,
                    'cate_name'     => $cateName,
                    'template_type' => MessageType::SYSTEM_CRUD_TYPE,
                    'title'         => $data['name'],
                    'content'       => $data['template'],
                ]);
            }
            $smsTemplate['message_id']       = $message['id'];
            $smsTemplate['type']             = MessageType::TYPE_SMS;
            $smsTemplate['message_title']    = $data['name'];
            $smsTemplate['content_template'] = $data['template'];
            $smsTemplate['template_id']      = $data['sms_template_id'];
            $smsTemplate['status']           = $data['sms_status'];
            $smsTemplate['crud_event_id']    = $id;
            if (isset($smsTemplate['id'])) {
                $messageTemplateService->update($smsTemplate['id'], $smsTemplate);
            } else {
                $messageTemplateService->create($smsTemplate);
            }
            $workTemplate['message_id']       = $message['id'];
            $workTemplate['type']             = MessageType::TYPE_WORK;
            $workTemplate['message_title']    = $data['name'];
            $workTemplate['content_template'] = $data['template'];
            $workTemplate['webhook_url']      = $data['work_webhook_url'];
            $workTemplate['status']           = $data['work_webhook_status'];
            $workTemplate['crud_event_id']    = $id;
            if (isset($workTemplate['id'])) {
                $messageTemplateService->update($workTemplate['id'], $workTemplate);
            } else {
                $messageTemplateService->create($workTemplate);
            }
            $dingTemplate['message_id']       = $message['id'];
            $dingTemplate['type']             = MessageType::TYPE_DING;
            $dingTemplate['message_title']    = $data['name'];
            $dingTemplate['content_template'] = $data['template'];
            $dingTemplate['webhook_url']      = $data['ding_webhook_url'];
            $dingTemplate['status']           = $data['ding_webhook_status'];
            $dingTemplate['crud_event_id']    = $id;
            if (isset($dingTemplate['id'])) {
                $messageTemplateService->update($dingTemplate['id'], $dingTemplate);
            } else {
                $messageTemplateService->create($dingTemplate);
            }
            $otherTemplate['message_id']       = $message['id'];
            $otherTemplate['type']             = MessageType::TYPE_OTHER;
            $otherTemplate['message_title']    = $data['name'];
            $otherTemplate['content_template'] = $data['template'];
            $otherTemplate['webhook_url']      = $data['other_webhook_url'];
            $otherTemplate['status']           = $data['other_webhook_status'];
            $otherTemplate['crud_event_id']    = $id;
            if (isset($otherTemplate['id'])) {
                $messageTemplateService->update($otherTemplate['id'], $otherTemplate);
            } else {
                $messageTemplateService->create($otherTemplate);
            }
        }

        Cache::tags('event')->clear();
    }

    /**
     * 停用失败的定时触发器.
     */
    public function disableTimerEvent(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $this->dao->update($id, ['status' => 0]);
        Cache::tags('event')->clear();
    }

    public function isTimerEventEnabled(int $id): bool
    {
        return $id > 0 && (int) $this->dao->value($id, 'status') === 1;
    }

    /**
     * 获取事件详情.
     * @email 136327134@qq.com
     * @date 2024/2/29
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getEventInfo(int $id): array
    {
        $eventInfo = $this->dao->get($id)?->toArray();
        if (! $eventInfo) {
            throw $this->exception('没有查询到事件详情');
        }
        if ($eventInfo['send_user']) {
            $eventInfo['send_user'] = app()->get(AdminService::class)->userIdByUserInfo($eventInfo['send_user']);
        }
        if (is_array($eventInfo['timer_options'])) {
            $eventInfo['timer_options'] = (object) $eventInfo['timer_options'];
        }
        if (is_array($eventInfo['options'])) {
            $eventInfo['options'] = (object) $eventInfo['options'];
        }
        if (! $eventInfo['crud_approve_id']) {
            $eventInfo['crud_approve_id'] = '';
        }
        return $eventInfo;
    }

    /**
     * 获取事件列表.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/14
     * @throws BindingResolutionException
     */
    public function getEventActionList(int $crudId)
    {
        return $this->dao->getEventList($crudId, [
            'crud_id', 'name', 'event', 'sort', 'target_crud_id',
            'crud_approve_id', 'send_type', 'send_user', 'notify_type',
            'additional_search', 'template', 'field_options', 'options',
        ]);
    }

    /**
     * 获取事件列表Id.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/15
     * @throws BindingResolutionException
     */
    public function getEventActionIds(int $crudId, bool $eventBool = false)
    {
        $list = $this->dao->getEventList($crudId, ['id', 'action', 'event']);

        $event = [];

        foreach ($list as $item) {
            if ($item['event'] === CrudEventEnum::EVENT_DATA_CHECK && $eventBool) {
                $event[] = $item['id'];
            } elseif (! $eventBool) {
                if ($item['event'] !== CrudEventEnum::EVENT_DATA_CHECK && ! (count($item['action']) == 1 && in_array(CrudTriggerEnum::TRIGGER_TIMER, $item['action']))) {
                    $event[] = $item['id'];
                }
            }
        }

        return $event;
    }

    /**
     * 获取定时任务事件列表.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/14
     * @throws BindingResolutionException
     */
    public function getEventTimerList()
    {
        $list = Cache::tags('event')->remember(self::EVENT_TIMER_CACHE_KEY, 3600, function () {
            return $this->dao->getEventList(select: [
                'crud_id', 'name', 'event', 'sort', 'target_crud_id', 'timer',
                'crud_approve_id', 'send_type', 'send_user', 'notify_type',
                'additional_search', 'template', 'field_options', 'options', 'action',
                'additional_search_boolean', 'id', 'timer_type', 'timer_options', 'curl_id',
                'sms_status', 'system_status', 'work_webhook_url', 'work_webhook_status', 'ding_webhook_url',
                'ding_webhook_status', 'other_webhook_url', 'other_webhook_status', 'update_field_options',
                'aggregate_target_search', 'aggregate_target_search_boolean', 'aggregate_data_search',
                'aggregate_data_search_boolean', 'aggregate_data_field', 'aggregate_field_rule',
            ]);
        });

        $timer = [];

        foreach ((array) $list as $item) {
            if (in_array(CrudTriggerEnum::TRIGGER_TIMER, $item['action'])) {
                $timer[] = $item;
            }
        }

        return $timer;
    }

    /**
     * 执行定时任务
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \Throwable
     * @email 136327134@qq.com
     * @date 2024/4/12
     */
    public function runTimerEvent()
    {
        // 获取当前时间
        $currentDateTime = Carbon::now();

        $timerList = $this->getEventTimerList();

        foreach ($timerList as $item) {
            if ($item['timer']) {
                $item['timer_type']              = 0;
                $item['timer_options']['second'] = $item['timer'];
            }
            $item['timer_options']['id'] = $item['id'];
            if ($this->isExecute($currentDateTime, (int) $item['timer_type'], $item['timer_options'] ?? [])) {
                CrudCronActionJob::dispatch($item);
            }
        }
    }

    /**
     * 执行定时器运行.
     * @return false|void
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function timerAction(array $item, int $lastId = 0)
    {
        $crudService  = app()->get(SystemCrudService::class);
        $modelService = app()->get(CrudModuleService::class);
        $crudInfo     = $crudService->get(where: $item['crud_id'], with: ['child'])?->toArray();
        if (! $crudInfo) {
            return false;
        }

        if ($item['event'] === CrudEventEnum::EVENT_GET_DATA) {
            if (! Cache::tags('event')->has('event_curl_' . $item['id'])) {
                $this->runEvent(
                    crudId: $item['crud_id'],
                    action: CrudTriggerEnum::TRIGGER_TIMER,
                    event: $item,
                );
            }
        } else {
            $viewSearch    = $item['additional_search'] ?? [];
            $searchBoolean = ($item['additional_search_boolean'] ?? 0) == 0 ? 'or' : 'and';

            $list = $modelService
                ->model(tableName: $crudInfo['table_name_en'])
                ->viewSearch(viewSearch: $viewSearch, boolean: $searchBoolean)
                ->when($lastId > 0, function ($query) use ($lastId) {
                    $query->where('id', '>', $lastId);
                })
                ->orderBy('id')
                ->limit(self::TIMER_ACTION_BATCH_SIZE)
                ->select(['id'])
                ->get()
                ->toArray();

            $dataIds = array_column($list, 'id');
            if (! $dataIds) {
                return false;
            }

            foreach ($dataIds as $dataId) {
                CrudCronActionJob::dispatch($item, (int) $dataId);
            }

            if (count($dataIds) === self::TIMER_ACTION_BATCH_SIZE) {
                CrudCronActionJob::dispatch($item, 0, (int) end($dataIds));
            }
        }
    }

    /**
     * 执行单条定时器数据，避免一个队列任务处理整批数据导致超时。
     * @return false|void
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function timerActionData(array $item, int $dataId)
    {
        if ($item['event'] === CrudEventEnum::EVENT_GET_DATA) {
            return $this->timerAction($item);
        }

        $crudService  = app()->get(SystemCrudService::class);
        $modelService = app()->get(CrudModuleService::class);
        $crudInfo     = $crudService->get(where: $item['crud_id'], with: ['child'])?->toArray();
        if (! $crudInfo) {
            return false;
        }

        $viewSearch    = $item['additional_search'] ?? [];
        $searchBoolean = ($item['additional_search_boolean'] ?? 0) == 0 ? 'or' : 'and';
        $data          = $modelService
            ->model(tableName: $crudInfo['table_name_en'])
            ->viewSearch(viewSearch: $viewSearch, boolean: $searchBoolean)
            ->where('id', $dataId)
            ->first()
            ?->toArray();
        if (! $data) {
            return false;
        }

        unset($data['id']);

        $scheduleData = [];
        $childInfo    = $crudInfo['child'] ?? null;
        if ($childInfo) {
            $scheduleData = $modelService
                ->model(tableName: $childInfo['table_name_en'])
                ->get($dataId)
                ?->toArray();
        }

        $this->runEvent(
            crudId: (int) $item['crud_id'],
            action: CrudTriggerEnum::TRIGGER_TIMER,
            event: $item,
            dataId: $dataId,
            data: $data,
            scheduleData: $scheduleData ?: []
        );
    }

    /**
     * 执行任务
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \Throwable
     * @email 136327134@qq.com
     * @date 2024/3/15
     */
    public function runEvent(
        int $crudId,
        string $action,
        array $event,
        array $eventIds = [],
        int $dataId = 0,
        array $data = [],
        array $scheduleData = [],
        array $originalData = [],
        array $originalScheduleData = []
    ): array {
        $eventLogService = app()->get(SystemCrudEventLogService::class);
        $res             = $this->result();

        try {
            if (in_array($action, $event['action'])) {
                // 指定字段更新才会触发
                if ($action === CrudTriggerEnum::TRIGGER_UPDATED && $event['update_field_options']) {
                    $dataKeys = [];
                    foreach ($data as $k => $v) {
                        if (in_array($k, $event['update_field_options'])) {
                            $dataKeys[] = $k;
                        }
                    }
                    foreach ($scheduleData as $k => $v) {
                        if (in_array($k, $event['update_field_options'])) {
                            $dataKeys[] = $k;
                        }
                    }
                    if (! count($dataKeys)) {
                        throw $this->exception('指定更新的字段,没有发生更新');
                    }
                }

                $crudService = app()->get(SystemCrudService::class);
                $crudInfo    = $crudService->get($crudId)?->toArray();
                if (! $crudInfo) {
                    return $this->result('没有查询到实体信息');
                }
                $modelService = app()->get(CrudModuleService::class);

                $join  = $crudService->getJoinCrudData($crudId, 1);
                $model = $modelService->model(crudId: $crudId)->setJoin($join)->withTrashed($action === CrudTriggerEnum::TRIGGER_DELETED);

                switch ($event['event']) {
                    case CrudEventEnum::EVENT_AUTO_CREATE:
                        $res = $this->runAutoCreate($modelService, $model, $crudInfo, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData);
                        break;
                    case CrudEventEnum::EVENT_SEND_NOTICE:
                        $res = $this->runSendNotice($modelService, $model, $crudInfo, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData);
                        break;
                    case CrudEventEnum::EVENT_DATA_CHECK:
                        $res = $this->runDataCheck($modelService, $model, $crudInfo, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData);
                        if (! $res['result']) {
                            throw $this->exception($res['mesage']);
                        }
                        break;
                    case CrudEventEnum::EVENT_GROUP_AGGREGATE:
                        $res = $this->runGroupAggregate($modelService, $model, $crudInfo, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData);
                        break;
                    case CrudEventEnum::EVENT_FIELD_AGGREGATE:
                        $res = $this->runFieldAggregate($modelService, $model, $crudInfo, $join, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData);
                        break;
                    case CrudEventEnum::EVENT_FIELD_UPDATE:
                        $res = $this->runFieldUpdate($modelService, $model, $crudInfo, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData);
                        break;
                    case CrudEventEnum::EVENT_AUTH_APPROVE:
                        $res = $this->runAutoApprove($modelService, $model, $crudInfo, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData, $originalData, $originalScheduleData);
                        break;
                    case CrudEventEnum::EVENT_AUTO_REVOKE_APPROVE:
                        $res = $this->runAutoRevokeApprove($modelService, $model, $crudInfo, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData, $originalData, $originalScheduleData);
                        break;
                    case CrudEventEnum::EVENT_GET_DATA:
                        $res = $this->runGetData($modelService, $model, $crudInfo, $join, $crudId, $action, $event, $eventIds, $dataId, [], $scheduleData);
                        break;
                    case CrudEventEnum::EVENT_PUSH_DATA:
                        $res = $this->runPushData($modelService, $model, $crudInfo, $join, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData);
                        break;
                    case CrudEventEnum::EVENT_TO_DO_SCHEDULE:
                        $res = $this->runToDoSchedule($modelService, $model, $crudInfo, $join, $crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData);
                        break;
                }

                $result = $res['result'] ? 'success' : 'error';

                $eventLogService->saveLog(
                    crudId: $crudId,
                    eventId: $event['id'],
                    action: $action,
                    result: $result,
                    parameter: [
                        'eventId'      => $eventIds,
                        'data'         => $data,
                        'event'        => $event,
                        'scheduleData' => $scheduleData,
                    ],
                    log: [
                        'message' => $res['mesage'],
                    ]
                );
            }

        
        } catch (\Throwable $e) {
            $res = $this->result('执行失败:' . $e->getMessage());

            $eventLogService->saveLog(
                crudId: $crudId,
                eventId: $event['id'],
                action: $action,
                result: 'error',
                parameter: [
                    'eventId'      => $eventIds,
                    'data'         => $data,
                    'event'        => $event,
                    'scheduleData' => $scheduleData,
                ],
                log: [
                    'message' => '执行失败:' . $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTrace(),
                ]
            );

            if ($event['event'] === CrudEventEnum::EVENT_DATA_CHECK) {
                $this->nextTask(
                    crudId: $crudId,
                    action: $action,
                    eventIds: $eventIds,
                    dataId: $dataId,
                    data: $data,
                    scheduleData: $scheduleData,
                    originalData: $originalData,
                    originalScheduleData: $originalScheduleData
                );

                throw $e;
            }
        }

        if ($event['event'] !== CrudEventEnum::EVENT_DATA_CHECK) {
            $this->nextTask(
                crudId: $crudId,
                action: $action,
                eventIds: $eventIds,
                dataId: $dataId,
                data: $data,
                scheduleData: $scheduleData,
                originalData: $originalData,
                originalScheduleData: $originalScheduleData
            );
        }

        return $res;
    }

    /**
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/20
     */
    public function result(string $message = '', bool $result = false)
    {
        return [
            'result' => $result,
            'mesage' => $message,
        ];
    }

    /**
     * 字段更新.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @email 136327134@qq.com
     * @date 2024/3/21
     */
    public function runFieldUpdate(CrudModuleService $modelService, BaseDao $model, array $crudInfo, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        if (! $event['target_crud_id']) {
            return $this->result('缺少目标ID');
        }

        if (! $event['field_options']) {
            return $this->result('缺少更新字段');
        }

        // 附加条件
        $viewSearch    = $event['additional_search'] ?? [];
        $searchBoolean = $event['additional_search_boolean'] == 0 ? 'or' : 'and';

        $viewSearch = $modelService->setViewSearch($viewSearch, $crudInfo['id'], 'form_field_uniqid');
        $select     = $this->getDbSelects($event['field_options'], $crudInfo['table_name_en']);

        // 提取计算公式中的模板变量
        foreach ($event['field_options'] as $index => $item) {
            if ($item['operator'] === CrudUpdateEnum::UPDATE_TYPE_FORMULA_VALUE) {
                preg_match_all('/\{[a-zA-Z0-9\_\.]+\}/', $item['value'], $fields);
                $fields = $fields[0] ?? [];
                foreach ($fields as $value) {
                    $field = str_replace(['{', '}'], '', $value);

                    [$alias, $name] = str_contains($field, '.')
                        ? explode('.', $field)
                        : [$crudInfo['table_name_en'], $field];
                    if ($crudInfo['table_name_en'] !== $alias) {
                        $select[] = $alias . '.' . $name . ' as ' . $alias . '_' . $name;
                    } else {
                        $select[] = $alias . '.' . $name;
                    }

                    $item['template'][] = $field = str_replace('.', '_', $field);
                    $item['value']      = str_replace($value, $field, $item['value']);
                }
                $item['expression']             = $item['value'];
                $event['field_options'][$index] = $item;
            }
        }

        $dataInfo = $model->searchWhere(where: [
            [
                'field_name' => 'id',
                'form_value' => CrudFormEnum::FORM_RADIO,
                'value'      => $dataId,
            ],
        ], viewSearch: $viewSearch, boolean: $searchBoolean)->select(array_merge(array_unique($select)))->first()?->toArray();
        if (! $dataInfo) {
            return $this->result('没有查询到当前更新的数据', true);
        }

        $fieldService = app()->get(SystemCrudFieldService::class);

        $updateWhere = [];

        // 查找当前的实体和事件执行的实体是什么关系
        if ($crudInfo['id'] === $event['target_crud_id']) {
            // 修改的是当前的实体信息
            $updateWhere['id'] = $dataId;
            // 设置模型不关联查询
            $model = $modelService->model(crudId: $crudInfo['id'])->setJoin([]);
        } else {
            // 当前实体一对一关联其他实体
            $associationFieldNameEn = $fieldService->value(['association_crud_id' => $event['target_crud_id'], 'crud_id' => $crudId], 'field_name_en');
            if (! $associationFieldNameEn) {
                // 其他实体一对一关联到当前实体
                $associationFieldNameEn = $fieldService->value(['crud_id' => $event['target_crud_id'], 'association_crud_id' => $crudId], 'field_name_en');
                if ($associationFieldNameEn) {
                    $updateWhere[$associationFieldNameEn] = $dataId;
                } else {
                    return $this->result('没有查询到目标实体关联的字段名称');
                }
            } else {
                $updateWhere['id'] = $dataInfo[$associationFieldNameEn] ?? 0;
            }

            if (! $updateWhere) {
                return $this->result('没有查询到目标实体关联的字段值');
            }

            // 设置最终，更新实体的模型
            try {
                $model = $modelService->model(crudId: $event['target_crud_id']);
            } catch (\Throwable $e) {
                return $this->result($e->getMessage());
            }
        }

        $updateData = [];

        $formFieldUniqid    = array_column($event['field_options'], 'form_field_uniqid');
        $list               = $fieldService->fieldNameByFieldCrud($formFieldUniqid, $crudInfo['table_name_en']);
        $expressionLanguage = app()->get(ExpressionLanguage::class);
        foreach ($event['field_options'] as $item) {
            $fieldName = $item['form_field_uniqid'];
            $type      = CrudFormEnum::FORM_INPUT;

            foreach ($list as $vv) {
                foreach ($vv['field'] as $field) {
                    if ($field['field_name_en'] === $fieldName) {
                        $type = $field['form_value'];
                        break;
                    }
                }
            }

            switch ($item['operator']) {
                case CrudUpdateEnum::UPDATE_TYPE_FIELD:
                    $value = '';
                    if (! empty($item['to_form_field_uniqid'])) {
                        $key   = str_replace('.', '_', $item['to_form_field_uniqid']);
                        $value = $dataInfo[$key];
                    }
                    $updateData[$fieldName] = $modelService->setValueAttribute($type, $value);
                    break;
                case CrudUpdateEnum::UPDATE_TYPE_VALUE:
                    $updateData[$fieldName] = $modelService->setValueAttribute($type, $item['value'] ?? '');
                    break;
                case CrudUpdateEnum::UPDATE_TYPE_NULL_VALUE:
                    $updateData[$fieldName] = $modelService->setValueEmptyAttribute($type);
                    break;
                case CrudUpdateEnum::UPDATE_TYPE_FORMULA_VALUE:
                    if (! empty($item['expression'])) {
                        $values = [];
                        if (! empty($item['template'])) {
                            foreach ($item['template'] as $field) {
                                $values[$field] = $dataInfo[$field] ?? 0;
                            }
                        }
                        $updateData[$fieldName] = $expressionLanguage->evaluate($item['expression'], $values);
                    }
                    break;
            }
        }

        $model->update($updateWhere, $updateData);

        return $this->result('执行成功', true);
    }

    /**
     * 数据验证
     * @return array
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/27
     */
    public function runDataCheck(CrudModuleService $modelService, BaseDao $model, array $crudInfo, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        // 附加条件
        $viewSearch    = $event['additional_search'] ?? [];
        $searchBoolean = $event['additional_search_boolean'] == 0 ? 'or' : 'and';

        // 数据验证
        $viewDataSearch    = $event['options']['search'] ?? [];
        $searchDataBoolean = ($event['options']['search_boolean'] ?? 0) == 0 ? 'or' : 'and';

        $template = $event['template'];
        preg_match_all('/\{[a-zA-Z0-9_.]+}/', $template, $templateField);

        $viewSearch = $modelService->setViewSearch($viewSearch, $crudId, 'form_field_uniqid');
        // 从数据验证是否要进行数据验证
        [$res, $resOr] = $this->check($viewSearch, $data, $searchBoolean);
        if ($viewSearch && $searchBoolean == 'and' && ! $res) {
            return $this->result('没有查询需要验证数据', true);
        }
        if ($viewSearch && $searchBoolean == 'or' && ! $resOr) {
            return $this->result('没有查询需要验证数据', true);
        }

        foreach ($templateField[0] ?? [] as $string) {
            $field = str_replace(['{', '}'], '', $string);

            foreach ($data as $k => $datum) {
                if ($field === $k) {
                    $datum['post'] = $datum['post']['name'] ?? $datum['post'];
                    $template      = str_replace($string, (string) $datum['post'], $template);
                }
            }
        }

        $viewDataSearch = $modelService->setViewSearch($viewDataSearch, $crudId, 'form_field_uniqid');
        // 执行数据验证
        [$res, $resOr] = $this->check($viewDataSearch, $data, $searchDataBoolean);

        if ($searchDataBoolean == 'and' && $res) {
            return $this->result($template ?: '没有查询需要验证数据');
        }
        if ($searchDataBoolean == 'or' && $resOr) {
            return $this->result($template ?: '没有查询需要验证数据');
        }

        return $this->result('数据验证通过', true);
    }

    /**
     * 验证数据.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/27
     */
    public function check(array $rule, array $data, string $searchDataBoolean)
    {
        $res   = true;
        $resOr = false;
        // 需要使用原始数据来进行验证
        foreach ($data as $key => $item) {
            $result = [];
            foreach ($rule as $i => $view) {
                $view['form_field_uniqid'] = $view['field_name'] ?? $view['form_field_uniqid'] ?? '';
                if ($key === $view['form_field_uniqid']) {
                    switch ($view['operator']) {
                        case CrudOperatorEnum::OPERATOR_IN:
                        case CrudOperatorEnum::OPERATOR_NOT_IN:
                            $view['form_value'] = $view['form_value'] ?? '';
                            switch ($view['form_value']) {
                                case CrudFormEnum::FORM_CASCADER_RADIO:
                                    $result[$view['form_field_uniqid']] = in_array($item['value'], array_map(fn ($val) => '/' . implode('/', $val) . '/', $view['value']));
                                    break;
                                case CrudFormEnum::FORM_IMAGE:
                                case CrudFormEnum::FORM_FILE:
                                    $item['value']                      = json_encode($item['value']);
                                    $view['value']                      = json_encode($view['value']);
                                    $result[$view['form_field_uniqid']] = str_contains($item['value'], $view['value']);
                                    break;
                                case CrudFormEnum::FORM_TAG:
                                case CrudFormEnum::FORM_CHECKBOX:
                                case CrudFormEnum::FORM_CASCADER_ADDRESS:
                                    $item['value']                      = $item['value'] ? '/' . implode('/', is_array($item['value']) ? $item['value'] : [$item['value']]) . '/' : '';
                                    $view['value']                      = $view['value'] ? '/' . implode('/', is_array($view['value']) ? $view['value'] : [$view['value']]) . '/' : '';
                                    $result[$view['form_field_uniqid']] = str_contains($item['value'], $view['value']);
                                    break;
                                case CrudFormEnum::FORM_RADIO:
                                    $result[$view['form_field_uniqid']] = in_array($item['value'], (array) $view['value']);
                                    break;
                                case CrudFormEnum::FORM_INPUT:
                                case CrudFormEnum::FORM_TEXTAREA:
                                    $result[$view['form_field_uniqid']] = str_contains($item['value'], $view['value']);
                                    break;
                                default:
                                    $result[$view['form_field_uniqid']] = $item['value'] == $view['value'];
                                    break;
                            }

                            if ($view['operator'] === CrudOperatorEnum::OPERATOR_NOT_IN) {
                                $result[$view['form_field_uniqid']] = ! $result[$view['form_field_uniqid']];
                            }

                            break;
                        case CrudOperatorEnum::OPERATOR_EQ:
                            $result[$view['form_field_uniqid']] = $item['value'] == $view['value'];
                            break;
                        case CrudOperatorEnum::OPERATOR_GT:
                            $result[$view['form_field_uniqid']] = $item['value'] > $view['value'];
                            break;
                        case CrudOperatorEnum::OPERATOR_GT_EQ:
                            $result[$view['form_field_uniqid']] = $item['value'] >= $view['value'];
                            break;
                        case CrudOperatorEnum::OPERATOR_LT:
                            $result[$view['form_field_uniqid']] = $item['value'] < $view['value'];
                            break;
                        case CrudOperatorEnum::OPERATOR_LT_EQ:
                            $result[$view['form_field_uniqid']] = $item['value'] <= $view['value'];
                            break;
                        case CrudOperatorEnum::OPERATOR_NOT_EQ:
                            $result[$view['form_field_uniqid']] = $item['value'] != $view['value'];
                            break;
                        case CrudOperatorEnum::OPERATOR_IS_EMPTY:
                            $result[$view['form_field_uniqid']] = empty($item['value']);
                            break;
                        case CrudOperatorEnum::OPERATOR_NOT_EMPTY:
                            $result[$view['form_field_uniqid']] = ! empty($item['value']);
                            break;
                        case CrudOperatorEnum::OPERATOR_BT:
                            [$startVal, $entVal]                = is_array($view['value']) ? $view['value'] : ['', ''];
                            $result[$view['form_field_uniqid']] = $item['value'] > $startVal && $item['value'] < $entVal;
                            break;
                        case CrudOperatorEnum::OPERATOR_N_DAY:
                            $result[$view['form_field_uniqid']] = $item['value'] > Carbon::today()->subDays((int) $view['value'])->toDateTimeString();
                            break;
                        case CrudOperatorEnum::OPERATOR_LAST_DAY:
                            $startTime = Carbon::today()->subDays((int) $view['value'])->toDateTimeString();
                            $entTime   = Carbon::today()->toDateTimeString();

                            $result[$view['form_field_uniqid']] = $item['value'] > $startTime && $item['value'] < $entTime;
                            break;
                        case CrudOperatorEnum::OPERATOR_NEXT_DAY:
                            $startTime = Carbon::today()->toDateTimeString();
                            $entTime   = Carbon::today()->addDays((int) $view['value'])->toDateTimeString();

                            $result[$view['form_field_uniqid']] = $item['value'] > $startTime && $item['value'] < $entTime;
                            break;
                        case CrudOperatorEnum::OPERATOR_TO_DAY:
                            $result[$view['form_field_uniqid']] = $item['value'] > Carbon::today()->startOfDay()->toDateTimeString() && $item['value'] < Carbon::today()->endOfDay()->toDateTimeString();
                            break;
                        case CrudOperatorEnum::OPERATOR_WEEK:
                            $startTime = Carbon::today()->startOfWeek()->toDateTimeString();
                            $entTime   = Carbon::today()->endOfWeek()->toDateTimeString();

                            $result[$view['form_field_uniqid']] = $item['value'] > $startTime && $item['value'] < $entTime;
                            break;
                        case CrudOperatorEnum::OPERATOR_MONTH:
                            $startTime = Carbon::today()->startOfMonth()->toDateTimeString();
                            $entTime   = Carbon::today()->endOfMonth()->toDateTimeString();

                            $result[$view['form_field_uniqid']] = $item['value'] > $startTime && $item['value'] < $entTime;
                            break;
                        case CrudOperatorEnum::OPERATOR_QUARTER:
                            $startTime = Carbon::today()->startOfQuarter()->toDateTimeString();
                            $entTime   = Carbon::today()->endOfQuarter()->toDateTimeString();

                            $result[$view['form_field_uniqid']] = $item['value'] > $startTime && $item['value'] < $entTime;
                            break;
                        case CrudOperatorEnum::OPERATOR_YEAR:
                            $startTime = Carbon::today()->startOfYear()->toDateTimeString();
                            $entTime   = Carbon::today()->endOfYear()->toDateTimeString();

                            $result[$view['form_field_uniqid']] = $item['value'] > $startTime && $item['value'] < $entTime;
                            break;
                        case CrudOperatorEnum::OPERATOR_REGEX:
                            if (in_array($item['item']['form_value'], CrudFormEnum::FORM_REGEX)) {
                                $result[$view['form_field_uniqid']] = ! preg_match((string) $view['value'], (string) $item['value']);
                            }
                            break;
                    }
                }
            }

            foreach ($result as $resu) {
                if ($searchDataBoolean === 'or' && $resu) {
                    $resOr = true;
                    break;
                }

                if ($searchDataBoolean === 'and') {
                    $res = $res && $resu;
                }
            }
        }
        return [$res, $resOr];
    }

    public function runGroupAggregate(CrudModuleService $modelService, BaseDao $model, array $crudInfo, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        $event['aggregate_field_rule']          = $event['aggregate_field_rule'] ?? [];
        $event['target_crud_id']                = $event['target_crud_id'] ?? 0;
        $event['aggregate_data_field']          = $event['aggregate_data_field'] ?? [];
        $event['aggregate_data_search']         = $event['aggregate_data_search'] ?? [];
        $event['aggregate_data_search_boolean'] = $event['aggregate_data_search_boolean'] ?? 0;

        if (! $event['aggregate_field_rule']) {
            return $this->result('没有聚合规格无法执行');
        }
        if (! $event['target_crud_id']) {
            return $this->result('没有目标实体无法执行');
        }

        $viewSearch    = $event['additional_search'] ?? [];
        $searchBoolean = $event['additional_search_boolean'] == 0 ? 'or' : 'and';

        $select       = [$crudInfo['table_name_en'] . '.*'];
        $fieldService = app()->get(SystemCrudFieldService::class);
        $crudService  = app()->get(SystemCrudService::class);
        $searchTableNames = [$crudInfo['table_name_en']];
        foreach ($event['aggregate_data_field'] as $item) {
            if (empty($item['to_form_field_uniqid'])) {
                continue;
            }

            $field = $this->parseValidCrudField(
                field: $item['to_form_field_uniqid'],
                defaultTableName: $crudInfo['table_name_en'],
                defaultCrudId: (int) $crudInfo['id'],
                crudService: $crudService,
                fieldService: $fieldService
            );
            if (! $field) {
                continue;
            }
            $searchTableNames[] = $field['table_name'];

            if ($crudInfo['table_name_en'] !== $field['table_name']) {
                $select[] = $field['qualified_name'] . ' as ' . $field['table_name'] . '_' . $field['field_name'];
            } else {
                $select[] = $field['field_name'];
            }
        }
        $searchTableNames = array_merge(array_unique($searchTableNames));

        $viewSearch = $this->filterValidCrudSearch(
            viewSearch: $modelService->setViewSearch($viewSearch, $crudInfo['id'], 'form_field_uniqid'),
            defaultTableName: $crudInfo['table_name_en'],
            defaultCrudId: (int) $crudInfo['id'],
            crudService: $crudService,
            fieldService: $fieldService,
            allowTableNames: $searchTableNames
        );

        $dataInfo = $model->searchWhere(where: [
            [
                'field_name' => 'id',
                'form_value' => 'input',
                'value'      => $dataId,
            ],
        ], viewSearch: $viewSearch, boolean: $searchBoolean)
            ->select($select)->first()?->toArray();
        if (! $dataInfo) {
            return $this->result('没有查询到当前更新的数据', true);
        }

        $targetCrudName = $crudService->value($event['target_crud_id'], 'table_name_en');
        $targetDataSearch = $this->filterValidCrudSearch(
            viewSearch: $modelService->setViewSearch($event['aggregate_data_search'], $event['target_crud_id']),
            defaultTableName: $targetCrudName ?: '',
            defaultCrudId: (int) $event['target_crud_id'],
            crudService: $crudService,
            fieldService: $fieldService,
            allowTableNames: $targetCrudName ? [$targetCrudName] : []
        );

        $modelService->model(crudId: $event['target_crud_id'])
            ->viewSearch(
                viewSearch: $targetDataSearch,
                boolean: $event['aggregate_data_search_boolean'] ? 'and' : 'or'
            )->select()->get()->toArray();
    }

    /**
     * 自动创建.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/25
     * @throws BindingResolutionException
     */
    public function runAutoCreate(CrudModuleService $modelService, BaseDao $model, array $crudInfo, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        if (! $event['target_crud_id']) {
            return $this->result('没有目标实体无法执行');
        }

        if (! $event['field_options']) {
            return $this->result('没有需要新建的字段信息');
        }

        $viewSearch         = $event['additional_search'] ?? [];
        $searchBoolean      = $event['additional_search_boolean'] == 0 ? 'or' : 'and';
        $fieldUniqids       = array_column($viewSearch, 'form_field_uniqid');
        $fieldOptionUniqids = array_column($event['field_options'], 'form_field_uniqid');

        $uniqids      = array_unique(array_merge($fieldUniqids, $fieldOptionUniqids));
        $fieldService = app()->get(SystemCrudFieldService::class);
        $crudField    = $fieldService->fieldNameByFieldCrud($uniqids, $crudInfo['table_name_en']);

        $select = $this->getDbSelects($event['field_options'], $crudInfo['table_name_en']);

        // 提取计算公式中的模板变量
        foreach ($event['field_options'] as $index => $item) {
            if ($item['operator'] === CrudUpdateEnum::UPDATE_TYPE_FORMULA_VALUE) {
                preg_match_all('/\{[a-zA-Z0-9\_\.]+\}/', $item['value'], $fields);
                $fields = $fields[0] ?? [];
                foreach ($fields as $value) {
                    $field = str_replace(['{', '}'], '', $value);

                    [$alias, $name] = str_contains($field, '.')
                        ? explode('.', $field)
                        : [$crudInfo['table_name_en'], $field];
                    if ($crudInfo['table_name_en'] !== $alias) {
                        $select[] = $alias . '.' . $name . ' as ' . $alias . '_' . $name;
                    } else {
                        $select[] = $alias . '.' . $name;
                    }

                    $item['template'][] = $field = str_replace('.', '_', $field);
                    $item['value']      = str_replace($value, $field, $item['value']);
                }
                $item['expression']             = $item['value'];
                $event['field_options'][$index] = $item;
            }
        }

        $viewSearch = $modelService->setViewSearch($viewSearch, $crudInfo['id'], 'form_field_uniqid');
        $dataInfo   = $model->searchWhere(where: [
            [
                'field_name' => 'id',
                'form_value' => 'input',
                'value'      => $dataId,
            ],
        ], viewSearch: $viewSearch, boolean: $searchBoolean)
            ->select($select)->first()?->toArray();
        if (! $dataInfo) {
            return $this->result('没有查询到当前更新的数据', true);
        }

        $fields = [];
        foreach ($crudField as $crud) {
            foreach ($crud['field'] as $field) {
                $name = $crud['crud_info']['table_name_en'] === $crudInfo['table_name_en'] ? '' : $crud['crud_info']['table_name_en'];
                $key  = ($name ? $name . '.' : '') . $field['field_name_en'];
                if (! isset($fields[$key])) {
                    $fields[$key] = [
                        'type'  => $field['form_value'],
                        'field' => $field['field_name_en'],
                    ];
                }
            }
        }

        $crudFieldName = $fieldService->value(['crud_id' => $event['target_crud_id'], 'association_crud_id' => $crudId], 'field_name_en');
        if (! $crudFieldName) {
            return $this->result('没有查询到其他表实体关联了当前表');
        }

        $data               = [];
        $expressionLanguage = app()->get(ExpressionLanguage::class);
        foreach ($event['field_options'] as $item) {
            $field = $item['form_field_uniqid'];
            if (! isset($fields[$field])) {
                continue;
            }

            $key = $fields[$field];

            if (! str_contains($field, '.')) {
                continue;
            }
            [, $fieldName] = explode('.', $field);

            switch ($item['operator']) {
                case CrudUpdateEnum::UPDATE_TYPE_FIELD:
                    // 获取到查询数据的key名
                    // 取值数据中key对应的数据
                    [$alias, $name] = str_contains($item['to_form_field_uniqid'], '.') ? explode('.', $item['to_form_field_uniqid']) : [$crudInfo['table_name_en'], $item['to_form_field_uniqid']];

                    if ($alias !== $crudInfo['table_name_en']) {
                        $name = str_replace('.', '_', $item['form_field_uniqid']);
                    }

                    $fieldValue       = $dataInfo[$name];
                    $data[$fieldName] = $modelService->setValueAttribute($key['type'], $fieldValue);
                    break;
                case CrudUpdateEnum::UPDATE_TYPE_VALUE:
                    $data[$fieldName] = $modelService->setValueAttribute($key['type'], $item['value']);
                    break;
                case CrudUpdateEnum::UPDATE_TYPE_NULL_VALUE:
                    $data[$fieldName] = $modelService->setValueEmptyAttribute($key['type']);
                    break;
                case CrudUpdateEnum::UPDATE_TYPE_FORMULA_VALUE:
                    if (! empty($item['expression'])) {
                        $values = [];
                        if (! empty($item['template'])) {
                            foreach ($item['template'] as $field) {
                                $values[$field] = $dataInfo[$field] ?? 0;
                            }
                        }
                        $data[$fieldName] = $expressionLanguage->evaluate($item['expression'], $values);
                    }
                    break;
            }
        }

        if ($data) {
            if (! isset($data['user_id']) && $fieldService->value(['field_name_en' => 'user_id', 'crud_id' => $event['target_crud_id']], 'id')) {
                $data['user_id'] = $dataInfo['user_id'];
            }
            if (! isset($data['owner_user_id']) && $fieldService->value(['field_name_en' => 'owner_user_id', 'crud_id' => $event['target_crud_id']], 'id')) {
                $data['owner_user_id'] = $dataInfo['owner_user_id'];
            }
            if (! isset($data[$crudFieldName])) {
                $data[$crudFieldName] = $dataId;
            }

            $modelService->model(crudId: $event['target_crud_id'])->setJoin([])->create($data);
        }

        return $this->result('添加成功', true);
    }

    /**
     * 发送消息通知.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/29
     * @throws BindingResolutionException
     */
    public function runSendNotice(CrudModuleService $modelService, BaseDao $model, array $crudInfo, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        $viewSearch    = $event['additional_search'] ?? [];
        $searchBoolean = $event['additional_search_boolean'] == 0 ? 'or' : 'and';
        $title         = $event['options']['title'] ?? '';

        $template = $event['template'];
        preg_match_all('/\{[a-zA-Z0-9\_\.]+\}/', $template, $templateField);
        preg_match_all('/\{[a-zA-Z0-9\_\.]+\}/', $title, $titleField);

        $uniqids = $select = [];

        $match = $templateField[0] ?? [];
        foreach ($match as $k) {
            $k                       = str_replace(['{', '}'], '', $k);
            $uniqids[]               = $k;
            [$tableName, $fieldName] = str_contains($k, '.') ? explode('.', $k) : [$crudInfo['table_name_en'], $k];
            if ($tableName !== $crudInfo['table_name_en']) {
                $k .= ' as ' . $tableName . '_' . $fieldName;
            } else {
                $k = $tableName . '.' . $fieldName;
            }
            $select[] = $k;
        }
        if (isset($titleField[0])) {
            foreach ($titleField[0] as $k) {
                $k                       = str_replace(['{', '}'], '', $k);
                $uniqids[]               = $k;
                [$tableName, $fieldName] = str_contains($k, '.') ? explode('.', $k) : [$crudInfo['table_name_en'], $k];
                if ($tableName !== $crudInfo['table_name_en']) {
                    $select[] = $k . ' as ' . $tableName . '_' . $fieldName;
                } else {
                    $select[] = $tableName . '.' . $fieldName;
                }
            }
        }

        $fieldService = app()->get(SystemCrudFieldService::class);
        $crudField    = $fieldService->fieldNameByFieldCrud($uniqids, $crudInfo['table_name_en']);

        $association = [];
        foreach ($crudField as $crud) {
            foreach ($crud['field'] as $item) {
                if ($item['association_crud_id'] && $item['association']) {
                    $mainField = $fieldService->value(['crud_id' => $item['association']['id'], 'is_main' => 1], 'field_name_en');
                    if ($mainField) {
                        $association[$item['association']['table_name_en']] = [
                            'field'      => $item['field_name_en'],
                            'main_field' => $mainField,
                        ];
                    }
                }
            }
        }
        if (! $select) {
            $select = [$crudInfo['table_name_en'] . '.*'];
        }

        $viewSearch = $modelService->setViewSearch($viewSearch, $crudInfo['id'], 'form_field_uniqid');
        // 查询当前数据信息

        $dataInfo = $model->searchWhere(where: [
            [
                'field_name' => 'id',
                'form_value' => CrudFormEnum::FORM_SWITCH,
                'value'      => $dataId,
            ],
        ], viewSearch: $viewSearch, boolean: $searchBoolean)
            ->with([
                'updateUser' => fn ($q) => $q->select(['id', 'name', 'avatar'])->withTrashed(),
            ])
            ->select($select)->first()?->toArray();
        if (! $dataInfo) {
            return $this->result('没有查询到当前数据', true);
        }

        foreach ($association as $table => $item) {
            $value = $dataInfo[$item['field']] ?? null;
            if (is_null($value)) {
                continue;
            }

            $dataInfo[$item['field']] = DB::table($table)->where('id', $value)->value($item['main_field']);
        }

        $dataVar = [];
        foreach ($templateField[0] ?? [] as $string) {
            [$tableName] = str_contains($string, '.') ? explode('.', $string) : [$crudInfo['table_name_en'], $string];
            if ($tableName !== $crudInfo['table_name_en']) {
                $key = str_replace('.', '_', $string);
            } else {
                $key = $string;
            }
            $key = str_replace(['{', '}'], '', $key);

            $value = $dataInfo[$key] ?? null;
            if (is_null($value)) {
                continue;
            }
            $dataVar[] = $value;
            $template  = str_replace($string, (string) $value, $template);
        }

        foreach ($titleField[0] ?? [] as $string) {
            [$tableName] = str_contains($string, '.') ? explode('.', $string) : [$crudInfo['table_name_en'], $string];
            if ($tableName !== $crudInfo['table_name_en']) {
                $key = str_replace('.', '_', $string);
            } else {
                $key = $string;
            }
            $key = str_replace(['{', '}'], '', $key);

            $value = $dataInfo[$key] ?? null;
            if (is_null($value)) {
                continue;
            }
            $title = str_replace($string, (string) $value, $title);
        }

        $list = app()->get(AdminService::class)->userIdByUserInfo($event['send_user']);

        $sendData = [];

        $messageCateService = app()->get(MessageCateService::class);
        $cateInfo           = $messageCateService->get(['cate_name' => $crudInfo['table_name']]);
        if (! $cateInfo) {
            $cateId = $messageCateService->create([
                'cate_name' => $crudInfo['table_name'],
                'is_show'   => 1,
                'uni_show'  => 1,
            ])->id;
        } else {
            $cateId = $cateInfo->id;
        }
        // 提醒类型 0 = 通知，1=短信，2=企业微信bot，3=钉钉机器人，4=其他bot
        foreach ($list as $item) {
            // 通知
            if (in_array('0', $event['notify_type']) && $event['system_status']) {
                $sendData[] = [
                    'send_id'         => 1,
                    'cate_id'         => $cateId,
                    'message_id'      => 0,
                    'cate_name'       => '触发器定时执行',
                    'title'           => $title,
                    'image'           => '',
                    'template_type'   => MessageType::SYSTEM_CRUD_TYPE,
                    'button_template' => '',
                    'to_uid'          => $item['id'],
                    'message'         => $template,
                    'other'           => json_encode(['crud_id' => $crudInfo['id'], 'table_name_en' => $crudInfo['table_name_en'], 'id' => $dataId]),
                    'url'             => '',
                    'uni_url'         => '',
                    'type'            => '',
                    'entid'           => 1,
                    'link_id'         => $dataId,
                    'link_status'     => '',
                ];
                $task     = new NoticeMessageTask($sendData);
                $res      = Task::deliver($task);
                $sendData = [];
            }
            if (in_array('1', $event['notify_type']) && $event['sms_template_id'] && $event['sms_status']) {
                $task = new SmsMessageTask($item['phone'], 1, $event['sms_template_id'], $template, $dataVar);
                $res  = $res && Task::deliver($task);
            }
        }
        if (in_array('2', $event['notify_type']) && $event['work_webhook_url'] && $event['work_webhook_status']) {
            $res = WebhookJob::dispatch($event['work_webhook_url'], $title, $template, sys_config('site_url') . '/admin/develop/crud/design?id=' . $crudId, 2, $dataInfo['update_user']['name'] ?? '', $crudInfo['table_name']);
        }
        if (in_array('3', $event['notify_type']) && $event['ding_webhook_url'] && $event['ding_webhook_status']) {
            $res = WebhookJob::dispatch($event['ding_webhook_url'], $title, $template, sys_config('site_url') . '/admin/develop/crud/design?id=' . $crudId, 2, $dataInfo['update_user']['name'] ?? '', $crudInfo['table_name']);
        }
        if (in_array('4', $event['notify_type']) && $event['other_webhook_url'] && $event['other_webhook_status']) {
            $res = WebhookJob::dispatch($event['other_webhook_url'], $title, $template, sys_config('site_url') . '/admin/develop/crud/design?id=' . $crudId, 2, $dataInfo['update_user']['name'] ?? '', $crudInfo['table_name']);
        }

        return $this->result('推送成功', true);
    }

    /**
     * 字段聚合.
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/26
     * @throws BindingResolutionException
     */
    public function runFieldAggregate(CrudModuleService $modelService, BaseDao $model, array $crudInfo, array $join, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        $event['aggregate_field_rule']          = $event['aggregate_field_rule'] ?? [];
        $event['target_crud_id']                = $event['target_crud_id'] ?? 0;
        $event['aggregate_data_search']         = $event['aggregate_data_search'] ?? [];
        $event['aggregate_data_search_boolean'] = $event['aggregate_data_search_boolean'] ?? 0;

        if (! $event['aggregate_field_rule']) {
            return $this->result('没有聚合规格无法执行');
        }
        if (! $event['target_crud_id']) {
            return $this->result('没有目标实体无法执行');
        }

        $viewSearch       = $event['additional_search'] ?? [];
        $searchBoolean    = $event['additional_search_boolean'] == 0 ? 'or' : 'and';
        $aggSearchBoolean = $event['aggregate_data_search_boolean'] == 0 ? 'or' : 'and';
        $fieldService     = app()->get(SystemCrudFieldService::class);
        $crudService      = app()->get(SystemCrudService::class);

        if ($model->isJoin()) {
            $select = [$crudInfo['table_name_en'] . '.*'];
        } else {
            $select = ['*'];
        }

        $crudTableNames  = [$crudInfo['table_name_en']];
        $joinTableNames  = array_column($join, 'table_name_en');
        $searchTableNames = array_merge($crudTableNames, $joinTableNames);

        // 聚合数据条件
        $aggregateSearch = [];
        foreach ($event['aggregate_data_search'] as $item) {
            if (empty($item['form_field_uniqid']) || empty($item['operator'])) {
                continue;
            }

            $field = $this->parseValidCrudField(
                field: $item['form_field_uniqid'],
                defaultTableName: $crudInfo['table_name_en'],
                defaultCrudId: (int) $crudInfo['id'],
                crudService: $crudService,
                fieldService: $fieldService,
                allowTableNames: $searchTableNames
            );
            if (! $field) {
                continue;
            }
            $aggregateSearch[] = [
                'alias'      => $field['table_name'],
                'field_name' => $field['field_name'],
                'value'      => $item['value'],
                'operator'   => $item['operator'],
            ];
        }
        //聚合的条件，无法在更新上使用，所以只能放在查询数据里面一起使用
        $aggregateSearch = $modelService->setViewSearch($aggregateSearch, $crudInfo['id']);
        $viewSearch = $this->filterValidCrudSearch(
            viewSearch: $modelService->setViewSearch($viewSearch, $crudInfo['id'], 'form_field_uniqid'),
            defaultTableName: $crudInfo['table_name_en'],
            defaultCrudId: (int) $crudInfo['id'],
            crudService: $crudService,
            fieldService: $fieldService,
            allowTableNames: $searchTableNames
        );

        $viewSearch  = array_merge($viewSearch, $aggregateSearch);
        // 查询当前数据信息
        $dataInfo = $model->searchWhere(where: [
            [
                'field_name' => 'id',
                'form_value' => 'input',
                'value'      => $dataId,
            ],
        ], viewSearch: $viewSearch, boolean: $searchBoolean)
            ->select($select)->first()?->toArray();
        if (! $dataInfo) {
            return $this->result('没有查询到当前聚合的数据', true);
        }

        $systemCrud     = app()->get(SystemCrudService::class);
        $targetCrudName = $systemCrud->value($event['target_crud_id'], 'table_name_en');
        if (! $targetCrudName) {
            return $this->result('没有查询到目标实体的名称');
        }

        if (! $join) {
            return $this->result('没有查询到实体链表数据');
        }

        $joinData = [];
        foreach ($join as $item) {
            $joinData[$item['table_name_en']] = $item['field_name_en'];
        }

        if (! isset($joinData[$targetCrudName])) {
            return $this->result('目标实体不再链表内');
        }

        $associationFieldNameEn = $fieldService->value(['association_crud_id' => $event['target_crud_id'], 'crud_id' => $crudId], 'field_name_en');
        if (! $associationFieldNameEn) {
            return $this->result('没有查询到目标实体关联的字段名称');
        }

        $updateWhere['id'] = $dataInfo[$associationFieldNameEn] ?? 0;
        if (! $updateWhere['id']) {
            return $this->result('没有查询到目标实体关联的字段值');
        }

        $updateData = [];

        $newModel = $modelService->setTableName($crudInfo['table_name_en'])->setJoin($join)->viewSearch(viewSearch: [
            [
                'field_name' => $targetCrudName . '.id',
                'operator'   => CrudOperatorEnum::OPERATOR_EQ,
                'value'      => $updateWhere['id'],
            ],
        ]);

        $sourceTableNames = array_merge([$crudInfo['table_name_en'], $targetCrudName], $joinTableNames);

        // 聚合目标
        foreach ($event['aggregate_field_rule'] as $item) {
            if (empty($item['form_field_uniqid']) || empty($item['operator'])) {
                continue;
            }

            $targetField = $this->parseValidCrudField(
                field: $item['form_field_uniqid'],
                defaultTableName: $targetCrudName,
                defaultCrudId: (int) $event['target_crud_id'],
                crudService: $crudService,
                fieldService: $fieldService,
                allowTableNames: [$targetCrudName]
            );
            if (! $targetField) {
                continue;
            }

            $sourceFieldName = '';
            if ($item['operator'] !== CrudAggregateEnum::AGGREGATE_COUNT) {
                if (empty($item['to_form_field_uniqid'])) {
                    continue;
                }
                $sourceField = $this->parseValidCrudField(
                    field: $item['to_form_field_uniqid'],
                    defaultTableName: $crudInfo['table_name_en'],
                    defaultCrudId: (int) $crudInfo['id'],
                    crudService: $crudService,
                    fieldService: $fieldService,
                    allowTableNames: $sourceTableNames
                );
                if (! $sourceField) {
                    continue;
                }
                $sourceFieldName = $sourceField['qualified_name'];
            }

            switch ($item['operator']) {
                case CrudAggregateEnum::AGGREGATE_SUM:
                    $updateData[$targetField['field_name']] = $newModel->sum($sourceFieldName);
                    break;
                case CrudAggregateEnum::AGGREGATE_COUNT:
                    $updateData[$targetField['field_name']] = $newModel->count();
                    break;
                case CrudAggregateEnum::AGGREGATE_UNIQID_COUNT:
                    $updateData[$targetField['field_name']] = $newModel->groupBy($sourceFieldName)->count();
                    break;
                case CrudAggregateEnum::AGGREGATE_AVG:
                    $updateData[$targetField['field_name']] = $newModel->avg($sourceFieldName);
                    break;
                case CrudAggregateEnum::AGGREGATE_MAX:
                    $updateData[$targetField['field_name']] = $newModel->max($sourceFieldName);
                    break;
                case CrudAggregateEnum::AGGREGATE_MIN:
                    $updateData[$targetField['field_name']] = $newModel->min($sourceFieldName);
                    break;
            }
        }

        // 设置最终，更新实体的模型
        try {
            $model = $modelService->model(crudId: $event['target_crud_id'])->setJoin([])->viewSearch();
        } catch (\Throwable $e) {
            return $this->result($e->getMessage());
        }

        if ($updateData && $updateWhere) {
            $model->where($updateWhere)->update($updateData);

            return $this->result('聚合成功', true);
        }
        return $this->result('缺少更新条件或者更新数据');
    }

    /**
     * 解析并校验低代码实体字段，避免无效配置穿透到 SQL 层。
     */
    protected function parseValidCrudField(
        string $field,
        string $defaultTableName,
        int $defaultCrudId,
        SystemCrudService $crudService,
        SystemCrudFieldService $fieldService,
        array $allowTableNames = []
    ): array {
        $field = trim($field);
        if ($field === '') {
            return [];
        }

        [$tableName, $fieldName] = str_contains($field, '.')
            ? explode('.', $field, 2)
            : [$defaultTableName, $field];

        $tableName = trim($tableName);
        $fieldName = trim($fieldName);
        if ($tableName === '' || $fieldName === '') {
            return [];
        }

        if ($allowTableNames && ! in_array($tableName, $allowTableNames, true)) {
            return [];
        }

        $crudId = $tableName === $defaultTableName
            ? $defaultCrudId
            : (int) $crudService->value(['table_name_en' => $tableName], 'id');

        if (! $crudId || ! $fieldService->getCrudTableFieldAllCache($crudId, $fieldName)) {
            return [];
        }

        if (! Schema::hasColumn($tableName, $fieldName)) {
            return [];
        }

        return [
            'crud_id'        => $crudId,
            'table_name'     => $tableName,
            'field_name'     => $fieldName,
            'qualified_name' => $tableName . '.' . $fieldName,
        ];
    }

    /**
     * 过滤已被删除或未同步到真实表结构的搜索字段。
     */
    protected function filterValidCrudSearch(
        array $viewSearch,
        string $defaultTableName,
        int $defaultCrudId,
        SystemCrudService $crudService,
        SystemCrudFieldService $fieldService,
        array $allowTableNames = []
    ): array {
        $search = [];
        foreach ($viewSearch as $item) {
            if (empty($item['field_name']) || empty($item['operator'])) {
                continue;
            }

            $field = $this->parseValidCrudField(
                field: $item['field_name'],
                defaultTableName: $defaultTableName,
                defaultCrudId: $defaultCrudId,
                crudService: $crudService,
                fieldService: $fieldService,
                allowTableNames: $allowTableNames
            );
            if (! $field) {
                continue;
            }

            if (! str_contains($item['field_name'], '.')) {
                $item['field_name'] = $field['field_name'];
            }
            $search[] = $item;
        }

        return $search;
    }

    /**
     * 远程获取数据更新字段.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|\ReflectionException
     */
    public function runGetData(CrudModuleService $modelService, BaseDao $model, array $crudInfo, array $join, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        if (! $event['target_crud_id']) {
            return $this->result('缺少目标ID');
        }
        if (! $event['curl_id']) {
            return $this->result('缺少请求ID');
        }

        if (! $event['field_options']) {
            return $this->result('缺少更新字段');
        }

        $isSkipValue = ! (bool) ($event['options']['is_skip_value'] ?? 0);

        $fieldService = app()->get(SystemCrudFieldService::class);

        // 设置最终，更新实体的模型
        try {
            $model = $modelService->model(crudId: $event['target_crud_id']);
        } catch (\Throwable $e) {
            return $this->result($e->getMessage());
        }

        try {
            $curlInfo = app()->get(SystemCrudCurlService::class)->send($event['curl_id'], [], $data['page'] ?? -1);
        } catch (\Throwable $e) {
            Cache::tags('event')->forget('event_curl_' . $event['id']);
            return $this->result($e->getMessage());
        }

        $formFieldUniqid = array_column($event['field_options'], 'form_field_uniqid');
        $list            = $fieldService->fieldNameByFieldCrud($formFieldUniqid, $crudInfo['table_name_en']);

        $listkey = null;
        foreach ($event['field_options'] as $item) {
            if (isset($item['operator']) && $item['operator'] == CrudUpdateEnum::UPDATE_TYPE_FIELD) {
                [$newk]  = explode('*', $item['to_form_field_uniqid']);
                $listkey = substr($newk, 0, strrpos($newk, '.'));
                break;
            }
        }
        if (! $listkey) {
            Cache::tags('event')->forget('event_curl_' . $event['id']);
            return $this->result('执行失败');
        }

        $response = (array) Arr::get($curlInfo['response'], $listkey, []);
        if (count($response) <= 0) {
            $curlInfo['pageData']['page_end'] = false;
        }

        // 提取计算公式中的模板变量
        foreach ($event['field_options'] as $index => $item) {
            if ($item['operator'] === CrudUpdateEnum::UPDATE_TYPE_FORMULA_VALUE) {
                preg_match_all('/\{[a-zA-Z0-9\_\.]+\}/', $item['value'], $fields);
                $fields = $fields[0] ?? [];
                foreach ($fields as $value) {
                    $field = str_replace(['{', '}'], '', $value);

                    [$alias, $name] = str_contains($field, '.')
                        ? explode('.', $field)
                        : [$crudInfo['table_name_en'], $field];
                    if ($crudInfo['table_name_en'] !== $alias) {
                        $select[] = $alias . '.' . $name . ' as ' . $alias . '_' . $name;
                    } else {
                        $select[] = $alias . '.' . $name;
                    }

                    $item['template'][] = $field = str_replace('.', '_', $field);
                    $item['value']      = str_replace($value, $field, $item['value']);
                }
                $item['expression']             = $item['value'];
                $event['field_options'][$index] = $item;
            }
        }

        $expressionLanguage = app()->get(ExpressionLanguage::class);

        foreach ($response as $listItem) {
            $updateId = null;
            $dataItem = [];
            $continue = false;
            foreach ($event['field_options'] as $item) {
                $fieldName = $item['form_field_uniqid'];

                $isUniqid  = false;
                $fieldInfo = [];
                foreach ($list as $vv) {
                    foreach ($vv['field'] as $field) {
                        if ($field['field_name_en'] === $fieldName) {
                            $isUniqid  = (bool) $field['is_uniqid'];
                            $fieldInfo = $field;
                            break;
                        }
                    }
                }

                $value = null;
                switch ($item['operator']) {
                    case CrudUpdateEnum::UPDATE_TYPE_FIELD:
                        [, $key]              = explode('*', $item['to_form_field_uniqid']);
                        $key                  = substr($key, 1);
                        $dataItem[$fieldName] = $value = Arr::get($listItem, $key);
                        break;
                    case CrudUpdateEnum::UPDATE_TYPE_VALUE:
                        $dataItem[$fieldName] = $modelService->setValueAttribute($fieldInfo['form_value'], $item['value']);
                        break;
                    case CrudUpdateEnum::UPDATE_TYPE_NULL_VALUE:
                        $dataItem[$fieldName] = $modelService->setValueEmptyAttribute($fieldInfo['form_value']);
                        break;
                    case CrudUpdateEnum::UPDATE_TYPE_FORMULA_VALUE:
                        if (! empty($item['expression'])) {
                            $values = [];
                            if (! empty($item['template'])) {
                                foreach ($item['template'] as $field) {
                                    $values[$field] = $dataInfo[$field] ?? 0;
                                }
                            }
                            $dataItem[$fieldName] = $expressionLanguage->evaluate($item['expression'], $values);
                        }
                        break;
                }

                if ($isSkipValue) {
                    if ($isUniqid && $value && $model->count([$fieldName => $value])) {
                        $continue = true;
                    }
                } else {
                    if ($isUniqid && $value) {
                        $updateId = $model->value([$fieldName => $value], 'id');
                        if ($updateId) {
                            unset($dataItem[$fieldName]);
                        }
                    }
                }
            }

            if ($continue) {
                continue;
            }
            if (! $dataItem) {
                continue;
            }
            if ($updateId) {
                $model->update(['id' => $updateId], $dataItem);
                $modelService->handleEvent(app()->get(SystemCrudService::class)->get($crudId), CrudTriggerEnum::TRIGGER_UPDATED, $updateId, $dataItem, []);
            } else {
                $dataItem['created_at'] = $dataItem['updated_at'] = date('Y-m-d H:i:s');
                if (! isset($dataItem['user_id'])) {
                    $dataItem['user_id'] = $crudInfo['user_id'];
                }
                if (! isset($dataItem['owner_user_id'])) {
                    $dataItem['owner_user_id'] = $crudInfo['user_id'];
                }
                $res = $model->create($dataItem);
                $modelService->handleEvent(app()->get(SystemCrudService::class)->get($crudId), CrudTriggerEnum::TRIGGER_CREATED, $res->id, $dataItem, []);
            }
        }
        if ($curlInfo['pageData']['is_page'] && $curlInfo['pageData']['page_end']) {
            Cache::tags('event')->set('event_curl_' . $event['id'], $event['id']);
            CrudEventRunGetDataJob::dispatch($crudId, $action, $event, (int) $curlInfo['pageData']['page'] + 1);
        }
        if (! $curlInfo['pageData']['page_end']) {
            Cache::tags('event')->forget('event_curl_' . $event['id']);
        }

        return $this->result('执行成功', true);
    }

    /**
     * 根据当前时间判断是否需要执行定时任务
     * @return bool
     */
    protected function isExecute(Carbon $currentDateTime, int $intervalType, array $timerOption = [])
    {
        // 执行的配置参数
        $config = [
            'month'   => $timerOption['month'] ?? 0, // 月份，1表示1月
            'day'     => $timerOption['day'] ?? 0, // 日期，1表示1号
            'hour'    => $timerOption['hour'] ?? 0, // 小时，0表示0点
            'minute'  => $timerOption['minute'] ?? 0, // 分钟，0表示0分
            'second'  => $timerOption['second'] ?? 0, // 秒数，0表示0秒
            'weekday' => $timerOption['weekday'] ?? 0, // 周几，0表示星期日
        ];

        if ($currentDateTime->daysInMonth < $config['day']) {
            $config['day'] = $currentDateTime->daysInMonth;
        }
        if ($config['month'] > 12) {
            $config['month'] = 12;
        }
        if ($config['hour'] > 24) {
            $config['hour'] = 24;
        }
        if ($config['minute'] > 60) {
            $config['minute'] = 60;
        }
        if ($config['second'] > 60) {
            $config['second'] = 60;
        }
        if ($config['weekday'] > 7) {
            $config['weekday'] = 7;
        }

        $execute = false;
        $key     = 'timer_execute_' . $intervalType . $timerOption['id'];
        if (Cache::has($key)) {
            return false;
        }

        // 根据周期类型进行判断
        switch ($intervalType) {
            case 0: // 间隔秒数
                $execute = $config['second'] && ($currentDateTime->second % $config['second']) == 0;
                break;
            case 1: // 间隔n分
                $execute = ($currentDateTime->minute % $config['minute']) == 0 && $currentDateTime->second == $config['second'];
                break;
            case 2: // 间隔n小时
                $execute = $config['hour'] && ($currentDateTime->hour % $config['hour']) == 0 && $currentDateTime->minute == $config['minute'] && $currentDateTime->second == $config['second'];
                break;
            case 3: // 间隔n天
                $execute = $config['day'] && ($currentDateTime->day % $config['day']) == 0 && $currentDateTime->hour == $config['hour'] && $currentDateTime->minute == $config['minute'] && $currentDateTime->second == $config['second'];
                break;
            case 4: // 每天
                $execute = $currentDateTime->hour == $config['hour'] && $currentDateTime->minute == $config['minute'] && $currentDateTime->second == $config['second'];
                break;
            case 5: // 每星期
                $dayOfWeek = $currentDateTime->dayOfWeek;
                if ($dayOfWeek == 0) {
                    $dayOfWeek = 7;
                }

                $execute = $dayOfWeek == $config['weekday'] && $currentDateTime->hour == $config['hour'] && $currentDateTime->minute == $config['minute'] && $currentDateTime->second == $config['second'];
                break;
            case 6: // 每月
                $execute = $config['day'] && $currentDateTime->day == $config['day'] && $currentDateTime->hour == $config['hour'] && $currentDateTime->minute == $config['minute'] && $currentDateTime->second == $config['second'];
                break;
            case 7: // 每年
                $execute = $currentDateTime->day == $config['day'] && $currentDateTime->hour == $config['hour'] && $currentDateTime->minute == $config['minute'] && $currentDateTime->second == $config['second'];
                break;
        }

        // 判断当前时间是否在执行期内
        if ($execute) {
            Cache::add($key, 1, 1);
            return true;
        }
        return false;
    }

    /**
     * 获取数据库查询的字段.
     * @return array|string[]
     * @email 136327134@qq.com
     * @date 2024/4/9
     */
    protected function getDbSelects(array $fields, string $tableName, bool $continue = false)
    {
        $select = [$tableName . '.*'];
        foreach ($fields as $item) {
            if ($continue && ! empty($item['operator']) && $item['operator'] === CrudUpdateEnum::UPDATE_TYPE_FIELD) {
                continue;
            }
            if (! empty($item['operator']) && $item['operator'] !== CrudUpdateEnum::UPDATE_TYPE_FIELD) {
                continue;
            }
            if (! empty($item['to_form_field_uniqid'])) {
                [$alias, $name] = strstr($item['to_form_field_uniqid'], '.') !== false
                    ? explode('.', $item['to_form_field_uniqid'])
                    : [$tableName, $item['to_form_field_uniqid']];
                if ($tableName !== $alias) {
                    $select[] = $alias . '.' . $name . ' as ' . $alias . '_' . $name;
                } else {
                    $select[] = $alias . '.' . $name;
                }
            }
        }

        return $select;
    }

    /**
     * 执行下一个任务
     * @return false
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \Throwable
     * @email 136327134@qq.com
     * @date 2024/3/15
     */
    protected function nextTask(
        int $crudId,
        string $action,
        array $eventIds = [],
        int $dataId = 0,
        array $data = [],
        array $scheduleData = [],
        array $originalData = [],
        array $originalScheduleData = []
    ) {
        // 执行下一个任务
        if ($eventIds) {
            $eventId = array_shift($eventIds);
            $event   = $this->dao->get($eventId)?->toArray();
            if (! $event) {
                Log::error('下一个任务查询失败，事件ID：' . $eventId);

                return false;
            }

            $this->runEvent($crudId, $action, $event, $eventIds, $dataId, $data, $scheduleData, $originalData, $originalScheduleData);
        }
    }

    /**
     * 自动审批.
     * @return array
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     */
    protected function runAutoApprove(CrudModuleService $modelService, CrudModuleDao $model, array $crudInfo, int $crudId, string $action, array $event, array $eventIds, int $dataId, array $data, array $scheduleData, array $originalData, array $originalScheduleData)
    {
        // 附加条件
        $viewSearch    = $event['additional_search'] ?? [];
        $searchBoolean = $event['additional_search_boolean'] == 0 ? 'or' : 'and';

        $viewSearch = $modelService->setViewSearch($viewSearch, $crudInfo['id'], 'form_field_uniqid');
        $dataExists = $model->searchWhere(where: [
            [
                'field_name' => 'id',
                'form_value' => 'input',
                'value'      => $dataId,
            ],
        ], viewSearch: $viewSearch, boolean: $searchBoolean)->first()?->toArray();
        if (! $dataExists) {
            return $this->result('数据不满足自动审批条件', true);
        }
        $userId = $dataExists['owner_user_id'] ?: uuid_to_uid($this->uuId(false));
        unset($originalData['update_user_id']);
        app()->get(ApproveApplyService::class)->saveCrudApply(
            crudId: $crudId,
            data: $data,
            approveId: $event['crud_approve_id'],
            userId: $userId,
            linkId: $dataId,
            action: $action,
            table: app()->get(SystemCrudService::class)->value($crudId, 'table_name_en'),
            scheduleData: $scheduleData,
            originalData: $originalData,
            originalScheduleData: $originalScheduleData
        );
        return $this->result('创建审批成功', true);
    }

    /**
     * 自动撤销审批.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function runAutoRevokeApprove(mixed $modelService, CrudModuleDao $model, array $crudInfo, int $crudId, string $action, array $event, array $eventIds, int $dataId, array $data, array $scheduleData, array $originalData, array $originalScheduleData)
    {
        $applyService = app()->get(ApproveApplyService::class);
        $applys       = $applyService->column(['crud_id' => $crudId, 'link_id' => $dataId, 'approve_id' => $event['crud_approve_id'], 'status' => 0], ['id', 'user_id']);
        foreach ($applys as $apply) {
            $applyService->revokeApply($apply['id'], (int) $apply['user_id']);
        }
        return $this->result('撤销审批成功', true);
    }

    /**
     * 推送数据.
     * @return array
     * @throws BindingResolutionException
     */
    protected function runPushData(CrudModuleService $modelService, BaseDao $model, array $crudInfo, array $join, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        if (! $event['target_crud_id']) {
            return $this->result('缺少目标ID');
        }
        if (! $event['curl_id']) {
            return $this->result('缺少请求ID');
        }

        if (! $event['field_options']) {
            return $this->result('缺少更新字段');
        }

        $viewSearch    = $event['additional_search'] ?? [];
        $searchBoolean = $event['additional_search_boolean'] == 0 ? 'or' : 'and';

        $viewSearch = $modelService->setViewSearch($viewSearch, $crudInfo['id'], 'form_field_uniqid');
        // 查询当前数据信息
        $select = [];
        foreach ($event['field_options'] as $item) {
            if (! empty($item['form_field_uniqid'])) {
                [$alias, $name] = str_contains($item['form_field_uniqid'], '.')
                    ? explode('.', $item['form_field_uniqid'])
                    : [$crudInfo['table_name_en'], $item['form_field_uniqid']];
                if ($crudInfo['table_name_en'] !== $alias) {
                    $select[] = $alias . '.' . $name . ' as ' . $alias . '_' . $name;
                } else {
                    $select[] = $alias . '.' . $name;
                }
            }
        }

        $dataInfo = $model->searchWhere(where: [
            [
                'field_name' => 'id',
                'form_value' => CrudFormEnum::FORM_SWITCH,
                'value'      => $dataId,
            ],
        ], viewSearch: $viewSearch, boolean: $searchBoolean)
            ->select($select)->first()?->toArray();
        if (! $dataInfo) {
            return $this->result('没有查询到当前数据', true);
        }

        try {
            app()->get(SystemCrudCurlService::class)->send($event['curl_id'], [
                'action' => $action,
                'data'   => $dataInfo,
            ]);

            return $this->result('推送成功', true);
        } catch (\Throwable $e) {
            return $this->result($e->getMessage());
        }
    }

    /**
     * 日程待办触发器.
     * @return array
     * @throws BindingResolutionException
     */
    protected function runToDoSchedule(CrudModuleService $modelService, BaseDao $model, array $crudInfo, array $join, int $crudId, string $action, array $event, array $eventIds, int $dataId = 0, array $data = [], array $scheduleData = [])
    {
        $startTime     = $event['options']['start_time'] ?? [];
        $endTime       = $event['options']['end_time'] ?? [];
        $viewSearch    = $event['additional_search'] ?? [];
        $searchBoolean = $event['additional_search_boolean'] == 0 ? 'or' : 'and';
        $title         = $event['options']['title'] ?? '';
        $scheduleUser  = $event['options']['schedule_user'] ?? [];
        if (! $scheduleUser) {
            return $this->result('没有接受成员无法发送');
        }
        $template = $event['options']['template'] ?? '';
        preg_match_all('/\{[a-zA-Z0-9\_\.]+\}/', $template, $templateField);
        preg_match_all('/\{[a-zA-Z0-9\_\.]+\}/', $title, $titleField);

        $uniqids = $select = [];

        $match = $templateField[0] ?? [];
        foreach ($match as $k) {
            $k                       = str_replace(['{', '}'], '', $k);
            $uniqids[]               = $k;
            [$tableName, $fieldName] = str_contains($k, '.') ? explode('.', $k) : [$crudInfo['table_name_en'], $k];
            if ($tableName !== $crudInfo['table_name_en']) {
                $k .= ' as ' . $tableName . '_' . $fieldName;
            } else {
                $k = $tableName . '.' . $fieldName;
            }
            $select[] = $k;
        }
        unset($tableName, $fieldName, $k);
        if (isset($titleField[0])) {
            foreach ($titleField[0] as $k) {
                $k                       = str_replace(['{', '}'], '', $k);
                $uniqids[]               = $k;
                [$tableName, $fieldName] = str_contains($k, '.') ? explode('.', $k) : [$crudInfo['table_name_en'], $k];
                if ($tableName !== $crudInfo['table_name_en']) {
                    $select[] = $k . ' as ' . $tableName . '_' . $fieldName;
                } else {
                    $select[] = $tableName . '.' . $fieldName;
                }
            }
            unset($tableName, $fieldName, $k);
        }

        if (isset($startTime['operator']) && ! empty($startTime['form_field_uniqid']) && $startTime['operator'] == CrudUpdateEnum::UPDATE_TYPE_FIELD) {
            [$tableName, $fieldName] = str_contains($startTime['form_field_uniqid'], '.') ? explode('.', $startTime['form_field_uniqid']) : [$crudInfo['table_name_en'], $startTime['form_field_uniqid']];
            if ($tableName !== $crudInfo['table_name_en']) {
                $select[] = $startTime['form_field_uniqid'] . ' as ' . $tableName . '_' . $fieldName;
            } else {
                $select[] = $tableName . '.' . $fieldName;
            }
            unset($tableName, $fieldName);
        }

        if (isset($endTime['operator']) && ! empty($endTime['form_field_uniqid']) && $endTime['operator'] == CrudUpdateEnum::UPDATE_TYPE_FIELD) {
            [$tableName, $fieldName] = str_contains($endTime['form_field_uniqid'], '.') ? explode('.', $endTime['form_field_uniqid']) : [$crudInfo['table_name_en'], $endTime['form_field_uniqid']];
            if ($tableName !== $crudInfo['table_name_en']) {
                $select[] = $endTime['form_field_uniqid'] . ' as ' . $tableName . '_' . $fieldName;
            } else {
                $select[] = $tableName . '.' . $fieldName;
            }
            unset($tableName, $fieldName);
        }

        if (isset($scheduleUser['operator']) && ! empty($scheduleUser['form_field_uniqid']) && $scheduleUser['operator'] == CrudUpdateEnum::UPDATE_TYPE_FIELD) {
            [$tableName, $fieldName] = str_contains($scheduleUser['form_field_uniqid'], '.') ? explode('.', $scheduleUser['form_field_uniqid']) : [$crudInfo['table_name_en'], $scheduleUser['form_field_uniqid']];
            if ($tableName !== $crudInfo['table_name_en']) {
                $select[] = $scheduleUser['form_field_uniqid'] . ' as ' . $tableName . '_' . $fieldName;
            } else {
                $select[] = $tableName . '.' . $fieldName;
            }
            unset($tableName, $fieldName);
        }

        $fieldService = app()->get(SystemCrudFieldService::class);
        $crudField    = $fieldService->fieldNameByFieldCrud($uniqids, $crudInfo['table_name_en']);

        $association = [];
        foreach ($crudField as $crud) {
            foreach ($crud['field'] as $item) {
                if ($item['association_crud_id'] && $item['association']) {
                    $mainField = $fieldService->value(['crud_id' => $item['association']['id'], 'is_main' => 1], 'field_name_en');
                    if ($mainField) {
                        $association[$item['association']['table_name_en']] = [
                            'field'      => $item['field_name_en'],
                            'main_field' => $mainField,
                        ];
                    }
                }
            }
        }
        if (! $select) {
            $select = [$crudInfo['table_name_en'] . '.*'];
        }

        $select[] = $crudInfo['table_name_en'] . '.user_id';

        $viewSearch = $modelService->setViewSearch($viewSearch, $crudInfo['id'], 'form_field_uniqid');
        // 查询当前数据信息

        $dataInfo = $model->searchWhere(where: [
            [
                'field_name' => 'id',
                'form_value' => CrudFormEnum::FORM_SWITCH,
                'value'      => $dataId,
            ],
        ], viewSearch: $viewSearch, boolean: $searchBoolean)
            ->select($select)->first()?->toArray();
        if (! $dataInfo) {
            return $this->result('没有查询到当前数据', true);
        }

        foreach ($titleField[0] ?? [] as $string) {
            [$tableName] = str_contains($string, '.') ? explode('.', $string) : [$crudInfo['table_name_en'], $string];
            if ($tableName !== $crudInfo['table_name_en']) {
                $key = str_replace('.', '_', $string);
            } else {
                $key = $string;
            }
            $key = str_replace(['{', '}'], '', $key);

            $value = $dataInfo[$key] ?? null;
            if (is_null($value)) {
                continue;
            }
            $title = str_replace($string, (string) $value, $title);
        }

        foreach ($templateField[0] ?? [] as $string) {
            [$tableName] = str_contains($string, '.') ? explode('.', $string) : [$crudInfo['table_name_en'], $string];
            if ($tableName !== $crudInfo['table_name_en']) {
                $key = str_replace('.', '_', $string);
            } else {
                $key = $string;
            }
            $key = str_replace(['{', '}'], '', $key);

            $value = $dataInfo[$key] ?? null;
            if (is_null($value)) {
                continue;
            }
            $template = str_replace($string, (string) $value, $template);
        }

        $members = [];
        if (isset($scheduleUser['operator']) && ! empty($scheduleUser['form_field_uniqid']) && $scheduleUser['operator'] == CrudUpdateEnum::UPDATE_TYPE_FIELD) {
            [$tableName, $fieldName] = str_contains($scheduleUser['form_field_uniqid'], '.') ? explode('.', $scheduleUser['form_field_uniqid']) : [$crudInfo['table_name_en'], $scheduleUser['form_field_uniqid']];
            if ($tableName !== $crudInfo['table_name_en']) {
                $key = $tableName . '_' . $fieldName;
            } else {
                $key = $fieldName;
            }
            $value = $dataInfo[$key] ?? 0;
            if ($value) {
                $members = [$value];
            }
            unset($key);
        } elseif (isset($scheduleUser['operator']) && ! empty($scheduleUser['value']) && $scheduleUser['operator'] == CrudUpdateEnum::UPDATE_TYPE_VALUE) {
            $members = $scheduleUser['value'];
        }

        $startTimeDate = $endTimeDate = '';

        if (isset($startTime['operator']) && ! empty($startTime['form_field_uniqid']) && $startTime['operator'] == CrudUpdateEnum::UPDATE_TYPE_FIELD) {
            [$tableName, $fieldName] = str_contains($startTime['form_field_uniqid'], '.') ? explode('.', $startTime['form_field_uniqid']) : [$crudInfo['table_name_en'], $startTime['form_field_uniqid']];
            if ($tableName !== $crudInfo['table_name_en']) {
                $key = $tableName . '_' . $fieldName;
            } else {
                $key = $fieldName;
            }
            $value = $dataInfo[$key] ?? 0;
            if ($value) {
                $startTimeDate = $value;
            }
            unset($key, $tableName, $fieldName);
        } elseif (isset($startTime['operator']) && ! empty($startTime['value']) && $startTime['operator'] == CrudUpdateEnum::UPDATE_TYPE_VALUE) {
            $startTimeDate = str_replace('/', '-', $startTime['value']);
        }

        if (isset($endTime['operator']) && ! empty($endTime['form_field_uniqid']) && $endTime['operator'] == CrudUpdateEnum::UPDATE_TYPE_FIELD) {
            [$tableName, $fieldName] = str_contains($endTime['form_field_uniqid'], '.') ? explode('.', $endTime['form_field_uniqid']) : [$crudInfo['table_name_en'], $endTime['form_field_uniqid']];
            if ($tableName !== $crudInfo['table_name_en']) {
                $key = $tableName . '_' . $fieldName;
            } else {
                $key = $fieldName;
            }
            $value = $dataInfo[$key] ?? 0;
            if ($value) {
                $endTimeDate = $value;
            }
            unset($key, $tableName, $fieldName);
        } elseif (isset($endTime['operator']) && ! empty($endTime['value']) && $endTime['operator'] == CrudUpdateEnum::UPDATE_TYPE_VALUE) {
            $endTimeDate = str_replace('/', '-', $startTime['value']);
        }

        $schedule = [
            'title'      => $title,
            'member'     => $members,
            'content'    => $template,
            'cid'        => 1,
            'color'      => '',
            'remind'     => $event['options']['remind_time'] ?? '',
            'period'     => 0,
            'rate'       => 1,
            'days'       => [],
            'all_day'    => $event['options']['schedule_cycle'] ?? 0,
            'start_time' => $startTimeDate,
            'end_time'   => $endTimeDate,
            'fail_time'  => $endTimeDate,
        ];

        app()->get(ScheduleService::class)->saveSchedule($dataInfo['user_id'], 1, $schedule);

        return $this->result('执行成功', true);
    }
}
