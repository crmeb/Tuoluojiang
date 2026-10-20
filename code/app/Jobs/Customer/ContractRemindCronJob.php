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

namespace App\Jobs\Customer;

use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

/**
 * 订单定时任务
 * 发送：订单即将到期提醒、订单今日到期提醒
 * Class ContractCronJob.
 */
class ContractRemindCronJob extends CronJob
{
    /**
     * 频率：每30s小时运行一次
     * @return int
     */
    public function interval()
    {
        return 30000;
    }

    public function run(): void
    {
        ContractRemindJob::dispatch();
    }
}
