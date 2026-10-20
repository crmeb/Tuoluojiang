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

namespace Tuoluojiang\Open\services\upload;

use Tuoluojiang\Open\services\BaseStorage;
use Illuminate\Support\Facades\Config;

/**
 * Class BaseUpload.
 */
abstract class BaseUpload extends BaseStorage
{
    /**
     * 缩略图.
     * @var string[]
     */
    protected array $thumb = ['big', 'mid', 'small'];

    /**
     * 缩略图配置.
     */
    protected array $thumbConfig = [
        'thumb_big_height'   => 800,
        'thumb_big_width'    => 800,
        'thumb_mid_height'   => 300,
        'thumb_mid_width'    => 300,
        'thumb_small_height' => 100,
        'thumb_small_width'  => 100,
    ];

    /**
     * 水印配置.
     */
    protected array $waterConfig = [
        'image_watermark_status' => 0,
        'watermark_type'         => 1,
        'watermark_image'        => '',
        'watermark_opacity'      => 0,
        'watermark_position'     => 1,
        'watermark_rotate'       => 0,
        'watermark_text'         => '',
        'watermark_text_angle'   => '',
        'watermark_text_color'   => '#000000',
        'watermark_text_size'    => '5',
        'watermark_text_font'    => '',
        'watermark_x'            => 0,
        'watermark_y'            => 0,
    ];

    /**
     * 图片信息.
     */
    protected object $fileInfo;

    /**
     * 下载图片信息.
     */
    protected $downFileInfo;

    /**
     * 要生成缩略图、水印的图片地址
     */
    protected string $filePath;

    /**
     * 验证配置.
     * @var string
     */
    protected mixed $validate;

    /**
     * 保存路径.
     */
    protected string $path = '';

    /**
     * 是否自动裁剪.
     */
    protected bool $authThumb = false;

    /**
     * 设置处理缩略图、水印图片路径.
     * @return $this
     */
    public function setFilepath(string $filePath): static
    {
        $this->filePath = str_starts_with($filePath, '.') ? substr($filePath, 1) : $filePath;
        return $this;
    }

    /**
     * 是否自动裁剪.
     * @return $this
     */
    public function setAuthThumb(bool $auth): static
    {
        $this->authThumb = $auth;
        return $this;
    }

