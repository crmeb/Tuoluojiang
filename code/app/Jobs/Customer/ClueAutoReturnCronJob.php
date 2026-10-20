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

namespace App\Jobs\Customer;

use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

/**
 * 线索自动退回.
 */
class ClueAutoReturnCronJob extends CronJob
{
    /**
     * 每60秒执行一次.
     * @return float|int
     */
    public function interval()
    {
        return 60 * 1000;
    }

    public function run(): void
    {
        ClueReturnCycleJob::dispatch();
        ClueReturnStatusJob::dispatch();
    }
}
