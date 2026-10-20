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

namespace App\Listeners;

use App\Http\Service\Other\UploadService;
use App\Support\YihaotongFactory;
use Tuoluojiang\Open\socket\Room;
use Tuoluojiang\Open\utils\Task;
use Hhxsv5\LaravelS\Illuminate\Cleaners\BaseCleaner;
use Hhxsv5\LaravelS\Illuminate\Cleaners\CleanerInterface;

/**
 * 清理业务静态缓存，避免 Swoole 常驻进程跨请求残留.
 *
 * 通过各组件显式 reset API 重置，不再使用无效的 Predis 反射清理。
 */
class StaticCleaner extends BaseCleaner implements CleanerInterface
{
    /**
     * 重置 Upload / Task / Room / 一号通 Factory 静态状态.
     */
    public function clean(): void
    {
        UploadService::resetUploadCache();
        Task::resetInstance();
        Room::resetRedisHandler();
        YihaotongFactory::reset();
    }
}
