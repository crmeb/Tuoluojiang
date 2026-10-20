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

use Swoole\Coroutine\Http\Client;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 携程请求返回资源
 * Class SwooleResponse.
 * @email 136327134@qq.com
 * @date 2024/1/25
 */
class SwooleResponse implements ResponseInterface
{
    /**
     * @var Client
     */
    private $swooleResponse;

    /**
     * SwooleResponse constructor.
     */
    public function __construct(Client $swooleResponse)
    {
        $this->swooleResponse = $swooleResponse;
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    public function getContent(bool $throw = true): string
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->swooleResponse->getBody();
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    public function getStatusCode(): int
    {
        return $this->swooleResponse->getStatusCode();
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    public function getHeaders(bool $throw = true): array
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        $headers = [];
        foreach ($this->swooleResponse->getHeaders() as $name => $value) {
            $headers[strtolower($name)][] = $value;
        }
        return $headers;
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    public function toArray(bool $throw = true): array
    {
        $content = $this->getContent($throw);
        if ($content === '' && $throw) {
            throw new \InvalidArgumentException('The content is not valid JSON.');
        }

        return json_decode($content, true);
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    public function cancel(): void
    {
        $this->swooleResponse->close();
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/1/24
     */
    public function getInfo(?string $type = null): mixed
    {
        if ($type === null || $type === 'http_code') {
            return $this->getStatusCode();
        }

        $httpResponse = [
            'http_code'        => $this->getStatusCode(),
            'response_headers' => $this->swooleResponse->getHeaders(),
            'url'              => $this->swooleResponse->host,
        ];

        return $type ? $httpResponse[$type] ?? null : $httpResponse;
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    private function checkStatusCode()
    {
        $code = $this->getInfo('http_code');

        if (500 <= $code) {
            throw new ServerException($this);
        }

        if (400 <= $code) {
            throw new ClientException($this);
        }

        if (300 <= $code) {
            throw new RedirectionException($this);
        }
    }
}
