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

namespace App\Task\crud;

use App\Http\Service\Crud\SystemCrudEventService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 实体触发器事件
 * Class CrudEventTask.
 * @deprecated
 */
class CrudEventTask extends Task
{
    protected $i = 0;

    public function __construct() {}

    public function handle(): void
    {
        try {
            app()->get(SystemCrudEventService::class)->runTimerEvent();
        } catch (\Throwable $e) {
            Log::error('实体触发器执行定时任务报错：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }
}
