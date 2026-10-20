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

namespace App\Http\Service\Message;

use App\Constants\NoticeEnum;
use App\Http\Dao\Message\MessageDao;
use App\Http\Service\Crud\SystemCrudEventService;
use App\Http\Service\Notice\MessageCateService;
use App\Http\Service\Notice\NoticeRecordService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\synchro\Message;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 系统消息
 * Class MessageService.
 */
class MessageService extends BaseService
{
    /**
     * MessageService constructor.
     */
    public function __construct(MessageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param null|mixed $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $data         = parent::getList($where, $field, $sort, $with);
        $data['list'] = collect($data['list'])->map(function ($item) {
            $typeToFieldMap = [
                NoticeEnum::TYPE_SYSTEM     => 'system_template',
                NoticeEnum::TYPE_SMS        => 'sms_template',
                NoticeEnum::TYPE_WORK_HOOK  => 'work_template',
                NoticeEnum::TYPE_DING_HOOK  => 'ding_template',
                NoticeEnum::TYPE_OTHER_HOOK => 'other_template',
                NoticeEnum::TYPE_WEWORK     => 'wework_template',
            ];
            $templateCollection = collect($item['message_template'])->keyBy('type');
            $templateFields     = collect($typeToFieldMap)->reduce(function ($carry, $field, $type) use ($templateCollection) {
                $carry[$field] = $templateCollection->get($type) ?: ['status' => 0];
                return $carry;
            }, []);
            return array_merge($item, (array) $templateFields);
        })->toArray();
        return $data;
    }

