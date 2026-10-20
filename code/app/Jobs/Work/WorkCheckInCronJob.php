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

namespace App\Jobs\Work;

use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

/**
 * 同步考勤数据.
 */
class WorkCheckInCronJob extends CronJob
{
    /**
     * 频率：每100s运行一次
     */
    public function interval()
    {
        return 1000 * 100;
    }

    public function run()
    {
        WorkCheckInJob::dispatch(now()->subMinutes(2), now());
    }
}
