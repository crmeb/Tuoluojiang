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

namespace Tuoluojiang\Open\services;

use App\Http\Service\Config\SystemStorageService;
use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\services\upload\Upload;

/**
 * Class UploadService.
 */
class UploadService
{
    /**
     * @var array
     */
    protected static $upload = [];

    /**
     * @var array
     */
    protected static $config = [];

    /**
     * @param null $type
     * @return mixed|Upload
     */
    public static function init($type = null)
    {
        if (is_null($type)) {
            $type = (int) sys_config('upload_type', 1);
        }
        if (isset(self::$upload['upload_' . $type])) {
            return self::$upload['upload_' . $type];
        }
        $type           = (int) $type;
        $config         = [];
        $storageService = app(SystemStorageService::class);
        $storage        = $storageService->get(['status' => 1]);
        switch ($type) {
            case 2:// 七牛
                $config = [
                    'accessKey'     => sys_config('qiniu_accessKey'),
                    'secretKey'     => sys_config('qiniu_secretKey'),
                    'uploadUrl'     => $storage?->domain,
                    'storageName'   => $storage?->name,
                    'storageRegion' => $storage?->region,
                ];
                break;
            case 3:// oss 阿里云
                $config = [
                    'accessKey'     => sys_config('accessKey'),
                    'secretKey'     => sys_config('secretKey'),
                    'uploadUrl'     => $storage?->domain,
                    'storageName'   => $storage?->name,
                    'storageRegion' => $storage?->region,
                ];
                break;
            case 4:// cos 腾讯云
                $config = [
                    'accessKey'     => sys_config('tengxun_accessKey'),
                    'secretKey'     => sys_config('tengxun_secretKey'),
                    'uploadUrl'     => $storage?->domain,
                    'storageName'   => $storage?->name,
                    'storageRegion' => $storage?->region,
                ];
                break;
        }
        self::$config                           = $config + ['type' => $type];
        return self::$upload['upload_' . $type] = new Upload($type, $config);
    }

    public static function uploadFile($file, $dir = null, $name = null, $isStream = false)
    {
        $upload = self::init();

        if (is_null($dir)) {
            $time = now()->toArray();
            $dir  = 'uploads/' . $time['year'] . '/' . $time['month'] . '/' . $time['day'];
        }
        if (! is_dir(public_path($dir)) && mkdir(public_path($dir), 0755, true) != true) {
            throw new UploadException('无法创建文件存储目录，请检查public目录权限');
        }
        $upload->validate()->to($dir);
        $res = $isStream ? $upload->stream($file, $name) : $upload->move($file);
        if ($res === false) {
            throw new UploadException(__($upload->getError()));
        }
        return $upload->getUploadInfo();
    }

    public static function uploadStream($file, $dir = null, $name = null)
    {
        return self::uploadFile($file, $dir, $name, true);
    }

    /**
     * 获取上传配置.
     */
    public function uploadConfig(): array
    {
        $type = (int) sys_config('upload_type', 1);
        if ($type == 2) {
            $qiniu = UploadService::init($type)->getSystem();
        }
        $config = match ($type) {
            2 => [// 七牛
                'key'    => $qiniu['key'],
                'token'  => $qiniu['token'],
                'domain' => $qiniu['domain'],
            ],
            3 => [// oss 阿里云
                'accessKey'     => sys_config('ent_accessKey'),
                'secretKey'     => sys_config('ent_secretKey'),
                'uploadUrl'     => sys_config('ent_uploadUrl'),
                'storageName'   => sys_config('ent_storage_name'),
                'storageRegion' => explode('.', sys_config('ent_storage_region'))[0],
            ],
            4 => [// cos 腾讯云
                'accessKey'     => sys_config('ent_tengxun_accessKey'),
                'secretKey'     => sys_config('ent_tengxun_secretKey'),
                'uploadUrl'     => sys_config('ent_tengxun_uploadUrl'),
                'storageName'   => sys_config('ent_tengxun_storage_name'),
                'storageRegion' => sys_config('ent_tengxun_storage_region'),
            ],
            default => [],
        };
        $config['type'] = $type;
        return $config;
    }
}
