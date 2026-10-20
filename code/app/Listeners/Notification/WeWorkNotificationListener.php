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
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * 企业微信通知监听器.
 */
class WeWorkNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $delay;

    protected string $tempType = 'template_card';

    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(SystemMessageEvent $event): void
    {
        if (! isset($event->templates['wework']['status']) || ! $event->templates['wework']['status']) {
            return;
        }
        $this->delay = $event->templates['system']['push_rule'] ? (int) $event->templates['system']['minute'] : $event->setDelay;
        try {
            if (! sys_config('wechat_work_build_agent_id')) {
                return;
            }
            $template = $event->templates['wework'];
            $params   = $event->params;
            $work     = app(Work::class);
            collect($event->receiver)->filter(fn ($item) => $item['member']['userid'] ?? false)->each(function ($value) use ($template, $params, $work, $event) {
                $sendStatus = app(NoticeSubscribeService::class)->isSend($value['id'], $event->entId, $event->type);
                if (! $sendStatus) {
                    Log::info('User unsubscribed from this notification type', [
                        'user_id'       => $value['id'],
                        'template_type' => $event->type,
                    ]);
                    return;
                }
                $work->sendMessage($this->getContent($template, $params, $event, $value));
            });
            Log::info('WeWork notification sent successfully', [
                'template_type'   => $event->type,
                'receivers_count' => count($event->receiver),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WeWork notification: ' . $e->getMessage(), $event->getData());
        }
    }

    public function getContent($template, $params, $event, $value)
    {
        $jumpUrl = $this->buildJumpUrl($template, $event->other);

        if ($this->tempType === 'template_card' && ! $jumpUrl) {
            return [
                'touser'  => $value['member']['userid'],
                'msgtype' => 'text',
                'agentid' => sys_config('wechat_work_build_agent_id'),
                'text'    => [
                    'content' => $this->buildTextContent($template, $params),
                ],
            ];
        }

        return match ($this->tempType) {
            'template_card' => [
                'touser'        => $value['member']['userid'],
                'msgtype'       => 'template_card',
                'agentid'       => sys_config('wechat_work_build_agent_id'),
                'template_card' => [
                    'card_type'               => 'text_notice',
                    'sub_title_text'          => $template['message_title'],
                    'horizontal_content_list' => collect($template['template_var'])->map(function ($item) use ($params) {
                        return [
                            'keyname' => $item,
                            'value'   => $params[$item] ?? '',
                        ];
                    })->all(),
                    'jump_list' => collect(json_decode($template['button_template'], true) ?? [])->map(function ($item) use ($jumpUrl) {
                        return [
                            'type'  => 1,
                            'title' => $item,
                            'url'   => $jumpUrl,
                        ];
                    })->values()->all(),
                    'card_action' => [
                        'type' => 1,
                        'url'  => $jumpUrl,
                    ],
                ],
            ],
            'markdown' => [
                'touser'   => $value['member']['userid'],
                'msgtype'  => 'markdown',
                'agentid'  => sys_config('wechat_work_build_agent_id'),
                'markdown' => [
                    'content' => $this->buildMarkdown($template, $params, $event->other),
                ],
            ],
            default => [
                'touser'   => $value['member']['userid'],
                'msgtype'  => 'textcard',
                'agentid'  => sys_config('wechat_work_build_agent_id'),
                'textcard' => [
                    'title'       => $template['message_title'],
                    'description' => $this->buildDescription($template['template_var'], $params),
                    'url'         => $template['uni_url'] ? $template['uni_url'] . '?' . http_build_query($event->other) : '',
                    'btntxt'      => '立即处理',
                ],
            ],
        };
    }

    private function buildDescription($templateVar, $params, $redKeys = [])
    {
        $description = '';
        collect($templateVar)->each(function ($item) use ($params, &$description, $redKeys) {
            if (in_array($item, $redKeys)) {
                $description .= "{$item}：<font color='red'>" . ($params[$item] ?? '') . "</font>\n";
            } else {
                $description .= "{$item}：" . ($params[$item] ?? '') . "\n";
            }
        });
        return rtrim($description, "\n");
    }

    private function buildMarkdown($template, $params, $other = [])
    {
        $description = '**' . $template['message_title'] . "**\n";
        collect($template['template_var'])->each(function ($item) use ($params, &$description) {
            $description .= "{$item}：" . ($params[$item] ?? '') . "\n";
        });
        if ($template['uni_url']) {
            $description .= '[立即查看](' . $template['uni_url'] . '?' . http_build_query($other) . ")\n";
        }
        return rtrim($description, "\n");
    }

    private function buildJumpUrl(array $template, array $other = []): string
    {
        if (empty($template['uni_url'])) {
            return '';
        }

        $query = http_build_query($other);
        if (! $query) {
            return $template['uni_url'];
        }

        return $template['uni_url'] . (str_contains($template['uni_url'], '?') ? '&' : '?') . $query;
    }

    private function buildTextContent(array $template, array $params): string
    {
        $content = $template['content_template'] ?: $template['message_title'];
        preg_match_all('/\{#([\x7f-\xffa-z0-9_]+)}/', $content, $matches);
        foreach ($matches[1] ?? [] as $item) {
            $content = str_replace('{#' . $item . '}', $params[$item] ?? '', $content);
        }

        return $content;
    }
}
