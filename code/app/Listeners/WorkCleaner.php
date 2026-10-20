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

namespace App\Listeners;

use Tuoluojiang\Open\services\wechat\config\HttpCommonConfig;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Tuoluojiang\Open\services\wechat\Work;
use Hhxsv5\LaravelS\Illuminate\Cleaners\BaseCleaner;
use Hhxsv5\LaravelS\Illuminate\Cleaners\CleanerInterface;
use Illuminate\Support\Facades\Facade;

/**
 * Class WorkCleaner.
 */
class WorkCleaner extends BaseCleaner implements CleanerInterface
{
    protected $instances = [
        HttpCommonConfig::class,
        Work::class,
        WorkConfig::class,
    ];

    public function clean()
    {
        foreach ($this->instances as $instance) {
            $this->currentApp->forgetInstance($instance);
            Facade::clearResolvedInstance($instance);
        }
    }
}
