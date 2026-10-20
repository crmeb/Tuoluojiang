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

use App\Constants\NoticeEnum;
use App\Events\SystemMessageEvent;
use App\Http\Service\Notice\NoticeRecordService;
use App\Http\Service\Notice\NoticeSubscribeService;
use Tuoluojiang\Open\services\SwooleTaskService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Socket通知监听器
 * 处理Socket实时通知消息.
 */
class SocketNotificationListener implements ShouldQueue
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
            $service = app(NoticeRecordService::class);
            foreach ($event->receiver as $user) {
                $messageInfo = $this->prepareSystemMessage($event, $user['id']);
                if (! $messageInfo) {
                    continue; // 模板未启用或未配置
                }
                $messageInfo['created_at'] = now()->toDateTimeString();
                if (! $event->other) {
                    $messageInfo['is_handle'] = 1;
                }
                $res = $service->create($messageInfo);
                // 直接实现Socket推送逻辑
                try {
                    $buttonTemplate = is_string($messageInfo['button_template']) ? json_decode($messageInfo['button_template'], true) : $messageInfo['button_template'];
                    $buttonTemplate = is_string($buttonTemplate) ? json_decode($buttonTemplate, true) : $buttonTemplate;
                    SwooleTaskService::ent()->entid($messageInfo['send_id'])->data('ent', [
                        'message'         => $messageInfo['message'],
                        'image'           => $messageInfo['image'],
                        'cate_name'       => $messageInfo['cate_name'],
                        'title'           => $messageInfo['title'],
                        'url'             => $messageInfo['url'],
                        'uni_url'         => $messageInfo['uni_url'],
                        'button_template' => $buttonTemplate,
                        'buttons'         => $this->getButtonInfo($messageInfo['template_type'], $event->linkStatus, $event->linkId, $messageInfo['url'], $messageInfo['uni_url']),
                        'other'           => is_string($messageInfo['other']) ? json_decode($messageInfo['other'], true) : $messageInfo['other'],
                        'template_type'   => $messageInfo['template_type'],
                        'uniqid'          => uniqid(),
                        'id'              => $res->id,
                        'link_id'         => $event->linkId ?? 0,
                        'link_status'     => $event->linkStatus ?? 0,
                    ])->type('notice')->to($user['uid'])->push();
                } catch (\Exception $e) {
                    Log::error('socketPush推送错误：' . json_encode(['msg' => $e->getMessage(), 'line' => $e->getLine()]));
                }
            }
            Log::info('Socket notification sent successfully', [
                'template_type'  => $event->messageInfo['template_type'],
                'receiver_count' => count($event->receiver),
                'entId'          => $event->entId,
            ]);
        } catch (\Exception $e) {
            Log::error('Socket notification listener error: ' . $e->getMessage(), $event->getData());
        }
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
    protected function getButtonInfo(string $noticeType, int $status, int $id = 0, string $url = '', string $uniUrl = ''): array
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
     * 准备系统通知消息数据.
     */
    private function prepareSystemMessage(SystemMessageEvent $event, int $receiverId): ?array
    {
        $sendStatus = app(NoticeSubscribeService::class)->isSend($receiverId, $event->entId, $event->type);
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
            'send_id'         => $event->senderId,
            'cate_id'         => $event->messageInfo['cate_id'] ?? 0,
            'message_id'      => $event->messageInfo['id'] ?? 0,
            'cate_name'       => $event->messageInfo['cate_name'] ?? '系统消息',
            'title'           => $event->messageInfo['title'] ?? '系统通知',
            'image'           => $systemMessage['image'] ?? '',
            'template_type'   => $event->type,
            'button_template' => $systemMessage['button_template'] ?? '',
            'message'         => $content,
            'other'           => json_encode($event->other),
            'url'             => $systemMessage['url'] ?? '',
            'uni_url'         => $systemMessage['uni_url'] ?? '',
            'type'            => 0,
            'entid'           => $event->entId,
            'link_id'         => $event->linkId ?? 0,
            'link_status'     => $event->linkStatus ?? 0,
            'to_uid'          => $receiverId,
        ];
    }
}
