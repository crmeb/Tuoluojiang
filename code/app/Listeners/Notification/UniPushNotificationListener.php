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
use App\Http\Service\Notice\NoticeSubscribeService;
use Tuoluojiang\Open\services\uniPush\options\PushMessageOptions;
use Tuoluojiang\Open\services\uniPush\options\PushOptions;
use Tuoluojiang\Open\services\uniPush\PushMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * UniPush通知监听器
 * 处理个推推送通知消息.
 */
class UniPushNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $delay;

    /**
     * 处理事件.
     */
    public function handle(SystemMessageEvent $event): void
    {
        if (! isset($event->templates['system']['status']) || ! $event->templates['system']['status']) {
            return;
        }
        $this->delay = $event->templates['system']['push_rule'] ? (int) ($event->templates['system']['minute']) : $event->setDelay;
        try {
            foreach ($event->receiver as $user) {
                // 直接实现UniPush发送逻辑
                try {
                    $messageInfo = $this->prepareSystemMessage($event, $user['id']);
                    if (! $messageInfo) {
                        continue;
                    }
                    $uniPush                     = app(PushMessage::class);
                    $option                      = new PushOptions();
                    $messageOption               = new PushMessageOptions();
                    $messageOption->title        = $messageInfo['title'];
                    $messageOption->badgeAddNum  = 1;
                    $messageOption->body         = $messageInfo['message'];
                    $messageOption->clickType    = 'payload';
                    $messageOption->payload      = json_encode(['url' => $messageInfo['uni_url'], 'type' => 'url']);
                    $messageOption->channelLevel = 3;
                    $option->setAudience($user['client_id']);
                    $option->setPushMessage($messageOption);
                    $option->pushChannel = [
                        'transmission' => json_encode([
                            'title' => $messageOption->title,
                            'body'  => $messageOption->body,
                            'url'   => $messageInfo['uni_url'],
                        ]),
                    ];

                    // 厂商推送消息参数
                    $pushChannel = new \GTPushChannel();
                    // ios
                    $ios = new \GTIos();
                    $ios->setType('notify');
                    $ios->setAutoBadge('+1');
                    $ios->setPayload('ios_payload');
                    $ios->setApnsCollapseId('apnsCollapseId');
                    // aps设置
                    $aps = new \GTAps();
                    $aps->setContentAvailable(0);
                    $aps->setSound('default');

                    $alert = new \GTAlert();
                    $alert->setTitle($messageOption->title);
                    $alert->setBody($messageOption->body);
                    $aps->setAlert($alert);
                    $ios->setAps($aps);

                    $multimedia = new \GTMultimedia();
                    $multimedia->setUrl($messageInfo['uni_url']);
                    $multimedia->setType(1);
                    $multimedia->setOnlyWifi(false);
                    $multimedia2 = new \GTMultimedia();
                    $multimedia2->setUrl($messageInfo['uni_url']);
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
                    // $thirdNotification->setIntent("intent:#Intent;component=uni.UNIA6C11DD/你要打开的 activity 全路径;S.parm1=value1;S.parm2=value2;end");
                    // $thirdNotification->setIntent('intent:#Intent;component=' . sys_config('uni_package_id') . "/{$messageInfo['uni_url']};end");
                    $thirdNotification->setUrl($messageInfo['uni_url']);
                    $thirdNotification->setPayload(json_encode(['url' => $messageInfo['uni_url'], 'type' => 'url']));
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
                    $notify->setPayload(json_encode(['url' => $messageInfo['uni_url'], 'type' => 'url']));
                    $uniPush->push($message, $notify, $pushChannel, $user['client_id']);
                } catch (\Exception $e) {
                    Log::error('uniPush推送错误:', ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
                }
            }

            Log::info('UniPush notification sent successfully', [
                'template_type'  => $event->type,
                'receiver_count' => count($event->receiver),
                'entId'          => $event->entId,
            ]);
        } catch (\Exception $e) {
            Log::error('UniPush notification listener error: ' . $e->getMessage(), [
                'exception'  => $e,
                'event_data' => $event->getData(),
            ]);
        }
    }

    /**
     * 准备系统通知消息数据.
     */
    private function prepareSystemMessage(SystemMessageEvent $event, int $receiverId): ?array
    {
        // 检查接收者是否订阅了此类型的通知
        $sendStatus = app()->get(NoticeSubscribeService::class)->isSend($receiverId, $event->entId, $event->type);
        if (! $sendStatus) {
            Log::info('User unsubscribed from this notification type', [
                'user_id'       => $receiverId,
                'template_type' => $event->type,
            ]);
            return null;
        }
        $systemMessage = $event->templates['system'];
        // 替换模板变量
        $templateVarPattern = '/\{#[\x7f-\xffa-z0-9_]+}/';
        preg_match_all($templateVarPattern, $systemMessage['content_template'], $matchResult);
        $templateVars  = $matchResult[0] ?? [];
        $replaceKeys   = [];
        $replaceValues = [];
        foreach ($templateVars as $var) {
            $key = trim(str_replace(['{#', '}'], '', $var));
            if (empty($key)) {
                continue;
            }
            $replaceKeys[]   = $var;
            $replaceValues[] = $event->params[$key] ?? '';
        }
        $content = ! empty($replaceKeys)
            ? str_replace($replaceKeys, $replaceValues, $systemMessage['content_template'])
            : $systemMessage['content_template'];

        // 返回准备好的消息数据
        return [
            'title'   => $event->messageInfo['title'] ?? '系统通知',
            'message' => $content,
            'uni_url' => $systemMessage['uni_url'] ? $systemMessage['uni_url'] . http_build_query($event->other) : '',
            'other'   => $event->other,
        ];
    }
}
