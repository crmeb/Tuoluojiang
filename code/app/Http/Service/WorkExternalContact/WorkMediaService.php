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

namespace App\Http\Service\WorkExternalContact;

use App\Constants\CacheEnum;
use App\Http\Dao\WorkExternalContact\WorkMediaDao;
use App\Http\Service\Other\UploadService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\wechat\Work;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 素材.
 */
class WorkMediaService extends BaseService
{
    private const WORK_MEDIA_PERMANENT_FAILURE_CODES = [
        40004, // 不合法的媒体文件类型
        40006, // 不合法的文件大小
        40009, // 不合法的图片文件大小
        40011, // 不合法的视频文件大小
        40123, // 上传临时图片素材，图片格式非法
        41080, // 附件资源大小超过限制
        41081, // 附件资源的图片分辨率超过限制
        41082, // 附件资源的视频时长超过限制
        41090, // 视频格式不合法
        44001, // 多媒体文件为空
        45001, // 多媒体文件大小超过限制
        45007, // 语音播放时间超过限制
    ];

    private const WORK_MEDIA_PERMANENT_FAILURE_MESSAGES = [
        'Unable to open path',
    ];

    public function __construct(WorkMediaDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 删除素材.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteMedia(array|string $ids): void
    {
        $ids   = is_string($ids) ? explode(',', $ids) : $ids;
        $files = $this->dao->select(['id' => $ids])?->toArray();
        foreach ($files as $file) {
            try {
                $upload = UploadService::init($file['up_type']);
                if ($file['up_type'] == 1 && ! str_starts_with($file['file_url'], '/')) {
                    $file['file_url'] = '/' . $file['file_url'];
                }
                $upload->delete($file['file_url']);
            } catch (\Throwable) {
            }
            $this->dao->delete(['id' => (int) $file['id']]);
        }
    }

    /**
     * 删除未关联的素材.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteUnlinkMedias(): void
    {
        $files = $this->dao->select(['link_id' => 0], ['id', 'up_type', 'file_url']);
        foreach ($files as $file) {
            try {
                $upload = UploadService::init($file->up_type);
                if ($file->up_type == 1 && ! str_starts_with($file->file_url, '/')) {
                    $file->file_url = '/' . $file->file_url;
                }
                $upload->delete($file->file_url);
            } catch (\Throwable) {
            }
            $file->delete(['id' => (int) $file['id']]);
        }
    }

    /**
     * 素材上传.
     * @return array|true
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function mediaUpload(string $file, int $uid, array $options = []): array|bool
    {
        $upload_type = sys_config('upload_type', 1) ?? 1;
        try {
            $path   = $this->make_path('media', 2, true);
            $upload = UploadService::init($upload_type);
            $res    = $upload->to($path)->validate()->move($file, false, $options);
            if (is_bool($res)) {
                $res === false && throw new UploadException($upload->getError());
                return true;
            }
            $fileInfo = $upload->getUploadInfo();
            $fileType = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
            if ($fileInfo) {
                $data['file_name'] = $fileInfo['name'];
                $data['real_name'] = $fileInfo['real_name'];
                $data['file_url']  = $fileInfo['dir'];
                $data['file_size'] = $fileInfo['size'];
                $data['file_type'] = $fileInfo['type'];
                $data['file_md5']  = md5_file(public_path($fileInfo['dir'])) ?? '';
                $data['file_ext']  = $fileType;
                $data['up_type']   = $upload_type;
                $data['uid']       = $uid;
                $model             = $this->dao->create($data);
                $this->id          = $model->id;
            }
            $id = $model->id ?? 0;
            return ['src' => link_file($model->file_url), 'url' => link_file($model->file_url), 'id' => $id, 'size' => $data['file_size'], 'name' => $data['real_name']];
        } catch (\Throwable $e) {
            throw new UploadException($e->getMessage());
        }
    }

    /**
     * 前端上传保存信息.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function mediaSave(array $fileInfo, int $uid): array
    {
        $fileType          = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $data['file_name'] = $fileInfo['name'];
        $data['real_name'] = $fileInfo['name'];
        $data['file_url']  = $fileInfo['url'];
        $data['file_size'] = $fileInfo['size'];
        $data['file_type'] = $fileInfo['type'];
        $data['file_md5']  = $fileInfo['md5'] ?? '';
        $data['file_ext']  = $fileType;
        $data['up_type']   = (int) sys_config('upload_type', 1);
        $data['uid']       = $uid;
        $model             = $this->dao->create($data);
        return [
            'src'       => $data['file_url'],
            'url'       => $data['file_url'],
            'attach_id' => $model->id,
            'id'        => $model->id,
            'size'      => $data['file_size'],
            'name'      => $data['real_name'],
        ];
    }

    /**
     * 通过URL上传素材.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function mediaUploadByUrl(string $url, int $uid): array
    {
        $upload_type = sys_config('upload_type', 1) ?? 1;
        $path        = $this->make_path('media', 2, true);

        // 使用 cURL 下载远程文件
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            throw new UploadException('远程文件下载失败: ' . ($error ?: 'HTTP ' . $httpCode));
        }

        // 获取文件扩展名
        $extInfo = pathinfo(parse_url($url, PHP_URL_PATH));
        $ext = $extInfo['extension'] ?? 'jpg';
        $filename = md5($url) . '.' . $ext;

        try {
            $upload = UploadService::init($upload_type);
            $upload->to($path);
            $res = $upload->stream($content, $filename);

            if ($res === false) {
                throw new UploadException($upload->getError() ?? '文件保存失败');
            }

            $fileInfo = pathinfo($filename);

            $data = [
                'file_name' => $filename,
                'real_name' => $filename,
                'file_url'  => $upload->getUploadInfo()['dir'] ?? $path . '/' . $filename,
                'file_size' => strlen($content),
                'file_type' => $this->getMimeType($ext),
                'file_md5'  => md5($content),
                'file_ext'  => $ext,
                'up_type'   => $upload_type,
                'uid'       => $uid,
            ];

            $model = $this->dao->create($data);

            return [
                'src'  => link_file($model->file_url),
                'url'  => link_file($model->file_url),
                'id'   => $model->id,
                'size' => $data['file_size'],
                'name' => $data['real_name'],
            ];
        } catch (\Throwable $e) {
            throw new UploadException($e->getMessage());
        }
    }

    /**
     * 获取MIME类型.
     */
    private function getMimeType(string $ext): string
    {
        return match (strtolower($ext)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }

    /**
     * 删除关联.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteLink(int $linkId, string $linkType): bool
    {
        $fileIds = $this->dao->column(['link_id' => $linkId, 'link_type' => $linkType], 'id');
        $fileIds && $this->deleteMedia($fileIds);
        return true;
    }

    /**
     * 设置关联.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setLink(array|int|string $fileIds, int $linkId, string $linkType): int
    {
        $fileIds    = is_array($fileIds) ? $fileIds : [$fileIds];
        $oldFileIds = $this->dao->column(['link_id' => $linkId, 'link_type' => $linkType], 'id');
        $oldFileIds && $this->deleteMedia(array_diff($oldFileIds, $fileIds));
        $files = $this->dao->select(['id' => $fileIds]);
        foreach ($files as $file) {
            $file->link_id   = $linkId;
            $file->link_type = $linkType;
            $file->save();
        }
        return count($files);
    }

    /**
     * 上传素材到企微(大文件分片).
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws TransportExceptionInterface
     */
    public function uploadToWork(object $file, Work $work)
    {
        try {
            $failId = Cache::tags([CacheEnum::TAG_MEDIA])->get('work_media_fail_id', []);
            if ($file['file_size'] > 20 * 1024 * 1024) {
                if ($file['up_type'] == 1) {
                    $filePath = $file['file_url'];
                    $md5      = is_file($filePath) ? md5_file($filePath) : '';
                } else {
                    $storageService = UploadService::init($file['up_type']);
                    $metaInfo       = $storageService->getMate($file['file_url']);
                    $md5Map         = [
                        2 => $metaInfo['md5'] ?? '',
                        3 => trim($metaInfo['etag'], '"') ?? '',
                        4 => isset($metaInfo['ETag']) ? substr($metaInfo['ETag'], 0, 32) : '',
                        5 => $metaInfo['ETag'] ?? '',
                        6 => $metaInfo['ETag'] ?? '',
                    ];
                    $md5 = $md5Map[(int) $file['up_type']] ?? '';
                }
                $result = $work->mediaUploadUrl($this->getFileType($file['file_type']), $file['file_name'], $file['file_url'], $md5);
                if (isset($result['jobid'])) {
                    $this->dao->update(['id' => $file['id']], ['job_id' => $result['jobid']]);
                } else {
                    Cache::tags([CacheEnum::TAG_MEDIA])->put('work_media_fail_id', array_merge($failId, [$file['id']]), 1800);
                    Log::error('企微分片上传临时素材失败,结果:', $result);
                }
            } else {
                $result = $work->mediaUpload($file['file_url'], $this->getFileType($file['file_type']));
                if ($this->isWorkMediaUploadSuccess($result)) {
                    $this->dao->update(['id' => $file['id']], ['media_id' => $result['media_id'], 'media_type' => $result['type'], 'fail_time' => now()->addDays(3)->toDateTimeString()]);
                } else {
                    $update = ['media_msg' => $result['errmsg'] ?? '请求超时'];
                    if ($this->isPermanentWorkMediaFailure($result)) {
                        $update['fail_time'] = Carbon::now()->addYears(10)->toDateTimeString();
                    } else {
                        Cache::tags([CacheEnum::TAG_MEDIA])->put('work_media_fail_id', array_merge($failId, [$file['id']]), 1800);
                    }
                    $this->dao->update(['id' => $file['id']], $update);
                    Log::error('企微上传临时素材失败,结果:', $result);
                }
                $attach = $work->attachUpload($file['file_url'], $this->getFileType($file['file_type']));
                if ($this->isWorkMediaUploadSuccess($attach)) {
                    $this->dao->update(['id' => $file['id']], ['attach_id' => $attach['media_id'], 'media_type' => $attach['type'], 'attach_fail' => now()->addDays(3)->toDateTimeString()]);
                } else {
                    $this->dao->update(['id' => $file['id']], ['attach_msg' => $attach['errmsg'] ?? '请求超时']);
                    Log::error('企微上传临时附件失败,结果:', $attach);
                }
            }
        } catch (\Exception $e) {
            Log::error('企微上传临时素材失败,结果:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 获取分片上传素材文件信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFileInfo(string $jobId): void
    {
        $files = $this->dao->select(['job_id' => $jobId ?: true], ['id', 'job_id'])?->toArray();
        $work  = app()->get(Work::class);
        foreach ($files as $file) {
            $result = $work->mediaUploadResult($file['job_id']);
            if (isset($result['detail'], $result['detail']['media_id'])) {
                $this->dao->update(['id' => $file['id']], [
                    'job_id'    => '',
                    'media_id'  => $result['detail']['media_id'],
                    'fail_time' => Carbon::make(date('Y-m-d H:i:s', $result['detail']['created_at']))->addDays(3)->toDateTimeString(),
                ]);
            } else {
                Log::error('企微分片上传临时素材失败,上传结果:', $result);
            }
        }
    }

    /**
     * 上传路径转化,默认路径.
     * @param mixed $path
     * @return string
     * @throws \Exception
     */
    protected function make_path($path, int $type = 2, bool $force = false)
    {
        $path = DIRECTORY_SEPARATOR . ltrim(rtrim($path));
        $path .= match ($type) {
            1       => DIRECTORY_SEPARATOR . date('Y'),
            2       => DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m'),
            3       => DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d'),
            default => '',
        };
        try {
            if (is_dir(public_path('uploads') . $path) || mkdir(public_path('uploads') . $path, 0755, true)) {
                return trim(str_replace(DIRECTORY_SEPARATOR, '/', $path), '.');
            }
            return '';
        } catch (\Exception $e) {
            if ($force) {
                throw new \Exception($e->getMessage());
            }
            return '无法创建文件夹，请检查您的上传目录权限：' . public_path('uploads') . $path;
        }
    }

    /**
     * 获取文件类型.
     * @return string
     */
    private function getFileType(string $mimeType)
    {
        // 分割 MIME 类型，取第一个部分（前缀）
        $typeParts   = explode('/', $mimeType, 2); // 限制分割为2部分，避免特殊类型如 "multipart/form-data" 出错
        $primaryType = $typeParts[0] ?? '';
        // 根据前缀判断类别
        return match ($primaryType) {
            'image' => 'image',
            'video' => 'video',
            'audio' => 'voice',
            default => 'file',
        };
    }

    private function isWorkMediaUploadSuccess(array $result): bool
    {
        return (int) ($result['errcode'] ?? -1) === 0 && ! empty($result['media_id']) && ! empty($result['type']);
    }

    private function isPermanentWorkMediaFailure(array $result): bool
    {
        if (in_array((int) ($result['errcode'] ?? -1), self::WORK_MEDIA_PERMANENT_FAILURE_CODES, true)) {
            return true;
        }

        foreach (self::WORK_MEDIA_PERMANENT_FAILURE_MESSAGES as $message) {
            if (str_contains($result['errmsg'] ?? '', $message)) {
                return true;
            }
        }

        return false;
    }
}
