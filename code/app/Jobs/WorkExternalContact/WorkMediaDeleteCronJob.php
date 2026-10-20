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

use App\Http\Service\WorkExternalContact\WorkMediaService;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

/**
 * 素材删除定时任务
 */
class WorkMediaDeleteCronJob extends CronJob
{
    /**
     * 频率：每5m运行一次
     * @return int
     */
    public function interval()
    {
        return 300000;
    }

    public function run()
    {
        if (!sys_config('wechat_work_corpid')){
            return true;
        }
        app()->get(WorkMediaService::class)->deleteUnlinkMedias();
    }
}
