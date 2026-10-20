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

namespace App\Listeners\wechat;

use App\Http\Service\Customer\LabelService;
use App\Http\Service\Work\WorkClientService;
use App\Http\Service\Work\WorkDepartmentService;
use App\Http\Service\Work\WorkGroupChatService;
use App\Http\Service\Work\WorkMemberService;
use App\Http\Service\WorkExternalContact\WorkMediaService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企业微信事件监听.
 */
class WorkListener
{
    /**
     * 监听企业微信事件.
     * @param mixed $message
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function __invoke($message, \Closure $next)
    {
        $response = null;
        try {
            $payload = $message->toArray();
        } catch (\Throwable $e) {
            Log::warning('解析企业微信事件报错：' . json_encode([
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'class_name' => is_object($message) ? get_class($message) : $message,
            ]));

            return $next($response);
        }

        Log::warning('企业微信事件监听:' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        switch ($payload['MsgType']) {
            case 'event':
                switch ($payload['Event']) {
                    case 'change_contact':// 通讯录事件
                        $this->changeContactEvent($payload);
                        break;
                    case 'change_external_chat':// 客户群事件
                        $this->changeExternalChatEvent($payload);
                        break;
                    case 'change_external_contact':// 客户事件
                        $this->externalContactEvent($payload);
                        break;
                    case 'change_external_tag':// 客户标签事件
                        $this->changeExternalTagEvent($payload);
                        break;
                    case 'batch_job_result':// 异步任务完成通知
                        $this->batchJobResultEvent($payload);
                        break;
                    case 'upload_media_job_finish':// 素材分片上传异步任务完成通知
                        $this->changeMediaEvent($payload);
                        break;
                }
                break;
            case 'text':// 文本消息
                break;
            case 'image':// 图片消息
                break;
            case 'voice':// 语音消息
                break;
            case 'video':// 视频消息
                break;
            case 'news':// 图文消息
                break;
            case 'update_button':// 模板卡片更新消息
                break;
            case 'update_template_card':// 更新点击用户的整张卡片
                break;
        }
        return $next($response);
    }

    public function batchJobResultEvent(array $payload)
    {
        switch ($payload['JobType']) {
            case 'sync_user':// 增量更新成员
                break;
            case 'replace_user':// 全量覆盖成员
                break;
            case 'invite_user':// 邀请成员关注
                break;
            case 'replace_party':// 全量覆盖部门
                break;
        }
    }

    /**
     * 企业微信通讯录事件.
     */
    public function changeContactEvent(array $payload)
    {
        if (! sys_config('wechat_work_user_switch')) {
            return null;
        }
        $response = null;
        try {
            switch ($payload['ChangeType']) {
                case 'create_user':// 新增成员事件
                    $make = app()->get(WorkMemberService::class);
                    $make->createMember($payload);
                    break;
                case 'update_user':// 更新成员事件
                    $make = app()->get(WorkMemberService::class);
                    $make->updateMember($payload);
                    break;
                case 'delete_user':// 删除成员事件
                    $make = app()->get(WorkMemberService::class);
                    $make->deleteMember($payload['ToUserName'], $payload['UserID']);
                    break;
                case 'create_party':// 新增部门事件
                    $make = app()->get(WorkDepartmentService::class);
                    $make->createDepartment($payload);
                    break;
                case 'update_party':// 更新部门事件
                    $make = app()->get(WorkDepartmentService::class);
                    $make->updateDepartment($payload['ToUserName'], (int) $payload['Id'], '');
                    break;
                case 'delete_party':// 删除部门事件
                    $make = app()->get(WorkDepartmentService::class);
                    $make->deleteDepartment($payload['ToUserName'], (int) $payload['Id']);
                    break;
                case 'update_tag':// 标签成员变更事件
                    break;
            }
        } catch (\Throwable $e) {
            Log::error(
                json_encode([
                    'message' => '企业微信通讯录事件发生错误:' . $e->getMessage(),
                    'payload' => $payload,
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }
        return $response;
    }

    /**
     * 客户事件.
     * @return |null
     */
    public function externalContactEvent(array $payload)
    {
        if (in_array($payload['ChangeType'] ?? '', ['add_external_contact', 'add_half_external_contact'], true)) {
            app()->get(WorkClientService::class)->sendChannelCodeWelcome($payload);
        }

        if (! sys_config('wechat_work_client_switch')) {
            return null;
        }
        $response = null;
        try {
            switch ($payload['ChangeType']) {
                case 'add_external_contact':// 添加企业客户事件
                    $make = app()->get(WorkClientService::class);
                    $make->createClient($payload);
                    break;
                case 'edit_external_contact':// 编辑企业客户事件
                    $make = app()->get(WorkClientService::class);
                    $make->updateClient($payload);
                    break;
                case 'del_external_contact':
                    $make = app()->get(WorkClientService::class);
                    $make->deleteClient($payload);
                    break;
                case 'add_half_external_contact':// 外部联系人免验证添加成员事件
                    $make = app()->get(WorkClientService::class);
                    $make->createClient($payload);
                    break;
                case 'del_follow_user':// 删除跟进成员事件
                    $make = app()->get(WorkClientService::class);
                    $make->deleteFollowClient($payload);
                    break;
                case 'transfer_fail':// 客户接替失败事件
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('客户事件发生错误:' . json_encode([
                'message' => $e->getMessage(),
                'payload' => $payload,
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE));
        }
        return $response;
    }

    /**
     * 客户群事件.
     */
    public function changeExternalChatEvent(array $payload)
    {
        try {
            switch ($payload['ChangeType']) {
                case 'create':// 客户群创建事件
                    $make = app()->get(WorkGroupChatService::class);
                    $make->saveWorkGroupChat($payload['ToUserName'], $payload['ChatId']);
                    break;
                case 'update':// 客户群变更事件
                    $make = app()->get(WorkGroupChatService::class);
                    $make->updateGroupChat($payload);
                    break;
                case 'dismiss':// 客户群解散事件
                    $make = app()->get(WorkGroupChatService::class);
                    $make->dismissGroupChat($payload['ToUserName'], $payload['ChatId']);
                    break;
            }
        } catch (\Throwable $e) {
            Log::error(
                json_encode([
                    'message' => $e->getMessage(),
                    'payload' => $payload,
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }
    }

    /**
     * 客户标签事件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function changeExternalTagEvent(array $payload)
    {
        if (! sys_config('wechat_work_client_switch')) {
            return null;
        }

        Log::warning('企业微信客户标签事件开始处理', [
            'change_type' => $payload['ChangeType'] ?? '',
            'tag_type'    => $payload['TagType'] ?? '',
            'id'          => $payload['Id'] ?? '',
            'corp_id'     => $payload['ToUserName'] ?? '',
        ]);

        try {
            $make = app()->get(LabelService::class);
            switch ($payload['ChangeType']) {
                case 'create':// 企业客户标签创建事件
                    $make->createUserLabel($payload['ToUserName'], $payload['Id'], $payload['TagType']);
                    break;
                case 'update':// 企业客户标签变更事件
                    $make->updateUserLabel($payload['ToUserName'], $payload['Id'], $payload['TagType']);
                    break;
                case 'delete':// 企业客户标签删除事件
                    $make->deleteUserLabel($payload['ToUserName'], $payload['Id'], $payload['TagType']);
                    break;
                case 'shuffle':// 企业客户标签重排事件
                    $make->syncTagOrder();
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('企业微信客户标签事件发生错误:' . json_encode([
                'message' => $e->getMessage(),
                'payload' => $payload,
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    private function changeMediaEvent(array $payload)
    {
        $jobId = $payload['JobId'];
        app()->get(WorkMediaService::class)->getFileInfo($jobId);
    }
}
