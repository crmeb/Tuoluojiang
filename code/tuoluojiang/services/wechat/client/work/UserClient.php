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
 * 用户
 * Class UserClient.
 * @email 136327134@qq.com
 * @date 2023/9/18
 */
class UserClient extends BaseClient
{
    /**
     * 获取部门成员详细信息.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getDetailedDepartmentUsers(int $departmentId, bool $fetchChild = false): Response|ResponseInterface
    {
        $params = [
            'department_id' => $departmentId,
            'fetch_child'   => (int) $fetchChild,
        ];

        return $this->api->get('cgi-bin/user/list', $params);
    }

    /**
     * 获取通讯录成员详情.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function get(string $userId): Response|ResponseInterface
    {
        return $this->api->get('cgi-bin/user/get', ['userid' => $userId]);
    }

    /**
     * 获取成员ID列表.
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function getUserListId(string $cursor = '', int $limit = 100)
    {
        return $this->api->get('cgi-bin/user/list_id', ['cursor' => $cursor, 'limit' => $limit]);
    }

    /**
     * userid转openid.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function userIdToOpenid(string $userId, ?int $agentId = null): Response|ResponseInterface
    {
        $params = [
            'userid'  => $userId,
            'agentid' => $agentId,
        ];

        return $this->api->postJson('cgi-bin/user/convert_to_openid', $params);
    }
}
