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

namespace App\Http\Service\WorkExternalContact;

use App\Constants\CustomEnum\CustomerEnum;
use App\Constants\System\ViewSearchEnum;
use App\Constants\Work\MediaEnum;
use App\Http\Dao\WorkExternalContact\WorkMassMessagingDao;
use App\Http\Dao\WorkExternalContact\WorkMassMessagingTempDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\LabelService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Work\WorkClientFollowService;
use App\Http\Service\Work\WorkClientFollowTagsService;
use App\Http\Service\Work\WorkClientService;
use App\Http\Service\Work\WorkGroupChatService;
use App\Jobs\WorkExternalContact\WorkMessagingAddJob;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\wechat\Work;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 企微群发消息.
 */
class WorkMassMessagingService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    protected $tempDao;

    public function __construct(WorkMassMessagingDao $dao, WorkMassMessagingTempDao $tempDao)
    {
        $this->dao = $dao;
        $this->tempDao = $tempDao;
    }

    /**
     * 获取列表.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $with           = ['creator', 'temp', 'send_admin', 'not_send_admin'];
        $list           = $this->dao->setTimeField('send_time')->getList($where, $field, $page, $limit, $sort, $with);
        $list           = collect($list)->map(function ($item) {
            $item['temp_content']         = $item['temp']['content'] ?? $this->tempDao->getTempTrashedContent($item['temp_id']);
            $item['temp_types']           = collect($item['temp']['attach'] ?? [])->pluck('types')->unique()->filter()->all();
            $item['send_user_string']     = collect($item['send_admin'] ?? [])->unique('id')->pluck('name')->unique()->filter()->implode('、') ?: '--';
            $item['not_sent_user_string'] = collect($item['not_send_admin'] ?? [])->unique('id')->pluck('name')->unique()->filter()->implode('、') ?: '--';
            $item['sent_uid']             = collect($item['send_admin'] ?? [])->unique('id')->pluck('id')->values()->all();
            $item['sent_user_string']     = collect($item['send_admin'] ?? [])->unique('id')->pluck('name')->unique()->filter()->implode('、') ?: '--';
            unset($item['temp'],$item['send_admin'],$item['not_send_admin']);
            return $item;
        })->forget(['temp', 'send_group', 'search', 'send_customer', 'send_admin', 'not_send_admin'])->all();
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 保存.
     * @return array|BaseModel|mixed|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceSave(array $data)
    {
        $tempData = collect($data['temp'] ?? []);
        unset($data['temp']);
        // 验证模板条件：无临时数据且无模板ID则报错
        if ($tempData->isEmpty() && ! $data['temp_id']) {
            throw $this->exception('缺少发送内容');
        }
        // 处理模板ID（新建或复用）
        if (empty($data['temp_id'])) {
            $tempService     = app(WorkMassMessagingTempService::class);
            $filteredTemp    = $this->filterDataByRules($tempData->all(), $data['uid']);
            $data['temp_id'] = $tempService->resourceSave($filteredTemp)['id'];
        }
        // 验证发送成员
        if (! $data['send_uid']) {
            throw $this->exception('请选择发送成员');
        }
        // 处理接收者列表（全量/筛选）
        $isAll           = (int) $data['is_all'];
        $messageType     = (int) ($data['types'] ?? 0);
        $customerService = app(CustomerService::class);
        switch ($isAll) {
            case 2:
                $followId      = [];
                $followService = app(WorkClientFollowService::class);
                $search        = collect($data['search'])->filter(function ($item) {
                    if (! Arr::has($item, ['field', 'value'])) {
                        return false;
                    }
                    return filled($item['field']) && filled($item['value']);
                })->pluck('value', 'field')->all();
                $labelSearch = $search['customer_label'] ?? [];
                if ($labelSearch) {
                    $workLabel = app(LabelService::class)->column(['id' => $labelSearch], 'work_tag_id');
                    $followId  = collect(app()->get(WorkClientFollowTagsService::class)->column(['tag_id' => $workLabel], 'follow_id') ?? [])->filter()->unique()->all();
                }
                $timeSearch = $search['created_at'] ?? [];
                if ($labelSearch) {
                    $followId = array_intersect($followId, collect($followService->column(['create_time' => $timeSearch], 'id') ?? [])->filter()->unique()->all());
                }
                $data['send_customer'] = collect($followService->select(['id' => $followId], with: ['client'])?->toArray() ?? [])->pluck('client.external_userid')->filter()->unique()->values()->all();
                $data['be_sent']       = count($data['send_customer']);
                break;
            case 1:
                switch ($messageType) {
                    case 1:
                        $group              = app()->get(WorkGroupChatService::class)->column(['admin_id' => $data['send_uid']], 'chat_id');
                        $data['send_group'] = $group ? collect($group)->pluck('chat_id')->filter()->values()->all() : [];
                        $data['be_sent']    = count($data['send_group']);
                        break;
                    case 0:
                        $data['send_customer'] = collect(
                            $customerService->column(['uid' => $data['send_uid']], 'external_userid')
                        )->filter()->unique()->values()->all();
                        $data['be_sent'] = count($data['send_customer']);
                        break;
                }
                break;
            default:
                if (! $messageType) {// 群发消息
                    // 验证筛选条件（转为集合判断是否为空）
                    if (collect($data['search'] ?? [])->isEmpty()) {
                        throw $this->exception('缺少筛选条件');
                    }
                    // 转换筛选条件为字段=>值格式
                    $search = collect($data['search'])->filter(function ($item) {
                        if (! Arr::has($item, ['field', 'value'])) {
                            return false;
                        }
                        return filled($item['field']) && filled($item['value']);
                    })->pluck('value', 'field')->all();
                    // 筛选客户：集合链式处理查询结果
                    $data['send_customer'] = collect(
                        $customerService->listSearch($search + ['types' => ViewSearchEnum::VIEW_CUSTOMER])->select('external_userid')->get()
                    )->pluck('external_userid')->filter()->unique()->values()->all();
                } else {
                    $data['be_sent'] = count($data['send_group']);
                }
        }
        ! $data['is_timed'] && $data['send_time'] = now()->toDateTimeString();
        $saved                                    = $this->dao->create(collect($data)->all());
        ! $data['is_timed'] && WorkMessagingAddJob::dispatch($saved->id)->delay(now()->addSeconds(10));
        return $saved->id;
    }

    /**
     * 企微发送群发消息.
     * @throws BindingResolutionException
     * @throws ClientExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \ReflectionException
     */
    public function sendWorkMsg(int $id)
    {
        $data = $this->dao->get($id, with: ['temp']);
        if ($data['types'] == 2) {
            $this->addWorkMoment($data);
        } else {
            $this->addWorkMsg($data);
        }
    }

    public function resourceUpdate($id, array $data)
    {
        $info     = $this->dao->get($id);
        $tempData = collect($data['temp'] ?? []);
        unset($data['temp']);
        // 验证模板条件：无临时数据且无模板ID则报错
        if ($tempData->isEmpty() && ! $data['temp_id']) {
            throw $this->exception('缺少发送内容');
        }
        $tempService = app(WorkMassMessagingTempService::class);
        // 处理模板ID（新建或复用）
        if (empty($data['temp_id'])) {
            $filteredTemp    = $this->filterDataByRules($tempData->all(), $data['uid']);
            $data['temp_id'] = $tempService->resourceSave($filteredTemp)['id'];
        } else {
            $tempService->resourceUpdate($data['temp_id'], $tempData->all());
        }
        if ($data['temp_id'] != $info?->temp_id) {
            $tempService->resourceDelete($info?->temp_id);
        }
        // 验证发送成员
        if (! $data['send_uid']) {
            throw $this->exception('请选择发送成员');
        }
        // 处理接收者列表（全量/筛选）
        $isAll           = (int) $data['is_all'];
        $messageType     = (int) ($data['types'] ?? 0);
        $customerService = app(CustomerService::class);
        switch ($isAll) {
            case 2:
                $followId      = [];
                $followService = app(WorkClientFollowService::class);
                $search        = collect($data['search'])->filter(function ($item) {
                    if (! Arr::has($item, ['field', 'value'])) {
                        return false;
                    }
                    return filled($item['field']) && filled($item['value']);
                })->pluck('value', 'field')->all();
                $labelSearch = $search['customer_label'] ?? [];
                if ($labelSearch) {
                    $workLabel = app(LabelService::class)->column(['id' => $labelSearch], 'work_tag_id');
                    $followId  = collect(app()->get(WorkClientFollowTagsService::class)->column(['tag_id' => $workLabel], 'follow_id') ?? [])->filter()->unique()->all();
                }
                $timeSearch = $search['created_at'] ?? [];
                if ($labelSearch) {
                    $followId = array_intersect($followId, collect($followService->column(['create_time' => $timeSearch], 'id') ?? [])->filter()->unique()->all());
                }
                $data['send_customer'] = collect($followService->select(['id' => $followId], with: ['client'])?->toArray() ?? [])->pluck('client.external_userid')->filter()->unique()->values()->all();
                $data['be_sent']       = count($data['send_customer']);
                break;
            case 1:
                switch ($messageType) {
                    case 1:
                        $group              = app()->get(WorkGroupChatService::class)->column(['admin_id' => $data['send_uid']], 'chat_id');
                        $data['send_group'] = $group ? collect($group)->pluck('chat_id')->filter()->values()->all() : [];
                        $data['be_sent']    = count($data['send_group']);
                        break;
                    case 0:
                        $data['send_customer'] = collect(
                            $customerService->column(['uid' => $data['send_uid']], 'external_userid')
                        )->filter()->unique()->values()->all();
                        $data['be_sent'] = count($data['send_customer']);
                        break;
                }
                break;
            default:
                if (! $messageType) {// 群发消息
                    // 验证筛选条件（转为集合判断是否为空）
                    if (collect($data['search'] ?? [])->isEmpty()) {
                        throw $this->exception('缺少筛选条件');
                    }
                    // 转换筛选条件为字段=>值格式
                    $search = collect($data['search'])->filter(function ($item) {
                        if (! Arr::has($item, ['field', 'value'])) {
                            return false;
                        }
                        return filled($item['field']) && filled($item['value']);
                    })->pluck('value', 'field')->all();
                    // 筛选客户：集合链式处理查询结果
                    $data['send_customer'] = collect(
                        $customerService->listSearch($search + ['types' => ViewSearchEnum::VIEW_CUSTOMER])->select('external_userid')->get()
                    )->pluck('external_userid')->filter()->unique()->values()->all();
                } else {
                    $data['be_sent'] = count($data['send_group']);
                }
        }
        ! $data['is_timed'] && $data['send_time'] = now()->toDateTimeString();
        unset($data['uid']);
        $saved = $this->dao->update($id, collect($data)->all());
        ! $data['is_timed'] && WorkMessagingAddJob::dispatch((int) $id)->delay(now()->addSeconds(10));
        return $saved;
    }

    /**
     * 删除.
     * @param mixed $id
     * @return null|bool|mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $info        = $this->dao->get($id);
        $tempService = app(WorkMassMessagingTempService::class);
        $tempService->resourceDelete($info?->temp_id);
        return $this->dao->delete($id);
    }

    public function resourceCreate(array $other = []): array
    {
        // TODO: Implement resourceCreate() method.
    }

    /**
     * 获取详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $item = $this->dao->get($id, with: ['temp', 'send_admin', 'not_send_admin'])?->toArray();
        if (! $item) {
            throw $this->exception('数据不存在');
        }
        $adminsMap         = collect(app()->get(AdminService::class)->select(['id' => $item['send_uid']], ['id', 'name', 'avatar', 'uid', 'phone'])?->toArray())->keyBy('id');
        $item['send_user'] = collect($item['send_uid'] ?? [])->map(fn ($uid) => $adminsMap[$uid] ?? null)->filter();
        if ($item['send_admin']) {
            $item['sent_uid'] = collect($item['send_admin'])->unique('id')->pluck('id')->values()->all();
        } else {
            $item['sent_uid'] = collect($item['send_admin'])->unique('id')->pluck('id')->values()->all();
        }
        unset($item['send_admin'], $item['not_send_admin']);
        $item['temp'] = collect($item['temp'] ?? [])->only(['content', 'attach'])->filter()->all();
        if (! empty($item['temp']['attach'])) {
            $item['temp']['attach'] = collect($item['temp']['attach'])
                ->map(function ($attachItem) {
                    $cleanAttach = collect($attachItem)->forget(['created_at', 'updated_at'])->all();
                    if (! empty($cleanAttach['file'])) {
                        $cleanAttach['file'] = collect($cleanAttach['file'])
                            ->only(['id', 'file_name', 'file_url', 'file_size'])
                            ->mapWithKeys(function ($value, $key) {
                                return match ($key) {
                                    'file_name' => ['name' => $value],
                                    'file_url'  => ['url' => link_file($value)],
                                    'file_size' => ['size' => $value],
                                    default     => [$key => $value]
                                };
                            })->all();
                    }
                    return $cleanAttach;
                })->all();
        }
        $item['search'] = $this->getSearchField($item['search']);
        return $item;
    }

    /**
     * 获取筛选字段.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSearchField(mixed $data = []): array
    {
        if (! $data) {
            return [];
        }
        $fields = collect(app()->get(FormService::class)->getCustomDataByTypes(
            1,
            ['key as field', 'key_name as name', 'type', 'input_type', 'dict_ident'],
            ['dictData' => fn ($q) => $q->whereNot('type_name', 'area_cascade')],
        ) ?? [])->filter(function ($value) {
            return ! in_array(strtolower($value['input_type'] ?? ''), ['images', 'file', 'oawangeditor']) && ! in_array(strtolower($value['field'] ?? ''), ['contract_followed', 'customer_followed']);
        })->values()->all();
        $dataMap = collect($data)->pluck('value', 'field')->all();
        return collect(array_merge(CustomerEnum::CUSTOMER_SEARCH_FIELD, CustomerEnum::CUSTOMER_VIEWER_SEARCH_FIELD))->concat($fields)->filter(function ($value) use ($data) {
            return in_array($value['field'], array_column($data, 'field'));
        })->map(function ($value) use ($dataMap) {
            $value['options'] = $value['dict'] ?? [];
            $value['value']   = $dataMap[$value['field']] ?? '';
            unset($value['dict']);
            return $value;
        })->unique('name')->values()->all();
    }

    /**
     * 更新状态
     * @throws BindingResolutionException
     * @throws ClientExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \ReflectionException
     */
    public function updateStatus(int $id, int $status): void
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('数据不存在');
        }
        if ($info->status == $status) {
            return;
        }
        $info->status = $status;
        if ($status == 0) {
            $tasks = app()->get(WorkMassMessagingTaskService::class)->select(['mass_id' => $id, 'status' => 0], ['msgid', 'moment_id'])?->toArray();
            $work  = app()->get(Work::class);
            foreach ($tasks as $task) {
                if ($info->types == 2) {
                    $work->cancelMomentTask($task['moment_id']);
                } else {
                    $work->cancelMsg($task['msgid']);
                }
            }
        }
        $info->save();
    }

    /**
     * 提醒群发.
     * @throws BindingResolutionException
     * @throws ClientExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \ReflectionException
     */
    public function remind(int $id): void
    {
        $msgIds = app()->get(WorkMassMessagingTaskService::class)->column(['mass_id' => $id, 'status' => 0], 'msgid');
        $work   = app()->get(Work::class);
        foreach ($msgIds as $msgId) {
            $work->remind($msgId);
        }
    }

    /**
     * 添加企微群发消息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function addWorkMsg(mixed $data)
    {
        $sendUser    = collect(app()->get(AdminService::class)->select(['id' => $data['send_uid']], with: ['work']))?->pluck('work.userid', 'id')->filter()->unique()->all();
        $work        = app()->get(Work::class);
        $taskService = app()->get(WorkMassMessagingTaskService::class);
        $be_sent     = 0;
        if ($data['types'] == 1) {
            $groupChat = collect(app()->get(WorkGroupChatService::class)->select(['corp_id' => sys_config('wechat_work_corpid')]))->groupBy('owner')->map(function ($item) {
                return collect($item)->pluck('chat_id')->filter()->values()->all();
            })->filter()->all();
        } else {
            $externalUserid = collect(app()->get(WorkClientService::class)->select([
                'userid'          => array_values($sendUser),
                'external_userid' => $data['send_customer'],
                'corp_id'         => sys_config('wechat_work_corpid'),
            ], ['external_userid', 'userid'])?->toArray())
                ->groupBy('userid')->map(function ($item) {
                    return collect($item)->pluck('external_userid')->filter()->values()->all();
                })->filter()->all();
        }
        foreach ($sendUser as $key => $userId) {
            try {
                $msg = [
                    'chat_type'    => $data['types'] == 1 ? 'group' : 'single',
                    'sender'       => $userId,
                    'allow_select' => (bool) $data['is_modify'],
                    'text'         => [
                        'content' => $data['temp']['content'],
                    ],
                    'attachments' => collect($data['temp']['attach'] ?? [])->map(function ($item) {
                        $mediaId = $item['file']['media_id'] ?? '';
                        $typeMap = [
                            MediaEnum::TYPE_FILE => function ($item) use ($mediaId) {
                                return $mediaId ? [
                                    'msgtype' => 'file',
                                    'file'    => ['media_id' => $item['file']['media_id'] ?? ''],
                                ] : null;
                            },
                            MediaEnum::TYPE_IMAGE => function ($item) use ($mediaId) {
                                return $mediaId ? [
                                    'msgtype' => 'image',
                                    'image'   => ['media_id' => $item['file']['media_id'] ?? ''],
                                ] : null;
                            },
                            MediaEnum::TYPE_VIDEO => function ($item) use ($mediaId) {
                                return $mediaId ? [
                                    'msgtype' => 'video',
                                    'video'   => ['media_id' => $item['file']['media_id'] ?? ''],
                                ] : null;
                            },
                            MediaEnum::TEMP_MINI_PROGRAM => function ($item) use ($mediaId) {
                                return $mediaId ? [
                                    'msgtype'     => 'miniprogram',
                                    'miniprogram' => [
                                        'title'        => $item['title'] ?? '',
                                        'pic_media_id' => $item['file']['media_id'] ?? '',
                                        'appid'        => $item['app_id'] ?? '',
                                        'page'         => $item['link'] ?? '',
                                    ],
                                ] : null;
                            },
                            MediaEnum::TEMP_LINK => function ($item) {
                                return [
                                    'msgtype' => 'link',
                                    'link'    => [
                                        'title'  => $item['title'] ?? '',
                                        'picurl' => isset($item['file']['file_url']) ? link_file($item['file']['file_url']) : '',
                                        'desc'   => $item['info'] ?? '',
                                        'url'    => $item['link'] ?? '',
                                    ],
                                ];
                            },
                        ];
                        $handler = $typeMap[$item['types'] ?? ''] ?? null;
                        return $handler ? $handler($item) : null;
                    })->filter()->all(),
                ];
                $chatId = [];
                // 处理群发或群聊
                if ($data['types'] == 1) {
                    $chatId = $data['is_all'] ? ($groupChat[$userId] ?? []) : $data['send_group'];
                }
                $msg   += $this->getMsgRecipientField($data, $chatId, $externalUserid ?? [], $userId);
                $result = $work->addMsgTemplate($msg);
                $count  = $data['types'] ? count($chatId) : count($externalUserid[$userId] ?? []);
                $be_sent += $count;
                if ($result) {
                    $taskService->create([
                        'mass_id'   => $data['id'],
                        'msgid'     => $result['msgid'],
                        'uid'       => $key,
                        'userid'    => $userId,
                        'fail_list' => $result['fail_list'] ?: null,
                        'types'     => $data['types'],
                        'sum_count' => $count,
                    ]);
                }
            } catch (\Exception) {
            }
        }
        $data->be_sent = $be_sent;
        $data->status  = 2;
        $data->save();
    }

    private function getMsgRecipientField(mixed $data, array $chatId, array $externalUserid, string $userId): array
    {
        if ((int) $data['types'] === 1) {
            return (int) $data['is_all'] === 1 && (int) $data['is_modify'] === 1 ? [] : ['chat_id_list' => $chatId];
        }

        return ['external_userid' => $externalUserid[$userId] ?? []];
    }

    /**
     * 添加企微朋友圈.
     * @throws ClientExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function addWorkMoment(mixed $data)
    {
        $sendUser    = collect(app()->get(AdminService::class)->select(['id' => $data['send_uid']], with: ['work']))?->pluck('work.userid', 'id')->filter()->unique()->all();
        $work        = app()->get(Work::class);
        $taskService = app()->get(WorkMassMessagingTaskService::class);
        $label       = collect(app()->get(LabelService::class)->column(['id' => $data['send_group'] ?: []], 'work_tag_id') ?? [])->filter()->values()->all();
        try {
            $msg = [
                'visible_range' => [
                    'sender_list' => [
                        'user_list' => array_values($sendUser),
                    ],
                    'external_contact_list' => [
                        'tag_list' => $label ?: [],
                    ],
                ],
                'text' => [
                    'content' => $data['temp']['content'],
                ],
            ];
            $mappedAttachments = collect($data['temp']['attach'] ?? [])
                ->map(function ($item) {
                    $typeMap = [
                        MediaEnum::TYPE_IMAGE => function ($item) {
                            $mediaId = $item['file']['attach_id'] ?? '';
                            return $mediaId ? [
                                'msgtype' => 'image',
                                'image'   => ['media_id' => $mediaId],
                            ] : null;
                        },
                        MediaEnum::TYPE_VIDEO => function ($item) {
                            $mediaId = $item['file']['attach_id'] ?? '';
                            return $mediaId ? [
                                'msgtype' => 'video',
                                'video'   => ['media_id' => $mediaId],
                            ] : null;
                        },
                        MediaEnum::TEMP_LINK => function ($item) {
                            $mediaId = $item['file']['attach_id'] ?? '';
                            return $mediaId ? [
                                'msgtype' => 'link',
                                'link'    => [
                                    'title'    => $item['title'] ?? '',
                                    'media_id' => $mediaId,
                                    'desc'     => $item['info'] ?? '',
                                    'url'      => $item['link'] ?? '',
                                ],
                            ] : null;
                        },
                    ];

                    $handler = $typeMap[$item['types'] ?? ''] ?? null;
                    return $handler ? $handler($item) : null;
                })->filter()->values();
            $baseType       = $mappedAttachments->first()['msgtype'] ?? null;
            $filteredByType = $baseType ? $mappedAttachments->filter(fn ($item) => $item['msgtype'] === $baseType) : collect();

            $finalAttachments = match ($baseType) {
                'image' => $filteredByType->take(9)->all(),
                'video' => $filteredByType->take(1)->all(),
                'link'  => $filteredByType->take(1)->all(),
                default => [],
            };
            $msg['attachments'] = $finalAttachments;
            $result             = $work->addMomentTask($msg);
            if ($result) {
                foreach ($sendUser as $key => $userId) {
                    $taskService->create([
                        'mass_id' => $data['id'],
                        'jobid'   => $result['jobid'],
                        'uid'     => $key,
                        'userid'  => $userId,
                        'types'   => $data['types'],
                    ]);
                }
            }
        } catch (\Exception) {
        }
        $data->status = 2;
        $data->save();
    }

    /**
     * 根据规则数组过滤目标数据.
     */
    private function filterDataByRules(array $data, int $uid): array
    {
        $rules = [
            ['content', ''],
            ['attach', []],
            ['types', 1],
            ['uid', $uid],
        ];
        $filtered = [];
        foreach ($rules as $rule) {
            $filtered[$rule[0]] = $data[$rule[0]] ?? $rule[1];
        }
        if (! $filtered['content']) {
            throw $this->exception('请填写发送内容');
        }
        return $filtered;
    }
}
