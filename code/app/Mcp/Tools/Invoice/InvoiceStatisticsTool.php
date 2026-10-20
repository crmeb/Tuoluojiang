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

namespace App\Mcp\Tools\Invoice;

use App\Constants\CustomEnum\InvoiceEnum as CustomerInvoiceEnum;
use App\Http\Service\Customer\InvoiceService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;

class InvoiceStatisticsTool extends BaseTool
{
    use InteractsWithService;

    public function getDescription(): string
    {
        return '获取发票、开票数量及开票金额统计';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                'contract_id' => ['type' => 'integer', 'description' => '合同ID'],
                'start_date' => ['type' => 'string', 'description' => '开始日期'],
                'end_date' => ['type' => 'string', 'description' => '结束日期'],
                'date_field' => ['type' => 'string', 'description' => '统计时间字段：real_date=实际开票日期，created_at=申请日期，bill_date=期望开票日期，默认real_date'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(InvoiceService::class);
        if (! empty($arguments['customer_id']) || ! empty($arguments['contract_id'])) {
            return $service->priceStatistics(1, (string) ($arguments['customer_id'] ?? ''), (string) ($arguments['contract_id'] ?? ''));
        }

        $where = ['entid' => 1];
        $dateField = (string) ($arguments['date_field'] ?? 'real_date');
        if (! in_array($dateField, ['real_date', 'created_at', 'bill_date'], true)) {
            $dateField = 'real_date';
        }
        if ($date = $this->dateRange($arguments)) {
            $where[$dateField] = $date;
        }

        return [
            'total'           => $service->count($where),
            'pending'         => $service->count($where + ['status' => CustomerInvoiceEnum::STATUS_AUDIT]),
            'issued'          => $service->count($where + ['status' => CustomerInvoiceEnum::STATUS_INVOICED]),
            'invalid'         => $service->count($where + ['status' => -1]),
            'total_amount'    => $service->sum($where, 'amount'),
            'issued_amount'   => $service->sum($where + ['status' => CustomerInvoiceEnum::STATUS_INVOICED], 'amount'),
            'pending_amount'  => $service->sum($where + ['status' => CustomerInvoiceEnum::STATUS_AUDIT], 'amount'),
        ];
    }
}
