<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Task\customer;

use App\Http\Service\Customer\CustomerService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 客户退回公海提醒事件
 * Class CustomerReturnRemindTask.
 */
class CustomerReturnRemindTask extends Task
{
    protected int $limit = 50;

    public function __construct() {}

    public function handle(): void
    {
        $switch = (int) sys_config('return_high_seas_switch');
        if ($switch < 1) {
            return;
        }

        $customerService = app()->get(CustomerService::class);
        try {
            $customerService->uncompletedReturnRemindTimer();
        } catch (\Throwable $e) {
            Log::error('客户未成交退回提醒失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            $customerService->unfollowedReturnRemindTimer();
        } catch (\Throwable $e) {
            Log::error('客户未跟进退回提醒失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }
}
