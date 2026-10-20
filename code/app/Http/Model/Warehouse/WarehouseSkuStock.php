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

namespace App\Http\Model\Warehouse;

use App\Http\Model\Customer\Product;
use App\Http\Model\Customer\ProductAttrValue;
use App\Http\Model\Warehouse\Traits\WarehouseSearchTrait;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Support\Facades\Schema;

/**
 * SKU 仓库库存模型.
 */
class WarehouseSkuStock extends BaseModel
{
    use TimeDataTrait;
    use WarehouseSearchTrait;

    protected $table = 'warehouse_sku_stock';

    protected $casts = [
        'id'             => 'integer',
        'warehouse_id'   => 'integer',
        'product_id'     => 'integer',
        'sku_id'         => 'integer',
        'good_stock'     => 'integer',
        'defect_stock'   => 'integer',
        'occupy_stock'   => 'integer',
        'transit_stock'  => 'integer',
        'low_limit'      => 'integer',
        'high_limit'     => 'integer',
        'stock_status'   => 'integer',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联仓库资料.
     */
    public function warehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'warehouse_id');
    }

    /**
     * 关联商品资料.
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    /**
     * 关联商品规格.
     */
    public function sku()
    {
        return $this->hasOne(ProductAttrValue::class, 'id', 'sku_id');
    }

    /**
     * 按库存状态查询.
     */
    public function scopeStockStatus($query, $value): void
    {
        if ($value !== '') {
            $query->where('stock_status', $value);
        }
    }

    /**
     * 筛选存在占用库存的记录.
     */
    public function scopeHasOccupy($query, $value): void
    {
        if ($value !== '') {
            $query->where('occupy_stock', '>', 0);
        }
    }

    /**
     * 筛选存在在途库存的记录.
     */
    public function scopeHasTransit($query, $value): void
    {
        if ($value !== '') {
            $query->where('transit_stock', '>', 0);
        }
    }

    /**
     * 按商品分类查询.
     */
    public function scopeCategoryId($query, $value): void
    {
        $query->whereHas('product', function ($query) use ($value) {
            $query->where('pid', $value);
        });
    }

    /**
     * 按商品名称、编号、规格或条码查询.
     */
    public function scopeKeyword($query, $value): void
    {
        if ($value !== '') {
            $query->where(function ($query) use ($value) {
                $query->whereHas('product', function ($query) use ($value) {
                    $query->where('name', 'like', '%' . $value . '%')
                        ->orWhere('number', 'like', '%' . $value . '%');
                })->orWhereHas('sku', function ($query) use ($value) {
                    $query->where('sku', 'like', '%' . $value . '%')
                        ->orWhere('bar_code', 'like', '%' . $value . '%')
                        ->orWhere('product_bar_code', 'like', '%' . $value . '%');
                });
            });
        }
    }

    /**
     * 按商品品牌查询.
     */
    public function scopeBrand($query, $value): void
    {
        if ($value === '') {
            return;
        }

        $columns = array_values(array_filter(['brand_id', 'brand', 'brand_name'], fn ($column) => Schema::hasColumn('customer_product', $column)));
        if (! $columns) {
            return;
        }

        $query->whereHas('product', function ($query) use ($columns, $value) {
            $query->where(function ($query) use ($columns, $value) {
                foreach ($columns as $column) {
                    if (is_numeric($value)) {
                        $query->orWhere($column, $value);
                    } else {
                        $query->orWhere($column, 'like', '%' . $value . '%');
                    }
                }
            });
        });
    }
}
