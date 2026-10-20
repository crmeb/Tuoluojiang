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

namespace App\Http\Service\Other;

use App\Constants\CacheEnum;
use App\Http\Service\Config\SystemStorageService;
use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\upload\Upload;
use Tuoluojiang\Open\utils\DownloadImage;
use Illuminate\Support\Facades\Cache;

/**
 * Class UploadService.
 */
class UploadService
{
    /**
     * 上传驱动实例缓存（按 type 复用）.
     *
     * @var array<string, Upload>
     */
    protected static $upload = [];

    /**
     * 清空静态上传实例缓存，供 Swoole 请求间隙释放驱动持有的缓冲.
     */
    public static function resetUploadCache(): void
    {
        self::$upload = [];
    }

    /**
     * @param null $type
     * @return mixed|Upload
     */
    public static function init($type = null)
    {
        if (is_null($type)) {
            $type = (int) sys_config('upload_type', 1);
        }
        $type   = (int) $type;
        $config = [];
        switch ($type) {
            case 2:// 七牛
                $config = [
                    'accessKey' => sys_config('qiniu_accessKey'),
                    'secretKey' => sys_config('qiniu_secretKey'),
                ];
                break;
            case 3:// oss 阿里云
                $config = [
                    'accessKey' => sys_config('accessKey'),
                    'secretKey' => sys_config('secretKey'),
                ];
                break;
            case 4:// cos 腾讯云
                $config = [
                    'accessKey' => sys_config('tengxun_accessKey'),
                    'secretKey' => sys_config('tengxun_secretKey'),
                    'appid'     => sys_config('tengxun_appid'),
                ];
                break;
            case 5:// 京东云
                $config = [
                    'accessKey'     => sys_config('jd_accessKey'),
                    'secretKey'     => sys_config('jd_secretKey'),
                    'storageRegion' => sys_config('jd_storage_region'),
                ];
                break;
            case 6:// 华为云
                $config = [
                    'accessKey'     => sys_config('hw_accessKey'),
                    'secretKey'     => sys_config('hw_secretKey'),
                    'storageRegion' => sys_config('hw_storage_region'),
                ];
                break;
            case 7:// 天翼云
                $config = [
                    'accessKey' => sys_config('ty_accessKey'),
                    'secretKey' => sys_config('ty_secretKey'),
                ];
                break;
            case 1:
                break;
            default:
                throw new UploadException('您已关闭上传功能');
        }

        // 除了本地存储其他都去获取配置信息
        if ($type !== 1) {
            $make                  = app()->get(SystemStorageService::class);
            $res                   = $make->getConfig($type);
            $config['uploadUrl']   = $res['domain'];
            $config['storageName'] = $res['name'];
            if ($type !== 5) {
                $config['storageRegion'] = $res['region'];
            }
            $config['cdn'] = $res['cdn'];
        }
        $thumb = sys_more(['thumb_big_height', 'thumb_big_width', 'thumb_mid_height', 'thumb_mid_width', 'thumb_small_height', 'thumb_small_width']);
        $water = sys_more([
            'image_watermark_status',
            'watermark_type',
            'watermark_image',
            'watermark_opacity',
            'watermark_position',
            'watermark_rotate',
            'watermark_text',
            'watermark_text_angle',
            'watermark_text_color',
            'watermark_text_size',
            'watermark_x',
            'watermark_y', ]);
        $config                                 = array_merge($config, ['thumb' => $thumb], ['water' => $water]);
        return self::$upload['upload_' . $type] = new Upload($type, $config);
    }

    /**
     * 生辰缩略图水印实例化.
     * @return Upload
     */
    public static function getOssInit(string $filePath, bool $is_remote_down = false)
    {
        // 本地
        $uploadUrl = sys_config('site_url');
        if ($uploadUrl && strpos($filePath, $uploadUrl) !== false) {
            $filePath = explode($uploadUrl, $filePath)[1] ?? '';
            return self::init(1)->setFilepath($filePath);
        }
        $fileArr    = parse_url($filePath);
        $fileHost   = $fileArr['scheme'] . '://' . $fileArr['host'];
        $storageArr = Cache::tags([CacheEnum::TAG_CONFIG])->remember('storage_list', (int) sys_config('system_cache_ttl', 3600), function () {
            return app()->get(SystemStorageService::class)->selectList([], 'domain,type')->toArray();
        });
        foreach ($storageArr as $item) {
            if ($fileHost == $item['domain']) {
                return self::init($item['type'])->setFilepath($filePath);
            }
        }
        // 远程图片 下载到本地处理
        if ($is_remote_down) {
            try {
                /** @var DownloadImage $down */
                $down     = app()->get(DownloadImage::class);
                $data     = $down->path('thumb_water')->downloadImage($filePath);
                $filePath = $data['path'] ?? '';
            } catch (\Throwable $e) {
                // 下载失败 传入原地址
            }
        }
        return self::init(1)->setFilepath($filePath);
    }
}
