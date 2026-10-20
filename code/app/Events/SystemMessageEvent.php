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

namespace App\Events;

use App\Constants\NoticeEnum;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Message\MessageService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 系统消息事件
 * 用于统一处理各类通知消息.
 */
class SystemMessageEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var array 消息内容
     */
    public array $messageInfo = [];

    public array $receiver = [];

    public array $templates;

    /**
     * @param string $type 通知类型
     * @param array $params 模板参数
     * @param array|int $receiverIds 接收者ID
     * @param int $senderId 发送者ID
     * @param array $other 附加信息
     * @param int $linkId 关联业务ID
     * @param ?int $linkStatus 链接状态
     * @param int $setDelay 延迟时间
     * @param int $entId 企业ID
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function __construct(
        public string $type,
        public array $params = [],
        array|int $receiverIds,
        public int $senderId = 0,
        public array $other = [],
        public int $linkId = 0,
        public ?int $linkStatus = 0,
        public int $setDelay = 0,
        public int $entId = 1
    ) {
        $messageInfo = app(MessageService::class)->getMessageContent($entId, $type);
        if (! $messageInfo) {
            return;
        }
        // 初始化消息模板和结果数组
        $templateTypes = [
            NoticeEnum::TYPE_SYSTEM     => 'system',
            NoticeEnum::TYPE_SMS        => 'sms',
            NoticeEnum::TYPE_WORK_HOOK  => 'work',
            NoticeEnum::TYPE_DING_HOOK  => 'ding',
            NoticeEnum::TYPE_OTHER_HOOK => 'bot',
            NoticeEnum::TYPE_WEWORK     => 'wework',
        ];
        $templates = array_fill_keys(array_values($templateTypes), []);
        collect($messageInfo['message_template'] ?? [])->map(function ($item) use ($templateTypes, &$templates) {
            $item['url']                              = $item['url'] ? link_file(config('app.ent.path') . $item['url']) : '';
            $item['uni_url']                          = $item['uni_url'] ? link_file(config('app.work.path') . $item['uni_url']) : '';
            $templates[$templateTypes[$item['type']]] = $item;
        });
        $this->templates = $templates;
        unset($messageInfo['message_template']);
        $this->messageInfo = $messageInfo;
        $receiverIds       = is_array($receiverIds) ? $receiverIds : [$receiverIds];
        $this->receiver    = collect(app(AdminService::class)->select(['id' => $receiverIds], ['id', 'uid', 'name', 'avatar', 'phone', 'client_id', 'work_member_id'], with: ['member'])?->toArray() ?? [])->keyBy('id')->all();
    }

    /**
     * 获取事件数据.
     */
    public function getData(): array
    {
        return [
            'type'        => $this->type,
            'messageInfo' => $this->messageInfo,
            'templates'   => $this->templates,
            'params'      => $this->params,
            'entId'       => $this->entId,
            'senderId'    => $this->senderId,
            'receiver'    => $this->receiver,
            'other'       => $this->other,
            'linkId'      => $this->linkId,
            'linkStatus'  => $this->linkStatus,
            'setDelay'    => $this->setDelay,
        ];
    }
}
