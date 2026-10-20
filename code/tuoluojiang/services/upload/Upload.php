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

namespace Tuoluojiang\Open\services\upload;

use Tuoluojiang\Open\services\BaseManager;
use Illuminate\Support\Facades\Config;

/**
 * Class Upload.
 * @mixin \Tuoluojiang\Open\services\upload\storage\Local
 * @mixin \Tuoluojiang\Open\services\upload\storage\OSS
 * @mixin \Tuoluojiang\Open\services\upload\storage\Qiniu
 * @mixin \Tuoluojiang\Open\services\upload\storage\Jdoss
 * @mixin \Tuoluojiang\Open\services\upload\storage\Tyoss
 * @mixin \Tuoluojiang\Open\services\upload\storage\Cos
 * @mixin \Tuoluojiang\Open\services\upload\storage\Obs
 */
class Upload extends BaseManager
{
    /**
     * 空间名.
     * @var string
     */
    protected $namespace = '\Tuoluojiang\Open\services\upload\storage\\';

    /**
     * 设置默认上传类型.
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        return Config::get('upload.default', 'local');
    }
}
