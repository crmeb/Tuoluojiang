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

namespace App\Task\customer;

use App\Http\Service\Customer\PaymentService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

/**
 * 订单续费提醒事件
 * Class ContractRenewRemindTask.
 */
class ContractRenewRemindTask extends Task
{
    protected int $limit = 50;

    public function __construct() {}

    public function handle(): void
    {
        try {
            $clientService = app()->get(PaymentService::class);
            $entList       = $clientService->getEntCacheList();
            if (! $entList) {
                return;
            }
            foreach ($entList as $item) {
                $sumCount  = $clientService->clientBillCountCache((int) $item['entid']);
                $pageCount = ceil($sumCount / $this->limit);
                for ($i = 1; $i <= $pageCount; ++$i) {
                    $clientService->timer((int)$item['entid'], $i, $this->limit);
                }
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
