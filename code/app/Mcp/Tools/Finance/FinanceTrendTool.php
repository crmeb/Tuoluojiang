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

namespace App\Mcp\Tools\Finance;

use App\Http\Service\Customer\PaymentService;
use App\Http\Service\Finance\BillService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

/**
 * 趋势分析.
 * dimension=performance 对齐管理端 POST client/customer/trend_statistics（PaymentService::getTrendStatistics）；
 * dimension=finance 走企业资金流水 BillService::getTrend.
 */
class FinanceTrendTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取销售业绩/回款趋势或企业资金趋势；业绩相关默认 dimension=performance（对齐管理端业绩趋势），资金流水用 dimension=finance';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'dimension' => [
                    'type'        => 'string',
                    'description' => '趋势口径：performance=客户回款/业绩趋势（默认，对齐管理端业绩统计），finance=企业资金流水趋势',
                    'enum'        => ['performance', 'finance'],
                    'default'     => 'performance',
                ],
                'time' => ['type' => 'string', 'description' => '时间范围，格式 YYYY/MM/DD-YYYY/MM/DD'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'income' => ['type' => 'integer', 'description' => 'finance 维度：收入层级'],
                'expend' => ['type' => 'integer', 'description' => 'finance 维度：支出层级'],
                'all' => ['type' => 'boolean', 'description' => 'finance 维度：是否返回完整趋势序列'],
                'cate_id' => ['type' => 'array', 'description' => 'finance 维度：账目分类ID列表'],
                'cate_ids' => ['type' => 'array', 'description' => 'finance 维度：分类ID列表别名'],
                'type' => ['type' => 'string', 'description' => 'finance 维度收支过滤：1=仅收入，0=仅支出，空=全部'],
                'user_id' => ['type' => 'array', 'description' => '人员用户ID列表，将按数据权限取交集', 'items' => ['type' => 'integer']],
                'uid' => ['type' => 'array', 'description' => '人员用户ID列表别名', 'items' => ['type' => 'integer']],
                'category_id' => ['type' => 'array', 'description' => 'performance 维度：订单/合同分类ID列表', 'items' => ['type' => 'integer']],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $dimension = (string) ($arguments['dimension'] ?? 'performance');
        if ($dimension === 'finance') {
            return $this->executeFinanceTrend($arguments);
        }

        return $this->executePerformanceTrend($arguments);
    }

    private function executePerformanceTrend(array $arguments): array
    {
        $scoped = $this->resolveScopedCustomerUserIds($arguments);
        if (! ($scoped['success'] ?? false)) {
            return $scoped['error'];
        }

        $time = $this->performanceTimeRange($arguments);
        $data = app(PaymentService::class)->getTrendStatistics(
            $time,
            $scoped['user_ids'] ?? [],
            $this->resolvePerformanceCategoryIds($arguments)
        );

        return array_merge($data, [
            '_meta' => [
                'metric'    => 'sales_performance_trend',
                'dimension' => 'performance',
                'source'    => 'PaymentService::getTrendStatistics',
                'time'      => $time,
            ],
        ]);
    }

    private function executeFinanceTrend(array $arguments): array
    {
        $requestedUserIds = (array) ($arguments['user_id'] ?? $arguments['uid'] ?? []);
        $allowedUserIds   = $this->isAdmin() ? [] : $this->getDataUids('bill_list', 1);
        $userIds          = $requestedUserIds
            ? ($allowedUserIds ? array_values(array_intersect(array_map('intval', $requestedUserIds), $allowedUserIds)) : array_map('intval', $requestedUserIds))
            : ($this->isAdmin() ? [] : ($allowedUserIds ?: [-1]));
        $cateIds = (array) ($arguments['cate_id'] ?? $arguments['cate_ids'] ?? []);
        if ($cateIds && is_array(reset($cateIds))) {
            $cateIds = array_values(array_unique(array_merge(...$cateIds)));
        }

        $time = $this->performanceTimeRange($arguments);
        $data = app(BillService::class)->getTrend(
            $time,
            $arguments['income'] ?? true,
            $arguments['expend'] ?? true,
            $arguments['all'] ?? true,
            $cateIds,
            (string) ($arguments['type'] ?? ''),
            $userIds,
            $this->getEntId()
        );

        return array_merge($this->toArray($data), [
            '_meta' => [
                'metric'    => 'finance_trend',
                'dimension' => 'finance',
                'source'    => 'BillService::getTrend',
                'time'      => $time,
            ],
        ]);
    }
}
