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

namespace Tuoluojiang\Open\services\upload\extend\cos;

use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\upload\XML;

class Client
{
    protected string $accessKey;

    protected string $secretKey;

    protected string $appid;

    /**
     * @var mixed|string
     */
    protected string $bucket;

    /**
     * @var mixed|string
     */
    protected string $region;

    /**
     * @var mixed|string
     */
    protected string $uploadUrl;

    protected string $action = '';

    protected array $response = ['content' => null, 'code' => 200, 'header' => []];

    protected array $request = ['header' => [], 'body' => [], 'host' => ''];

    protected string $cosacl = 'public-read';

    /**
     * Client constructor.
     */
    public function __construct(array $config)
    {
        $this->accessKey = $config['accessKey'] ?? '';
        $this->secretKey = $config['secretKey'] ?? '';
        $this->appid     = $config['appid'] ?? '';
        $this->bucket    = $config['bucket'] ?? '';
        $this->region    = $config['region'] ?? 'ap-chengdu';
        $this->uploadUrl = $config['uploadUrl'] ?? '';
    }

    /**
     * 获取实际请求
     * @return array
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function getResponse()
    {
        $response = $this->response;

        $this->response = ['content' => null, 'http_code' => 200, 'header' => []];

        return $response;
    }

    /**
     * @return array
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function getRequest()
    {
        $request = $this->request;

        $this->request = ['header' => [], 'body' => [], 'host' => ''];

        return $request;
    }

    /**
     * 上传文件.
     * @return string[]
     * @email 136327134@qq.com
     * @date 2022/9/29
     */
    public function putObject(string $key, $body, array $header = [])
    {
        $this->checkOptions();

        $url = $this->makeUpUrl();

        $imageUrl = ($this->ssl() ? 'https://' : 'http://') . $url . '/' . $key;
        $res      = $this->request($imageUrl, 'PUT', ['body' => $body], array_merge([
            'Content-Type' => 'image/jpeg',
            'x-cos-acl'    => $this->cosacl,
            'Content-MD5'  => base64_encode(md5($body, true)),
            'Host'         => $url,
        ], $header));

        if ($res && ! empty($res['Message'])) {
            throw new UploadException($res['Message']);
        }

        return [
            'name' => $key,
            'path' => $imageUrl,
        ];
    }

    /**
     * 删除文件.
     * @return array|false
     * @email 136327134@qq.com
     * @date 2022/10/19
     */
    public function deleteObject(string $bucket, string $key)
    {
        $url = $this->getRequestHost($bucket);

        $header = [
            'Host' => $url,
        ];

        $res = $this->request('https://' . $url . '/' . $key, 'delete', [], $header);

        if ($res && ! empty($res['Message'])) {
            throw new UploadException($res['Message']);
        }

        return $res;
    }

    /**
     * 获取桶列表.
     * @return array|false|\SimpleXMLElement|string
     * @email 136327134@qq.com
     * @date 2022/10/19
     */
    public function listBuckets()
    {
        $url = 'service.cos.myqcloud.com';

        $header = [
            'Host' => $url,
        ];

        $res = $this->request('https://' . $url . '/', 'get', [], $header);

        if ($res && ! empty($res['Message'])) {
            throw new UploadException($res['Message']);
        }

        return $res;
    }

    /**
     * 检测桶，不存在返回true.
     * @return bool
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function headBucket(string $bucket, string $region = '')
    {
        $url = $this->getRequestHost($bucket, $region);

        $header = [
            'Host' => $url,
        ];

        $this->request('https://' . $url, 'head', [], $header);

        $response = $this->getResponse();

        return $response['code'] == 404;
    }

    /**
     * 创建桶.
     * @return array|false|\SimpleXMLElement|string
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function createBucket(string $bucket, string $region = '', string $acl = 'public-read')
    {
        return $this->noBodyRequest('put', $bucket, $region, $acl);
    }

    /**
     * 设置跨域
     * @return string
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function putBucketCors(string $bucket, array $data, string $region = '')
    {
        $url = $this->getRequestHost($bucket, $region);

        $xml = $this->xmlBuild($data, 'CORSConfiguration', 'CORSRule');

        $header = [
            'Host'           => $url,
            'Content-Type'   => 'application/xml',
            'Content-Length' => strlen($xml),
            'Content-MD5'    => base64_encode(md5($xml, true)),
        ];

        $res = $this->request('https://' . $url . '/?cors', 'put', ['xml' => $xml], $header);

        if ($res && ! empty($res['Message'])) {
            throw new UploadException($res['Message']);
        }

        return $res;
    }

    /**
     * 删除.
     * @return array|false|\SimpleXMLElement|string
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function deleteBucket(string $name, string $region = '')
    {
        return $this->noBodyRequest('delete', $name, $region);
    }

    /**
     * 获取桶下的.
     * @return array|false|\SimpleXMLElement|string
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function getBucketDomain(string $name, string $region = '')
    {
        $this->action = 'domain';
        return $this->noBodyRequest('get', $name, $region);
    }

    /**
     * 绑定域名.
     * @return array|false|\SimpleXMLElement|string
     * @email 136327134@qq.com
     * @date 2022/10/19
     */
    public function putBucketDomain(string $bucket, string $region, array $data)
    {
        $url = $this->getRequestHost($bucket, $region);

        $xml = $this->xmlBuild($data, 'DomainConfiguration', 'DomainRule');

        $header = [
            'Host'           => $url,
            'Content-Type'   => 'application/xml',
            'Content-Length' => strlen($xml),
            'Content-MD5'    => base64_encode(md5($xml, true)),
        ];

        $res = $this->request('https://' . $url . '/?domain', 'put', ['xml' => $xml], $header);

        if ($res && ! empty($res['Message'])) {
            throw new UploadException($res['Message']);
        }

        return $res;
    }

