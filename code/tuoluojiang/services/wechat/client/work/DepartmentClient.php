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
 * 部门
 * Class DepartmentClient.
 * @email 136327134@qq.com
 * @date 2023/9/15
 */
class DepartmentClient extends BaseClient
{
    /**
     * 获取部门信息.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function get(int $id): Response|ResponseInterface
    {
        return $this->api->get('cgi-bin/department/get', compact('id'));
    }

    /**
     * 获取部门列表.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function list(?int $id = null): Response|ResponseInterface
    {
        return $this->api->get('cgi-bin/department/list', compact('id'));
    }

    /**
     * 获取子部门ID列表.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function simpleList(?int $id = null): Response|ResponseInterface
    {
        return $this->api->get('cgi-bin/department/simplelist', compact('id'));
    }

    /**
     * 获取成员ID列表.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getUserListIds(int $limit = 0, string $cursor = ''): Response|ResponseInterface
    {
        $data = [];

        if ($limit) {
            $data['limit'] = $limit;
        }

        if ($cursor) {
            $data['cursor'] = $cursor;
        }

        return $this->api->postJson('cgi-bin/user/list_id', $data);
    }
}
