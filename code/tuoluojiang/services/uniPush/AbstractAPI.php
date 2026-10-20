<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\services\uniPush;

use Tuoluojiang\Open\exceptions\ApiException;
use Tuoluojiang\Open\services\HttpService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class AbstractAPI.
 */
class AbstractAPI
{
    public const BASE_URL = 'https://restapi.getui.com/v2/{$appId}';

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $appKey;

    /**
     * @var string
     */
    protected $masterSecret;

    /**
     * @var HttpService
     */
    protected $http;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * AbstractAPI constructor.
     */
    public function __construct(HttpService $service)
    {
        $this->http  = $service;
        $this->cache = Cache::store();
    }

    /**
     * 设置APPID.
     * @return $this
     */
    public function setAppId(string $appId)
    {
        $this->appId = $appId;
        return $this;
    }

    /**
     * 设置APPkey.
     * @return $this
     */
    public function setAppKey(string $appKey)
    {
        $this->appKey = $appKey;
        return $this;
    }

    /**
     * 设置masterSecret.
     * @return $this
     */
    public function setMasterSecret(string $masterSecret)
    {
        $this->masterSecret = $masterSecret;
        return $this;
    }

    /**
     * 以JSON形式发送post请求
     * @return Collection
     * @throws \Exception
     */
    public function parseJSON(string $url, array $data, array $header = [])
    {
        return $this->curl($url, $data, $header, 'post', true);
    }

    /**
     * post请求
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function parsePost(string $url, array $data)
    {
        return $this->http($url, $data);
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function parseGet(string $url, array $data = [])
    {
        return $this->http($url, $data, 'get');
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function http(string $url, array $data, string $method = 'post')
    {
        if (! $this->appId || ! $this->appKey || ! $this->masterSecret) {
            throw new ApiException('个推配置缺失，请检查APPID、APPKEY、MASTER_SECRET');
        }
        $token = $this->getToken(); // 本地
        $header = ['token:' . $token, 'content-type:application/json;charset=utf-8'];
        return $this->http->setHeader($header)->parseJsonCurl($this->url($url), json_encode($data), $method, true);
    }

    /**
     * 返回API地址
     * @return string
     */
    protected function url(string $path = '')
    {
        $baseUrl = $this->resolvBaseUrl();
        $base    = strstr($path, 'http') === false;
        return ($base ? $baseUrl : '') . ($path ? $base ? '/' . $path : $path : '');
    }

    /**
     * @return string|string[]
     */
    protected function resolvBaseUrl()
    {
        return str_replace('{$appId}', $this->appId, self::BASE_URL);
    }

    /**
     * 获取签名.
     * @return string
     */
    protected function getSign(int $msectime)
    {
        return hash('sha256', $this->appKey . $msectime . $this->masterSecret);
    }

    /**
     * 获取毫秒值
     * @return int
     */
    protected function getMsectime()
    {
        return now()->getTimestampMs();
    }

    /**
     * curl请求
     * @return Collection
     * @throws \Exception
     */
    protected function curl(string $url, array $data, array $header = [], ?string $method = null, bool $json = false)
    {
        $header   = $json ? array_merge(['content-type:application/json'], $header) : $header;
        $method   = $method ?: 'post';
        $response = $this->http->setHeader($header)->request($this->url($url), $method, $data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse JSON: :' . json_last_error_msg());
        }
        return collect($response);
    }

    /**
     * 获取token.
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function getToken()
    {
        $name  = 'UNI_PUSH_TOKEN_' . $this->appId;
        $token = $this->cache->get($name);
        if (! $token) {
            $msectime = $this->getMsectime();
            $header   = ['content-type:application/json;charset=utf-8'];
            $response = $this->http->setHeader($header)->parseJsonCurl($this->url('auth'), json_encode([
                'sign'      => $this->getSign($msectime),
                'timestamp' => $msectime,
                'appkey'    => $this->appKey,
            ]), isHeader: true);
            $data = $response->get('data', []);
            if (! isset($data['token'])) {
                throw new ApiException('获取token失败:' . $response->get('msg'));
            }
            if ($data['token']) {
                $this->cache->set($name, $data['token'], 3600);
                $token = $data['token'];
            }
        }
        return $token;
    }
}
