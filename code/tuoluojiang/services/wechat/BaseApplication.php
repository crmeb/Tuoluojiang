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

namespace Tuoluojiang\Open\services\wechat;

use Tuoluojiang\Open\services\wechat\contract\BaseApplicationInterface;
use EasyWeChat\MiniApp\Application as MiniAppApplication;
use EasyWeChat\OfficialAccount\Application as OfficialAccountApplication;
use EasyWeChat\Pay\Application as PayApplication;
use EasyWeChat\Work\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Class BaseApplication.
 */
abstract class BaseApplication implements BaseApplicationInterface
{
    // app端
    public const APP = 'app';

    // h5端、公众端
    public const WEB = 'web';

    // 小程序端
    public const MINI = 'mini';

    // 开发平台
    public const OPEN = 'open';

    // pc端
    public const PC = 'pc';

    public const BASE_URL = 'https://api.weixin.qq.com';

    /**
     * 访问端.
     */
    protected string $accessEnd = '';

    protected string $name;

    protected static array $property = [];

    protected string $pushMessageHandler;

    /**
     * Debug.
     */
    protected bool $debug = true;

    /**
     * @email 136327134@qq.com
     * @date 2023/9/14
     * @param mixed $name
     * @param mixed $arguments
     * @return mixed|object
     */
    public function __call($name, $arguments)
    {
        $className = $this->getClientClassName($name);
        if (class_exists($className)) {
            $client = $this->application()->getClient();
            return new $className($client);
        }
        throw new WechatException('请求类不存在');
    }

    /**
     * 设置消息处理类.
     * @return $this
     */
    public function setPushMessageHandler(string $handler): static
    {
        $this->pushMessageHandler = $handler;
        return $this;
    }

    /**
     * 设置访问端.
     * @return $this
     */
    public function setAccessEnd(string $accessEnd)
    {
        if (in_array($accessEnd, [self::APP, self::WEB, self::MINI])) {
            $this->accessEnd = $accessEnd;
        }
        return $this;
    }

    /**
     * 获取请求驱动.
     * @return string
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    public function getClientClassName(string $name)
    {
        $class = Str::studly($name) . 'Client';
        return '\Tuoluojiang\Open\services\wechat\client\\' . $this->name . '\\' . $class;
    }

    /**
     * 记录错误日志.
     */
    protected function error(\Throwable $e)
    {
        $this->debug && Log::info($e->getMessage() . '|' . $e->getLine() . '|' . $e->getFile() . '|' . json_encode($e->getTrace()));
    }

    /**
     * 请求日志.
     * @param mixed $request
     * @param mixed $response
     */
    protected function logger(string $message, $request, $response)
    {
        $debug = $this->debug;

        if ($debug) {
            if (is_object($response) && method_exists($response, 'toArray')) {
                $response = $response->toArray();
            }

            Log::info($message . '|' . json_encode($request) . '|' . json_encode($response));
        }
    }

    /**
     * 设置request.
     * @email 136327134@qq.com
     * @date 2023/9/14
     */
    protected function setRequest(Application|MiniAppApplication|OfficialAccountApplication|PayApplication $application)
    {
        $request                 = request();
        $symfonyRequest          = new SymfonyRequest($request->query(), $request->post(), [], $request->cookie(), [], $request->server(), $request->getContent());
        $symfonyRequest->headers = new HeaderBag($request->header());
        $application->setRequestFromSymfonyRequest($symfonyRequest);
    }

    /**
     * 设置日志.
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    protected function setLogger(MiniAppApplication|OfficialAccountApplication $loggerAwareTrait)
    {
        $loggerAwareTrait->setLogger(app('log'));
    }

    /**
     * 设置缓存.
     * @param $withCache Application|OfficialAccountApplication|MiniAppApplication|
     */
    protected function setCache($withCache)
    {
        $withCache->setCache(app('cache')->store());
    }

    /**
     * 设置http请求
     * @param $application Application|OfficialAccountApplication|MiniAppApplication|PayApplication
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    protected function setHttpClient($application, string $baseUrl = self::BASE_URL)
    {
        $application->setHttpClient(new CustomHttpClient($baseUrl));
    }
}
