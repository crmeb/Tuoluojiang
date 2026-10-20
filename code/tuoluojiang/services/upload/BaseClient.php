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

namespace Tuoluojiang\Open\services\upload;

/**
 *  基础请求
 */
abstract class BaseClient
{
    /**
     * 是否解析为xml.
     */
    protected bool $isXml = true;

    protected array $curlFn = [];

    /**
     * @return $this
     */
    public function middleware(callable $curlFn)
    {
        $this->curlFn[] = $curlFn;
        return $this;
    }

    /**
     * 发起请求
     * @return array|mixed|SimpleXMLElement
     */
    protected function requestClient(string $url, string $method, array $data = [], array $clientHeader = [], int $timeout = 10)
    {
        $headers = [];
        foreach ($clientHeader as $key => $item) {
            $headers[] = $key . ':' . $item;
        }
        $curl = curl_init($url);
        // 请求方式
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        // post请求
        if (! empty($data['body'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data['body']);
        } elseif (! empty($data['json'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data['json']));
        } else {
            $curlFn = $this->curlFn;
            foreach ($curlFn as $item) {
                if ($item instanceof \Closure) {
                    $curlFn($curl);
                }
            }
        }
        // 超时时间
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        // 设置header头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

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
        [$content, $status] = [curl_exec($curl), curl_getinfo($curl)];
        $content            = trim(substr($content, $status['header_size']));
        if ($this->isXml) {
            return XML::parse($content);
        }
        return json_decode($content, true);
    }
}
