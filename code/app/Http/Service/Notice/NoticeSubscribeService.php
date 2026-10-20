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

use App\Http\Contract\Notice\MessageSubscribeInterface;
use App\Http\Dao\Notice\MessageSubscribeDao;
use App\Http\Service\Message\MessageService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

class NoticeSubscribeService extends BaseService implements MessageSubscribeInterface
{
    private MessageService $messageServices;

    public function __construct(MessageSubscribeDao $dao, MessageService $messageServices)
    {
        $this->dao             = $dao;
        $this->messageServices = $messageServices;
    }

    /**
     * 保存取消订阅消息列表.
     * @throws BindingResolutionException
     */
    public function saveSubscribe(int $id, int $status, int $uid): void
    {
        if (! $this->messageServices->value($id, 'user_sub')) {
            throw $this->exception('该消息通知无法取消订阅');
        }
        $messageId = $this->dao->value(['user_id' => $uid], 'message_id') ?: [];
        $messageId = is_array($messageId) ? $messageId : json_decode($messageId, true);
        if ($status) {
            if (($key = array_search($id, $messageId)) !== false) {
                unset($messageId[$key]);
                $this->transaction(function () use ($uid, $messageId) {
                    return $this->dao->updateOrCreate(['user_id' => $uid], ['user_id' => $uid, 'message_id' => $messageId]);
                });
            }
        } else {
            if (! in_array($id, $messageId)) {
                $this->transaction(function () use ($uid, $messageId, $id) {
                    $messageId[] = $id;
                    $this->dao->updateOrCreate(['user_id' => $uid], ['user_id' => $uid, 'message_id' => json_encode($messageId)]);
                });
            }
        }
    }

    /**
     * 验证消息是否订阅.
     * @throws BindingResolutionException
     */
    public function isSend(int|string $userId, int $entId, string $templateType): bool
    {
        if (strlen((string) $userId) >= 32) {
            $userId = uuid_to_uid($userId, $entId);
        }
        $message = $this->messageServices->get(['entid' => $entId, 'template_type' => $templateType]);
        if (! $message) {
            return false;
        }
        if (! $message->user_sub) {
            return true;
        }
        $messageId = is_array($messageId = $this->dao->value(['user_id' => $userId], 'message_id') ?: []) ? $messageId : json_decode($messageId, true);
        if (! $messageId) {
            return true;
        }
        if (in_array($message['id'], $messageId)) {
            return false;
        }
        return true;
    }
}
