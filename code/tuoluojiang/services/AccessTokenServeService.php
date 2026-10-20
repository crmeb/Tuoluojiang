<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\services;

use Tuoluojiang\Open\exceptions\ApiException;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class AccessTokenServeService.
 */
class AccessTokenServeService extends HttpService
{
    /**
     * 登录接口.
     */
    public const USER_LOGIN = 'user/login';

    /**
     * 配置.
     * @var string
     */
    protected $account;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $cacheTokenPrefix = '_crmeb_plat';

    /**
     * @var string
     */
    protected $apiHost = 'http://sms.crmeb.net/api/';

    /**
     * AccessTokenServeService constructor.
     */
    public function __construct(string $account, string $secret)
    {
        $this->account = $account;
        $this->secret  = $secret;
        $this->cache   = app()->cache;
    }

    /**
     * 获取配置.
     * @return array
     */
    public function getConfig()
    {
        return [
            'account' => $this->account,
            'secret'  => $this->secret,
        ];
    }

    /**
     * 获取缓存token.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getToken()
    {
        $accessTokenKey = md5($this->account . '_' . $this->secret . $this->cacheTokenPrefix);
        $cacheToken     = $this->cache->get($accessTokenKey);
        if (! $cacheToken) {
            $getToken = $this->getTokenFromServer();
            $this->cache->add($accessTokenKey, $getToken['access_token'], 300);
            $cacheToken = $getToken['access_token'];
        }
        $this->accessToken = $cacheToken;

        return $cacheToken;
    }

    /**
     * 从服务器获取token.
     * @return mixed
     */
    public function getTokenFromServer()
    {
        $params = [
            'account' => $this->account,
            'secret'  => $this->secret,
        ];
        $response = $this->postJSON($this->get(self::USER_LOGIN), $params);
        if (! $response) {
            throw new ApiException(__('Failed to get token'));
        }
        if ($response->get('status') === 200) {
            return $response->get('data', '');
        }
        throw new ApiException($response->get('msg', ''));
    }

    /**
     * 请求
     * @return array|mixed
     */
    public function httpRequest(string $url, array $data = [], string $method = self::METHOD_POST, bool $isHeader = true)
    {
        $header = [];
        if ($isHeader) {
            if (! ($accessToken = $this->getToken())) {
                throw new ApiException(__('Configuration changed or token invalid'));
            }
            $header = ['Authorization' => 'Bearer-' . $accessToken];
        }

        $res = $this->setHeader($header)->parseJSON($this->get($url), $data, $method);
        if (! $res) {
            throw new ApiException(__('Platform error of No.1 communication：Exception occurred. Please try again later'));
        }
        $status = $res->get('status');
        $msg    = $res->get('msg');
        if ($status != 200) {
            throw new ApiException($msg ? '平台错误：' . $msg : '平台错误：发生异常，请稍后重试');
        }
        return $res->get('data', []);
    }

    /**
     * @return string
     */
    public function get(string $apiUrl = '')
    {
        return $this->apiHost . $apiUrl;
    }
}
