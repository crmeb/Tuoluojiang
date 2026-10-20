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

namespace Tuoluojiang\Open\services\wechat\client\work;

use Tuoluojiang\Open\services\wechat\client\BaseClient;
use Tuoluojiang\Open\services\wechat\WechatException;
use EasyWeChat\Kernel\HttpClient\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 消息发送
 * Class MessageClient.
 * @email 136327134@qq.com
 * @date 2023/10/8
 */
class MessageClient extends BaseClient
{
    /**
     * Required attributes.
     */
    protected array $required = ['content', 'title', 'url', 'pic_media_id', 'appid', 'page'];

    /**
     * @var array|string[]
     */
    protected array $textMessage = [
        'content' => '',
    ];

    protected array $imageMessage = [
    ];

    /**
     * @var array|string[]
     */
    protected array $linkMessage = [
        'title'  => '',
        'picurl' => '',
        'desc'   => '',
        'url'    => '',
    ];

    /**
     * @var array|string[]
     */
    protected array $miniprogramMessage = [
        'title'        => '',
        'pic_media_id' => '',
        'appid'        => '',
        'page'         => '',
    ];

    /**
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function send(array $message): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/message/send', $message);
    }

    /**
     * 添加企业群发消息模板
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function submit(array $msg): Response|ResponseInterface
    {
        $params = $this->formatMessage($msg);

        return $this->api->postJson('cgi-bin/externalcontact/add_msg_template', $params);
    }

    /**
     * 取消群发.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function cancel(string $msgId): Response|ResponseInterface
    {
        $data = [
            'msgid' => $msgId,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/cancel_groupmsg_send', $data);
    }

    /**
     * 提醒群发.
     * @throws TransportExceptionInterface
     */
    public function remind(string $msgId): Response|ResponseInterface
    {
        $data = [
            'msgid' => $msgId,
        ];
        return $this->api->postJson('cgi-bin/externalcontact/remind_groupmsg_send', $data);
    }

    /**
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/26
     */
    public function sendWelcome(string $welcomeCode, array $msg): Response|ResponseInterface
    {
        $formattedMsg = $this->formatMessage($msg);

        $params = array_merge($formattedMsg, [
            'welcome_code' => $welcomeCode,
        ]);

        return $this->api->postJson('cgi-bin/externalcontact/send_welcome_msg', $params);
    }

    /**
     * 获取群发成员发送任务列表.
     * @param string $msgId 群发消息的id，通过获取群发记录列表接口返回
     * @param null|int $limit 返回的最大记录数，整型，最大值1000，默认值500，超过最大值时取默认值
     * @param null|string $cursor 用于分页查询的游标，字符串类型，由上一次调用返回，首次调用可不填
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getGroupmsgTask(string $msgId, ?int $limit = null, ?string $cursor = null): Response|ResponseInterface
    {
        $data = [
            'msgid'  => $msgId,
            'limit'  => $limit,
            'cursor' => $cursor,
        ];
        $writableData = array_filter($data, function (string $key) use ($data) {
            return ! is_null($data[$key]);
        }, ARRAY_FILTER_USE_KEY);
        return $this->api->postJson('cgi-bin/externalcontact/get_groupmsg_task', $writableData);
    }

    /**
     * 获取企业群发成员执行结果.
     * @param string $msgId 群发消息的id，通过获取群发记录列表接口返回
     * @param string $userid 发送成员userid，通过获取群发成员发送任务列表接口返回
     * @param null|int $limit 返回的最大记录数，整型，最大值1000，默认值500，超过最大值时取默认值
     * @param null|string $cursor 用于分页查询的游标，字符串类型，由上一次调用返回，首次调用可不填
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getGroupmsgSendResult(string $msgId, string $userid, ?int $limit = null, ?string $cursor = null): Response|ResponseInterface
    {
        $data = [
            'msgid'  => $msgId,
            'userid' => $userid,
            'limit'  => $limit,
            'cursor' => $cursor,
        ];
        $writableData = array_filter($data, function (string $key) use ($data) {
            return ! is_null($data[$key]);
        }, ARRAY_FILTER_USE_KEY);
        return $this->api->postJson('cgi-bin/externalcontact/get_groupmsg_send_result', $writableData);
    }

    /**
     * @return array
     * @email 136327134@qq.com
     * @date 2023/9/26
     */
    protected function formatMessage(array $data = [])
    {
        $params = $data;

        if (! empty($params['text'])) {
            $params['text'] = $this->formatFields($params['text'], $this->textMessage);
        }

        if (! empty($params['image'])) {
            $params['image'] = $this->formatFields($params['image'], $this->imageMessage);
        }

        if (! empty($params['link'])) {
            $params['link'] = $this->formatFields($params['link'], $this->linkMessage);
        }

        if (! empty($params['miniprogram'])) {
            $params['miniprogram'] = $this->formatFields($params['miniprogram'], $this->miniprogramMessage);
        }

        return $params;
    }

    /**
     * @return array
     * @email 136327134@qq.com
     * @date 2023/9/26
     */
    protected function formatFields(array $data = [], array $default = [])
    {
        $params = array_merge($default, $data);
        foreach ($params as $key => $value) {
            if (in_array($key, $this->required, true) && empty($value) && empty($default[$key])) {
                throw new WechatException(sprintf('Attribute "%s" can not be empty!', $key));
            }

            $params[$key] = empty($value) ? $default[$key] : $value;
        }

        return $params;
    }
}
