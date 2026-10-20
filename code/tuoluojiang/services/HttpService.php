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

namespace Tuoluojiang\Open\services;

use Tuoluojiang\Open\exceptions\HttpServiceExceptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class HttpService.
 * @method Collection getJSON($http, array $data = []) GET 请求
 * @method Collection postJSON($http, array $data = []) POST 请求
 * @method Collection putJSON($http, array $data = []) PUT 请求
 * @method Collection deleteJSON($http, array $data = []) DELETE 请求
 */
class HttpService
{
    public const METHOD_GET = 'get';

    public const METHOD_POST = 'post';

    public const METHOD_PUT = 'put';

    public const METHOD_DELETE = 'delete';

    /**
     * header头信息.
     * @var array
     */
    protected $header = [];

    /**
     * api接口地址
     * @var string
     */
    protected $apiHttp;

    /**
     * 超时秒数.
     * @var int
     */
    protected $timeout = 10;

    /**
     * 是否开启debug.
     * @var bool
     */
    protected $debug = true;

    /**
     * HttpService constructor.
     */
    public function __construct(array $config = [])
    {
        $this->apiHttp = $config['apiHttp'] ?? null;
        $this->timeout = $config['timeout'] ?? $this->timeout;
        $this->debug   = $config['debug'] ?? env('APP_DEBUG', false);
        if (isset($config['header']) && is_array($config['header'])) {
            $this->header = $config['header'];
        }
    }

    /**
     * @param mixed $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $method     = [self::METHOD_POST, self::METHOD_GET, self::METHOD_DELETE, self::METHOD_PUT];
        $actionName = str_contains($name, 'JSON') ? str_replace('JSON', '', $name) : $name;
        if (in_array($actionName, $method)) {
            return $this->parseJSON($arguments[0], $arguments[1] ?? [], $actionName);
        }
        throw new HttpServiceExceptions(__('The method does not exist :class->:method()', ['class' => __CLASS__, 'method' => $name]));
    }

    /**
     * 设置请求地址
     * @return $this
     */
    public function setApiHttp(string $http): static
    {
        $this->apiHttp = $http;
        return $this;
    }