    /**
     * @return array|false|\SimpleXMLElement|string
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    public function noBodyRequest(string $method, string $bucket, string $region = '', ?string $acl = null, bool $isExc = true)
    {
        $url = $this->getRequestHost($bucket, $region);

        $header = [
            'Host' => $url,
        ];

        if ($acl) {
            $header['x-cos-acl'] = $acl;
        }

        if (in_array($method, ['put', 'post'])) {
            $header['Content-Length'] = 0;
        }

        $res          = $this->request('https://' . $url . '/' . ($this->action ? '?' . $this->action : ''), $method, [], $header);
        $this->action = '';

        if ($isExc) {
            if ($res && ! empty($res['Message'])) {
                throw new UploadException($res['Message']);
            }
        }

        return $res;
    }

    /**
     * 发起请求
     * @return array|false|\SimpleXMLElement|string
     * @email 136327134@qq.com
     * @date 2022/9/29
     */
    public function request(string $url, string $method, array $data, array $header = [], int $timeout = 5)
    {
        $this->request['body'] = $data;
        $this->request['host'] = $url;

        $urlAttr = parse_url($url);
        $curl    = curl_init($url);
        $method  = strtoupper($method);
        // 请求方式
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        // 超时时间
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        // 设置header头

        $header = array_merge($header, $this->getSign($url, $method, $urlAttr['path'] ?? '', [], $header));

        $this->request['header'] = $header;

        $clientHeader = [];
        foreach ($header as $key => $item) {
            $clientHeader[] = $key . ':' . $item;
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $clientHeader);

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

        // post请求
        if ($method == 'PUT' && ! empty($data['body'])) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            // 注意这里的'file'是上传地址指定的key名
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data['body']);
        }

        if (! empty($data['xml'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data['xml']);
        }

        [$content, $status] = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];

        $content = trim(substr($content, $status['header_size']));

        $this->response['content'] = $content;
        $this->response['code']    = $status['http_code'];
        $this->response['header']  = $status;

        $res = XML::parse($content);
        if ($res) {
            return $res;
        }
        return (intval($status['http_code']) === 200) ? $content : false;
    }

    /**
     * 获取签名.
     * @return array
     * @email 136327134@qq.com
     * @date 2022/9/27
     */
    public function getSign(string $url, string $method, string $urlPath, array $query = [], array $headers = [])
    {
        return (new Signature($this->accessKey, $this->secretKey, ['signHost' => $url]))->signRequest($method, $urlPath, $query, $headers);
    }

    /**
     * 拼接请求地址
     * @return string
     * @email 136327134@qq.com
     * @date 2022/9/29
     */
    protected function makeUpUrl()
    {
        return $this->bucket . '.cos.' . $this->region . '.myqcloud.com';
    }

    /**
     * @return bool
     * @email 136327134@qq.com
     * @date 2022/9/29
     */
    protected function ssl()
    {
        return strstr($this->uploadUrl, 'https://') !== false;
    }

    /**
     * 检查参数.
     * @email 136327134@qq.com
     * @date 2022/9/29
     */
    protected function checkOptions()
    {
        if (! $this->bucket) {
            throw new UploadException('请传入桶名');
        }
        if (! $this->region) {
            throw new UploadException('请传入所属地域');
        }
        if (! $this->accessKey) {
            throw new UploadException('请传入SecretId');
        }
        if (! $this->secretKey) {
            throw new UploadException('请传入SecretKey');
        }
    }

    /**
     * 组合成xml.
     * @return string
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    protected function xmlBuild(array $xmlAttr, string $root = 'xml', string $itemKey = 'item')
    {
        $xml = '<' . $root . '>';
        $xml .= '<' . $itemKey . '>';

        foreach ($xmlAttr as $kk => $vv) {
            if (is_array($vv)) {
                foreach ($vv as $v) {
                    $xml .= '<' . $kk . '>' . $v . '</' . $kk . '>';
                }
            } else {
                $xml .= '<' . $kk . '>' . $vv . '</' . $kk . '>';
            }
        }
        $xml .= '</' . $itemKey . '>';
        $xml .= '</' . $root . '>';

        return $xml;
    }

    /**
     * @return string
     * @email 136327134@qq.com
     * @date 2022/10/17
     */
    protected function getRequestHost(string $bucket, string $region = '')
    {
        if (! $this->accessKey) {
            throw new UploadException('请传入SecretId');
        }
        if (! $this->secretKey) {
            throw new UploadException('请传入SecretKey');
        }

        if (! str_contains($bucket, '-')) {
            $bucket = $bucket . '-' . $this->appid;
        }

        return $bucket . '.cos.' . ($region ?: $this->region) . '.myqcloud.com';
    }
}
