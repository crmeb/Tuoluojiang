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

use App\Http\Model\Customer\Contract;
use App\Http\Model\Customer\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 订单状态事件
 * Class ContractStatusTask.
 */
class ContractStatusJob implements ShouldQueue, ShouldBeUnique
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
        try {
            if (now(config('app.timezone'))->format('H') == '01') {
                $this->statusTimer();
            }
        } catch (\Throwable $e) {
            Log::error('订单状态定时更新失败：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    /**
     * 更新订单状态
     */
    protected function statusTimer(): void
    {
        $now = now(config('app.timezone'))->toDateString();
        Order::query()->where('start_date', '>', $now)->where('contract_status', '<', 2)->update(['contract_status' => '1']);
        Order::query()->where('end_date', '<', $now)->where('contract_status', '<', 3)->update(['contract_status' => '2']);
    }
}
