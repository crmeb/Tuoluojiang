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

namespace App\Task\financial;

use App\Http\Service\Customer\PaymentService;
use App\Http\Service\Finance\BillService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 客户账目记录生成财务记录.
 */
class FinancialRecordTask extends Task
{
    /**
     * @var BillService|mixed
     */
    private mixed $billService;

    /**
     * @var PaymentService|mixed
     */
    private mixed $service;

    /**
     * Create a new job instance.
     * @param mixed $clientBillId
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected $clientBillId)
    {
        $this->service     = app()->get(PaymentService::class);
        $this->billService = app()->get(BillService::class);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $info = $this->service->get($this->clientBillId);
            if (! $info) {
                return true;
            }
            $this->billService->create([
                'entid'     => $info->entid,
                'uid'       => uid_to_uuid((int) $info->uid),
                'user_id'   => (int) $info->uid,
                'cate_id'   => $info->bill_cate_id,
                'num'       => $info->num,
                'edit_time' => $info->date,
                'types'     => $info->bill_types,
                'type_id'   => $info->type_id,
                'pay_type'  => $info->pay_type,
                'link_id'   => $info->id,
                'link_cate' => 'client',
            ]);
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
