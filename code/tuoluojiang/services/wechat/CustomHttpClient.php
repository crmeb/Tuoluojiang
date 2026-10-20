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
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 携程curl请求
 * Class CustomHttpClient.
 * @email 136327134@qq.com
 * @date 2024/1/24
 */
class CustomHttpClient implements HttpClientInterface
{
    protected string $baseUrl = '';

    protected int $timeout = 3;

    protected array $httpConfig = [];

    /**
     * CustomHttpClient constructor.
     */
    public function __construct(string $baseUrl, array $httpConfig = [])
    {
        $this->baseUrl    = $baseUrl;
        $this->httpConfig = $httpConfig;
    }

    /**
     * 发起请求
     * @email 136327134@qq.com
     * @date 2024/1/24
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // 合并传入的选项和默认选项
        $options = array_merge(self::OPTIONS_DEFAULTS, $this->httpConfig, $options);

        $ssl     = str_contains($this->baseUrl, 'https://');
        $baseUrl = str_replace(['https://', 'http://', '/'], '', $this->baseUrl);

        // 使用 Swoole Client 完成请求
        $client = new Client($baseUrl, $ssl ? 443 : 80, $ssl);

        $client->set([
            'timeout'       => ! empty($options['timeout']) ? $options['timeout'] : $this->timeout,
            'ssl_cert_file' => $options['cert'] ?? null,
            'ssl_key_file'  => $options['ssl_key'] ?? null,
        ]);

        $client->setMethod($method);
        $headers = [];
        // 设置请求头
        foreach ($options['headers'] as $key => $value) {
            if (is_string($key)) {
                $values = [];
                if (is_array($value)) {
                    foreach ($value as $item) {
                        [$type, $val] = strstr($item, ':') !== false ? explode(':', $item) : [null, null];
                        if ($type && $val) {
                            $values[] = $val;
                        }
                    }
                    $headers[$key] = implode(',', $values);
                } else {
                    $values[]      = $value;
                    $headers[$key] = implode(',', $values);
                }
            } else {
                [$type, $val]   = strstr($value, ':') !== false ? explode(':', $value) : [null, null];
                $values[]       = $val;
                $headers[$type] = implode(',', $values);
            }
        }

        $client->setHeaders($headers);

        if (! empty($options['query'])) {
            $url = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($options['query']);
        }
        if (! empty($options['json'])) {
            $client->setData($options['json']);
        } elseif (! empty($options['body'])) {
            $client->setData($options['body']);
        }

        // 发起请求
        $client->execute('/' . $url);

        // 创建响应对象
        $response = new SwooleResponse($client);

        // 关闭客户端连接
        $client->close();

        return $response;
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        // 创建 ResponseStreamInterface 对象并进行流式处理
        return new AsyncContext($responses, $this->httpClient, $timeout);
        // 返回流对象
    }

    /**
     * @return $this
     * @email 136327134@qq.com
     * @date 2024/1/25
     */
    public function withOptions(array $options): static
    {
        $this->httpConfig = array_merge(self::OPTIONS_DEFAULTS, $options);
        if (! empty($this->httpConfig['base_uri']) && $this->httpConfig['base_uri'] !== $this->baseUrl) {
            $this->baseUrl = $this->httpConfig['base_uri'];
        }
        return $this;
    }
}
