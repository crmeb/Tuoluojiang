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
 * 外部联系
 * Class ExternalContactClient.
 * @email 136327134@qq.com
 * @date 2023/9/15
 */
class ExternalContactClient extends BaseClient
{
    /**
     * 获取外部联系人详情.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function get(string $externalUserId): Response|ResponseInterface
    {
        return $this->api->get('cgi-bin/externalcontact/get', [
            'external_userid' => $externalUserId,
        ]);
    }

    /**
     * 批量获取客户详情.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function batchGet(array $userIdList, string $cursor = '', int $limit = 100): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/batch/get_by_user', [
            'userid_list' => $userIdList,
            'cursor'      => $cursor,
            'limit'       => $limit,
        ]);
    }

    /**
     * 获取企业标签库.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getCorpTags(array $tagIds = [], array $groupIds = []): Response|ResponseInterface
    {
        $params = [
            'tag_id'   => $tagIds,
            'group_id' => $groupIds,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/get_corp_tag_list', $params);
    }

    /**
     * 添加企业客户标签.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function addCorpTag(array $params): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/add_corp_tag', $params);
    }

    /**
     * 编辑企业客户标签.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function updateCorpTag(string $id, ?string $name = null, ?int $order = null): Response|ResponseInterface
    {
        $params = [
            'id' => $id,
        ];

        if (! \is_null($name)) {
            $params['name'] = $name;
        }

        if (! \is_null($order)) {
            $params['order'] = $order;
        }

        return $this->api->postJson('cgi-bin/externalcontact/edit_corp_tag', $params);
    }

    /**
     * 删除企业客户标签.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function deleteCorpTag(array $tagId, array $groupId): Response|ResponseInterface
    {
        $params = [
            'tag_id'   => $tagId,
            'group_id' => $groupId,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/del_corp_tag', $params);
    }

    /**
     * 编辑客户企业标签.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function markTags(array $params): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/mark_tag', $params);
    }

    /**
     * 获取客户群列表.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getGroupChats(array $params): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/groupchat/list', $params);
    }

    /**
     * 获取客户群详情.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getGroupChat(string $chatId, int $needName = 0): Response|ResponseInterface
    {
        $params = [
            'chat_id'   => $chatId,
            'need_name' => $needName,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/groupchat/get', $params);
    }

    /**
     * 获取「群聊数据统计」数据. (按自然日聚合的方式).
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function groupChatStatisticGroupByDay(int $dayBeginTime, int $dayEndTime, array $userIds = []): Response|ResponseInterface
    {
        $params = [
            'day_begin_time' => $dayBeginTime,
            'day_end_time'   => $dayEndTime,
            'owner_filter'   => [
                'userid_list' => $userIds,
            ],
        ];
        return $this->api->postJson('cgi-bin/externalcontact/groupchat/statistic_group_by_day', $params);
    }
}
