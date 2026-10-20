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
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Http\Client;
use Qiniu\Http\Error;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

/**
 * TODO 七牛云上传
 * Class Qiniu.
 */
class Qiniu extends BaseUpload
{
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
    protected object $handle;

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

    /**
     * 水印位置.
     * @var string[]
     */
    protected array $position = [
        '1' => 'NorthWest', // ：左上
        '2' => 'North', // ：中上
        '3' => 'NorthEast', // ：右上
        '4' => 'West', // ：左中
        '5' => 'Center', // ：中部
        '6' => 'East', // ：右中
        '7' => 'SouthWest', // ：左下
        '8' => 'South', // ：中下
        '9' => 'SouthEast', // ：右下
    ];

    protected string $cdn;

    /**
     * 初始化.
     * @return mixed|void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->accessKey     = $config['accessKey'] ?? null;
        $this->secretKey     = $config['secretKey'] ?? null;
        $this->uploadUrl     = $this->checkUploadUrl($config['uploadUrl'] ?? '');
        $this->storageName   = $config['storageName'] ?? null;
        $this->cdn           = $config['cdn'] ?? null;
        $this->storageRegion = $config['storageRegion'] ?? null;
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
            $bucketManager      = new BucketManager($this->app(), new Config());
            [$response, $error] = $bucketManager->stat($storageName ?: $this->storageName, $fileKey);
            if ($error !== null) {
                return $this->setError($error->message());
            }
            return $response;
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 上传文件.
     * @param bool $realName
     * @return array|bool|mixed|\StdClass|string
     */
    public function move(string $file = 'file', $realName = false)
    {
        $fileHandle = app()->request->file($file);
        if (! $fileHandle) {
            return $this->setError('上传的文件不存在');
        }
        if ($this->validate) {
            //            if (! in_array($fileHandle->getClientOriginalExtension() ?: $fileHandle->extension(), $this->validate['fileExt'])) {
            //                return $this->setError('不合法的文件后缀');
            //            }
            if ($fileHandle->getSize() > $this->validate['filesize']) {
                return $this->setError('文件过大');
            }
            if (! in_array($fileHandle->getMimeType(), $this->validate['fileMime'])) {
                return $this->setError('不合法的文件类型：' . $fileHandle->getMimeType());
            }
        }
        $key   = $this->saveFileName($fileHandle->getRealPath(), $fileHandle->getClientOriginalExtension() ?: $fileHandle->extension());
        $key   = $this->getUploadPath($key);
        $token = $this->app()->uploadToken($this->storageName);
        try {
            $uploadMgr        = new UploadManager();
            [$result, $error] = $uploadMgr->putFile($token, $key, $fileHandle->getRealPath());
            if ($error !== null) {
                return $this->setError($error->message());
            }
            $this->fileInfo->uploadInfo    = $result;
            $this->fileInfo->realName      = $fileHandle->getClientOriginalName();
            $this->fileInfo->filePath      = $this->uploadUrl . '/' . $key;
            $this->fileInfo->fileName      = $key;
            $this->fileInfo->fileType      = $fileHandle->getMimeType();
            $this->fileInfo->filePathWater = $this->water($this->fileInfo->filePath);
            $this->fileInfo->fileSize      = $fileHandle->getSize();
            $this->authThumb && $this->thumb($this->fileInfo->filePath);
            return $this->fileInfo;
        } catch (UploadException $e) {
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
        $key   = $this->getUploadPath($key);
        $token = $this->app()->uploadToken($this->storageName, $key);
        try {
            $uploadMgr        = new UploadManager();
            [$result, $error] = $uploadMgr->put($token, $key, $fileContent);
            if ($error !== null) {
                return $this->setError($error->message());
            }
            $this->fileInfo->uploadInfo    = $result;
            $this->fileInfo->realName      = $key;
            $this->fileInfo->filePath      = ($this->cdn ?: $this->uploadUrl) . '/' . $key;
            $this->fileInfo->fileName      = $key;
            $this->fileInfo->filePathWater = $this->water($this->fileInfo->filePath);
            $this->thumb($this->fileInfo->filePath);
            return $this->fileInfo;
        } catch (UploadException $e) {
            return $this->setError($e->getMessage());
        }
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
                        $this->fileInfo->{$key} = $filePath . '?imageView2/2/w/' . $config[$width] . '/h/' . $config[$height];
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
            if (strpos($filePath, '?') === false) {
                $filePath .= '?watermark';
            } else {
                $filePath .= '&watermark';
            }
            switch ($waterConfig['watermark_type']) {
                case 1:// 图片
                    if (! $waterConfig['watermark_image']) {
                        throw new AdminException('请先配置水印图片');
                    }
                    $waterPath = $filePath .= '/1/image/' . base64_encode($waterConfig['watermark_image']) . '/gravity/' . ($this->position[$waterConfig['watermark_position']] ?? 'SouthEest') . '/dissolve/' . $waterConfig['watermark_opacity'] . '/dx/' . $waterConfig['watermark_x'] . '/dy/' . $waterConfig['watermark_y'];
                    break;
                case 2:// 文字
                    if (! $waterConfig['watermark_text']) {
                        throw new AdminException('请先配置水印文字');
                    }
                    $waterPath = $filePath .= '/2/text/' . base64_encode($waterConfig['watermark_text']) . '/fill/' . base64_encode($waterConfig['watermark_text_color']) . '/fontsize/' . $waterConfig['watermark_text_size'] . '/gravity/' . ($this->position[$waterConfig['watermark_position']] ?? 'SouthEest') . '/dx/' . $waterConfig['watermark_x'] . '/dy/' . $waterConfig['watermark_y'];
                    break;
            }
        }
        return $waterPath;
    }

