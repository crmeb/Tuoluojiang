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
 * 朋友圈
 * Class MomentClient.
 * @email 136327134@qq.com
 * @date 2023/9/15
 */
class MomentClient extends BaseClient
{
    /**
     * 创建发表任务
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function createTask(array $param): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/add_moment_task', $param);
    }

    /**
     * 停止发表企业朋友圈.
     * @throws TransportExceptionInterface
     */
    public function cancelTask(string $momentId): Response|ResponseInterface
    {
        $param = [
            'moment_id' => $momentId,
        ];
        return $this->api->postJson('cgi-bin/externalcontact/cancel_moment_task', $param);
    }

    /**
     * 获取任务创建结果.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getTask(string $jobId): Response|ResponseInterface
    {
        return $this->api->get('cgi-bin/externalcontact/get_moment_task_result', ['jobid' => $jobId]);
    }

    /**
     * 获取企业全部的发表列表.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function list(array $params): Response|ResponseInterface
    {
        return $this->api->postJson('cgi-bin/externalcontact/get_moment_list', $params);
    }

    /**
     * 获取客户朋友圈企业发表的列表.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getTasks(string $momentId, string $cursor = '', int $limit = 500): Response|ResponseInterface
    {
        $params = [
            'moment_id' => $momentId,
            'cursor'    => $cursor,
            'limit'     => $limit,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/get_moment_task', $params);
    }

    /**
     * 获取客户朋友圈发表时选择的可见范围.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getCustomers(string $momentId, string $userId, string $cursor, int $limit): Response|ResponseInterface
    {
        $params = [
            'moment_id' => $momentId,
            'userid'    => $userId,
            'cursor'    => $cursor,
            'limit'     => $limit,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/get_moment_customer_list', $params);
    }

    /**
     * 获取客户朋友圈发表后的可见客户列表.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getSendResult(string $momentId, string $userId, string $cursor, int $limit): Response|ResponseInterface
    {
        $params = [
            'moment_id' => $momentId,
            'userid'    => $userId,
            'cursor'    => $cursor,
            'limit'     => $limit,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/get_moment_send_result', $params);
    }

    /**
     * 获取客户朋友圈的互动数据.
     * @throws TransportExceptionInterface
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getComments(string $momentId, string $userId): Response|ResponseInterface
    {
        $params = [
            'moment_id' => $momentId,
            'userid'    => $userId,
        ];

        return $this->api->postJson('cgi-bin/externalcontact/get_moment_comments', $params);
    }
}
