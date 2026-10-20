<?php

namespace App\Http\Controller\AdminApi\Module;

use App\Constants\Crud\CrudEventEnum;
use App\Constants\Crud\CrudUpdateEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Crud\SystemCrudEventRequest;
use App\Http\Service\Crud\SystemCrudEventService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\Message\MessageTemplateService;
use Tuoluojiang\Open\services\expressionLanguage\ExpressionLanguage;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 触发器.
 * @package App\Http\Controller\AdminApi\Module
 */
#[Prefix('ent/crud/event')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CrudEventController extends AuthController
{
    /**
     * Crud constructor.
     */
    public function __construct(SystemCrudEventService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 事件类型.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Get('type', '触发器类型')]
    public function getEventType()
    {
        return $this->success(SystemCrudService::EVENT_TYPE);
    }

    /**
     * 获取聚合类型.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Get('aggregate', '触发器聚合类型')]
    public function getAggregateType()
    {
        return $this->success(SystemCrudService::AGGREGATE_TYPE);
    }

    /**
     * 获取执行动作类型.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Get('action', '触发器执行动作类型')]
    public function getActionType()
    {
        return $this->success(SystemCrudService::ACTION_TYPE);
    }

    /**
     * 获取触发器详情.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Get('info/{id}', '触发器详情')]
    public function getEventInfo($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->service->getEventInfo((int)$id));
    }

    /**
     * 获取事件内的字段信息.
     * @param int $eventId
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/19
     */
    #[Get('crud/{id}/{eventId?}', '触发器详情关联数据')]
    public function getEventCrud($id, $eventId = 0)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success(app()->make(SystemCrudService::class)->getEventCrud((int)$id, (int)$eventId));
    }


    /**
     * 事件列表.
     * @param int $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/18
     */
    #[Get('list/{id?}', '触发器列表')]
    public function eventList($id = 0)
    {
        $where = [
            'name'    => $this->request->get('name', ''),
            'crud_id' => $this->request->get('crud_id', ''),
            'cate_id' => $this->request->get('cate_id', ''),
        ];

        if ($id) {
            $where['crud_id'] = $id;
        }
        $order = $this->request->get('id_order_dy') ? 'desc' : 'asc';

        return $this->success($this->service->getList($where, ['id', 'crud_id', 'name', 'event', 'action', 'sort', 'updated_at', 'status'], ['sort' => 'desc', 'id' => $order]));
    }

    /**
     * 保存事件.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Post('save', '触发器保存')]
    public function saveEvent(SystemCrudService $crudService)
    {
        [$crudId, $name, $event] = $this->request->postMore([
            ['crud_id', ''],
            ['name', ''],
            ['event', ''],
        ], true);
        if (!$crudId) {
            return $this->fail('缺少参数');
        }
        if (!$crudService->count(['id' => $crudId])) {
            return $this->fail('没有查询到实体');
        }
        if (!$name) {
            return $this->fail('请输入事件名称');
        }
        if (!$event) {
            return $this->fail('请选择事件类型');
        }

        $eventAll = array_column(SystemCrudService::EVENT_TYPE, 'value');
        if (!in_array($event, $eventAll)) {
            return $this->fail('选择的事件类型不存在');
        }

        if (in_array($event, [
                CrudEventEnum::EVENT_AUTH_APPROVE,
                CrudEventEnum::EVENT_AUTO_REVOKE_APPROVE,
            ]) && app()->make(SystemCrudService::class)->value($crudId, 'crud_id')) {
            return $this->fail('从表不允许创建审批触发器');
        }

        $res = $this->service->create([
            'crud_id'                         => $crudId,
            'name'                            => $name,
            'event'                           => $event,
            'status'                          => 1,
            'additional_search_boolean'       => 1,
            'aggregate_target_search_boolean' => 1,
            'aggregate_data_search_boolean'   => 1,
        ]);
        event('system.crud');
        return $this->success('添加成功', ['id' => $res->id]);
    }

    /**
     * 修改事件.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Put('update/{id}', '触发器修改')]
    public function updateEvent(SystemCrudEventRequest $request, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $data = $request->postMore([
            ['name', ''],
            ['timer', 0],
            ['timer_type', 0],
            ['action', []],
            ['target_crud_id', 0],
            ['curl_id', 0],
            ['send_type', 0],
            ['send_user', []],
            ['notify_type', ''],
            ['sms_template_id', ''],
            ['system_status', ''],
            ['sms_status', ''],
            ['work_webhook_url', ''],
            ['work_webhook_status', ''],
            ['ding_webhook_url', ''],
            ['ding_webhook_status', ''],
            ['other_webhook_url', ''],
            ['other_webhook_status', ''],
            ['update_field_options', ''],
            ['sort', 0],
            ['additional_search', []],
            ['additional_search_boolean', 0],
            ['field_options', []],
            ['crud_approve_id', 0],
            ['template', ''],
            ['options', []], // 日程配置
            ['timer_options', []], // 时间配置
            ['aggregate_target_search', []],
            ['aggregate_target_search_boolean', 0],
            ['aggregate_data_search', []],
            ['aggregate_data_field', []],
            ['aggregate_data_search_boolean', 0],
            ['aggregate_field_rule', []],
        ]);
        if (!$data['action']) {
            return $this->fail('请选择执行动作');
        }
        $eventInfo = $this->service->get($id);
        if (!$eventInfo) {
            return $this->fail('没有查询到事件');
        }
        $data['event'] = $eventInfo->event;
        $data['crud_id'] = $eventInfo->crud_id;
        if (in_array($data['event'], [CrudEventEnum::EVENT_FIELD_UPDATE, CrudEventEnum::EVENT_AUTO_CREATE])
            && !$data['target_crud_id']) {
            return $this->fail('请选择目标实体');
        }
        if ($data['event'] === CrudEventEnum::EVENT_SEND_NOTICE) {
            if (!$data['template']) {
                return $this->fail('请输入通知内容');
            }
        }
        if ($data['event'] === CrudEventEnum::EVENT_DATA_CHECK) {
            if (!$data['template']) {
                return $this->fail('请输入校验失败模板内容');
            }
        }
        if (!in_array($data['event'], [
            CrudEventEnum::EVENT_GROUP_AGGREGATE,
            CrudEventEnum::EVENT_FIELD_AGGREGATE,
            CrudEventEnum::EVENT_DATA_CHECK,
            CrudEventEnum::EVENT_SEND_NOTICE,
            CrudEventEnum::EVENT_AUTH_APPROVE,
            CrudEventEnum::EVENT_AUTO_REVOKE_APPROVE,
            CrudEventEnum::EVENT_TO_DO_SCHEDULE,
            CrudEventEnum::EVENT_GET_DATA,
        ])) {
            if (!$data['target_crud_id']) {
                return $this->fail('请选择目标实体');
            }
            if (!$data['field_options']) {
                return $this->fail('请选择目标字段');
            }
        }
        if (in_array($data['event'], [
                CrudEventEnum::EVENT_AUTH_APPROVE,
                CrudEventEnum::EVENT_AUTO_REVOKE_APPROVE,
            ]) && !$data['crud_approve_id']) {
            return $this->fail('请选择审批流程');
        }

        // 检测获取数据选择的字段是否是一个列下的字段
        if ($data['event'] === CrudEventEnum::EVENT_GET_DATA) {
            $keys = [];
            foreach ($data['field_options'] as $i => $option) {
                if (isset($option['operator']) && in_array($option['operator'], [CrudUpdateEnum::UPDATE_TYPE_SKIP_VALUE, CrudUpdateEnum::UPDATE_TYPE_FIELD])) {
                    if (!str_contains($option['to_form_field_uniqid'], '*')) {
                        return $this->fail('必须选择带有星号的源字段');
                    }
                    [$newk] = explode('*', $option['to_form_field_uniqid']);
                    $keys[$newk] = $i;
                }
            }
            if (!$keys) {
                return $this->fail('至少选择一个源字段列表数据');
            }
            if (count($keys) >= 2) {
                return $this->fail('选择的源字段必须为同一个数组下的字段');
            }
        }

        // 检测输入的公式是否有误
        if (in_array($data['event'], [CrudEventEnum::EVENT_FIELD_UPDATE, CrudEventEnum::EVENT_AUTO_CREATE])) {
            $expressionLanguage = app()->get(ExpressionLanguage::class);
            foreach ($data['field_options'] as $option) {
                if ($option['operator'] === CrudUpdateEnum::UPDATE_TYPE_FORMULA_VALUE) {
                    preg_match_all('/\{[a-zA-Z0-9\_\.]+\}/', $option['value'], $fields);
                    $fields = $fields[0] ?? [];
                    foreach ($fields as $value) {
                        $field = str_replace(['{', '}', '.'], ['', '', '_'], $value);
                        $option['value'] = str_replace($value, $field, $option['value']);
                        $option['template'][$field] = 1;
                    }
                    if (!empty($option['value'])) {
                        try {
                            $expressionLanguage->evaluate($option['value'], $option['template'] ?? []);
                        } catch (\Throwable $e) {
                            return $this->fail('目标字段“' . ($option['field_name'] ?? $option['form_field_uniqid']) . '”输入的公式错误,错误原因:' . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->service->updateEvent((int)$id, $data);
        event('system.crud');
        return $this->success('修改成功');
    }

    /**
     * 修改事件状态
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/18
     */
    #[Put('status/{id}', '触发器状态修改')]
    public function statusEvent($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $status = (int)$this->request->post('status', 0);
        $this->service->update($id, ['status' => $status]);

        app()->get(MessageTemplateService::class)->update(['crud_event_id' => $id], ['status' => $status]);

        event('system.crud');

        return $this->success('修改成功');
    }

    /**
     * 删除事件.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Delete('del/{id}', '触发器删除')]
    public function deleteEvent($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $this->service->delete($id);

        app()->get(MessageTemplateService::class)->delete(['crud_event_id' => $id]);

        event('system.crud');

        return $this->success('删除成功');
    }
}