    /**
     * 获取上传配置信息.
     * @return array
     */
    public function getSystem()
    {
        $token  = $this->app()->uploadToken($this->storageName);
        $domain = $this->uploadUrl;
        $key    = $this->saveFileName();
        return compact('token', 'domain', 'key');
    }

    /**
     * TODO 删除资源.
     * @return array|mixed
     */
    public function delete(string $filePath)
    {
        $bucketManager = new BucketManager($this->app(), new Config());
        $filePath      = ! str_contains($filePath, $this->uploadUrl) ? $filePath : str_replace(trim($this->uploadUrl) . '/', '', $filePath);
        return $bucketManager->delete($this->storageName, $filePath);
    }

    /**
     * 获取七牛云上传密钥.
     * @return mixed|string
     */
    public function getTempKeys()
    {
        $token  = $this->app()->uploadToken($this->storageName);
        $domain = $this->uploadUrl;
        $cdn    = $this->cdn;
        $key    = $this->saveFileName(null, 'mp4');
        $type   = 'QINIU';
        return compact('token', 'domain', 'key', 'type', 'cdn');
    }

    /**
     * 获取当前所有桶列表.
     * @return bool|mixed
     */
    public function listbuckets(?string $region = null, bool $line = false, bool $shared = false)
    {
        $bucket             = new BucketManager($this->app());
        [$response, $error] = $bucket->listbuckets($region, $line ? 'true' : 'false', $shared ? 'true' : 'false');
        if ($error !== null) {
            return $this->setError($error->message());
        }
        return $response;
    }

    /**
     * @return bool|mixed
     */
    public function createBucket(string $name, string $region = 'z0')
    {
        $regionData = $this->getRegion();
        if (! in_array($region, array_column($regionData, 'value'))) {
            return $this->setError('七牛云:无效的区域');
        }
        $url                     = 'https://' . Config::UC_HOST . '/mkbucketv3/' . $name . '/region/' . $region;
        $body                    = null;
        $headers                 = $this->app()->authorizationV2($url, 'POST', $body, 'application/json');
        $headers['Content-Type'] = 'application/json';
        $ret                     = Client::post($url, $body, $headers);
        if (! $ret->ok()) {
            $error = new Error($url, $ret);
            if ($error->message() === 'bucket exists') {
                return $this->setError('七牛云：云空间已存在');
            }
            return $this->setError('七牛云：' . $error->message());
        }
        return ($ret->body === null) ? [] : $ret->json();
    }

