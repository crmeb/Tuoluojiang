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
use EasyWeChat\Kernel\HttpClient\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 联系客服
 * Class ContactWayClient.
 * @email 136327134@qq.com
 * @date 2023/9/19
 */
class ContactWayClient extends BaseClient
{
    /**
     * 配置客户联系「联系我」方式.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function create(int $type, int $scene, array $config = []): Response|ResponseInterface
    {
        $params = array_merge([
            'type'  => $type,
            'scene' => $scene,
        ], $config);

        return $this->api->postJson('cgi-bin/externalcontact/add_contact_way', $params);
    }

    /**
     * 更新企业已配置的「联系我」方式.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function update(string $configId, array $config = []): Response|ResponseInterface
    {
        $params = array_merge([
            'config_id' => $configId,
        ], $config);

        return $this->api->postJson('cgi-bin/externalcontact/update_contact_way', $params);
    }

    /**
     * 获取企业已配置的「联系我」方式.
     * @throws TransportExceptionInterface
     */
    public function get(string $configId): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/get_contact_way', [
            'config_id' => $configId,
        ]);
    }

    /**
     * 删除企业已配置的「联系我」方式.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function delete(string $configId): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/del_contact_way', [
            'config_id' => $configId,
        ]);
    }

    /**
     * 获取企业已配置的「联系我」列表，注意，该接口仅可获取2021年7月10日以后创建的「联系我」.
     * @param string $cursor 分页查询使用的游标，为上次请求返回的 next_cursor
     * @param int $limit 每次查询的分页大小，默认为100条，最多支持1000条
     * @param null|int $startTime 「联系我」创建起始时间戳, 不传默认为90天前
     * @param null|int $endTime 「联系我」创建结束时间戳, 不传默认为当前时间
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function list(string $cursor = '', int $limit = 100, ?int $startTime = null, ?int $endTime = null): Response|ResponseInterface
    {
        $data = [
            'cursor' => $cursor,
            'limit'  => $limit,
        ];
        if ($startTime) {
            $data['start_time'] = $startTime;
        }
        if ($endTime) {
            $data['end_time'] = $endTime;
        }
        return $this->api->postJson('cgi-bin/externalcontact/list_contact_way', $data);
    }

    /**
     * 结束临时会话.
     * @param string $userId 企业成员的user_id
     * @param string $externalUserId 客户的外部联系人user_id
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function closeTempChat(string $userId, string $externalUserId): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/close_temp_chat', [
            'userid'          => $userId,
            'external_userid' => $externalUserId,
        ]);
    }
}