    /**
     * 设置标题头.
     * @return $this
     */
    public function setHeader(array $header): static
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 发起请求
     * @param mixed $http
     */
    public function parseJSON($http, array $data = [], ?string $method = null): Collection
    {
        if (is_array($http)) {
            $data = $http;
            $http = $this->apiHttp;
        }

        if (! $http) {
            throw new HttpServiceExceptions(__('The request address cannot be empty'));
        }

        [$httpCode, $content, $header] = $this->request($http, $method ?: self::METHOD_POST, $data);
        if (! $httpCode) {
            throw new HttpServiceExceptions(__('request was aborted'));
        }

        $response = json_decode($content, true, 512, JSON_BIGINT_AS_STRING);

        //        $this->debugLog('API response decoded:', ['httpCode' => $httpCode, 'content' => $response, 'header' => $header]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpServiceExceptions(__('Failed to parse JSON: :error', ['error' => json_last_error_msg()]));
        }
        if (empty($response['data'])) {
            $response['data'] = [];
        }
        if (! empty($header['Token'][0])) {
            $response['token'] = $header['Token'][0];
        }
        return collect($response);
    }

    /**
     * 发起请求
     * @param array|mixed $data
     */
    public function parseJsonCurl(mixed $http, mixed $data = [], ?string $method = null, bool $isHeader = false, int $timeout = 15): Collection
    {
        if (is_array($http)) {
            $data = $http;
            $http = $this->apiHttp;
        }
        if (! $http) {
            throw new HttpServiceExceptions(__('The request address cannot be empty'));
        }
        [$httpCode, $content, $header] = $this->requests($http, $method ?: self::METHOD_POST, $data, $isHeader, $timeout);
        if (! $httpCode) {
            throw new HttpServiceExceptions(__('request was aborted'));
        }

        $response = json_decode($content, true, 512, JSON_BIGINT_AS_STRING);

        //        $this->debugLog('API response decoded:', ['httpCode' => $httpCode, 'content' => $response, 'header' => $header]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpServiceExceptions(__('Failed to parse JSON: :error', ['error' => json_last_error_msg()]));
        }
        if (empty($response['data'])) {
            $response['data'] = [];
        }
        return collect($response);
    }

    /**
     * curl 请求
     */
    public function request(string $url, string $method = self::METHOD_GET, array $data = []): array
    {
        $response = Http::timeout($this->timeout)->withHeaders($this->header)->{$method}($url, $data)->throw(function ($e) {
            throw new HttpServiceExceptions($e->getMessage());
        });

        $this->reset();

        return [$response->status() == 200, $response->body(), $response->headers()];
    }

    /**
     * curl 请求
     * @param mixed $url
     */
    public function requests(string $url, string $method = 'get', array|string $data = [], bool $header = false, int $timeout = 15, bool $code = false): array|string
    {
        $curl   = curl_init($url);
        $method = strtoupper($method);
        // 请求方式
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        // post请求
        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        // 超时时间
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        // 设置header头
        if ($header !== false) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
        }

        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        // 返回抓取数据
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // 输出header头信息
        curl_setopt($curl, CURLOPT_HEADER, true);
        // TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        // https请求
        if (strpos('$' . $url, 'https://') == 1) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        //        self::$curlError = curl_error($curl);

        [$content, $status] = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];
        if ($content === false) {
            $content = '';
        }
        //        self::$status    = $status;
        $header  = trim(substr($content, 0, $status['header_size']));
        $content = trim(substr($content, $status['header_size']));
        if ($code) {
            return $content;
        }
        //        return (intval($status["http_code"]) === 200) ? $content : false;
        return [$status['http_code'], $content, $header];
    }

    /**
     * 模拟流式请求
     * @return false|mixed
     * @throws InvalidArgumentException
     */
    public function stream(string $url, string $method = 'get', mixed $data = [], ?callable $stream = null, bool $header = false, ?string $uuid = null, ?string $tagName = null, int $timeout = 120): mixed
    {
        $curl   = curl_init($url);
        $method = strtoupper($method);
        // 请求方式
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        // post请求
        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, min(15, $timeout));
        // 设置header头
        if ($header !== false) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
        }

        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        // 输出header头信息
        curl_setopt($curl, CURLOPT_HEADER, false);
        // TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        // https请求
        if (strpos('$' . $url, 'https://') == 1) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        //        self::$curlError = curl_error($curl);

        // 设置为流式传输，不直接返回响应数据
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        if (defined('CURLOPT_TCP_NODELAY')) {
            curl_setopt($curl, CURLOPT_TCP_NODELAY, true);
        }
        if (defined('CURLOPT_BUFFERSIZE')) {
            curl_setopt($curl, CURLOPT_BUFFERSIZE, 256);
        }
        // 设置回调函数，用于处理每次接收到的数据块
        $sseBuffer = '';
        $rawBuffer = '';
        $chunk     = 0;
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$sseBuffer, &$rawBuffer, &$chunk, $stream, $tagName, $uuid) {
            $length = strlen($data);

            $data = str_replace(": keep-alive\n\n", '', $data);
            $data = str_replace(["\r\n", "\r"], "\n", $data);
            $rawBuffer .= $data;
            $sseBuffer .= $data;

            while (($pos = strpos($sseBuffer, "\n\n")) !== false) {
                $event = substr($sseBuffer, 0, $pos + 2);
                $sseBuffer = substr($sseBuffer, $pos + 2);
                if (trim($event) !== '') {
                    $this->runFun($stream, $event);
                }
            }

            ++$chunk;
            // 更频繁探测中断，降低长流式下的停止延迟.
            if ($uuid && $tagName && $chunk % 4 === 0 && $this->streamShouldStop($uuid, $tagName)) {
                $this->runFun($stream, "data: [DONE]\n\n");
                $this->forgetStreamStop($uuid, $tagName);

                return 0;
            }

            return $length; // 必须返回数据长度，否则传输中断
        });

        if (curl_exec($curl) === false) {
            curl_close($curl);

            // 超时或中断时保留已接收的 SSE 分片，供上层按 reasoning/content 落库.
            return $rawBuffer !== '' ? $rawBuffer : false;
        }

        curl_close($curl);

        return $rawBuffer;
    }

    private function streamShouldStop(string $uuid, string $tagName): bool
    {
        try {
            return Cache::tags([$tagName])->get($uuid) === 'stop';
        } catch (\Throwable) {
            return Cache::get($uuid) === 'stop';
        }
    }

    private function forgetStreamStop(string $uuid, string $tagName): void
    {
        try {
            Cache::tags([$tagName])->delete($uuid);
        } catch (\Throwable) {
            Cache::forget($uuid);
        }
    }

    public function runFun(callable $stream, string $data): void
    {
        try {
            if (trim($data)) {
                $stream && $stream($data);
            }
        } catch (\Throwable $e) {
            Log::error('流式请求回调异常：' . $e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
        }
    }

    /**
     * 记录日志.
     */
    protected function debugLog(string $message, array $contents = []): void
    {
        $this->debug && Log::debug($message, $contents);
    }

    /**
     * 重置.
     */
    protected function reset(): static
    {
        $this->header  = [];
        $this->timeout = 10;
        $this->apiHttp = null;
        $this->debug   = true;
        return $this;
    }
}
