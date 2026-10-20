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

namespace Tuoluojiang\Open\services\synchro;

use App\Http\Service\Company\CompanyService;
use Tuoluojiang\Open\traits\VersionHeaderTrait;
use Tuoluojiang\Open\exceptions\ApiException;
use Tuoluojiang\Open\services\HttpService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class TokenService.
 */
abstract class TokenService extends HttpService
{
    use VersionHeaderTrait;

    /**
     * 登录接口.
     */
    public const COMPANY_LOGIN = '/api/v2/company/login';

    /**
     * @var mixed|string
     */
    protected string $apiHost = '';

    protected string $account = '';

    protected string $secret = '';

    /**
     * @var Cache
     */
    protected $cache;

    protected string $accessToken = '';

    protected string $cacheTokenPrefix = '_crm_oa';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if ($account = app()->get(CompanyService::class)->value(['id' => 1], 'uniqued')) {
            $this->account = $account;
        }

        $this->apiHost = env('API_HOST', 'https://manage.tuoluojiang.com');

        $this->secret = md5($this->account . $this->cacheTokenPrefix);
        return $this;
    }

    /**
     * 获取缓存token.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getToken()
    {
        $cache          = app()->cache;
        $accessTokenKey = md5($this->account . '_' . $this->secret . $this->cacheTokenPrefix);
        $cacheToken     = $cache->get($accessTokenKey);
        if (! $cacheToken) {
            $getToken = $this->getTokenFromServer();
            $cache->add($accessTokenKey, $getToken['token'], 300);
            $cacheToken = $getToken['token'];
        }
        $this->accessToken = $cacheToken;
        return $cacheToken;
    }

    /**
     * 从服务器获取token.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function getTokenFromServer()
    {
        $params = [
            'account'  => $this->account,
            'password' => $this->secret,
        ];

        return $this->httpRequest(self::COMPANY_LOGIN, $params, 'POST', false);
    }

    /**
     * 请求
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function httpRequest(string $url, array $data = [], string $method = 'POST', bool $isHeader = true)
    {
        $header = [];
        if ($isHeader) {
            $this->getToken();
            if (! $this->accessToken) {
                throw new ApiException('配置已更改token已失效');
            }
            $header = ['Authorization' => 'Bearer ' . $this->accessToken];
        }
        $header   = $this->getHeader($header);
        $response = collect();
        switch ($method) {
            case 'POST':
                $response = $this->setHeader($header)->postJSON($this->get($url), $data);
                break;
            case 'GET':
                $response = $this->setHeader($header)->getJSON($this->get($url), $data);
                break;
        }
        if ($response->get('status') === 200) {
            return $response->get('data', '') ?: $response->get('message', '');
        }
        throw new ApiException($response->get('message', '平台错误：发生异常，请稍后重试'));
    }

    /**
     * @return string
     */
    public function get(string $apiUrl = '')
    {
        return $this->apiHost . $apiUrl;
    }
}
