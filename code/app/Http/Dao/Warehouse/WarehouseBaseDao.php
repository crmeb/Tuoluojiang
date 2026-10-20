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

namespace App\Http\Dao\Warehouse;

use App\Http\Dao\BaseDao;
use Illuminate\Database\Eloquent\Builder;

/**
 * 仓储基础 DAO，提供仓储列表分页、排序和关联加载能力.
 */
abstract class WarehouseBaseDao extends BaseDao
{
    /**
     * 获取仓储模块列表数据.
     */
    public function getList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, array|string $sort = ['id' => 'desc'], array $with = [])
    {
        return $this->search($where)->when($with, function ($query) use ($with) {
            $query->with($with);
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($sort = sort_mode($sort), function ($query) use ($sort) {
            if (is_array($sort)) {
                foreach ($sort as $key => $value) {
                    is_numeric($key) ? $query->orderByDesc($value) : $query->orderBy($key, $value);
                }
            } else {
                $query->orderByDesc($sort);
            }
        })->select($field)->get();
    }

    /**
     * 仓储单据列表扩展搜索.
     */
    public function search($where, ?bool $authWhere = null)
    {
        $productKeyword = '';
        if (is_array($where)) {
            $productKeyword = trim((string) ($where['product_keyword'] ?? ''));
            unset($where['product_keyword']);
        }

        return parent::search($where, $authWhere)
            ->when($productKeyword !== '', function (Builder $query) use ($productKeyword) {
                $query->whereHas('details', function (Builder $query) use ($productKeyword) {
                    $query->whereHas('product', function (Builder $query) use ($productKeyword) {
                        $query->where('name', 'like', '%' . $productKeyword . '%')
                            ->orWhere('number', 'like', '%' . $productKeyword . '%');
                    })->orWhereHas('sku', function (Builder $query) use ($productKeyword) {
                        $query->where('sku', 'like', '%' . $productKeyword . '%')
                            ->orWhere('bar_code', 'like', '%' . $productKeyword . '%');
                    });
                });
            });
    }
}
