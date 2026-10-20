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

namespace Tuoluojiang\Open\services\upload\extend\jdoss;

use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\upload\BaseClient;
use Tuoluojiang\Open\services\upload\extend\cos\SimpleXMLElement;

/**
 *  京东云上传.
 */
class Client extends BaseClient
{
    // 默认地域
    public const DEFAULT_REGION = 'cn-north-1';

    /**
     * AK.
     */
    protected string $accessKeyId;

    /**
     * SK.
     */
    protected string $secretKey;

    /**
     * 桶名.
     */
    protected string $bucketName;

    /**
     * 地区.
     */
    protected string $region;

    /**
     * @var mixed|string
     */
    protected string $uploadUrl;

    protected string $baseUrl = 's3.<REGION>.jdcloud-oss.com';

    /**
     * Client constructor.
     */
    public function __construct(array $config = [])
    {
        $this->accessKeyId = $config['accessKey'] ?? '';
        $this->secretKey   = $config['secretKey'] ?? '';
        $this->bucketName  = $config['bucket'] ?? '';
        $this->region      = $config['region'] ?? self::DEFAULT_REGION;
        $this->uploadUrl   = $config['uploadUrl'] ?? '';
    }

    /**
     * 检测桶，不存在返回true.
     * @return array|bool|SimpleXMLElement
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function headBucket(string $bucket, string $region = '')
    {
        $url = $this->getRequestUrl($bucket, $region);

        $header = [
            'Host' => $url,
        ];

        return $this->request('https://' . $url, 'head', [], $header);
    }

    /**
     * 获取桶列表.
     * @return array|SimpleXMLElement
     * @email 136327134@qq.com
     * @date 2023/5/18
     */
    public function listBuckets()
    {
        $url    = $this->getRequestUrl();
        $header = [
            'Host' => $url,
            'Date' => gmdate('D, d M Y H:i:s \G\M\T'),
        ];

        return $this->request('https://' . $url . '/', 'GET', [], $header);
    }

    public function createBucket($name, $region, $acl)
    {
        $url    = $this->getRequestUrl($name, $region);
        $header = [
            'Host'      => $url,
            'Date'      => gmdate('D, d M Y H:i:s \G\M\T'),
            'name'      => $name,
            'x-amz-acl' => $acl,
        ];

        return $this->request('https://' . $url . '/', 'PUT', [], $header);
    }

    public function getRegion()
    {
        return [
            [
                'value' => 'cn-north-1',
                'label' => '华北-北京',
            ],
            [
                'value' => 'cn-south-1',
                'label' => '华南-广州',
            ],
            [
                'value' => 'cn-east-2',
                'label' => '华东-上海',
            ],
            [
                'value' => 'cn-east-1',
                'label' => '华东-宿迁',
            ],
        ];
    }

    /**
     * 获取请求域名.
     * @return string
     * @email 136327134@qq.com
     * @date 2023/5/18
     */
    protected function getRequestUrl(string $bucket = '', string $region = self::DEFAULT_REGION)
    {
        if (! $this->accessKeyId) {
            throw new UploadException('请传入SecretId');
        }
        if (! $this->secretKey) {
            throw new UploadException('请传入SecretKey');
        }

        return ($bucket ? $bucket . '.' : '') . 's3.' . $region . '.jdcloud-oss.com';
    }

    /**
     * 发起请求
     * @return array|SimpleXMLElement
     * @email 136327134@qq.com
     * @date 2023/5/18
     */
    protected function request(string $url, string $method, array $data = [], array $clientHeader = [], int $timeout = 10)
    {
        $canonicalUri         = '/';
        $canonicalQueryString = '';
        $canonicalHeaders     = '';
        $signedHeaders        = '';
        $payload              = '';

        $urlAttr = pathinfo($url);
        if ($urlAttr['dirname'] !== 'https:') {
            $urlParse = parse_url($urlAttr['dirname'] ?? '');
            if (isset($urlParse['path'])) {
                $canonicalUri .= substr($urlParse['path'], 1) . '/';
            }
            if (isset($urlAttr['basename'])) {
                $canonicalUri .= $urlAttr['basename'];
            }
            if (! ($pos = strripos($canonicalUri, '/')) || strlen($canonicalUri) - 1 !== $pos) {
                $canonicalUri .= '/';
            }
        }

        if (! empty($data['query'])) {
            $query = $payload = $data['query'];
            ksort($query);
            $queryAttr = [];
            foreach ($query as $key => $item) {
                $queryAttr[urlencode($key)] = urlencode($item);
            }
            if ($queryAttr) {
                $canonicalQueryString = implode('&', $queryAttr);
            }
        } elseif (! empty($data['body'])) {
            $payload = $data['body'];
        } elseif (! empty($data['json'])) {
            $payload = json_encode($data['json']);
        }

        if ($clientHeader) {
            $canonicalHeadersAtrr = $signedHeadersAttr = [];
            ksort($clientHeader);
            foreach ($clientHeader as $key => $item) {
                $canonicalHeadersAtrr[] = strtolower($key) . ':' . trim($item);
                $signedHeadersAttr[]    = strtolower($key);
            }
            if ($canonicalHeadersAtrr) {
                $canonicalHeaders = implode("\n", $canonicalHeadersAtrr);
                $signedHeaders    = implode(';', $signedHeadersAttr);
            }
        }

        $clientHeader['Authorization'] = $this->generateAwsSignatureV4(
            $data['region'] ?? self::DEFAULT_REGION,
            $method,
            $canonicalUri,
            $canonicalQueryString,
            $canonicalHeaders,
            $signedHeaders,
            $payload
        );

        return $this->requestClient($url, $method, $data, $clientHeader, $timeout);
    }

    /**
     * 生成签名.
     * @return string
     * @email 136327134@qq.com
     * @date 2023/5/18
     */
    protected function generateAwsSignatureV4(string $region, string $httpMethod, string $canonicalUri, string $canonicalQueryString, string $canonicalHeaders, string $signedHeaders, $payload, string $service = 's3')
    {
        $algorithm = 'AWS4-HMAC-SHA256';
        $t         = new \DateTime('UTC');
        $amzDate   = $t->format('Ymd\THis\Z');
        $dateStamp = $t->format('Ymd');

        $canonicalRequest = $httpMethod . "\n" . $canonicalUri . "\n" . $canonicalQueryString . "\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . hash('sha256', $payload);
        $credentialScope  = $dateStamp . '/' . $region . '/' . $service . '/aws4_request';
        $stringToSign = $algorithm . "\n" . $amzDate . "\n" . $credentialScope . "\n" . hash('sha256', $canonicalRequest);
        $signingKey   = hash_hmac(
            'sha256',
            'aws4_request',
            hash_hmac(
                'sha256',
                $service,
                hash_hmac(
                    'sha256',
                    $region,
                    hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true),
                    true
                ),
                true
            ),
            true
        );
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return $algorithm . ' Credential=' . $this->accessKeyId . '/' . $credentialScope . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;
    }
}
