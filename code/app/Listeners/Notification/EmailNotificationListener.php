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
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Message\MessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 邮件通知监听器
 * 处理邮件通知消息.
 */
class EmailNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * 处理事件.
     */
    public function handle(SystemMessageEvent $event): void
    {
        if (! isset($event->templates['email']['status']) || ! $event->templates['email']['status']) {
            return;
        }
        try {
            // 获取邮件配置
            $messageService = app()->get(MessageService::class);
            $emailMessage   = $messageService->get([
                'entid'         => $event->entId,
                'template_type' => $event->templateType,
            ]);

            if (! $emailMessage || ! $emailMessage['status']) {
                Log::info('Email notification disabled or not configured', [
                    'template_type' => $event->templateType,
                    'entId'         => $event->entId,
                ]);
                return;
            }

            // 获取邮件模板配置
            $emailTemplate = collect($emailMessage['message_template'])->filter(fn ($item) => $item['type'] == 0)->first(); // 假设邮件模板类型为0

            if (! $emailTemplate || empty($emailTemplate['status'])) {
                Log::warning('Email template not found or not enabled', [
                    'template_type' => $event->templateType,
                    'entId'         => $event->entId,
                ]);
                return;
            }

            // 提取模板变量
            preg_match_all('/\{#([\x7f-\xffa-z0-9_]+)}/', $emailTemplate['content_template'], $matches);
            $dataVar = array_reduce($matches[1] ?? [], function ($carry, $key) use ($event) {
                $carry[$key] = $event->params[$key] ?? '';
                return $carry;
            }, []);

            // 替换模板变量
            $content = $emailTemplate['content_template'];
            foreach ($dataVar as $key => $value) {
                $content = str_replace('{#' . $key . '}', $value, $content);
            }

            // 准备消息数据
            $receiverIds = is_array($event->receiverIds) ? $event->receiverIds : [$event->receiverIds];

            foreach ($receiverIds as $receiverId) {
                // 获取用户邮箱
                $adminService = app()->get(AdminService::class);
                $user         = $adminService->get((int) $receiverId);

                if (! $user || empty($user['email'])) {
                    Log::warning('User email not found', ['user_id' => $receiverId]);
                    continue;
                }

                $email = $user['email'];

                // 发送邮件
                Mail::raw($content, function ($message) use ($email, $event, $emailTemplate) {
                    $message->to($email)
                        ->subject($event->title ?: $emailTemplate['title_template'] ?? '系统邮件通知');
                });
            }

            Log::info('Email notification sent successfully', [
                'template_type'  => $event->templateType,
                'receiver_count' => count($receiverIds),
                'entId'          => $event->entId,
            ]);
        } catch (\Exception $e) {
            Log::error('Email notification listener error: ' . $e->getMessage(), [
                'exception'  => $e,
                'event_data' => $event->getData(),
            ]);
        }
    }
}