    /**
     * 上传文件路径.
     * @return $this
     */
    public function to(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 获取文件信息.
     */
    public function getFileInfo(): object
    {
        return $this->fileInfo;
    }

    /**
     * 设置验证规则.
     * @return $this
     */
    public function validate(?array $validate = null): static
    {
        if (is_null($validate)) {
            $validate = $this->getConfig();
        }
        $this->validate = $validate;
        return $this;
    }

    /**
     * 获取上传信息.
     */
    public function getUploadInfo(): array
    {
        if (isset($this->fileInfo->filePath)) {
            $fileInfo = pathinfo($this->fileInfo->fileName);
            return [
                'name'             => $this->fileInfo->fileName,
                'real_name'        => $this->fileInfo->realName ?? '',
                'size'             => $this->fileInfo->fileSize ?? '',
                'type'             => $this->fileInfo->fileType ?? '',
                'dir'              => $this->fileInfo->filePath,
                'thumb_path'       => $this->fileInfo->filePath,
                'thumb_path_big'   => $this->fileInfo->filePathBig ?? '',
                'thumb_path_mid'   => $this->fileInfo->filePathMid ?? '',
                'thumb_path_small' => $this->fileInfo->filePathSmall ?? '',
                'thumb_path_water' => $this->fileInfo->filePathWater ?? '',
                'time'             => time(),
                'ext'              => strtolower($fileInfo['extension']),
            ];
        }
        return [];
    }

    /**
     * 获取下载信息.
     */
    public function getDownloadInfo(): array
    {
        if (isset($this->downFileInfo->downloadFilePath)) {
            if (! str_contains($this->downFileInfo->downloadFilePath, 'http')) {
                $url = request()->getHost() . $this->downFileInfo->downloadFilePath;
            } else {
                $url = $this->downFileInfo->downloadFilePath;
            }
            $headers = $this->getFileHeaders($url);
            return [
                'name'       => $this->downFileInfo->downloadFileName,
                'real_name'  => $this->downFileInfo->downloadRealName ?? '',
                'size'       => $headers['size'] ?? 0,
                'type'       => $headers['type'] ?? 'image/jpeg',
                'dir'        => $this->downFileInfo->downloadFilePath ?? '',
                'thumb_path' => $this->downFileInfo->downloadFilePath ?? '',
                'time'       => time(),
            ];
        }
        return [];
    }

    /**
     * 文件上传.
     * @return mixed
     */
    abstract public function move(string $file = 'file');

    /**
     * 文件流上传.
     * @param mixed $fileContent
     * @return mixed
     */
    abstract public function stream($fileContent, ?string $key = null);

    /**
     * 删除文件.
     * @return mixed
     */
    abstract public function delete(string $filePath);

    /**
     * 拉取空间.
     * @return mixed
     */
    abstract public function listbuckets(string $region, bool $line = false, bool $shared = false);

    /**
     * 创建空间.
     * @return mixed
     */
    abstract public function createBucket(string $name, string $region);

    /**
     * 获得区域
     * @return mixed
     */
    abstract public function getRegion();

    /**
     * 删除空间.
     * @return mixed
     */
    abstract public function deleteBucket(string $name);

    /**
     * 绑定自定义域名.
     * @return mixed
     */
    abstract public function bindDomian(string $name, string $domain, ?string $region = null);

    /**
     * 设置跨域
     * @return mixed
     */
    abstract public function setBucketCors(string $name, string $region);

    /**
     * 获取上传密钥.
     * @return mixed
     */
    abstract public function getTempKeys();

    /**
     * 获取缩略图.
     * @return mixed
     */
    abstract public function thumb(string $filePath = '');

    /**
     * 添加水印.
     * @return mixed
     */
    abstract public function water(string $filePath = '');

    protected function initialize(array $config)
    {
        $this->fileInfo    = $this->downFileInfo = new \stdClass();
        $thumbConfig       = $this->thumbConfig;
        $config['thumb']   = $config['thumb'] ?? [];
        $this->thumbConfig = $config['thumb'] ?? [];
        foreach ($config['thumb'] as $item) {
            if ($item == '' || $item == 0) {
                $this->thumbConfig = $thumbConfig;
            }
        }
        $this->waterConfig = array_merge($this->waterConfig, $config['water'] ?? []);
    }

    /**
     * 检测是否是图片.
     * @param mixed $filePath
     * @return bool
     */
    protected function checkImage($filePath)
    {
        // 获取图像信息
        $info = @getimagesize($filePath);
        // 检测图像合法性
        if ($info === false || ($info[2] === IMAGETYPE_GIF && empty($info['bits']))) {
            return false;
        }
        return true;
    }

    /**
     * 验证合法上传域名.
     * @return string
     */
    protected function checkUploadUrl(string $url)
    {
        if ($url && strstr($url, 'http') === false) {
            $url = 'http://' . $url;
        }
        return $url;
    }

    /**
     * 获取系统配置.
     * @return mixed
     */
    protected function getConfig()
    {
        $filesize = (int) sys_config('global_attach_size');
        if ($filesize > 0) {
            $config['filesize'] = $filesize * 1024 * 1024;
        } else {
            $config['filesize'] = Config::get($this->configFile . '.filesize', []);
        }
        $config['fileExt']  = Config::get($this->configFile . '.fileExt', []);
        $config['fileMime'] = sys_config('upload_mime');
        return $config;
    }

    /**
     * 验证目录是否正确.
     * @return false|string
     */
    protected function getUploadPath(string $key)
    {
        $path = ($this->path ? $this->path . '/' : '') . $key;
        if ($path && $path[0] === '/') {
            $path = substr($path, 1);
        }
        return $path;
    }

    /**
     * 提取文件名.
     * @return string
     */
    protected function saveFileName(?string $path = null, string $ext = 'jpg')
    {
        return ($path ? substr(md5($path), 0, 5) : '') . date('YmdHis') . rand(0, 9999) . '.' . $ext;
    }

    /**
     * 提取文件后缀以及之前部分.
     * @return false|string[]
     */
    protected function getFileName(string $path)
    {
        $_empty = ['', ''];
        if (! $path) {
            return $_empty;
        }
        if (strpos($path, '?')) {
            $_tarr = explode('?', $path);
            $path  = trim($_tarr[0]);
        }
        $arr = explode('.', $path);
        if (! is_array($arr) || count($arr) <= 1) {
            return $_empty;
        }
        $ext_name = trim($arr[count($arr) - 1]);
        $ext_name = ! $ext_name ? 'jpg' : $ext_name;
        return [explode('.' . $ext_name, $path)[0], $ext_name];
    }

    /**
     * 获取图片地址
     * @return string
     */
    protected function getFilePath(string $filePath = '', bool $is_parse_url = false)
    {
        $path = $filePath ?: $this->filePath;
        if ($is_parse_url) {
            $data = parse_url($path);
            // 远程地址处理
            if (isset($data['host'], $data['path'])) {
                if (file_exists(app()->getRootPath() . 'public' . $data['path'])) {
                    $path = $data['path'];
                }
            }
        }
        return $path;
    }

    /**
     * 获取文件类型和大小.
     * @param bool $isData
     * @return array
     */
    protected function getFileHeaders(string $url, $isData = true)
    {
        stream_context_set_default(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $header['size'] = 0;
        $header['type'] = 'image/jpeg';
        if (! $isData) {
            return $header;
        }
        try {
            $headerArray = get_headers(str_replace('\\', '/', $url), true);
            if (! isset($headerArray['Content-Length'])) {
                $header['size'] = 0;
            }
            if (! isset($headerArray['Content-Type'])) {
                $header['type'] = 'image/jpeg';
            }
            if (is_array($headerArray['Content-Length']) && count($headerArray['Content-Length']) == 2) {
                $header['size'] = $headerArray['Content-Length'][1];
            }
            if (is_array($headerArray['Content-Type']) && count($headerArray['Content-Type']) == 2) {
                $header['type'] = $headerArray['Content-Type'][1];
            }
        } catch (\Exception $e) {
        }
        return $header;
    }

    /**
     * 实例化上传.
     * @return mixed
     */
    abstract protected function app();
}
