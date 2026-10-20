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

namespace App\Jobs\Assess;

use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

/**
 * 定时检测考核结束期没有创建绩效的人员提醒
 * Class AssessAbnormalCronJob.
 */
class AssessAbnormalCronJob extends CronJob
{
    /**
     * 频率：每10s运行一次
     * @return int
     */
    public function interval()
    {
        return 30000;
    }

    public function run()
    {
        AssessAbnormalJob::dispatchPeriods();
    }
}
