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
 * 获取用户信息.
 */
class AuthClient extends BaseClient
{
    /**
     * 获取用户信息.
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function getUserinfo(string $code)
    {
        $userInfo = $this->api->get('cgi-bin/auth/getuserinfo', ['code' => $code])->toArray();
        if ($userInfo['errcode'] !== 0) {
            throw new WechatException($userInfo['errmsg']);
        }
        return $userInfo;
    }
}
