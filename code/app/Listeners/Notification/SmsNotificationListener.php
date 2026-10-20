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

namespace App\Listeners\Notification;

use App\Events\SystemMessageEvent;
use App\Task\message\SmsMessageTask;
use Tuoluojiang\Open\utils\MessageType;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * 短信通知监听器
 * 处理短信通知消息.
 */
class SmsNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $delay;

    /**
     * 处理事件.
     */
    public function handle(SystemMessageEvent $event): void
    {
        if (! isset($event->templates['sms']['status']) || ! $event->templates['sms']['status']) {
            return;
        }
        $this->delay = $event->templates['system']['push_rule'] ? (int) $event->templates['system']['minute'] : $event->setDelay;
        try {
            // 不需要发送短信的消息类型
            $excludeTypes = [
                MessageType::ENTERPRISE_VERIFY_TYPE,
                MessageType::ENTERPRISE_VEERIFY_FAIL_TYPE,
            ];
            if (in_array($event->type, $excludeTypes)) {
                Log::info('SMS notification excluded for specific message type', [
                    'template_type' => $event->type,
                ]);
                return;
            }
            $smsTemplate = $event->templates['sms'];
            if (! $smsTemplate || empty($smsTemplate['status']) || empty($smsTemplate['template_id'])) {
                Log::warning('SMS template not found or not enabled', [
                    'template_type' => $event->type,
                    'entId'         => $event->entId,
                ]);
                return;
            }
            // 提取模板变量
            preg_match_all('/\{#([\x7f-\xffa-z0-9_]+)}/', $smsTemplate['content_template'], $matches);
            $dataVar = array_reduce($matches[1] ?? [], function ($carry, $key) use ($event) {
                $carry[$key] = $event->params[$key] ?? '';
                return $carry;
            }, []);
            // 获取企业ID
            $entId          = $event->entId ?: $event->senderId;
            $smsSendContent = $smsTemplate['content_template'];
            foreach ($event->receiver as $user) {
                // 使用任务队列发送短信
                Task::deliver(new SmsMessageTask($user['phone'], $entId, $smsTemplate['template_id'], $smsSendContent, $dataVar));
            }
            Log::info('SMS notification sent successfully', [
                'template_type'  => $event->type,
                'receiver_count' => count($event->receiver),
                'entId'          => $event->entId,
            ]);
        } catch (\Exception $e) {
            Log::error('SMS notification listener error: ' . $e->getMessage(), [
                'exception'  => $e,
                'event_data' => $event->getData(),
            ]);
        }
    }
}
