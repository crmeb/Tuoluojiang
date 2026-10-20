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

namespace Tuoluojiang\Open\services\upload\storage;

use Tuoluojiang\Open\exceptions\AdminException;
use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\upload\BaseUpload;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Log;
use Qcloud\Cos\Client;
use QCloud\COSSTS\Sts;

/**
 * 腾讯云COS文件上传
 * Class COS.
 */
class Cos extends BaseUpload
{
    /**
     * 应用id.
     */
    protected string $appid;

    /**
     * accessKey.
     * @var mixed
     */
    protected string $accessKey;

    /**
     * secretKey.
     * @var mixed
     */
    protected string $secretKey;

    /**
     * 句柄.
     */
    protected Client $handle;

    /**
     * 空间域名 Domain.
     * @var mixed
     */
    protected string $uploadUrl;

    /**
     * 存储空间名称  公开空间.
     * @var mixed
     */
    protected string $storageName;

    /**
     * COS使用  所属地域
     * @var null|mixed
     */
    protected string $storageRegion;

    protected string $cdn;

    /**
     * 水印位置.
     * @var string[]
     */
    protected array $position = [
        '1' => 'northwest', // ：左上
        '2' => 'north', // ：中上
        '3' => 'northeast', // ：右上
        '4' => 'west', // ：左中
        '5' => 'center', // ：中部
        '6' => 'east', // ：右中
        '7' => 'southwest', // ：左下
        '8' => 'south', // ：中下
        '9' => 'southeast', // ：右下
    ];

