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

namespace App\Http\Service\Notice;

use App\Constants\NoticeEnum;
use App\Http\Dao\Notice\MessageNoticeDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Assess\UserAssessService;
use App\Http\Service\Company\CompanyApplyService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\Message\MessageTemplateService;
use App\Jobs\WebhookJob;
use App\Task\message\NoticeMessageTask;
use App\Task\message\SmsMessageTask;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\SwooleTaskService;
use Tuoluojiang\Open\services\uniPush\options\PushMessageOptions;
use Tuoluojiang\Open\services\uniPush\options\PushOptions;
use Tuoluojiang\Open\services\uniPush\PushMessage;
use Tuoluojiang\Open\services\wechat\Work;
use Tuoluojiang\Open\utils\MessageType;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 企业消息
 * Class NoticeRecordService.
 * @mixin MessageNoticeDao
 */
class NoticeRecordService extends BaseService
{
    /**
     * 发送人id,0=总平台消息.
     * @var int
     */
    protected $sendId = 0;

    /**
     * 送达人id.
     * @var array|string
     */
    protected $toId;

    /**
     * 送达人uid(多个).
     * @var array
     */
    protected $toIds;

    /**
     * 系统消息类型.
     * @var int
     */
    protected $type = 0;

    /**
     * 消息类型.
     * @var int
     */
    protected $noticeType = 0;

    /**
     * 消息内容.
     * @var string
     */
    protected $message = '';

    /**
     * 跳转链接.
     * @var string
     */
    protected $url = '';

    /**
     * 跳转链接.
     * @var string
     */
    protected $uniUrl = '';

    /**
     * 企业id.
     * @var int,0=总平台消息
     */
    protected $entid = 0;

    /**
     * 延迟秒数.
     * @var int
     */
    protected $delay;

    /**
     * @var array|string
     */
    protected $phone;

    /**
     * NoticeRecordService constructor.
     */
    public function __construct(MessageNoticeDao $dao)
    {
        $this->dao   = $dao;
        $this->delay = 0;
    }

    /**
     * 发送人.
     * @return $this
     */
    public function i(int $sendId)
    {
        $this->sendId = $sendId;
        return $this;
    }

    /**
     * 送达人.
     * @return $this
     */
    public function to(array|string $toId)
    {
        $this->toId = $toId;
        return $this;
    }

    /**
     * 批量设置送达人.
     * @return $this
     */
    public function bathTo(array $toIds)
    {
        $this->toIds = $toIds;
        return $this;
    }

    /**
     * 消息类型.
     * @return $this
     */
    public function noticeType(int $noticeType)
    {
        $this->noticeType = $noticeType;
        return $this;
    }

