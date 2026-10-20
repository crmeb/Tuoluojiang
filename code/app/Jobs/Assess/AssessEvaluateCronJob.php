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

namespace App\Jobs\Assess;

use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

/**
 * 自我评价提醒和开启考核任务提醒
 * Class AssessEvaluateCronJob.
 */
class AssessEvaluateCronJob extends CronJob
{
    /**
     * 频率：每2秒运行一次
     * @return int
     */
    public function interval()
    {
        return 20000;
    }

    public function run()
    {
        AssessEvaluateJob::dispatch();
    }
}
