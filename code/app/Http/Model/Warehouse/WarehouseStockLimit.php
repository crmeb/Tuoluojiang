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
 * 库存上下限模型.
 */
class WarehouseStockLimit extends BaseModel
{
    use WarehouseSearchTrait;

    protected $table = 'warehouse_stock_limit';

    protected $casts = [
        'id'           => 'integer',
        'warehouse_id' => 'integer',
        'product_id'   => 'integer',
        'sku_id'       => 'integer',
        'low_limit'    => 'integer',
        'high_limit'   => 'integer',
        'operator'     => 'integer',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
    ];
}
