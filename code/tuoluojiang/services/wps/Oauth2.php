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

namespace Tuoluojiang\Open\services\wps;

use Tuoluojiang\Open\exceptions\HttpServiceExceptions;
use Tuoluojiang\Open\services\HttpService;
use Illuminate\Cache\TaggedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Oauth2
{
    /**
     * 请求地址
     */
    public const HTTP = 'https://openapi.wps.cn/oauthapi/';

    public const WPS_ACCESS_TOKEN_CACHE_NAME = 'WPS_ACCESS_TOKEN';

    public const WPS_REFRESH_TOKEN_CACHE_NAME = 'WPS_REFRESH_TOKEN';

    /**
     * 应用唯一标识.
     * @var string
     */
    protected $appid;

    /**
     * 应用密钥.
     * @var string
     */
    protected $appKey;

    /**
     * accessToken.
     * @var string
     */
    protected $accessToken;

    /**
     * refreshToken.
     * @var string
     */
    protected $refreshToken;

    /**
     * code.
     * @var string
     */
    protected $code;

    /**
     * @var TaggedCache
     */
    protected $refreshCache;

    /**
     * @var TaggedCache
     */
    protected $accessCache;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var HttpService
     */
    protected $http;

    /**
     * Oauth2 constructor.
     * @param null $request
     */
    public function __construct(Config $config, HttpService $service, $request = null)
    {
        $this->request      = $request ? app('request') : null;
        $this->appid        = $config->get('appid');
        $this->appKey       = $config->get('appKey');
        $this->accessCache  = Cache::tags(self::WPS_ACCESS_TOKEN_CACHE_NAME);
        $this->refreshCache = Cache::tags(self::WPS_REFRESH_TOKEN_CACHE_NAME);
        $this->http         = $service;
        Log::error('appid:' . $this->appid . '/appKey:' . $this->appKey);
    }

    /**
     * @return HttpService
     */
    public function http(array $header = [])
    {
        return $this->http->setHeader(['Content-type' => 'application/json'] + $header);
    }

    /**
     * 获取用户token信息.
     * @return array
     */
    public function getUserToken()
    {
        $code = $this->getCode();
        if (! $code) {
            throw new HttpServiceExceptions(__('Missing code'));
        }
        /** @var HttpService $service */
        $service = app()->get(HttpService::class);

        $response = $service->setHeader([
            'Content-type' => 'application/json',
        ])->getJSON(self::HTTP . 'v2/token', [
            'appid'  => $this->getAppid(),
            'appkey' => $this->getAppKey(),
            'code'   => $code,
        ]);

        if (! ($token = $response->get('token'))) {
            throw new HttpServiceExceptions(__('获取token失败'));
        }

        $this->setAccessToken($token['access_token'], $token['openid']);
        $this->setRefreshToken($token['refresh_token'], $token['openid']);
        return $token;
    }

    /**
     * 获取当前用户token.
     * @return mixed|string
     */
    public function getToken(string $openid)
    {
        $accessToken  = $this->getAccessToken($openid);
        $refreshToken = $this->getRefreshToken($openid);
        if (! $accessToken && ! $refreshToken) {
            throw new HttpServiceExceptions(__('accessToken获取失败'));
        }
        if (! $accessToken && $refreshToken) {
            /** @var HttpService $service */
            $service  = app()->get(HttpService::class);
            $response = $service->setHeader([
                'Content-type' => 'application/json',
            ])->getJSON(self::HTTP . 'v2/token/refresh', [
                'appid'         => $this->getAppid(),
                'appkey'        => $this->getAppKey(),
                'refresh_token' => $refreshToken,
            ]);

            if (! ($token = $response->get('token'))) {
                throw new HttpServiceExceptions(__('获取token失败'));
            }

            $this->setAccessToken($token['access_token'], $token['openid']);
            $this->setRefreshToken($token['refresh_token'], $token['openid']);
            $accessToken = $token['access_token'];
        }
        return $accessToken;
    }

    public function getAppid(): string
    {
        return $this->appid;
    }

    /**
     * 设置appid.
     */
    public function setAppid(string $appid)
    {
        $this->appid = $appid;
        return $this;
    }

    /**
     * 设置APPkey.
     */
    public function setAppKey(string $appKey)
    {
        $this->appKey = $appKey;
        return $this;
    }

    public function getAppKey(): string
    {
        return $this->appKey;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        if (! $this->code) {
            $this->code = $this->request->get('code');
        }
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 设置request.
     * @param mixed $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * 获取AccessToken.
     * @return mixed
     */
    public function getAccessToken(?string $openid = null)
    {
        if (! $this->accessToken) {
            $this->accessToken = $this->accessCache->get($openid);
        }
        return $this->accessToken;
    }

    /**
     * 获取RefreshToken.
     * @return mixed
     */
    public function getRefreshToken(?string $openid = null)
    {
        if (! $this->refreshToken) {
            $this->refreshToken = $this->refreshCache->get($openid);
        }
        return $this->refreshToken;
    }

    /**
     * 设置AccessToken.
     * @param mixed $accessToken
     * @return $this
     */
    public function setAccessToken(string $accessToken, string $openid)
    {
        if (! $this->accessCache->has($openid)) {
            $this->accessCache->add($openid, $accessToken, 86000);
        }
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * 设置RefreshToken.
     * @return $this
     */
    public function setRefreshToken(string $refreshToken, string $openid)
    {
        if ($this->refreshCache->has($openid)) {
            $this->refreshCache->add($openid, $refreshToken, 86400 * 15);
        }
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