    /**
     * 获取区域
     * @return mixed|string[][]
     */
    public function getRegion()
    {
        return [
            [
                'value' => 'z0',
                'label' => '华东',
            ],
            [
                'value' => 'z1',
                'label' => '华北',
            ],
            [
                'value' => 'z2',
                'label' => '华南',
            ],
            [
                'value' => 'na0',
                'label' => '北美',
            ],
            [
                'value' => 'as0',
                'label' => '东南亚',
            ],
            [
                'value' => 'cn-east-2',
                'label' => '华东-浙江2',
            ],
        ];
    }

    /**
     * 删除空间.
     * @return bool|mixed
     */
    public function deleteBucket(string $name)
    {
        $bucket             = new BucketManager($this->app());
        [$response, $error] = $bucket->deleteBucket($name);
        if ($error !== null) {
            return $this->setError($error->message());
        }
        return $response;
    }

    /**
     * 获取七牛域名.
     * @return null|array|bool|mixed
     */
    public function getDomian(string $name)
    {
        $url                     = 'https://' . Config::UC_HOST . '/v2/domains?tbl=' . $name;
        $body                    = null;
        $headers                 = $this->app()->authorizationV2($url, 'POST', $body, 'application/x-www-form-urlencoded');
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $ret                     = Client::post($url, $body, $headers);
        if (! $ret->ok()) {
            $error = new Error($url, $ret);
            return $this->setError('七牛云：' . $error->message());
        }
        return ($ret->body === null) ? [] : $ret->json();
    }

    public function getDomianInfo(string $host)
    {
        $url                     = 'https://' . Config::API_HOST . '/domain/' . $host;
        $headers                 = $this->app()->authorization($url, null, 'application/x-www-form-urlencoded');
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $ret                     = Client::get($url, $headers);
        if (! $ret->ok()) {
            $error = new Error($url, $ret);
            return $this->setError('七牛云：' . $error->message());
        }
        return ($ret->body === null) ? [] : $ret->json();
    }

    /**
     * @return null|array|bool|mixed
     */
    public function bindDomian(string $name, string $domain, ?string $region = null)
    {
        $parseDomin = parse_url($domain);
        $url        = 'https://' . Config::API_HOST . '/domain/' . $parseDomin['host'];
        $body       = [
            'type'     => 'normal',
            'platform' => 'web',
            'geocover' => 'china',
            'source'   => [
                'sourceType'        => 'qiniuBucket',
                'sourceQiniuBucket' => $name,
                'TestURLPath'       => 'qiniu_do_not_delete.gif',
            ],
            'protocol' => $parseDomin['scheme'],
            'cache'    => [
                'cacheControls' => [
                    [
                        'time'     => 1,
                        'timeunit' => 4,
                        'type'     => 'all',
                        'rule'     => '*',
                    ],
                ],
                'ignoreParam' => false,
            ],
        ];
        $bodyJson                = json_encode($body);
        $headers                 = $this->app()->authorization($url, $bodyJson, 'application/json');
        $headers['Content-Type'] = 'application/json';
        $ret                     = Client::post($url, $bodyJson, $headers);
        if (! $ret->ok()) {
            $error = new Error($url, $ret);
            return $this->setError('七牛云：' . $error->message());
        }
        return ($ret->body === null) ? [] : $ret->json();
    }

    /**
     * 跨域
     * @return bool
     */
    public function setBucketCors(string $name, string $region)
    {
        return true;
    }

    /**
     * 实例化七牛云.
     * @return Auth|object
     */
    protected function app()
    {
        if (! $this->accessKey || ! $this->secretKey) {
            throw new UploadException('400721');
        }
        $this->handle = new Auth($this->accessKey, $this->secretKey);
        return $this->handle;
    }
}