    /**
     * 初始化.
     * @return mixed|void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->accessKey                          = $config['accessKey'] ?? null;
        $this->appid                              = $config['appid'] ? (string) $config['appid'] : '';
        $this->secretKey                          = $config['secretKey'] ?? null;
        $this->uploadUrl                          = $this->checkUploadUrl($config['uploadUrl'] ?? '');
        $this->storageName                        = $config['storageName'] ?? null;
        $this->storageRegion                      = $config['storageRegion'] ?? null;
        $this->cdn                                = $config['cdn'] ?? null;
        $this->waterConfig['watermark_text_font'] = 'simfang仿宋.ttf';
    }

    /**
     * 获取文件信息.
     */
    public function getMate(string $key): mixed
    {
        try {
            $storageName = explode('.', parse_url($key)['host'] ?? '')[0] ?? '';
            $key         = format_url($key);
            $uploadUrl   = format_url($this->uploadUrl);
            if (str_contains($key, $uploadUrl)) {
                $fileKey = str_replace($uploadUrl . '/', '', $key);
            } else {
                $fileKey = $key;
            }
            return $this->app()->headObject([
                'Bucket' => $storageName ?: $this->storageName,
                'Key'    => $fileKey,
            ])->toArray();
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 文件流上传.
     * @param mixed $fileContent
     * @return array|bool|mixed|\StdClass
     */
    public function stream($fileContent, ?string $key = null)
    {
        if (! $key) {
            $key = $this->saveFileName();
        }
        return $this->upload($key, true, $fileContent);
    }

    /**
     * 文件上传.
     * @param bool $realName
     * @return array|bool|mixed|\StdClass
     */
    public function move(string $file = 'file', $realName = false)
    {
        return $this->upload($file);
    }

    /**
     * 缩略图.
     * @return array|mixed
     */
    public function thumb(string $filePath = '', string $fileName = '', string $type = 'all')
    {
        $filePath                    = $this->getFilePath($filePath);
        $data                        = ['big' => $filePath, 'mid' => $filePath, 'small' => $filePath];
        $this->fileInfo->filePathBig = $this->fileInfo->filePathMid = $this->fileInfo->filePathSmall = $this->fileInfo->filePathWater = $filePath;
        if ($filePath) {
            $config = $this->thumbConfig;
            foreach ($this->thumb as $v) {
                if ($type == 'all' || $type == $v) {
                    $height = 'thumb_' . $v . '_height';
                    $width  = 'thumb_' . $v . '_width';
                    $key    = 'filePath' . ucfirst($v);
                    if (sys_config('image_thumbnail_status', 1) && isset($config[$height], $config[$width]) && $config[$height] && $config[$width]) {
                        $this->fileInfo->{$key} = $filePath . '?imageMogr2/thumbnail/' . $config[$width] . 'x' . $config[$height];
                        $this->fileInfo->{$key} = $this->water($this->fileInfo->{$key});
                        $data[$v]               = $this->fileInfo->{$key};
                    } else {
                        $this->fileInfo->{$key} = $this->water($this->fileInfo->{$key});
                        $data[$v]               = $this->fileInfo->{$key};
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 水印.
     * @return mixed|string
     */
    public function water(string $filePath = '')
    {
        $filePath    = $this->getFilePath($filePath);
        $waterConfig = $this->waterConfig;
        $waterPath   = $filePath;
        if ($waterConfig['image_watermark_status'] && $filePath) {
            if (! str_contains($filePath, '?')) {
                $filePath .= '?watermark';
            } else {
                $filePath .= '&watermark';
            }
            switch ($waterConfig['watermark_type']) {
                case 1:// 图片
                    if (! $waterConfig['watermark_image']) {
                        throw new AdminException('请先配置水印图片');
                    }
                    $waterPath = $filePath .= '/1/image/' . base64_encode($waterConfig['watermark_image']) . '/gravity/' . ($this->position[$waterConfig['watermark_position']] ?? 'northwest') . '/blogo/1/dx/' . $waterConfig['watermark_x'] . '/dy/' . $waterConfig['watermark_y'];
                    break;
                case 2:// 文字
                    if (! $waterConfig['watermark_text']) {
                        throw new AdminException('请先配置水印文字');
                    }
                    $waterPath = $filePath .= '/2/text/' . base64_encode($waterConfig['watermark_text']) . '/font/' . base64_encode($waterConfig['watermark_text_font']) . '/fill/' . base64_encode($waterConfig['watermark_text_color']) . '/fontsize/' . $waterConfig['watermark_text_size'] . '/gravity/' . ($this->position[$waterConfig['watermark_position']] ?? 'northwest') . '/dx/' . $waterConfig['watermark_x'] . '/dy/' . $waterConfig['watermark_y'];
                    break;
            }
        }
        return $waterPath;
    }

    /**
     * TODO 删除资源.
     * @return mixed
     */
    public function delete(string $filePath)
    {
        try {
            $cleanFilePath  = format_url($filePath);
            $cleanUploadUrl = format_url($this->uploadUrl);
            if (str_contains($cleanFilePath, $cleanUploadUrl)) {
                $fileKey = str_replace($cleanUploadUrl . '/', '', $cleanFilePath);
            } else {
                $fileKey = $cleanFilePath;
            }
            $this->app()->deleteObject([
                'Bucket' => $this->storageName,
                'Key'    => $fileKey,
            ]);
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 生成签名.
     * @return array|mixed
     * @throws \Exception
     */
    public function getTempKeys()
    {
        $sts    = new Sts();
        $config = [
            'url'             => 'https://sts.tencentcloudapi.com/',
            'domain'          => 'sts.tencentcloudapi.com',
            'proxy'           => '',
            'secretId'        => $this->accessKey, // 固定密钥
            'secretKey'       => $this->secretKey, // 固定密钥
            'bucket'          => $this->storageName, // 换成你的 bucket
            'region'          => $this->storageRegion, // 换成 bucket 所在园区
            'durationSeconds' => 1800, // 密钥有效期
            'allowPrefix'     => ['*'], // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
            // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
            'allowActions' => [
                // 简单上传
                'name/cos:PutObject',
                'name/cos:PostObject',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload',
            ],
        ];
        // 获取临时密钥，计算签名
        $result           = $sts->getTempKeys($config);
        $result['url']    = $this->uploadUrl . '/';
        $result['cdn']    = $this->cdn;
        $result['type']   = 'COS';
        $result['bucket'] = $this->storageName;
        $result['region'] = $this->storageRegion;
        return $result;
    }

    /**
     * 计算临时密钥用的签名.
     * @param mixed $opt
     * @param mixed $key
     * @param mixed $method
     * @param mixed $config
     * @return string
     */
    public function getSignature($opt, $key, $method, $config)
    {
        $formatString = $method . $config['domain'] . '/?' . $this->json2str($opt, 1);
        $sign         = hash_hmac('sha1', $formatString, $key);
        return base64_encode($this->_hex2bin($sign));
    }

    public function _hex2bin($data)
    {
        $len = strlen($data);
        return pack('H' . $len, $data);
    }

    // obj 转 query string
    public function json2str($obj, $notEncode = false)
    {
        ksort($obj);
        $arr = [];
        if (! is_array($obj)) {
            return $this->setError($obj . ' must be a array');
        }
        foreach ($obj as $key => $val) {
            $arr[] = $key . '=' . ($notEncode ? $val : rawurlencode($val));
        }
        return join('&', $arr);
    }

    // v2接口的key首字母小写，v3改成大写，此处做了向下兼容
    public function backwardCompat($result)
    {
        if (! is_array($result)) {
            return $this->setError($result . ' must be a array');
        }
        $compat = [];
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $compat[lcfirst($key)] = $this->backwardCompat($value);
            } elseif ($key == 'Token') {
                $compat['sessionToken'] = $value;
            } else {
                $compat[lcfirst($key)] = $value;
            }
        }
        return $compat;
    }

    /**
     * 桶列表.
     * @return array|mixed
     *                     "Name" => "record-1254950941"
     *                     "Location" => "ap-chengdu"
     *                     "CreationDate" => "2019-05-16T08:33:29Z"
     *                     "BucketType" => "cos"
     */
    public function listbuckets(?string $region = null, bool $line = false, bool $shared = false)
    {
        try {
            $res = $this->app()->listBuckets();
            return $res['Buckets'][0]['Bucket'] ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 创建桶.
     * @param string $acl public-read=公共独写
     * @return bool|mixed
     */
    public function createBucket(string $name, string $region = '', string $acl = 'public-read')
    {
        $regionData = $this->getRegion();
        $regionData = array_column($regionData, 'value');
        if (! in_array($region, $regionData)) {
            return $this->setError('COS:无效的区域!');
        }
        $this->storageRegion = $region;
        $app                 = $this->app();
        // 检测桶
        try {
            $app->headBucket([
                'Bucket' => $name,
            ]);
        } catch (\Throwable $e) {
            // 桶不存在返回404
            if (strstr('404', $e->getMessage())) {
                return $this->setError('COS:' . $e->getMessage());
            }
        }
        // 创建桶
        try {
            $res = $app->createBucket([
                'Bucket' => $name . '-' . $this->appid,
                'ACL'    => $acl,
            ]);
        } catch (\Throwable $e) {
            if (strstr('[curl] 6', $e->getMessage())) {
                return $this->setError('COS:无效的区域!!');
            }
            if (strstr('Access Denied.', $e->getMessage())) {
                return $this->setError('COS:无权访问');
            }
            return $this->setError('COS:' . $e->getMessage());
        }
        return $res;
    }

    /**
     * 删除桶.
     * @return bool|mixed
     */
    public function deleteBucket(string $name)
    {
        try {
            $this->app()->deleteBucket([
                'Bucket' => $name,
            ]);
            return true;
        } catch (\Throwable $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * @return array|object
     */
    public function getDomian(string $name, ?string $region = null)
    {
        $this->storageRegion = $region;
        try {
            $res = $this->app()->getBucketDomain([
                'Bucket' => $name,
            ]);
            $domainRules = $res['DomainRules'];
            return array_column($domainRules, 'Name');
        } catch (\Throwable $e) {
        }
        return [];
    }

    /**
     * 绑定域名.
     * @return bool|mixed
     */
    public function bindDomian(string $name, string $domain, ?string $region = null): mixed
    {
        $this->storageRegion = $region;
        $parseDomain         = parse_url($domain);
        try {
            $res = $this->app()->putBucketDomain([
                'Bucket'      => $name,
                'DomainRules' => [
                    [
                        'Name'              => $parseDomain['host'],
                        'Status'            => 'ENABLED',
                        'Type'              => 'REST',
                        'ForcedReplacement' => 'CNAME',
                    ],
                ],
            ]);
            if (method_exists($res, 'toArray')) {
                $res = $res->toArray();
            }
            if ($res['RequestId'] ?? null) {
                return true;
            }
        } catch (\Throwable $e) {
            if ($message = $this->setMessage($e->getMessage())) {
                return $this->setError($message);
            }
            return $this->setError($e->getMessage());
        }
        return false;
    }

    /**
     * 设置跨域
     */
    public function setBucketCors(string $name, string $region): bool
    {
        $this->storageRegion = $region;
        try {
            $this->app()->putBucketCors([
                'Bucket'    => $name,
                'CORSRules' => [
                    [
                        'AllowedHeaders' => ['*'],
                        'AllowedMethods' => ['PUT', 'GET', 'POST', 'DELETE', 'HEAD'],
                        'AllowedOrigins' => ['*'],
                        'ExposeHeaders'  => ['ETag', 'Content-Length', 'x-cos-request-id'],
                        'MaxAgeSeconds'  => 12,
                    ],
                ],
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 地域
     * @return mixed|string[][]
     */
    public function getRegion()
    {
        return [
            [
                'value' => 'ap-chengdu',
                'label' => '成都',
            ],
            [
                'value' => 'ap-shanghai',
                'label' => '上海',
            ],
            [
                'value' => 'ap-guangzhou',
                'label' => '广州',
            ],
            [
                'value' => 'ap-nanjing',
                'label' => '南京',
            ],
            [
                'value' => 'ap-beijing',
                'label' => '北京',
            ],
            [
                'value' => 'ap-chongqing',
                'label' => '重庆',
            ],
            [
                'value' => 'ap-shenzhen-fsi',
                'label' => '深圳金融',
            ],
            [
                'value' => 'ap-shanghai-fsi',
                'label' => '上海金融',
            ],
            [
                'value' => 'ap-beijing-fsi',
                'label' => '北京金融',
            ],
            [
                'value' => 'ap-hongkong',
                'label' => '中国香港',
            ],
            [
                'value' => 'ap-singapore',
                'label' => '新加坡',
            ],
            [
                'value' => 'ap-mumbai',
                'label' => '孟买',
            ],
            [
                'value' => 'ap-jakarta',
                'label' => '雅加达',
            ],
            [
                'value' => 'ap-seoul',
                'label' => '首尔',
            ],
            [
                'value' => 'ap-bangkok',
                'label' => '曼谷',
            ],
            [
                'value' => 'ap-tokyo',
                'label' => '东京',
            ],
            [
                'value' => 'na-siliconvalley',
                'label' => '硅谷（美西）',
            ],
            [
                'value' => 'na-ashburn',
                'label' => '弗吉尼亚（美东）',
            ],
            [
                'value' => 'na-toronto',
                'label' => '多伦多',
            ],
            [
                'value' => 'sa-saopaulo',
                'label' => '圣保罗',
            ],
            [
                'value' => 'eu-frankfurt',
                'label' => '法兰克福',
            ],
            [
                'value' => 'eu-moscow',
                'label' => '莫斯科',
            ],
        ];
    }

    /**
     * 实例化cos.
     * @return Client
     */
    protected function app()
    {
        if (! $this->accessKey || ! $this->secretKey) {
            throw new UploadException(400721);
        }
        $this->handle = new Client([
            'credentials' => [
                'secretId'  => $this->accessKey,
                'secretKey' => $this->secretKey,
            ],
            'region' => $this->storageRegion ?: 'ap-chengdu',
        ]);
        return $this->handle;
    }

    /**
     * 上传文件.
     * @param bool $isStream 是否为流上传
     * @param null|string $fileContent 流内容
     * @return array|bool|\StdClass
     */
    protected function upload(?string $file = null, bool $isStream = false, ?string $fileContent = null)
    {
        if (! $isStream) {
            $fileHandle = app()->request->file($file);
            if (! $fileHandle) {
                return $this->setError('上传的文件不存在');
            }
            if ($this->validate) {
                if ($fileHandle->getSize() > $this->validate['filesize']) {
                    return $this->setError('文件过大');
                }
                if (! in_array($fileHandle->getMimeType(), $this->validate['fileMime'])) {
                    return $this->setError('不合法的文件类型：' . $fileHandle->getMimeType());
                }
            }
            $key  = $this->saveFileName($fileHandle->getRealPath(), $fileHandle->getClientOriginalExtension() ?: $fileHandle->extension());
            $body = fopen($fileHandle->getRealPath(), 'rb');
            $body = (string) Utils::streamFor($body);
        } else {
            $key  = $file;
            $body = $fileContent;
        }
        try {
            $mimeType                      = getMimetype($key);
            $key                           = $this->getUploadPath($key);
            $this->fileInfo->uploadInfo    = $this->app()->upload($this->storageName, $key, $body, $mimeType ? ['Content-Type' => $mimeType] : []);
            $this->fileInfo->filePath      = ($this->cdn ?: $this->uploadUrl) . '/' . $key;
            $this->fileInfo->realName      = isset($fileHandle) ? $fileHandle->getClientOriginalName() : $key;
            $this->fileInfo->fileName      = $key;
            $this->fileInfo->filePathWater = $this->water($this->fileInfo->filePath);
            $this->fileInfo->fileSize      = $fileHandle->getSize();
            $this->fileInfo->fileType      = $fileHandle->getMimeType();
            $this->authThumb && $this->thumb($this->fileInfo->filePath);
            return $this->fileInfo;
        } catch (UploadException $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 处理.
     * @return string
     */
    protected function setMessage(string $message)
    {
        $data = [
            'The specified bucket does not exist.'                                                                              => '指定的存储桶不存在。',
            'Please add CNAME/TXT record to DNS then try again later. Please allow up to 10 mins before your DNS takes effect.' => '请将CNAME记录添加到DNS，然后稍后重试。在DNS生效前，请等待最多10分钟。',
        ];
        $msg = $data[$message] ?? '';
        if ($msg) {
            return $msg;
        }
        foreach ($data as $item) {
            if (strstr($message, $item)) {
                return $item;
            }
        }
        return '';
    }
}
