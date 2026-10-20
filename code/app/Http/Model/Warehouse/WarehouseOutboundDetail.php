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

/**
 * 出库单明细模型.
 */
class WarehouseOutboundDetail extends BaseModel
{
    use WarehouseSearchTrait;

    protected $table = 'warehouse_outbound_detail';

    protected $casts = [
        'id'            => 'integer',
        'outbound_id'   => 'integer',
        'product_id'    => 'integer',
        'sku_id'        => 'integer',
        'stock_quality' => 'integer',
        'quantity'      => 'integer',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 按出库单 ID 查询.
     */
    public function scopeOutboundId($query, $value): void
    {
        is_array($value) ? $query->whereIn('outbound_id', $value) : $query->where('outbound_id', $value);
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
}
