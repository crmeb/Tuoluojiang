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

use App\Constants\System\ConfigEnum;
use App\Http\Service\Customer\LeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 线索退回线索池提醒事件.
 */
class ClueReturnRemindJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    public $uniqueFor = 300;

    public function __construct() {}

    public function handle(): void
    {
        $switch = (int) sys_config(ConfigEnum::RETURN_CLUE_SWITCH['key']);
        if ($switch < 1) {
            return;
        }
        $customerClueService = app(LeadService::class);
        try {
            $customerClueService->unCustomerAndReturnRemind();
        } catch (\Throwable $e) {
            Log::error('线索未成交退回提醒失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            $customerClueService->unFollowedAndReturnRemind();
        } catch (\Throwable $e) {
            Log::error('线索未跟进退回提醒失败:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }
}
