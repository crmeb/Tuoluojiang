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

namespace Tuoluojiang\Open\services\upload\storage;

use Tuoluojiang\Open\exceptions\AdminException;
use Tuoluojiang\Open\services\upload\BaseUpload;
use Tuoluojiang\Open\utils\DownloadImage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * 本地上传
 * Class Local.
 */
class Local extends BaseUpload
{
    /**
     * 缩略图、水印图存放位置.
     */
    public string $thumbWaterPath = 'thumb_water';

    /**
     * 默认存放路径.
     */
    protected string $defaultPath;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->defaultPath                        = Config::get('filesystems.disks.uploads.path_url');
        $this->waterConfig['watermark_text_font'] = public_path('statics/font/simsunb.ttf');
    }

    public function getTempKeys()
    {
        return [
            'upload_url' => sys_config('site_url'),
            'type'       => 'local',
            'url'        => sys_config('site_url'),
        ];
    }

    /**
     * 生成上传文件目录.
     * @param null $root
     * @param mixed $path
     */
    public function uploadDir($path, $root = null): string
    {
        if ($root === null) {
            $root = public_path('/');
        }
        return str_replace('\\', '/', $root . 'uploads/' . $path);
    }

    /**
     * 检测filepath是否是远程地址
     * @return bool
     */
    public function checkFilePathIsRemote(string $filePath)
    {
        return str_contains($filePath, 'https:') || str_contains($filePath, 'http:') || str_starts_with($filePath, '//');
    }

    /**
     * 生成与配置相关的文件名称以及路径.
     * @param string $filePath 原地址
     * @param string $toPath 保存目录
     * @param array $config 配置相关参数
     * @return string
     */
    public function createSaveFilePath(string $filePath, string $toPath, array $config = [], string $root = '/')
    {
        [$path, $ext] = $this->getFileName($filePath);
        $fileName     = md5(json_encode($config) . $filePath);
        return $this->uploadDir($toPath, $root) . '/' . $fileName . '.' . $ext;
    }

    /**
     * 文件上传.
     * @return array|bool|mixed|\StdClass
     */
    public function move(string $file = 'file', ?bool $realName = false, array $option = [])
    {
        $fileHandle = app('request')->file($file);
        if (! $fileHandle) {
            return $this->setError('上传的文件不存在');
        }
        $fileExt = $fileHandle->getClientOriginalExtension() ?: $fileHandle->extension();
        if ($this->validate) {
            //            if (! in_array($fileExt, $this->validate['fileExt'])) {
            //                return $this->setError('不合法的文件后缀');
            //            }
            if ($fileHandle->getSize() > $this->validate['filesize']) {
                return $this->setError('文件过大');
            }
            if (! in_array($fileHandle->getMimeType(), $this->validate['fileMime'])) {
                return $this->setError('不合法的文件类型：' . $fileHandle->getMimeType());
            }
        }
        // 分片上传
        $chunkTotal = $option['chunk_total'] ?? 0;
        if ($chunkTotal > 1) {
            $res = $this->chunk($fileHandle, (int) $option['chunk_index'] ?? 0, (int) $chunkTotal, (string) $option['md5'] ?? '');
            if (is_bool($res)) {
                return $res;
            }
            $fileName = $res;
        } else {
            if ($realName || $fileExt == 'md' || $fileExt == 'xmind') {
                $fileName = Storage::disk('uploads')->putFileAs($this->path, $fileHandle, $fileHandle->getClientOriginalName());
            } else {
                $fileName = Storage::disk('uploads')->putFileAs($this->path, $fileHandle, $this->saveFileName($fileHandle->getClientOriginalName(), $fileExt));
            }
        }
        if (! $fileName) {
            return $this->setError('Upload failure');
        }
        $this->fileInfo->realName = $fileHandle->getClientOriginalName();
        $this->fileInfo->fileName = basename(Storage::disk('uploads')->path($fileName));
        $this->fileInfo->fileSize = Storage::disk('uploads')->size($fileName);
        $this->fileInfo->fileType = Storage::disk('uploads')->mimeType($fileName);
        $this->fileInfo->fileExt  = $fileExt;
        $this->fileInfo->filePath = rtrim($this->defaultPath, '/') . '/' . $fileName;
        if ($this->checkImage(public_path($this->fileInfo->filePath)) && $this->authThumb && pathinfo($fileName, PATHINFO_EXTENSION) != 'ico' && pathinfo($fileName, PATHINFO_EXTENSION) != 'gif') {
            try {
                $this->thumb($this->fileInfo->filePath, $this->fileInfo->fileName);
            } catch (\Throwable $e) {
                return $this->setError($e->getMessage());
            }
        }
        return $this->fileInfo;
    }

    /**
     * 文件流上传.
     * @param mixed $fileContent
     */
    public function stream($fileContent, ?string $key = null): bool
    {
        if (! $key) {
            $key = $this->saveFileName();
        }

        $dir = $this->uploadDir($this->path);
        if (! $this->validDir($dir)) {
            return $this->setError('Failed to generate upload directory, please check the permission!');
        }

        $fileName = $dir . '/' . $key;
        file_put_contents($fileName, $fileContent);

        $this->fileInfo->fileSize = File::size($fileName);
        $this->fileInfo->fileType = File::mimeType($fileName);
        $this->fileInfo->realName = $key;
        $this->fileInfo->fileName = $key;
        $this->fileInfo->filePath = Config::get('filesystems.disks.uploads.path_url') . $this->path . DIRECTORY_SEPARATOR . $key;
        if ($this->checkImage(public_path($this->fileInfo->filePath)) && $this->authThumb && pathinfo($fileName, PATHINFO_EXTENSION) != 'ico' && pathinfo($fileName, PATHINFO_EXTENSION) != 'gif') {
            try {
                $this->thumb($this->fileInfo->filePath, $this->fileInfo->fileName);
            } catch (\Throwable $e) {
                return $this->setError($e->getMessage());
            }
        }
        return true;
    }

    /**
     * 文件流下载保存图片.
     * @return array|bool|mixed|\StdClass
     */
    public function down(string $fileContent, ?string $key = null)
    {
        if (! $key) {
            $key = $this->saveFileName();
        }
        $dir = $this->uploadDir($this->path);
        if (! $this->validDir($dir)) {
            return $this->setError('Failed to generate upload directory, please check the permission!');
        }
        $fileName = $dir . '/' . $key;
        file_put_contents($fileName, $fileContent);
        $this->fileInfo->fileSize             = File::size($fileName);
        $this->fileInfo->fileType             = File::mimeType($fileName);
        $this->downFileInfo->downloadRealName = $key;
        $this->downFileInfo->downloadFileName = $key;
        $this->downFileInfo->downloadFilePath = $this->defaultPath . '/' . $this->path . '/' . $key;
        return $this->downFileInfo;
    }

    /**
     * 生成缩略图.
     * @return array|mixed|string[]
     */
    public function thumb(string $filePath = '', string $fileName = '', string $type = 'all')
    {
        $config                      = $this->thumbConfig;
        $data                        = ['big' => $filePath, 'mid' => $filePath, 'small' => $filePath];
        $this->fileInfo->filePathBig = $this->fileInfo->filePathMid = $this->fileInfo->filePathSmall = $this->fileInfo->filePathWater = $filePath;
        // 地址存在且不是远程地址
        $filePath = str_replace(sys_config('site_url'), '', $filePath);
        if ($filePath && ! $this->checkFilePathIsRemote($filePath)) {
            $findPath = str_replace($fileName, $type . '_' . $fileName, $filePath);
            if (file_exists('.' . $findPath)) {
                return [
                    'big'   => str_replace($fileName, 'big_' . $fileName, $filePath),
                    'mid'   => str_replace($fileName, 'mid_' . $fileName, $filePath),
                    'small' => str_replace($fileName, 'small_' . $fileName, $filePath),
                ];
            }
            try {
                $this->water($filePath);
                foreach ($this->thumb as $v) {
                    if ($type == 'all' || $type == $v) {
                        $height   = 'thumb_' . $v . '_height';
                        $width    = 'thumb_' . $v . '_width';
                        $savePath = str_replace($fileName, $v . '_' . $fileName, $filePath);
                        // 防止重复生成
                        if (! file_exists('.' . $savePath)) {
                            //                            Image::make(public_path($filePath))->fit($config[$width], $config[$height])->save(public_path($savePath));
                            Image::make(public_path($filePath))->resize($config[$width], null, function ($constraint) {
                                $constraint->aspectRatio();
                            })->save(public_path($savePath));
                        }
                        $key      = 'filePath' . ucfirst($v);
                        $data[$v] = $this->fileInfo->{$key} = $savePath;
                    }
                }
            } catch (\Throwable $e) {
                Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
                throw new AdminException($e->getMessage());
            }
        }
        return $data;
    }

    /**
     * 添加水印.
     * @return mixed|string
     */
    public function water(string $filePath = '')
    {
        $waterConfig = $this->waterConfig;
        if ($waterConfig['image_watermark_status'] && $filePath) {
            switch ($waterConfig['watermark_type']) {
                case 1:
                    if ($waterConfig['watermark_image']) {
                        $filePath = $this->image($filePath, $waterConfig);
                    }
                    break;
                case 2:
                    $filePath = $this->text($filePath, $waterConfig);
                    break;
            }
        }
        return $filePath;
    }

    /**
     * 图片水印.
     * @return string
     */
    public function image(string $filePath, array $waterConfig = [])
    {
        if (! $waterConfig) {
            $waterConfig = $this->waterConfig;
        }
        $watermark_image = $waterConfig['watermark_image'];
        // 远程图片
        $filePath = str_replace(sys_config('site_url'), '', $filePath);
        if ($watermark_image && $this->checkFilePathIsRemote($watermark_image)) {
            // 看是否在本地
            $pathName = $this->getFilePath($watermark_image, true);
            if ($pathName == $watermark_image) {// 不再本地  继续下载
                [$p, $e]         = $this->getFileName($watermark_image);
                $name            = 'water_image_' . md5($watermark_image) . '.' . $e;
                $watermark_image = '.' . $this->defaultPath . '/' . $this->thumbWaterPath . '/' . $name;
                if (! file_exists($watermark_image)) {
                    try {
                        $data            = app()->get(DownloadImage::class)->path($this->thumbWaterPath)->downloadImage($waterConfig['watermark_image'], $name);
                        $watermark_image = $data['path'] ?? '';
                    } catch (\Throwable $e) {
                        throw new AdminException(400724);
                    }
                }
            } else {
                $watermark_image = '.' . $pathName;
            }
        }
        if (! $watermark_image) {
            throw new AdminException('请先配置水印图片');
        }
        $savePath = public_path($filePath);
        try {
            Image::make(public_path($filePath))->insert($watermark_image, $waterConfig['watermark_position'] ?: 1, (int) $waterConfig['watermark_opacity'])->save($savePath);
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
        return $savePath;
    }

    /**
     * 文字水印.
     * @return string
     */
    public function text(string $filePath, array $waterConfig = [])
    {
        if (! $waterConfig) {
            $waterConfig = $this->waterConfig;
        }
        if (! $waterConfig['watermark_text']) {
            throw new AdminException('请先配置水印文字');
        }
        $savePath = public_path() . $filePath;
        try {
            $Image = Image::make(public_path($filePath));
            if (strlen($waterConfig['watermark_text_color']) < 7) {
                $waterConfig['watermark_text_color'] = substr($waterConfig['watermark_text_color'], 1);
                $waterConfig['watermark_text_color'] = '#' . $waterConfig['watermark_text_color'] . $waterConfig['watermark_text_color'];
            }
            if (strlen($waterConfig['watermark_text_color']) > 7) {
                $waterConfig['watermark_text_color'] = substr($waterConfig['watermark_text_color'], 0, 7);
            }
            $Image->text($waterConfig['watermark_text'], $waterConfig['watermark_text_font'], (int) $waterConfig['watermark_text_size'], $waterConfig['watermark_text_color'], $waterConfig['watermark_position'], [$waterConfig['watermark_x'], $waterConfig['watermark_y'], $waterConfig['watermark_text_angle']])->save($savePath);
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage() . $e->getLine());
        }
        return $savePath;
    }

    /**
     * 删除文件.
     * @return bool|mixed
     */
    public function delete(string $filePath)
    {
        $siteUrl = sys_config('site_url', config('app.url'));
        if (str_contains($filePath, $siteUrl)) {
            $filePath = str_replace($siteUrl, 'public', $filePath);
        } else {
            $filePath = 'public' . $filePath;
        }
        if (file_exists($filePath)) {
            try {
                $fileArr  = explode('/', $filePath);
                $fileName = end($fileArr);
                unlink($filePath);
                unlink(str_replace($fileName, 'big_' . $fileName, $filePath));
                unlink(str_replace($fileName, 'mid_' . $fileName, $filePath));
                unlink(str_replace($fileName, 'small_' . $fileName, $filePath));
                return true;
            } catch (\Exception $e) {
                return $this->setError($e->getMessage());
            }
        }
        return false;
    }

    public function listbuckets(string $region, bool $line = false, bool $shared = false)
    {
        return [];
    }

    public function createBucket(string $name, string $region)
    {
        return null;
    }

    public function deleteBucket(string $name)
    {
        return null;
    }

    public function setBucketCors(string $name, string $region)
    {
        return true;
    }

    public function getRegion()
    {
        return [];
    }

    public function bindDomian(string $name, string $domain, ?string $region = null)
    {
        return true;
    }

    public function getDomian(string $name, ?string $region = null)
    {
        return [];
    }

    /**
     * 检查上传目录不存在则生成.
     * @param mixed $dir
     */
    protected function validDir($dir): bool
    {
        return ! (! is_dir($dir) && ! mkdir($dir, 0755, true));
    }

    protected function app()
    {
        // TODO: Implement app() method.
    }

    /**
     * 分片上传.
     */
    private function chunk(mixed $fileHandle, int $chunkIndex, int $chunkTotal, string $md5 = ''): bool|string
    {
        try {
            $filename = $fileHandle->getClientOriginalName();
            $upPath   = public_path('uploads' . DIRECTORY_SEPARATOR . trim($this->path, '/'));
            $filepath = $upPath . DIRECTORY_SEPARATOR . $filename;
            is_dir($upPath) || mkdir($upPath, 0755, true) || throw new AdminException('创建文件失败');
            if (! rename($fileHandle->getRealPath(), $filepath . '__' . $chunkIndex)) {
                throw new AdminException('上传分片文件失败');
            }
            if ($this->countChunk($upPath, $filename) === $chunkTotal) {
                $data = '';
                for ($i = 0; $i < $chunkTotal; ++$i) {
                    $data .= file_get_contents($filepath . '__' . $i);
                }
                if (! file_put_contents($filepath, $data)) {
                    throw new AdminException('上传文件失败');
                }
                for ($i = 0; $i < $chunkTotal; ++$i) {
                    @unlink($filepath . '__' . $i);
                }
                if ($md5 && md5_file($filepath) !== $md5) {
                    throw new AdminException('校验文件失败');
                }
                $fileName = trim($this->path, '/') . DIRECTORY_SEPARATOR . $filename;
            } else {
                return true;
            }
            return $fileName;
        } catch (\Throwable $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 计算分片数量.
     */
    private function countChunk(string $path, string $name): int
    {
        if (! is_dir($path)) {
            return 0;
        }
        $files = glob($path . DIRECTORY_SEPARATOR . $name . '__*');
        if (! $files) {
            return 0;
        }

        return count(array_unique(array_filter($files, function ($item) {
            return is_file($item);
        })));
    }
}
