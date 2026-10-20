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
 * 客户群聊配置
 * Class GroupChatClient.
 * @email 136327134@qq.com
 * @date 2023/9/15
 */
class GroupChatClient extends BaseClient
{
    /**
     * 更新客户群进群方式配置.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function updateJoinWay(string $configId, string $roomBaseName, array $chatIdList, string $state, int $autoCreateRoom = 1, int $roomBaseId = 1, ?string $remark = null, int $scene = 2): Response|ResponseInterface
    {
        $data = [
            'config_id'        => $configId,
            'scene'            => $scene,
            'remark'           => $remark,
            'auto_create_room' => $autoCreateRoom,
            'room_base_name'   => $roomBaseName,
            'room_base_id'     => $roomBaseId,
            'chat_id_list'     => $chatIdList,
            'state'            => $state,
        ];
        return $this->api->postJson('cgi-bin/externalcontact/groupchat/update_join_way', $data);
    }

    /**
     * 配置客户群进群方式.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function addJoinWay(string $roomName, array $chatIdList, string $state, int $autoCreateRoom = 1, int $roomBaseId = 1, ?string $remark = null, int $scene = 2): Response|ResponseInterface
    {
        $data = [
            'scene'            => $scene,
            'remark'           => $remark,
            'chat_id_list'     => $chatIdList,
            'auto_create_room' => $autoCreateRoom,
            'room_base_name'   => $roomName,
            'room_base_id'     => $roomBaseId,
            'state'            => $state,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/groupchat/add_join_way', $data);
    }

    /**
     * 获取客户群进群方式配置.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getJoinWay(string $configId): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/groupchat/get_join_way', ['config_id' => $configId]);
    }

    /**
     * 删除客户群进群方式配置.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function deleteJoinWay(string $configId): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/groupchat/del_join_way', ['config_id' => $configId]);
    }
}
