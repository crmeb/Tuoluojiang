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
use Tuoluojiang\Open\services\upload\BaseUpload;
use GuzzleHttp\Psr7\Utils;
use Obs\ObsClient;
use Obs\ObsException;

class Obs extends BaseUpload
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
    protected ObsClient $handle;

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
            $res = $this->app()->getObjectMetadata([
                'Bucket' => $storageName ?: $this->storageName,
                'Key'    => $fileKey,
            ]);
            return $res->toArray();
        } catch (ObsException $e) {
            $errorMsg = match ($e->getStatusCode()) {
                409     => 'OBS:Bucket已存在（并发创建冲突）',
                403     => 'OBS:权限不足（缺少创建桶权限）',
                400     => 'OBS:参数错误（检查地域或名称格式）',
                default => "OBS:获取属性失败（{$e->getStatusCode()}）：{$e->getExceptionMessage()}",
            };
            return $this->setError($errorMsg);
        }
    }

    public function move(string $file = 'file', bool $isStream = false, ?string $fileContent = null)
    {
        if (! $isStream) {
            $fileHandle = app()->request->file($file);
            if (! $fileHandle) {
                return $this->setError('上传的文件不存在');
            }
            if ($this->validate) {
                if (filesize($fileHandle) > $this->validate['filesize']) {
                    return $this->setError('文件过大');
                }
                if (! in_array($fileHandle->getOriginalMime(), $this->validate['fileMime'])) {
                    return $this->setError('不合法的文件类型：' . $fileHandle->getOriginalMime());
                }
            }
            $key = $this->saveFileName($fileHandle->getRealPath(), $fileHandle->getOriginalExtension());

            $body = fopen($fileHandle->getRealPath(), 'rb');
            $body = (string) Utils::streamFor($body);
        } else {
            $key  = $file;
            $body = $fileContent;
        }
        $key = $this->getUploadPath($key);

        try {
            $uploadInfo                    = $this->app()->putObject($key, $body, 'application/octet-stream');
            $this->fileInfo->uploadInfo    = $uploadInfo;
            $this->fileInfo->realName      = $fileHandle->getOriginalName();
            $this->fileInfo->filePath      = ($this->cdn ?: $this->uploadUrl) . '/' . $key;
            $this->fileInfo->fileName      = $key;
            $this->fileInfo->filePathWater = $this->water($this->fileInfo->filePath);
            $this->fileInfo->fileSize      = $fileHandle->getSize();
            $this->fileInfo->fileType      = $fileHandle->getMimeType();
            $this->authThumb && $this->thumb($this->fileInfo->filePath);
            return $this->fileInfo;
        } catch (\Throwable $e) {
            return $this->setError($e->getMessage());
        }
    }

    public function stream($fileContent, ?string $key = null)
    {
        if (! $key) {
            $key = $this->saveFileName();
        }
        return $this->move($key, true, $fileContent);
    }

    /**
     * 删除文件.
     * @return bool|mixed|void
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
        } catch (ObsException $e) {
            $errorMsg = match ($e->getStatusCode()) {
                409     => 'OBS:Bucket已存在（并发创建冲突）',
                403     => 'OBS:权限不足（缺少创建桶权限）',
                400     => 'OBS:参数错误（检查地域或名称格式）',
                default => "OBS:删除失败（{$e->getStatusCode()}）：{$e->getExceptionMessage()}",
            };
            return $this->setError($errorMsg);
        }
    }

    /**
     * 初始化.
     * @return mixed|void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->accessKey                          = $config['accessKey'] ?? null;
        $this->secretKey                          = $config['secretKey'] ?? null;
        $this->uploadUrl                          = $this->checkUploadUrl($config['uploadUrl'] ?? '');
        $this->storageName                        = $config['storageName'] ?? null;
        $this->storageRegion                      = $config['storageRegion'] ?? null;
        $this->cdn                                = $config['cdn'] ?? null;
        $this->waterConfig['watermark_text_font'] = 'simfang仿宋.ttf';
    }

    public function listbuckets(?string $region = null, bool $line = false, bool $shared = false)
    {
        try {
            $res = $this->app()->listBuckets();
            return $res['Buckets'] ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 创建存储空间.
     * @return bool|mixed
     */
    public function createBucket(string $name, string $region, string $acl = '')
    {
        $validRegions = array_column($this->getRegion(), 'value');
        if (! in_array($region, $validRegions)) {
            return $this->setError("OBS:无效的区域（{$region}）");
        }
        $obsClient = $this->app();
        try {
            $obsClient->headBucket(['Bucket' => $name]);
            return $this->setError('OBS:Bucket已存在');
        } catch (ObsException $e) {
            $statusCode = $e->getStatusCode();
            // 404表示桶不存在，可继续创建；其他状态码为异常错误
            if ($statusCode !== 404) {
                return $this->setError("OBS:检查桶状态失败（{$statusCode}）：{$e->getExceptionMessage()}");
            }
        }
        try {
            $obsClient->createBucket([
                'Bucket'             => $name,
                'StorageClass'       => ObsClient::StorageClassStandard,
                'LocationConstraint' => $region,
                'ACL'                => $acl ?: ObsClient::AclPublicRead,
            ]);
            return true; // 创建成功
        } catch (ObsException $e) {
            $errorMsg = match ($e->getStatusCode()) {
                409     => 'OBS:Bucket已存在（并发创建冲突）',
                403     => 'OBS:权限不足（缺少创建桶权限）',
                400     => 'OBS:参数错误（检查地域或名称格式）',
                default => "OBS:创建失败（{$e->getStatusCode()}）：{$e->getExceptionMessage()}",
            };
            return $this->setError($errorMsg);
        }
    }

    /**
     * 获取区域
     * @return mixed|string[][]
     */
    public function getRegion()
    {
        return [
            [
                'value' => 'cn-north-1',
                'label' => '华北-北京一',
            ],
            [
                'value' => 'cn-north-9',
                'label' => '华北-乌兰察布一',
            ],
            [
                'value' => 'cn-east-2',
                'label' => '华东-上海二',
            ],
            [
                'value' => 'cn-east-3',
                'label' => '华东-上海一',
            ],
            [
                'value' => 'cn-south-1',
                'label' => '华南-广州',
            ],
            [
                'value' => 'ap-southeast-1',
                'label' => '中国-香港',
            ],
            [
                'value' => 'cn-south-4',
                'label' => '华南-广州-友好用户环境',
            ],
            [
                'value' => 'cn-southwest-2',
                'label' => '西南-贵阳一',
            ],
            [
                'value' => 'la-north-2',
                'label' => '拉美-墨西哥城二',
            ],
            [
                'value' => 'na-mexico-1',
                'label' => '拉美-墨西哥城一',
            ],
            [
                'value' => 'sa-brazil-1',
                'label' => '拉美-圣保罗一',
            ],
            [
                'value' => 'la-south-2',
                'label' => '拉美-圣地亚哥',
            ],
            [
                'value' => 'tr-west-1',
                'label' => '土耳其-伊斯坦布尔',
            ],
            [
                'value' => 'ap-southeast-2',
                'label' => '亚太-曼谷',
            ],
            [
                'value' => 'ap-southeast-3',
                'label' => '亚太-新加坡',
            ],
            [
                'value' => 'af-south-1',
                'label' => '非洲-约翰内斯堡',
            ],
        ];
    }

    /**
     * 删除存储空间.
     * @return bool|mixed|string
     */
    public function deleteBucket(string $name, string $region = '')
    {
        $validRegions = array_column($this->getRegion(), 'value');
        if (! in_array($region, $validRegions)) {
            return $this->setError("OBS:无效的区域（{$region}）");
        }
        $this->storageRegion = $region;
        $obsClient           = $this->app();
        try {
            $obsClient->headBucket(['Bucket' => $name]);
        } catch (ObsException $e) {
            if ($e->getStatusCode() == 404) {
                return $this->setError('OBS:存储桶不存在');
            }
            return $this->setError("OBS:检查桶状态失败：{$e->getExceptionMessage()}");
        }
        $clearResult = $this->clearBucketObjects($obsClient, $name);
        if ($clearResult !== true) {
            return $clearResult; // 返回清空失败的错误信息
        }
        try {
            $obsClient->deleteBucket(['Bucket' => $name]);
            return true;
        } catch (ObsException $e) {
            $errorMsg = match ($e->getStatusCode()) {
                409     => 'OBS:桶不为空（可能存在未清理的对象或分片）',
                403     => 'OBS:无删除权限（需bucket:DeleteBucket权限）',
                404     => 'OBS:桶不存在（删除过程中被其他操作移除）',
                default => "OBS:删除失败（{$e->getStatusCode()}）：{$e->getExceptionMessage()}",
            };
            return $this->setError($errorMsg);
        }
    }

    public function getDomian($name, $region)
    {
        try {
            $res = $this->app()->getBucketCustomDomain(['Bucket' => $name]);
            if ($res) {
                $domainRules = $res->toArray()['ListBucketCustomDomainsResult'];
                return array_column($domainRules, 'DomainName');
            }
            return [];
        } catch (\Throwable $e) {
        }
        return [];
    }

    /**
     * 绑定域名.
     * @return bool|mixed
     */
    public function bindDomian(string $name, string $domain, ?string $region = null)
    {
        $parseDomain = parse_url($domain);
        try {
            $this->app()->setBucketCustomDomain(['Bucket' => $name, 'DomainName' => $parseDomain['host']]);
            return true;
        } catch (ObsException $e) {
            $errorMsg = match ($e->getStatusCode()) {
                404     => 'OBS:存储桶不存在',
                default => "OBS:设置空间域名失败（{$e->getStatusCode()}）：{$e->getExceptionMessage()}",
            };
            return $this->setError($errorMsg);
        }
    }

    /**
     * 设置跨域规则.
     * @return bool|mixed
     */
    public function setBucketCors(string $name, string $region)
    {
        $validRegions = array_column($this->getRegion(), 'value');
        if (! in_array($region, $validRegions)) {
            return $this->setError("OBS:无效的区域（{$region}）");
        }
        $this->storageRegion = $region;
        try {
            $this->app()->setBucketCors([
                'Bucket'    => $name,
                'CorsRules' => [
                    [
                        'AllowedHeader' => ['*'],
                        'AllowedMethod' => ['PUT', 'GET', 'POST', 'DELETE', 'HEAD'],
                        'AllowedOrigin' => ['*'],
                        'ExposeHeader'  => ['ETag'],
                        'MaxAgeSeconds' => 0,
                    ],
                ],
            ]);
            return true;
        } catch (ObsException $e) {
            $errorMsg = match ($e->getStatusCode()) {
                404     => 'OBS:存储桶不存在',
                403     => 'OBS:无权限设置跨域规则（需bucket:PutBucketCORS权限）',
                400     => 'OBS:跨域规则格式错误（检查规则参数）',
                default => "OBS:设置跨域失败（{$e->getStatusCode()}）：{$e->getExceptionMessage()}",
            };
            return $this->setError($errorMsg);
        }
    }

    /**
     * @date 2023/6/13
     * @param mixed $callbackUrl
     * @param mixed $dir
     * @return array
     */
    public function getTempKeys($callbackUrl = '', $dir = '')
    {
        // TODO: Implement getTempKeys() method.
        $base64CallbackBody = base64_encode(json_encode([
            'callbackUrl'      => $callbackUrl,
            'callbackBody'     => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => 'application/x-www-form-urlencoded',
        ]));

        $policy = json_encode([
            'expiration' => $this->gmtIso8601(time() + 300),
            'conditions' => [
                [0 => 'content-length-range', 1 => 0, 2 => 1048576000],
                ['bucket' => $this->storageName],
                [0        => 'starts-with', 1 => '$key', 2 => $dir],
            ],
        ]);
        $base64Policy = base64_encode($policy);
        $signature    = base64_encode(hash_hmac('sha1', $base64Policy, $this->secretKey, true));
        return [
            'accessid'  => $this->accessKey,
            'host'      => $this->uploadUrl,
            'policy'    => $base64Policy,
            'signature' => $signature,
            'expire'    => time() + 30,
            'callback'  => $base64CallbackBody,
            'cdn'       => $this->cdn,
            'type'      => 'OBS',
        ];
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
                        $this->fileInfo->{$key} = $filePath . '?x-oss-process=image/resize,h_' . $config[$height] . ',w_' . $config[$width];
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
            if (strpos($filePath, '?x-oss-process') === false) {
                $filePath .= '?x-oss-process=image';
            }
            switch ($waterConfig['watermark_type']) {
                case 1:// 图片
                    if (! $waterConfig['watermark_image']) {
                        throw new AdminException('请先配置水印图片');
                    }
                    $waterPath = $filePath .= '/watermark,image_' . base64_encode($waterConfig['watermark_image']) . ',t_' . $waterConfig['watermark_opacity'] . ',g_' . ($this->position[$waterConfig['watermark_position']] ?? 'nw') . ',x_' . $waterConfig['watermark_x'] . ',y_' . $waterConfig['watermark_y'];
                    break;
                case 2:// 文字
                    if (! $waterConfig['watermark_text']) {
                        throw new AdminException('请先配置水印文字');
                    }
                    $waterConfig['watermark_text_color'] = str_replace('#', '', $waterConfig['watermark_text_color']);
                    $waterPath                           = $filePath .= '/watermark,text_' . base64_encode($waterConfig['watermark_text']) . ',color_' . $waterConfig['watermark_text_color'] . ',size_' . $waterConfig['watermark_text_size'] . ',g_' . ($this->position[$waterConfig['watermark_position']] ?? 'nw') . ',x_' . $waterConfig['watermark_x'] . ',y_' . $waterConfig['watermark_y'];
                    break;
            }
        }
        return $waterPath;
    }

    /**
     * 实例化cos.
     * @return ObsClient
     */
    protected function app()
    {
        $this->handle = new ObsClient([
            'key'    => $this->accessKey,
            'secret' => $this->secretKey,
            //            'endpoint' => $this->storageRegion ? "obs.{$this->storageRegion}.myhuaweicloud.com" : 'obs.cn-north-9.myhuaweicloud.com',
            'endpoint' => $this->storageRegion ? "obs.{$this->storageRegion}.myhuaweicloud.com" : 'obs.cn-north-9.myhuaweicloud.com',
            //            'bucket'    => $this->storageName,
        ]);
        return $this->handle;
    }

    /**
     * 获取ISO时间格式.
     * @param mixed $time
     * @throws \Exception
     */
    protected function gmtIso8601($time): string
    {
        $dtStr      = date('c', $time);
        $myDateTime = new \DateTime($dtStr);
        $expiration = $myDateTime->format(\DateTimeInterface::ISO8601);
        $pos        = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . 'Z';
    }

    /**
     * 清空bucket.
     */
    private function clearBucketObjects(ObsClient $obsClient, string $bucketName): bool|string
    {
        try {
            // 1. 删除所有普通对象
            $marker = '';
            while (true) {
                $listResult = $obsClient->listObjects([
                    'Bucket'  => $bucketName,
                    'Marker'  => $marker,
                    'MaxKeys' => 1000, // 每次最多列举1000个对象
                ]);

                $objects = $listResult->get('Contents', []);
                if (empty($objects)) {
                    break;
                }

                // 批量删除对象
                $deleteParams = [
                    'Bucket'  => $bucketName,
                    'Objects' => array_map(function ($obj) {
                        return ['Key' => $obj['Key']];
                    }, $objects),
                ];
                $obsClient->deleteObjects($deleteParams);

                $marker = end($objects)['Key'];
            }

            // 2. 删除所有分片上传残留（未完成的分片任务）
            $uploadIdMarker = '';
            while (true) {
                $listUploadsResult = $obsClient->listMultipartUploads([
                    'Bucket'         => $bucketName,
                    'UploadIdMarker' => $uploadIdMarker,
                ]);

                $uploads = $listUploadsResult->get('Uploads', []);
                if (empty($uploads)) {
                    break;
                }

                // 终止每个分片上传任务
                foreach ($uploads as $upload) {
                    $obsClient->abortMultipartUpload([
                        'Bucket'   => $bucketName,
                        'Key'      => $upload['Key'],
                        'UploadId' => $upload['UploadId'],
                    ]);
                }

                $uploadIdMarker = end($uploads)['UploadId'];
            }

            return true;
        } catch (ObsException $e) {
            return $this->setError("OBS:清空桶内容失败：{$e->getExceptionMessage()}");
        }
    }
}