    /**
     * 系统消息类型.
     * @return $this
     */
    public function type(int $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return $this
     */
    public function setPhone(array|string $phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * 发送消息第二版本.
     * @param int|mixed $linkStatus
     * @return null[]|true
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function sendMessage(string $type, array $params = [], array $other = [], int $linkId = 0, mixed $linkStatus = 0)
    {
        $entId = $this->entid ?: $this->sendId;

        // 获取消息内容
        $messageService = app()->get(MessageService::class);
        $messageInfo    = $messageService->getMessageContent($entId, $type);

        // 如果没有找到消息内容，直接返回成功
        if (empty($messageInfo)) {
            return true;
        }

        // 初始化消息模板和结果数组
        $messageTemplate = $messageInfo['message_template'] ?? [];
        $templateTypes   = [
            MessageType::TYPE_SYSTEM => 'system',
            MessageType::TYPE_SMS    => 'sms',
            MessageType::TYPE_WORK   => 'work',
            MessageType::TYPE_DING   => 'ding',
            MessageType::TYPE_OTHER  => 'bot',
        ];

        // 初始化各类型消息模板和结果
        $templates = array_fill_keys(array_values($templateTypes), []);
        $res       = array_fill_keys(array_map(fn ($type) => "{$type}_job_id", array_values($templateTypes)), null);

        // 根据类型分类消息模板
        foreach ($messageTemplate as $item) {
            $type = $templateTypes[$item['type']] ?? null;
            if ($type) {
                $templates[$type] = $item;
            }
        }

        // 解构模板数组以便后续使用
        ['system' => $system, 'sms' => $sms, 'work' => $work, 'ding' => $ding, 'bot' => $bot] = $templates;
        if (! empty($system['url'])) {
            if (empty($work['url'])) {
                $work['url'] = sys_config('site_url') . $system['url'];
            }
            if (empty($ding['url'])) {
                $ding['url'] = sys_config('site_url') . $system['url'];
            }
            if (empty($bot['url'])) {
                $bot['url'] = sys_config('site_url') . $system['url'];
            }
        }
        // 系统消息
        $res['system_job_id'] = $this->systemSend($system, $messageInfo, $params, $other, $linkId, $linkStatus);
        // 短信消息
        $res['sms_job_id'] = $this->smsSend($sms, $messageInfo, $params);
        // 企业微信webhook
        $res['work_job_id'] = $this->workSend($work, $messageInfo, $params);
        // 钉钉消息
        $res['ding_job_id'] = $this->dingSend($ding, $messageInfo, $params);
        // 其他消息
        $res['other_job_id'] = $this->otherSend($bot, $messageInfo, $params);
        // 企业微信消息
        $this->sendWeWorkPush($messageInfo, $params);

        $this->reset();

        return $res;
    }

    /**
     * 短信发送
     * @param array $sms 短信配置
     * @param array $messageInfo 消息信息
     * @return array|bool
     */
    public function smsSend(array $sms, array $messageInfo, array $params)
    {
        // 不需要发送短信的消息类型
        $excludeTypes = [
            MessageType::ENTERPRISE_VERIFY_TYPE,
            MessageType::ENTERPRISE_VEERIFY_FAIL_TYPE,
        ];
        if (in_array($messageInfo['template_type'], $excludeTypes)) {
            return false;
        }
        if (empty($sms['status'])) {
            return [];
        }
        // 提取模板变量
        preg_match_all('/\{#([\x7f-\xffa-z0-9_]+)}/', $sms['content_template'], $matches);
        $dataVar = array_reduce($matches[1] ?? [], function ($carry, $key) use ($params) {
            $carry[$key] = $params[$key] ?? '';
            return $carry;
        }, []);

        // 检查是否有可用的手机号
        $phone = $this->getAvailablePhoneNumbers($messageInfo);
        if (empty($phone)) {
            return false;
        }

        // 检查模板ID
        if (empty($sms['template_id'])) {
            return false;
        }

        // 获取企业ID
        $entId          = $this->entid ?: $this->sendId;
        $smsSendContent = $sms['content_template'];

        // 发送短信任务
        $result = true;
        foreach ($phone as $item) {
            $task   = new SmsMessageTask($item, $entId, $sms['template_id'], $smsSendContent, $dataVar);
            $result = $result && Task::deliver($task);
        }

        return $result;
    }

    /**
     * 系统消息发送（加入队列）.
     *
     * @param array $system 系统消息配置（含模板、推送规则、链接等）
     * @param array $messageInfo 消息基础信息（分类、标题等）
     * @param array $params 模板变量替换参数
     * @param array $other 附加信息（JSON存储）
     * @param int $linkId 关联业务ID
     * @param mixed $linkStatus 关联业务状态
     * @return bool 队列投递结果
     * @throws \Exception 缺少必要参数时抛出异常
     */
    public function systemSend(array $system, array $messageInfo, array $params, array $other = [], int $linkId = 0, mixed $linkStatus = 0): bool
    {
        $res = false;
        if (empty($system['status'])) {
            return false;
        }
        $templateVarPattern   = '/\{#[\x7f-\xffa-z0-9_]+}/';
        $errorMsgMissingParam = '缺少参数：%s';
        $errorMsgEmptyToUid   = '送达人不能为空';
        $errorMsgEmptyMessage = '消息内容不能为空';
        preg_match_all($templateVarPattern, $system['content_template'], $matchResult);
        $templateVars  = $matchResult[0] ?? [];
        $replaceKeys   = [];
        $replaceValues = [];

        foreach ($templateVars as $var) {
            $key = trim(str_replace(['{#', '}'], '', $var));
            if (empty($key)) {
                continue;
            }
            $replaceKeys[]   = $var;
            $replaceValues[] = $params[$key] ?? '';
        }
        $content = ! empty($replaceKeys)
            ? str_replace($replaceKeys, $replaceValues, $system['content_template'])
            : $system['content_template'];

        $messageData = [];
        $targetUids  = [];
        if (! empty($this->toIds)) {
            $targetUids = $this->toIds;
        } elseif (! empty($this->toId)) {
            $targetUids = [$this->toId];
        }
        $baseMessage = [
            'send_id'         => $this->sendId,
            'cate_id'         => $messageInfo['cate_id'],
            'message_id'      => $messageInfo['id'],
            'cate_name'       => $messageInfo['cate_name'],
            'title'           => $messageInfo['title'],
            'image'           => $system['image'],
            'template_type'   => $messageInfo['template_type'],
            'button_template' => $system['button_template'],
            'message'         => $content,
            'other'           => json_encode($other),
            'url'             => $this->url ?: $system['url'],
            'uni_url'         => $this->uniUrl ?: $system['uni_url'],
            'type'            => $this->type,
            'entid'           => $this->entid,
            'link_id'         => $linkId,
            'link_status'     => $linkStatus,
        ];
        foreach ($targetUids as $toId) {
            $item           = $baseMessage;
            $item['to_uid'] = is_array($toId) ? $toId['to_uid'] : $toId;
            $messageData[]  = $item;
        }
        foreach ($messageData as $item) {
            if (! isset($item['to_uid']) || ! isset($item['message'])) {
                throw $this->exception(sprintf($errorMsgMissingParam, isset($item['to_uid']) ? 'message' : 'to_uid'));
            }
            if (empty($item['to_uid'])) {
                throw $this->exception($errorMsgEmptyToUid);
            }
            if (empty($item['message'])) {
                throw $this->exception($errorMsgEmptyMessage);
            }
        }
        if (! empty($messageData)) {
            $delay = $system['push_rule'] ? (int) ($system['minute']) * 60 : $this->delay;
            $task  = new NoticeMessageTask($messageData);
            $task->delay($delay);
            $res = Task::deliver($task);
        }
        return $res;
    }

    /**
     * 企业微信发送
     * @return false|PendingDispatch
     */
    public function workSend(array $work, array $messageInfo, array $params = [])
    {
        $res = false;
        if (! empty($work['status'])) {
            preg_match_all('/\{#[\x7f-\xffa-z0-9_]+}/', $work['content_template'], $arr);
            $templateNewVar = $arr[0] ?? [];
            $newParams      = [];
            $newKey         = [];
            foreach ($templateNewVar as $v) {
                $key      = trim(str_replace(['{#', '}'], '', $v));
                $newKey[] = $v;
                if ($key) {
                    $newParams[] = $params[$key] ?? '';
                }
            }
            if ($newKey) {
                $content = str_replace($newKey, $newParams, $work['content_template']);
            } else {
                $content = $work['content_template'];
            }
            if (isset($work['webhook_url']) && $work['webhook_url']) {
                $res = WebhookJob::dispatch($work['webhook_url'], $messageInfo['title'], $content, $work['url'], 2);
            }
        }
        return $res;
    }

    /**
     * 钉钉发送
     * @param mixed $messageInfo
     * @return false|PendingDispatch
     */
    public function dingSend(array $ding, $messageInfo, array $params = [])
    {
        $res = false;
        if (! empty($ding['status'])) {
            preg_match_all('/\{#[\x7f-\xffa-z0-9_]+}/', $ding['content_template'], $arr);
            $templateNewVar = $arr[0] ?? [];
            $newParams      = [];
            $newKey         = [];
            foreach ($templateNewVar as $v) {
                $key      = trim(str_replace(['{#', '}'], '', $v));
                $newKey[] = $v;
                if ($key) {
                    $newParams[] = $params[$key] ?? '';
                }
            }
            if ($newKey) {
                $content = str_replace($newKey, $newParams, $ding['content_template']);
            } else {
                $content = $ding['content_template'];
            }

            if (isset($ding['webhook_url']) && $ding['webhook_url']) {
                $res = WebhookJob::dispatch($ding['webhook_url'], $messageInfo['title'], $content, $ding['url'], 2);
            }
        }
        return $res;
    }

    /**
     * 其他消息发送
     * @return false|PendingDispatch
     */
    public function otherSend(array $bot, array $messageInfo, array $params = [])
    {
        $res = false;
        if (! empty($bot['status'])) {
            preg_match_all('/\{#[\x7f-\xffa-z0-9_]+}/', $bot['content_template'], $arr);
            $templateNewVar = $arr[0] ?? [];
            $newParams      = [];
            $newKey         = [];
            foreach ($templateNewVar as $v) {
                $key      = trim(str_replace(['{#', '}'], '', $v));
                $newKey[] = $v;
                if ($key) {
                    $newParams[] = $params[$key] ?? '';
                }
            }
            if ($newKey) {
                $content = str_replace($newKey, $newParams, $bot['content_template']);
            } else {
                $content = $bot['content_template'];
            }
            if (isset($bot['webhook_url']) && $bot['webhook_url']) {
                $res = WebhookJob::dispatch($bot['webhook_url'], $messageInfo['title'], $content, $bot['url'], 2);
            }
        }
        return $res;
    }

    /**
     * 设置消息内容.
     * @return $this
     */
    public function message(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 设置跳转链接.
     * @return $this
     */
    public function url(string $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 设置uni跳转链接.
     * @return $this
     */
    public function uniUrl(string $url)
    {
        $this->uniUrl = $url;
        return $this;
    }

    /**
     * 设置企业ID.
     * @return $this
     */
    public function ent(int $entid)
    {
        $this->entid = $entid;
        return $this;
    }

    /**
     * 设置执行时间.
     * @return $this
     */
    public function delay(string $time)
    {
        $this->delay = $time;
        return $this;
    }

    /**
     * 写入消息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function runJob(array $data)
    {
        $adminService           = app()->get(AdminService::class);
        $noticeSubscribeService = app()->get(NoticeSubscribeService::class);
        foreach ($data as $item) {
            $item['created_at'] = date('Y-m-d H:i:s');
            if (! empty($item['other'])) {
                $other = $item['other'];
            } else {
                $item['is_handle'] = 1;
                $other             = null;
            }

            $sendStatus = $noticeSubscribeService->isSend($item['to_uid'], $item['entid'], $item['template_type']);
            if (! $sendStatus) {
                continue;
            }
            $res = $this->dao->create($item);

            $toUid = $item['to_uid'];
            // 检查发送人的uid是不是32的
            if (strlen((string) $toUid) !== 32) {
                $uid = $adminService->value($toUid, 'uid');
            } else {
                $uid = $toUid;
            }
            // 获取当前用户所在哪个企业中
            // 所在企业中,或者给个人发送的消息
            $this->sendSocketPush($res->id, 1, $item, $other);
            $this->sendUniPush($adminService->value(['uid' => $uid], 'client_id'), $item);
            Log::info('发送消息：' . json_encode($item, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 获取消息列表.
     * @param int $is_read
     * @param mixed $is_handle
     * @param mixed $reverse
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getMessageList(string $uid, int $entid, int|string $cateId = '', string $title = '', $is_read = 0, bool $Renlist = true, $is_handle = '', $reverse = false)
    {
        $userId = app()->get(AdminService::class)->value(['uid' => $uid], 'id') ?: 0;
        $where  = [
            'entid'     => $entid,
            'to_uid'    => $userId,
            'uid'       => $uid,
            'is_read'   => $is_read,
            'cate_id'   => $cateId,
            'title'     => $title,
            'is_handle' => $is_handle,
        ];

        if ($Renlist) {
            [$page, $limit] = $this->getPageValue();
            // $field          = ['title', 'entid', 'is_handle', 'other', 'url', 'uni_url', 'image', 'created_at', 'template_type', 'button_template', 'cate_id', 'cate_name', 'id', 'is_read', 'message', 'message_id', 'type'];
            $field = ['*'];
            $list  = $this->dao->setWhere($where, $field, $page, $limit, 'id', [
                'enterprise' => function ($query) {
                    $query->select(['id', 'enterprise_name']);
                },
                'template' => fn ($query) => $query->select(['message_id', 'uni_url', 'url']),
                'user'     => fn ($query) => $query->select(['id', 'name']),
            ])->get()?->toArray();
            $messageMap = app(MessageService::class)
                ->column(['template_type' => array_filter(array_column($list, 'template_type'))], ['cate_id', 'cate_name'], 'template_type');
            // 获取移动端跳转地址
            foreach ($list as &$item) {
                if ((! $item['cate_name'] || ! $item['cate_id']) && isset($messageMap[$item['template_type']])) {
                    $item['cate_id']   = $item['cate_id'] ?: ($messageMap[$item['template_type']]['cate_id'] ?? 0);
                    $item['cate_name'] = $item['cate_name'] ?: ($messageMap[$item['template_type']]['cate_name'] ?? '');
                }
                $item['button_template'] = is_string($item['button_template']) ? json_decode($item['button_template'], true) : $item['button_template'];
                $item['other']           = is_string($item['other']) ? json_decode($item['other'], true) : $item['other'];
                $item['uni_url']         = $item['template'] ? $item['template']['uni_url'] : '';
                $item['url']             = $item['template'] ? $item['template']['url'] : '';
                $item['buttons']         = $this->getButtonInfo(
                    (string) $item['template_type'],
                    (int) $item['link_status'],
                    (int) $item['link_id'],
                    (string) ($item['url'] ?? ''),
                    (string) ($item['uni_url'] ?? '')
                );
                unset($item['template'], $item['entid']);
                if ($is_handle !== '') {
                    $item['detail'] = $this->getNoticeInfo($item['other'], $item['template_type']);
                } else {
                    $item['detail'] = [];
                }
            }
            if ($reverse && $list) {
                $list = array_reverse($list);
            }
        } else {
            $list = [];
        }
        $messageNum = $this->dao->setWhere($where)->count();
        return compact('list', 'messageNum');
    }

    /**
     * 个人中心订阅列表.
     * @param mixed $uuId
     * @param mixed $entId
     * @param mixed $where
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     */
    public function getListForUser($uuId, $entId, $where)
    {
        $userId = app()->get(FrameService::class)->uuidToUid($uuId, $entId);
        /** @var MessageService $services */
        $services       = app()->get(MessageService::class);
        [$page, $limit] = $this->getPageValue();
        $where['ids']   = app()->get(MessageTemplateService::class)->column(['type' => 0, 'status' => 1], 'message_id');
        $list           = $services->dao->getList($where, ['id', 'cate_id', 'cate_name', 'template_type', 'title', 'content', 'user_sub'], page: $page, limit: $limit);
        if ($list) {
            $subInfo   = app()->get(NoticeSubscribeService::class)->get(['user_id' => $userId]);
            $messageId = $subInfo->message_id ?? [];
            foreach ($list as &$value) {
                if (! $subInfo) {
                    $value['is_subscribe'] = $value['user_sub'] ? 1 : 2;
                } else {
                    $value['is_subscribe'] = $value['user_sub'] ? ($subInfo->is_subscribe ? (int) in_array($value['id'], $messageId) : (int) ! in_array($value['id'], $messageId)) : 2;
                }
            }
        }
        $count = $services->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取消息详情并标记已读.
     * @param int $id 消息ID
     * @param int $userId 用户ID
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfoAndMarkRead(int $id, int $userId): array
    {
        $messageInfo = $this->dao->get($id);
        if (! $messageInfo) {
            throw $this->exception('消息不存在');
        }

        // 验证消息是否属于当前用户
        if ($messageInfo->to_uid != $userId) {
            throw $this->exception('无权访问此消息');
        }

        // 如果消息未读，标记为已读
        if ($messageInfo->is_read == 0) {
            $messageInfo->is_read = 1;
            $messageInfo->save();
            // 更新个推角标
            $this->updatePushBadge($userId);
        }

        // 处理按钮模板和附加信息
        $messageInfo->button_template = is_string($messageInfo->button_template) ? json_decode($messageInfo->button_template, true) : $messageInfo->button_template;
        $messageInfo->other           = is_string($messageInfo->other) ? json_decode($messageInfo->other, true) : $messageInfo->other;
        $messageInfo->buttons         = $this->getButtonInfo(
            $messageInfo->template_type,
            (int) $messageInfo->link_status,
            (int) $messageInfo->link_id,
            $messageInfo->url ?? '',
            $messageInfo->uni_url ?? ''
        );

        return $messageInfo->toArray();
    }

    /**
     * 获取待处理消息数量.
     * @param mixed $uid
     * @param mixed $entid
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getCount($uid, $entid)
    {
        $where = [
            'entid'     => $entid,
            'to_uid'    => app()->get(FrameService::class)->uuidToUid($uid, $entid),
            'uid'       => $uid,
            'is_handle' => 0,
        ];
        return [
            'all'     => $this->dao->setWhere($where)->count(),
            'assess'  => $this->dao->setWhere(array_merge($where, ['template_type' => [MessageType::ASSESS_SELF_TYPE, MessageType::ASSESS_PUBLISH_TYPE]]))->count(),
            'approve' => $this->dao->setWhere(array_merge($where, ['template_type' => MessageType::BUSINESS_APPROVAL_TYPE]))->count(),
        ];
    }

    /**
     * 修改处理状态
     * @param string $toUid
     * @param string $entId
     * @param string $cardId
     * @param int $isHandle
     * @param mixed $otherId
     * @param mixed $templateType
     * @return false|int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updateHandler($otherId, $templateType, $toUid = '', $entId = '', $cardId = '', $isHandle = 1)
    {
        if (! $toUid && ! $cardId) {
            return false;
        }
        $where['other_id']      = $otherId;
        $where['template_type'] = $templateType;
        if ($toUid) {
            $where['to_uid'] = $toUid;
        } elseif ($cardId) {
            $where['to_uid'] = $cardId;
        }
        return $this->dao->update($where, ['is_handle' => $isHandle]);
    }

    public function testPush($clientId)
    {
        $push = $this->getParam();
        $push->setCid($clientId);

        /** @var \GTClient $uniPush */
        $uniPush = app()->get(\GTClient::class, [
            'domainUrl'    => 'https://restapi.getui.com',
            'appkey'       => \sys_config('uni_push_appkey', env('UNI_PUSH_APPKEY', '')),
            'appId'        => \sys_config('uni_push_appid', env('UNI_PUSH_APPID', '')),
            'masterSecret' => \sys_config('uni_push_master_secret', env('UNI_PUSH_MASTER_SECRET', '')),
        ]);
        $res = $uniPush->pushApi()->pushToSingleByCid($push);
    }

    public function getParam()
    {
        $push = new \GTPushRequest();
        $push->setRequestId(\micro_time());
        // 设置setting
        $set = new \GTSettings();
        $set->setTtl(3600000);
        //    $set->setSpeed(1000);
        //    $set->setScheduleTime(1591794372930);
        $strategy = new \GTStrategy();
        $strategy->setDefault(\GTStrategy::STRATEGY_THIRD_FIRST);
        //    $strategy->setIos(GTStrategy::STRATEGY_GT_ONLY);
        //    $strategy->setOp(GTStrategy::STRATEGY_THIRD_FIRST);
        //    $strategy->setHw(GTStrategy::STRATEGY_THIRD_ONLY);
        $set->setStrategy($strategy);
        $push->setSettings($set);
        // 设置PushMessage，
        $message = new \GTPushMessage();
        // 通知
        $notify = new \GTNotification();
        $notify->setTitle('notdifyddd');
        $notify->setBody('notify bdoddy');
        $notify->setBigText('bigTdext');
        // 与big_text二选一
        //    $notify->setBigImage("BigImage");

        $notify->setLogo('push.png');
        $notify->setLogoUrl('LogoUrl');
        $notify->setChannelId('Default');
        $notify->setChannelName('Default');
        $notify->setChannelLevel(2);

        $notify->setClickType('none');
        $notify->setIntent('intent:#Intent;component=你的包名/你要打开的 activity 全路径;S.parm1=value1;S.parm2=value2;end');
        $notify->setUrl('url');
        $notify->setPayload('Payload');
        $notify->setNotifyId(time());
        $notify->setRingName('ring_name');
        $notify->setBadgeAddNum(1);
        //    $message->setNotification($notify);
        // 透传 ，与通知、撤回三选一
        $message->setTransmission('试试透传');
        // 撤回
        $revoke = new \GTRevoke();
        $revoke->setForce(true);
        $revoke->setOldTaskId('taskId');
        //    $message->setRevoke($revoke);
        $push->setPushMessage($message);
        $message->setDuration('1590547347000-1590633747000');
        // 厂商推送消息参数
        $pushChannel = new \GTPushChannel();
        // ios
        $ios = new \GTIos();
        $ios->setType('notify');
        $ios->setAutoBadge('1');
        $ios->setPayload('ios_payload');
        $ios->setApnsCollapseId('apnsCollapseId');
        // aps设置
        $aps = new \GTAps();
        $aps->setContentAvailable(0);
        $aps->setSound('com.gexin.ios.silenc');
        $aps->setCategory('category');
        $aps->setThreadId('threadId');

        $alert = new \GTAlert();
        $alert->setTitle('alert title');
        $alert->setBody('alert body');
        $alert->setActionLocKey('ActionLocKey');
        $alert->setLocKey('LocKey');
        $alert->setLocArgs(['LocArgs1', 'LocArgs2']);
        $alert->setLaunchImage('LaunchImage');
        $alert->setTitleLocKey('TitleLocKey');
        $alert->setTitleLocArgs(['TitleLocArgs1', 'TitleLocArgs2']);
        $alert->setSubtitle('Subtitle');
        $alert->setSubtitleLocKey('SubtitleLocKey');
        $alert->setSubtitleLocArgs(['subtitleLocArgs1', 'subtitleLocArgs2']);
        $aps->setAlert($alert);
        $ios->setAps($aps);

        $multimedia = new \GTMultimedia();
        $multimedia->setUrl('url');
        $multimedia->setType(1);
        $multimedia->setOnlyWifi(false);
        $multimedia2 = new \GTMultimedia();
        $multimedia2->setUrl('url2');
        $multimedia2->setType(2);
        $multimedia2->setOnlyWifi(true);
        $ios->setMultimedia([$multimedia]);
        $ios->addMultimedia($multimedia2);
        $pushChannel->setIos($ios);
        // 安卓
        $android = new \GTAndroid();
        $ups     = new \GTUps();
        //    $ups->setTransmission("ups Transmission");
        $thirdNotification = new \GTThirdNotification();
        $thirdNotification->setTitle('title' . \micro_time());
        $thirdNotification->setBody('body' . \micro_time());
        $thirdNotification->setClickType(\GTThirdNotification::CLICK_TYPE_URL);
        $thirdNotification->setIntent('intent:#Intent;component=你的包名/你要打开的 activity 全路径;S.parm1=value1;S.parm2=value2;end');
        $thirdNotification->setUrl('http://docs.getui.com/getui/server/rest_v2/push/');
        $thirdNotification->setPayload('payload');
        $thirdNotification->setNotifyId(456666);
        $ups->addOption('HW', 'badgeAddNum', 1);
        $ups->addOption('OP', 'channel', 'Default');
        $ups->addOption('OP', 'aaa', 'bbb');
        $ups->addOption(null, 'a', 'b');

        $ups->setNotification($thirdNotification);
        $android->setUps($ups);
        $pushChannel->setAndroid($android);
        $push->setPushChannel($pushChannel);

        return $push;
    }

    /**
     * 获取通知按钮信息.
     *
     * @param string $noticeType 通知类型（如contract_abnormal、daily等）
     * @param int $status 通知状态（-1:作废/删除,1:已通过,2:未通过,4:已结束,5:已删除/查看）
     * @param int $id 关联ID
     * @param string $url H5链接
     * @param string $uniUrl 小程序链接
     * @return array 按钮信息数组
     */
    public function getButtonInfo(string $noticeType, int $status, int $id = 0, string $url = '', string $uniUrl = ''): array
    {
        $contractRelatedKeywords = [
            'contract_abnormal', 'contract_overdue_day_remind', 'contract_soon_overdue_remind',
            'contract_overdue_remind', 'contract_urgent_renew', 'contract_day_remind',
            'approv', 'recall', 'contract_return_money', 'contract_renew',
            'contract_expend', 'finance_verify_fail',
        ];
        $assessKeyword         = 'assess';
        $dailyKeyword          = 'daily';
        $attendanceKeyword     = 'attendance';
        $enterpriseKeyword     = 'enterprise';
        $assessAbnormalKeyword = 'assess_abnormal';
        $newsKeyword           = 'news';
        $buttonData            = [
            'url'     => $url,
            'uni_url' => $uniUrl,
            'action'  => '',
            'title'   => '',
        ];
        $newUrl = $newUniUrl = '';
        if ($id > 0) {
            $newUrl    = str_contains($url, '?') ? $url . '&id=' . $id : $url . '?id=' . $id;
            $newUniUrl = str_contains($uniUrl, '?') ? $uniUrl . '&id=' . $id : $uniUrl . '?id=' . $id;
        }
        switch ($status) {
            case -1:
                // 简化多关键词判断逻辑
                $isContractRelated = false;
                foreach ($contractRelatedKeywords as $keyword) {
                    if (str_contains($noticeType, $keyword)) {
                        $isContractRelated = true;
                        break;
                    }
                }
                if ($isContractRelated) {
                    $buttonData['action'] = NoticeEnum::STATUS_RECALL;
                    $buttonData['title']  = '已作废';
                } elseif (str_contains($noticeType, $enterpriseKeyword)) {
                    $buttonData['action'] = NoticeEnum::STATUS_DELETE;
                    $buttonData['title']  = '已处理';
                } elseif (str_contains($noticeType, $assessAbnormalKeyword)) {
                    $buttonData['action'] = NoticeEnum::STATUS_RECALL;
                    $buttonData['title']  = '';
                } else {
                    $buttonData['action'] = NoticeEnum::STATUS_DELETE;
                    $buttonData['title']  = '已删除';
                }
                $buttonData['url'] = $buttonData['uni_url'] = '';
                break;
            case 1:
                $buttonData['action'] = NoticeEnum::STATUS_DETAIL;
                $isDailyAssessNews    = str_contains($noticeType, $dailyKeyword)
                    || str_contains($noticeType, $assessKeyword)
                    || str_contains($noticeType, $newsKeyword);
                $buttonData['title'] = $isDailyAssessNews ? '立即查看' : '已通过';
                if ($id > 0) {
                    $buttonData['url']     = $newUrl;
                    $buttonData['uni_url'] = $newUniUrl;
                }
                break;
            case 2:
                $buttonData['action'] = NoticeEnum::STATUS_DETAIL;
                $buttonData['title']  = str_contains($noticeType, $assessKeyword) ? '立即查看' : '未通过';
                if ($id > 0) {
                    $buttonData['url']     = $newUrl;
                    $buttonData['uni_url'] = $newUniUrl;
                }
                break;
            case 4:
                $buttonData['action'] = NoticeEnum::STATUS_DELETE;
                $buttonData['title']  = '已结束';
                $buttonData['url']    = $buttonData['uni_url'] = '';
                break;
            case 5:
                if (str_contains($noticeType, $assessKeyword)) {
                    $buttonData['action'] = NoticeEnum::STATUS_DETAIL;
                    $buttonData['title']  = '立即查看';
                    if ($id > 0) {
                        $buttonData['url']     = $newUrl;
                        $buttonData['uni_url'] = $newUniUrl;
                    }
                } else {
                    $buttonData['action'] = NoticeEnum::STATUS_DELETE;
                    $buttonData['title']  = '已删除';
                    $buttonData['url']    = $buttonData['uni_url'] = '';
                }
                break;
            default:
                $buttonData['action'] = NoticeEnum::STATUS_DETAIL;
                // 简化标题判断逻辑
                $isDailyNotAttendance = str_contains($noticeType, $dailyKeyword)
                    && ! str_contains($noticeType, $attendanceKeyword);
                $buttonData['title'] = $isDailyNotAttendance ? '立即填写' : '立即查看';
                if ($id > 0) {
                    $buttonData['url']     = $newUrl;
                    $buttonData['uni_url'] = $newUniUrl;
                }
        }
        return [$buttonData];
    }

    /**
     * 批量更新.
     * @throws BindingResolutionException
     */
    public function batchUpdate(string $uuid, int $entId, int $isRead, int $cateId, array $ids): int
    {
        $where = ['entid' => $entId, 'to_uid' => uuid_to_uid($uuid, $entId)];
        if ($cateId) {
            $where['cate_id'] = $cateId;
        } else {
            $where['ids'] = $ids;
        }
        $result = $this->dao->update($where, ['is_read' => $isRead]);

        // 如果是标记为已读，则更新个推角标
        if ($isRead == 1) {
            $this->updatePushBadge(uuid_to_uid($uuid, $entId));
        }

        return $result;
    }

    /**
     * 批量删除.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function batchDelete(string $uuid, int $entId, int $cateId, array $ids)
    {
        $where = ['entid' => $entId, 'to_uid' => uuid_to_uid($uuid, $entId)];
        if ($cateId) {
            $where['cate_id'] = $cateId;
        } else {
            $where['ids'] = $ids;
        }
        return $this->dao->delete($where);
    }

    /**
     * 根据用户ID获取未读消息数量.
     */
    public function getUnreadCountByUserId(int $userId): int
    {
        $where = [
            'to_uid'  => $userId,
            'is_read' => 0,
        ];

        return $this->dao->count($where);
    }

    /**
     * 更新用户个推角标.
     */
    public function updatePushBadge(int $userId): void
    {
        try {
            $clientId = app(AdminService::class)->value($userId, 'client_id');
            if (! $clientId) {
                return;
            }
            $unreadCount = $this->dao->count(['to_uid' => $userId, 'is_read' => 0]);
            // 使用个推服务设置正确的角标数
            app(PushMessage::class)->userBadge([$clientId], (string) $unreadCount);
        } catch (\Exception $e) {
            Log::error('更新个推角标失败: ' . $e->getMessage(), [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * 更新已读状态.
     * @param mixed $isRead
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateRead(array $where, $isRead, int $uid)
    {
        if ($where['cate_id']) {
            if ($where['cate_id'] == 'all'){
                unset($where['cate_id']);
            }
            unset($where['ids']);
        } else {
            unset($where['cate_id']);
        }
        if (! $where) {
            $where = ['to_uid' => $uid];
        }
        $this->dao->update($where, ['is_read' => $isRead]);
        $this->updatePushBadge($uid);
    }

    /**
     * 获取可用的手机号列表.
     * @param mixed $messageInfo
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getAvailablePhoneNumbers(array $messageInfo): array
    {
        if (! empty($this->toId['phone'])) {
            return [$this->toId['phone']];
        }

        if (! empty($this->toIds[0]['phone'])) {
            $noticeSubscribeService = app()->get(NoticeSubscribeService::class);
            $this->toIds            = array_filter($this->toIds, function ($item) use ($noticeSubscribeService, $messageInfo) {
                return $noticeSubscribeService->isSend($item['to_uid'], 1, $messageInfo['template_type']);
            });
            return array_column($this->toIds, 'phone');
        }

        return $this->phone ?? [];
    }

    /**
     * Socket推送
     * @param mixed $id
     * @param mixed $entId
     * @param mixed $item
     * @param mixed $other
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendSocketPush($id, $entId, $item, $other)
    {
        try {
            $buttonTemplate = is_string($item['button_template']) ? json_decode($item['button_template'], true) : $item['button_template'];
            $buttonTemplate = is_string($buttonTemplate) ? json_decode($buttonTemplate, true) : $buttonTemplate;
            SwooleTaskService::ent()->entid($entId)->data('ent', [
                'message'         => $item['message'],
                'image'           => $item['image'],
                'cate_name'       => $item['cate_name'],
                'title'           => $item['title'],
                'url'             => $item['url'],
                'uni_url'         => $item['uni_url'],
                'button_template' => $buttonTemplate,
                'buttons'         => app()->get(NoticeRecordService::class)->getButtonInfo($item['template_type'], (int) $item['link_status'], (int) $item['link_id'], $item['url'], $item['uni_url']),
                'other'           => is_string($other) ? json_decode($other, true) : $other,
                'template_type'   => $item['template_type'],
                'uniqid'          => uniqid(),
                'id'              => $id,
                'link_id'         => $item['link_id'],
                'link_status'     => $item['link_status'],
            ])->type('notice')->to($item['to_uid'])->push();
        } catch (\Exception $e) {
            Log::error('socketPush推送错误：' . json_encode(['msg' => $e->getMessage(), 'line' => $e->getLine()]));
        }
    }

    /**
     * UniPush推送
     * @return true|void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function sendUniPush(string $clientId, array $messageInfo)
    {
        try {
            if (! $clientId) {
                return true;
            }
            if (! $messageInfo) {
                return true;
            }
            $uniPush                    = app(PushMessage::class);
            $option                     = new PushOptions();
            $messageOption              = new PushMessageOptions();
            $messageOption->title       = $messageInfo['title'];
            $messageOption->badgeAddNum = 1;
            $messageOption->body        = $messageInfo['message'];
            if ($messageInfo['other']) {
                $other = is_string($messageInfo['other']) ? json_decode($messageInfo['other'], true) : $messageInfo['other'];
                $param = '';
                foreach ($other as $k => $v) {
                    $param .= $k . '=' . $v . '&';
                }
                $url = $messageInfo['uni_url'] . '?' . rtrim($param, '&');
            } else {
                $url = $messageInfo['uni_url'];
            }
            $messageOption->clickType    = 'payload';
            $messageOption->payload      = json_encode(['url' => $url, 'type' => 'url']);
            $messageOption->channelLevel = 3;
            $option->setAudience($clientId);
            $option->setPushMessage($messageOption);
            $option->pushChannel = [
                'transmission' => json_encode([
                    'title' => $messageOption->title,
                    'body'  => $messageOption->body,
                    'url'   => $url,
                ]),
            ];

            // 厂商推送消息参数
            $pushChannel = new \GTPushChannel();
            // ios
            $ios = new \GTIos();
            $ios->setType('notify');
            $ios->setAutoBadge('1');
            $ios->setPayload('ios_payload');
            $ios->setApnsCollapseId('apnsCollapseId');
            // aps设置
            $aps = new \GTAps();
            $aps->setContentAvailable(0);
            $aps->setSound('com.gexin.ios.silence');

            $alert = new \GTAlert();
            $alert->setTitle($messageOption->title);
            $alert->setBody($messageOption->body);
            $aps->setAlert($alert);
            $ios->setAps($aps);

            $multimedia = new \GTMultimedia();
            $multimedia->setUrl($url);
            $multimedia->setType(1);
            $multimedia->setOnlyWifi(false);
            $multimedia2 = new \GTMultimedia();
            $multimedia2->setUrl($url);
            $multimedia2->setType(2);
            $multimedia2->setOnlyWifi(true);
            $ios->setMultimedia([$multimedia]);
            $ios->addMultimedia($multimedia2);
            $pushChannel->setIos($ios);
            // 安卓
            $android = new \GTAndroid();
            $ups     = new \GTUps();
            //    $ups->setTransmission("ups Transmission");
            $thirdNotification = new \GTThirdNotification();
            $thirdNotification->setTitle('title' . \micro_time());
            $thirdNotification->setBody('body' . \micro_time());
            $thirdNotification->setClickType(\GTThirdNotification::CLICK_TYPE_URL);
            //            $thirdNotification->setIntent("intent:#Intent;component=uni.UNIA6C11DD/你要打开的 activity 全路径;S.parm1=value1;S.parm2=value2;end");
            $thirdNotification->setIntent('intent:#Intent;component=' . \sys_config('uni_package_id') . "/{$url};end");
            $thirdNotification->setUrl($url);
            $thirdNotification->setPayload(json_encode(['url' => $url, 'type' => 'url']));
            $ups->addOption('HW', 'badgeAddNum', 1);
            $ups->addOption('OP', 'channel', 'Default');
            $ups->addOption('OP', 'aaa', 'bbb');
            $ups->addOption(null, 'a', 'b');

            $ups->setNotification($thirdNotification);
            $android->setUps($ups);
            $pushChannel->setAndroid($android);

            /** @var \GTPushMessage $message */
            $message = app()->get(\GTPushMessage::class);
            /** @var \GTNotification $notify */
            $notify = app()->get(\GTNotification::class);
            $notify->setTitle($messageInfo['title']);
            $notify->setBody($messageInfo['message']);
            $notify->setClickType('payload');
            $notify->setChannelLevel(4);
            $notify->setPayload(json_encode(['url' => $url, 'type' => 'url']));
            $res = $uniPush->push($message, $notify, $pushChannel, $clientId);
            Log::info('uniPush推送结果:', $res);
        } catch (\Exception $e) {
            Log::error('uniPush推送错误:', ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 重置.
     */
    protected function reset()
    {
        $this->type    = 0;
        $this->message = null;
        $this->url     = null;
        $this->uniUrl  = null;
        $this->toId    = null;
        $this->uid     = null;
        $this->sendId  = null;
        $this->phone   = null;
    }

    /**
     * 获取消息关联内容详情.
     * @param mixed $param
     * @param mixed $templateType
     * @return null|array|Model
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function getNoticeInfo($param, $templateType)
    {
        if (empty($param['id'])) {
            return [];
        }
        if (! in_array($templateType, $this->dao->pendingType)) {
            return [];
        }
        return match ($templateType) {
            MessageType::BUSINESS_APPROVAL_TYPE => app()->get(CompanyApplyService::class)->get($param['id'], ['id', 'card_id', 'status', 'created_at', 'approve_id'], [
                'content' => fn ($query) => $query->select(['id', 'title', 'value', 'types', 'apply_id', 'content']),
                'card'    => fn ($query) => $query->select(['id', 'name', 'avatar']),
                'approve' => fn ($query) => $query->select(['id', 'name']),
            ]),
            MessageType::ASSESS_SELF_TYPE, MessageType::ASSESS_PUBLISH_TYPE => app()->get(UserAssessService::class)->get($param['id'], ['id', 'name', 'period', 'start_time', 'test_uid', 'status', 'end_time'], [
                'test',
            ]),
            default => [],
        };
    }

    /**
     * 推送企业微信.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function sendWeWorkPush(array $messageInfo, array $params = []): void
    {
        if (! sys_config('wechat_work_build_agent_id')) {
            return;
        }
        $targetUids = ! empty($this->toIds) ? $this->toIds : (! empty($this->toId) ? [$this->toId] : []);
        $template   = collect($messageInfo['message_template'])->filter(fn ($item) => $item['type'] == 0)->last();
        $work       = app(Work::class);
        collect($targetUids)->filter(fn ($item) => $item['userid'])->each(function ($item) use ($template, $params, $work) {
            $sendParams = [
                'touser'        => $item['userid'],
                'msgtype'       => 'template_card',
                'agentid'       => sys_config('wechat_work_build_agent_id'),
                'template_card' => [
                    'card_type'               => 'text_notice',
                    'sub_title_text'          => $template['message_title'],
                    'horizontal_content_list' => collect($template['template_var'])->map(function ($item) use ($params) {
                        return [
                            'keyname' => $item,
                            'value'   => $params[$item],
                        ];
                    })->all(),
                    'jump_list' => collect(json_decode($template['button_template'], true) ?? [])->map(function ($item) use ($template) {
                        return [
                            'type'  => 1,
                            'title' => $item,
                            'url'   => link_file('/work' . $template['uni_url']),
                        ];
                    })->values()->all(),
                    'card_action' => [
                        'type' => 1,
                        'url'  => link_file('/work'),
                    ],
                ],
            ];
            $work->sendMessage($sendParams);
        });
    }
}