    /**
     * 获取详情.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function info(int $id)
    {
        $message = $this->dao->get($id, ['*'], ['messageTemplate'])?->toArray();
        if (! $message) {
            throw $this->exception('消息不存在');
        }
        $typeToFieldMap = [
            NoticeEnum::TYPE_SYSTEM     => 'system_template',
            NoticeEnum::TYPE_SMS        => 'sms_template',
            NoticeEnum::TYPE_WORK_HOOK  => 'work_template',
            NoticeEnum::TYPE_DING_HOOK  => 'ding_template',
            NoticeEnum::TYPE_OTHER_HOOK => 'other_template',
            NoticeEnum::TYPE_WEWORK     => 'wework_template',
        ];
        collect($typeToFieldMap)->each(function ($fieldName, $enumType) use (&$message) {
            $message[$fieldName] = collect($message['message_template'] ?? [])->keyBy('type')->get($enumType, []);
        });
        return $message;
    }

    /**
     * 保存消息详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveMessage(int $id, array $data): void
    {
        [$remindTime, $status, $smsStatus, $templateId, $workWebhookUrl, $workStatus, $dingWebhookUrl, $dingStatus, $otherWebhookUrl, $otherStatus, $weworkStatus] = $data;
        if ($remindTime) {
            $this->dao->update($id, ['remind_time' => $remindTime]);
        } else {
            $messageTemplateService = app(MessageTemplateService::class);
            $contentTemplate        = $this->dao->value($id, 'content');
            $templates              = collect($messageTemplateService->select(['message_id' => $id]) ?? [])->keyBy('type');
            $systemMsg              = $templates->get(NoticeEnum::TYPE_SYSTEM);
            $url                    = $systemMsg?->url ?? '';
            $uniUrl                 = $systemMsg?->uni_url ?? '';
            $baseCreateData         = [
                'message_id'       => $id,
                'url'              => $url,
                'uni_url'          => $uniUrl,
                'content_template' => $contentTemplate,
                'relation_status'  => 1,
            ];
            collect([
                // 系统消息：仅更新状态，无创建/CRUD
                [
                    'type'   => NoticeEnum::TYPE_SYSTEM,
                    'status' => $status,
                    'handle' => function ($msg) use ($status) {
                        $msg->status = $status;
                        $msg->save();
                    },
                ],
                // 短信消息：特殊有template_id，无webhook
                [
                    'type'        => NoticeEnum::TYPE_SMS,
                    'status'      => $smsStatus,
                    'template_id' => $templateId,
                    'crudFields'  => ['sms_status', 'sms_template_id'],
                    'handle'      => function ($msg) use ($smsStatus, $templateId) {
                        $msg->status      = $smsStatus;
                        $msg->template_id = $templateId;
                        $msg->save();
                    },
                ],
                // 企业微信hook：有webhook_url+CRUD
                [
                    'type'        => NoticeEnum::TYPE_WORK_HOOK,
                    'status'      => $workStatus,
                    'webhook_url' => $workWebhookUrl,
                    'crudFields'  => ['work_webhook_status', 'work_webhook_url'],
                    'handle'      => function ($msg) use ($workStatus, $workWebhookUrl) {
                        $msg->status      = $workStatus;
                        $msg->webhook_url = $workWebhookUrl;
                        $msg->save();
                    },
                ],
                // 钉钉hook：有webhook_url+CRUD
                [
                    'type'        => NoticeEnum::TYPE_DING_HOOK,
                    'status'      => $dingStatus,
                    'webhook_url' => $dingWebhookUrl,
                    'crudFields'  => ['ding_webhook_status', 'ding_webhook_url'],
                    'handle'      => function ($msg) use ($dingStatus, $dingWebhookUrl) {
                        $msg->status      = $dingStatus;
                        $msg->webhook_url = $dingWebhookUrl;
                        $msg->save();
                    },
                ],
                // 其他hook：特殊用dingStatus，有webhook_url+CRUD
                [
                    'type'        => NoticeEnum::TYPE_OTHER_HOOK,
                    'status'      => $otherStatus,
                    'webhook_url' => $otherWebhookUrl,
                    'crudStatus'  => $dingStatus, // 特殊：CRUD更新用dingStatus
                    'crudFields'  => ['other_webhook_status', 'other_webhook_url'],
                    'handle'      => function ($msg) use ($otherStatus, $otherWebhookUrl) {
                        $msg->status      = $otherStatus;
                        $msg->webhook_url = $otherWebhookUrl;
                        $msg->save();
                    },
                ],
                // 企业微信：仅更新状态，有创建
                [
                    'type'   => NoticeEnum::TYPE_WEWORK,
                    'status' => $weworkStatus,
                    'handle' => function ($msg) use ($weworkStatus) {
                        $msg->status = $weworkStatus;
                        $msg->save();
                    },
                ],
            ])->each(function ($item) use ($templates, $messageTemplateService, $baseCreateData) {
                $msg              = $templates->get($item['type']);
                $crudEventService = app(SystemCrudEventService::class);

                // 存在则执行更新逻辑 + 处理CRUD事件
                if ($msg) {
                    $item['handle']($msg); // 执行自定义更新逻辑
                    // 有CRUD配置且有crud_event_id时，执行更新
                    if (isset($item['crudFields']) && $msg->crud_event_id) {
                        $crudStatus = $item['crudStatus'] ?? $item['status']; // 兼容other的特殊status
                        $crudData   = [
                            $item['crudFields'][0] => $crudStatus,
                        ];
                        // 有url字段时补充CRUD更新
                        if (count($item['crudFields']) > 1) {
                            $crudData[$item['crudFields'][1]] = $item['webhook_url'] ?? '';
                        }
                        // 短信特殊补充template_id的CRUD更新
                        if ($item['type'] === NoticeEnum::TYPE_SMS) {
                            $crudData[$item['crudFields'][1]] = $item['template_id'];
                        }
                        $crudEventService->update($msg->crud_event_id, $crudData);
                    }
                    return;
                }
                // 不存在则创建：合并公共参数+自定义参数
                $createData = array_merge($baseCreateData, [
                    'type'   => $item['type'],
                    'status' => $item['status'],
                ]);
                // 有webhook_url则补充
                if (isset($item['webhook_url'])) {
                    $createData['webhook_url'] = $item['webhook_url'];
                }
                // 短信特殊补充template_id，且剔除无意义的url/uni_url
                if ($item['type'] === NoticeEnum::TYPE_SMS) {
                    $createData['template_id'] = $item['template_id'];
                    unset($createData['url'], $createData['uni_url']);
                }
                // 系统消息创建：仅保留核心参数（无url/uni_url/webhook）
                if ($item['type'] === NoticeEnum::TYPE_SYSTEM) {
                    $createData = array_intersect_key($createData, array_flip(['message_id', 'type', 'status', 'content_template', 'relation_status']));
                }

                $messageTemplateService->create($createData);
            });
        }
    }

    /**
     * 同步总后台消息.
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public function syncMessage(int $entId)
    {
        /** @var Message $make */
        $make = app()->get(Message::class);
        $list = $make->setConfig($entId)->getMessageList();
        /** @var MessageTemplateService $messageService */
        $messageService = app()->get(MessageTemplateService::class);

