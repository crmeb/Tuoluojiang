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

namespace App\Jobs\WorkExternalContact;

use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

/**
 * 定时获取群发任务结果.
 */
class WorkMessagingResultCronJob extends CronJob
{
    public function interval()
    {
        return 45 * 1000;
    }

    public function run()
    {
        if (!sys_config('wechat_work_corpid')){
            return true;
        }
        WorkMessagingResultJob::dispatch();
    }
}
