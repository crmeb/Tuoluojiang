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

namespace App\Mcp\Tools\Order;

use App\Http\Service\Customer\OrderService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;
use Tuoluojiang\Open\utils\Date;

/**
 * 订单业绩统计，对齐管理端业绩统计中的订单指标（OrderService::performanceStatistics）.
 */
class OrderStatisticsTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取订单业绩统计（新订单数、新订单金额、未收金额，对齐管理端业绩统计）';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'time' => ['type' => 'string', 'description' => '时间范围，格式 YYYY/MM/DD-YYYY/MM/DD'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'user_id' => ['type' => 'array', 'description' => '销售人员用户ID列表，将按客户数据权限取交集', 'items' => ['type' => 'integer']],
                'uid' => ['type' => 'array', 'description' => '销售人员用户ID列表别名', 'items' => ['type' => 'integer']],
                'category_id' => ['type' => 'array', 'description' => '订单分类ID列表', 'items' => ['type' => 'integer']],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $scoped = $this->resolveScopedCustomerUserIds($arguments);
        if (! ($scoped['success'] ?? false)) {
            return $scoped['error'];
        }

        $time = $this->timeRange($arguments);
        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        $data = app(OrderService::class)->performanceStatistics(
            $searchTime,
            $scoped['user_ids'] ?? [],
            $ratioTime,
            $this->resolvePerformanceCategoryIds($arguments)
        );

        return array_merge($data, [
            '_meta' => [
                'metric' => 'order_performance',
                'source' => 'OrderService::performanceStatistics',
                'time'   => $searchTime,
            ],
        ]);
    }
}
