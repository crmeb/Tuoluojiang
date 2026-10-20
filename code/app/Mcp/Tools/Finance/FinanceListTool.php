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

use App\Mcp\Tools\Abstract\BaseTool;
use App\Http\Service\Finance\BillService;
use App\Mcp\Tools\Traits\InteractsWithService;

class FinanceListTool extends BaseTool
{
    use InteractsWithService;

    public function getDescription(): string
    {
        return '获取财务流水、收付款及营收明细列表';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'page' => ['type' => 'integer', 'description' => '页码，默认1', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '每页数量，默认20，最大100', 'default' => 20],
                'time' => ['type' => 'string', 'description' => '记账时间范围，如 2026/05/01-2026/05/31'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'cate_id' => ['type' => 'array', 'description' => '分类ID二维数组，兼容前端分类级联', 'items' => ['type' => 'array']],
                'cate_ids' => ['type' => 'array', 'description' => '分类ID列表', 'items' => ['type' => 'integer']],
                'types' => ['type' => 'integer', 'description' => '收支类型：1=收入，0=支出'],
                'type' => ['type' => 'integer', 'description' => '收支类型别名：1=收入，0=支出'],
                'type_id' => ['type' => 'integer', 'description' => '付款方式ID'],
                'user_id' => ['type' => 'array', 'description' => '创建人用户ID列表，将自动按数据权限取交集', 'items' => ['type' => 'integer']],
                'uid' => ['type' => 'array', 'description' => '创建人用户ID列表别名', 'items' => ['type' => 'integer']],
                'keyword' => ['type' => 'string', 'description' => '关键词，可匹配金额、备注、分类、回款备注'],
                'sort' => ['type' => 'string', 'description' => '排序字段，如 created_at desc、edit_time desc', 'default' => 'created_at'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(BillService::class);
        $this->applyPage($arguments);

        $where = [
            'cate_id' => [],
            'entid'   => 1,
            'sort'    => $arguments['sort'] ?? 'created_at',
        ];

        if ($time = $this->timeRange($arguments)) {
            $where['time'] = $time;
        }

        if (! empty($arguments['cate_id'])) {
            $cateId = (array) $arguments['cate_id'];
            $where['cate_id'] = is_array(reset($cateId)) ? $cateId : [$cateId];
        } elseif (! empty($arguments['cate_ids'])) {
            $where['cate_id'] = [(array) $arguments['cate_ids']];
        }

        foreach (['types', 'type_id'] as $key) {
            if (array_key_exists($key, $arguments) && $arguments[$key] !== '' && $arguments[$key] !== null) {
                $where[$key] = $arguments[$key];
            }
        }
        if (! isset($where['types']) && array_key_exists('type', $arguments) && $arguments['type'] !== '' && $arguments['type'] !== null) {
            $where['types'] = $arguments['type'];
        }
        if (! empty($arguments['keyword'])) {
            $where['name_like'] = $arguments['keyword'];
        }

        $requestedUserIds = (array) ($arguments['user_id'] ?? $arguments['uid'] ?? []);
        $allowedUserIds   = $this->isAdmin() ? [] : $this->getDataUids('bill_list', 1);
        if ($requestedUserIds) {
            $where['user_id'] = $allowedUserIds ? array_values(array_intersect(array_map('intval', $requestedUserIds), $allowedUserIds)) : array_map('intval', $requestedUserIds);
        } elseif (! $this->isAdmin()) {
            $where['user_id'] = $allowedUserIds ?: [-1];
        }

        return $service->getList($where);
    }
}
