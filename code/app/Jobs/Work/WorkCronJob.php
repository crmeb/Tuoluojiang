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

namespace App\Jobs\Work;

use App\Http\Service\Work\WorkClientService;
use App\Http\Service\Work\WorkDepartmentService;
use App\Http\Service\Work\WorkMemberService;
use Tuoluojiang\Open\services\wechat\config\WorkConfig;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

class WorkCronJob extends CronJob
{
    /**
     * 频率：每120秒运行一次
     * @return int
     */
    public function interval()
    {
        return 120000;
    }

    public function run(): void
    {
        if (sys_config('wechat_work_user_switch')) {
            if (! app()->get(WorkConfig::class)->getCorpId()) {
                return;
            }

            // 同步部门
            app()->get(WorkDepartmentService::class)->authDepartment(false);

            app()->get(WorkMemberService::class)->authUpdataMemberV1();

            if (sys_config('wechat_work_client_switch')) {
                // 同步客户
                //                app()->get(WorkClientService::class)->authGetExternalcontact();
            }
        }
    }
}
