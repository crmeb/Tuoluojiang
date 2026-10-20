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

use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Finance\BillService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

/**
 * 资金分类占比 / 人员业绩排行 / 部门业绩排行.
 * personnel 对齐管理端 POST client/customer/ranking；
 * frame 对齐管理端 POST client/customer/frame_ranking；
 * category 走 BillService::getRankAnalysis.
 */
class FinanceRankAnalysisTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取资金分类占比、人员销售业绩排行或部门业绩排行；人员/部门业绩对齐管理端业绩统计排行接口';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'dimension' => [
                    'type'        => 'string',
                    'description' => '分析维度：category=账目分类占比（默认），personnel=人员销售业绩排行，frame=部门业绩排行',
                    'enum'        => ['category', 'personnel', 'frame'],
                    'default'     => 'category',
                ],
                'page' => ['type' => 'integer', 'description' => '人员排行页码，默认1', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '人员排行每页数量，默认20，最大100', 'default' => 20],
                'time' => ['type' => 'string', 'description' => '时间范围，格式 YYYY/MM/DD-YYYY/MM/DD；空则人员/部门排行按当月'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'cate_id' => ['type' => 'integer', 'description' => '分类ID'],
                'types' => ['type' => 'integer', 'description' => '账目分类维度的收支类型；personnel/frame 固定按销售回款业绩统计'],
                'cate_ids' => ['type' => 'array', 'description' => '分类ID列表'],
                'user_id' => ['type' => 'array', 'description' => 'category 维度为创建人ID，personnel/frame 维度为销售人员ID，将自动按数据权限取交集', 'items' => ['type' => 'integer']],
                'uid' => ['type' => 'array', 'description' => '人员用户ID列表别名', 'items' => ['type' => 'integer']],
                'category_id' => ['type' => 'array', 'description' => '人员/部门业绩使用的订单分类ID列表', 'items' => ['type' => 'integer']],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $dimension = (string) ($arguments['dimension'] ?? 'category');
        if ($dimension === 'personnel') {
            return $this->executePersonnelRank($arguments);
        }
        if ($dimension === 'frame') {
            return $this->executeFrameRank($arguments);
        }

        $service = app(BillService::class);
        $requestedUserIds = (array) ($arguments['user_id'] ?? []);
        $allowedUserIds   = $this->isAdmin() ? [] : $this->getDataUids('bill_list', 1);
        $userIds          = $requestedUserIds
            ? ($allowedUserIds ? array_values(array_intersect(array_map('intval', $requestedUserIds), $allowedUserIds)) : array_map('intval', $requestedUserIds))
            : ($this->isAdmin() ? [] : ($allowedUserIds ?: [-1]));
        $cateIds = (array) ($arguments['cate_ids'] ?? []);
        if ($cateIds && is_array(reset($cateIds))) {
            $cateIds = array_values(array_unique(array_merge(...$cateIds)));
        }

        return $service->getRankAnalysis(
            $this->performanceTimeRange($arguments),
            (int) ($arguments['cate_id'] ?? 0),
            (int) ($arguments['types'] ?? 0),
            $cateIds,
            $userIds
        );
    }

    private function executePersonnelRank(array $arguments): array
    {
        [$page, $limit] = $this->applyPage($arguments);
        $scoped = $this->resolveScopedCustomerUserIds($arguments);
        if (! ($scoped['success'] ?? false)) {
            return $scoped['error'];
        }

        // 与管理端 ranking 一致：空 time 交给 Date::ringRatioTime → 当月
        $time = $this->timeRange($arguments);
        $result = app(CustomerService::class)->getRanking(
            $time,
            $scoped['user_ids'] ?? [],
            $this->resolvePerformanceCategoryIds($arguments),
            $this->getEntId()
        );
        if (isset($result['list'], $result['count'])) {
            $total = (int) $result['count'];
            $result['page']     = $page;
            $result['limit']    = $limit;
            $result['total']    = $total;
            $result['has_more'] = $page * $limit < $total;
        }

        $result['_meta'] = [
            'metric'    => 'personnel_performance',
            'dimension' => 'personnel',
            'source'    => 'CustomerService::getRanking',
            'time'      => $time !== '' ? $time : 'current_month',
        ];

        return $result;
    }

    private function executeFrameRank(array $arguments): array
    {
        $scoped = $this->resolveScopedCustomerUserIds($arguments);
        if (! ($scoped['success'] ?? false)) {
            return $scoped['error'];
        }

        $time = $this->timeRange($arguments);
        $list = app(CustomerService::class)->getFrameRanking(
            $time,
            $scoped['user_ids'] ?? [],
            $this->resolvePerformanceCategoryIds($arguments)
        );
        $list = $this->toArray($list);

        return [
            'list'  => $list,
            'count' => count($list),
            '_meta' => [
                'metric'    => 'frame_performance',
                'dimension' => 'frame',
                'source'    => 'CustomerService::getFrameRanking',
                'time'      => $time !== '' ? $time : 'current_month',
            ],
        ];
    }
}