        // sync remote cate
        app()->get(MessageCateService::class)->syncRemoteCate();

        $res = $this->transaction(function () use ($list, $messageService, $entId) {
            foreach ($list as $item) {
                $messageTemplate     = $item['message_template'] ?? [];
                $messageType         = $item['message_type'] ?? [];
                $cateName            = $item['message_cate']['cate_name'] ?? '';
                $item['relation_id'] = $relationId = $item['id'];
                unset($item['path'], $item['auth_apply'], $item['message_cate'], $item['id'], $item['deleted_at'], $item['message_id'], $item['message_template'], $item['message_type']);
                $item['template_var'] = $messageType['template_var'];
                $messageId            = $this->dao->value(['entid' => $entId, 'relation_id' => $relationId], 'id');
                $template             = [];
                $data                 = [
                    'cate_id'       => $item['cate_id'],
                    'cate_name'     => $cateName,
                    'template_type' => $item['template_type'],
                    'template_time' => $messageType['template_time'],
                    'title'         => $item['title'],
                    'content'       => $item['content'],
                    'relation_id'   => $item['relation_id'],
                    'template_var'  => $item['template_var'],
                ];
                if ($messageId) {
                    $this->dao->update(['id' => $messageId], $data);
                    foreach ($messageTemplate as $value) {
                        $value['relation_id'] = $value['id'];
                        $value['message_id']  = $messageId;
                        if ($value['button_template']) {
                            if (is_array($value['button_template'])) {
                                $value['button_template'] = json_encode($value['button_template']);
                            }
                        }
                        unset($value['id'], $value['deleted_at'], $value['sms_tem_id'], $value['template_id']);
                        $id = $messageService->value(['message_id' => $messageId, 'relation_id' => $value['relation_id']], 'id');
                        if ($id) {
                            unset($value['relation_id'], $value['status'], $value['temp_id']);
                            $messageService->update($id, $value);
                        } else {
                            if ($value['status']) {
                                $value['relation_status'] = $value['status'];
                                $value['template_id']     = $value['temp_id'];
                                unset($value['temp_id']);
                                $template[] = $value;
                            }
                        }
                    }
                } else {
                    $data['entid'] = $entId;
                    if ($data['template_time'] == 1) {
                        $data['remind_time'] = '09:00';
                        if (in_array($data['template_type'], ['clock_remind', 'clock_remind_after_work'])) {
                            $data['remind_time'] = '600';
                        }

                        if ($data['template_type'] == 'remind_work_card_short') {
                            $data['remind_time'] = '300';
                        }

                        if ($data['template_type'] == 'remind_after_work_card_short') {
                            $data['remind_time'] = '10:00';
                        }
                    }

                    $res      = $this->dao->create($data);
                    $template = [];
                    foreach ($messageTemplate as $value) {
                        $value['relation_id'] = $value['id'];
                        $value['message_id']  = $res->id;
                        if ($value['button_template']) {
                            if (is_array($value['button_template'])) {
                                $value['button_template'] = json_encode($value['button_template']);
                            }
                        }
                        unset($value['id'], $value['deleted_at'], $value['sms_tem_id'], $value['template_id']);
                        if ($value['status']) {
                            $value['relation_status'] = $value['status'];
                            $value['button_template'] = json_encode($value['button_template']);
                            $value['template_id']     = $value['temp_id'];
                            unset($value['temp_id']);
                            $template[] = $value;
                        }
                    }
                }
                if ($template) {
                    $messageService->insert($template);
                }
            }
            return true;
        });
        if ($res) {
            Cache::delete(md5('notice_cate_' . $entId));
        }
    }

    /**
     * 获取消息分类列表.
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public function getMessageCateList(int $entId): mixed
    {
        return Cache::remember(md5('notice_cate_' . $entId), (int) sys_config('system_cache_ttl', 3600), function () {
            return app()->get(MessageCateService::class)->select([], ['*', 'cate_name as label', 'id as value'])?->toArray();
        });
    }

    /**
     * 获取消息分类数量.
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public function getMessageCateCount(int $entId, int $toUid)
    {
        $cate   = $this->getMessageCateList($entId);
        $group  = app()->get(NoticeRecordService::class)->getMessageGroupCount($entId, $toUid);
        $column = [];
        foreach ($group as $item) {
            $column[$item['cate_id']] = $item['count'];
        }
        foreach ($cate as &$item) {
            $item['count'] = $column[$item['id']] ?? 0;
        }
        return $cate;
    }

    /**
     * 获取消息类型.
     * @return string
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMessageContent(int $entId, string $typeStr)
    {
        return Cache::tags('message')->remember(md5('message_' . $entId . '_' . $typeStr), (int) sys_config('system_cache_ttl', 3600), function () use ($entId, $typeStr) {
            $message = $this->dao->get(
                ['template_type' => $typeStr, 'entid' => $entId],
                ['id', 'template_type', 'title', 'template_var', 'remind_time', 'template_time', 'cate_id', 'cate_name'],
                [
                    'messageTemplate' => fn ($query) => $query->select([
                        'message_id', 'template_id', 'type', 'url', 'uni_url', 'image', 'message_title', 'webhook_url', 'button_template', 'content_template', 'push_rule', 'minute', 'status',
                    ]),
                ]
            )?->toArray();
            if (! $message) {
                return $message;
            }
            foreach ($message['message_template'] as $index => $item) {
                preg_match_all('/(?<={\$)[^}]+|(?<={\#)[^}]+/', $item['content_template'], $match);
                $message['message_template'][$index]['template_var'] = $match[0] ?? [];
            }

            return $message;
        });
    }

    /**
     * 修改消息状态.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateStatus(int $id, int $type, int $status): void
    {
        $message = $this->dao->get($id, ['id', 'template_type', 'content', 'title', 'crud_id'], ['messageTemplate'])?->toArray();
        if (! $message) {
            throw $this->exception('消息信息获取失败');
        }
        $messageTemplate = collect($message['message_template'])->keyBy('type');
        $tempService     = app(MessageTemplateService::class);
        $template        = $messageTemplate->get($type);
        if ($template) {
            $tempService->update(['message_id' => $id, 'type' => $type], ['status' => $status]);
            if ($template['crud_event_id']) {
                $eventInfo = app()->get(SystemCrudEventService::class)->get($template['crud_event_id']);
                if ($eventInfo) {
                    $eventInfo->status = $status;
                    $eventInfo->save();
                }
            }
        } elseif (! $message['crud_id']) {
            $saveData = $messageTemplate->get(NoticeEnum::TYPE_SYSTEM);
            unset($saveData['id']);
            $saveData['type']   = $type;
            $saveData['status'] = $status;
            $tempService->create($saveData);
        } else {
            throw $this->exception('低代码应用消息模板不存在');
        }
        Cache::tags('message')->forget('message_1_' . $message['template_type']);
    }

    /**
     * 批量设置消息渠道.
     * @param mixed $data
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function batchSaveMessage(array $param, $data)
    {
        foreach ($param as $value) {
            try {
                $this->saveMessage((int) $value, $data);
            } catch (\Exception) {
            }
        }
    }

    /**
     * 批量修改推送状态
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function batchUpdateStatus(array $param, int $type, int $status)
    {
        foreach ($param as $value) {
            try {
                $this->updateStatus((int) $value, $type, $status);
            } catch (\Exception) {
            }
        }
    }
}
