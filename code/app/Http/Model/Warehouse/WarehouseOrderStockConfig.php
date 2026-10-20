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

use App\Http\Model\Warehouse\Traits\WarehouseSearchTrait;
use App\Http\Model\BaseModel;

/**
 * 订单库存扣减配置模型.
 */
class WarehouseOrderStockConfig extends BaseModel
{
    use WarehouseSearchTrait;

    protected $table = 'warehouse_order_stock_config';

    protected $casts = [
        'id'                   => 'integer',
        'auto_create_outbound' => 'integer',
        'deduct_after_paid'    => 'integer',
        'status'               => 'integer',
        'created_at'           => 'datetime:Y-m-d H:i:s',
        'updated_at'           => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 按订单来源查询.
     */
    public function scopeOrderSource($query, $value): void
    {
        $query->where('order_source', $value);
    }
}
