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

use App\Http\Model\BaseModel;

/**
 * 库管仓库关联模型.
 */
class WarehouseManagerWarehouse extends BaseModel
{
    protected $table = 'warehouse_manager_warehouse';

    protected $casts = [
        'id'                => 'integer',
        'manager_id'        => 'integer',
        'warehouse_id'      => 'integer',
        'can_stock'         => 'integer',
        'can_stock_setting' => 'integer',
        'can_inbound'       => 'integer',
        'can_outbound'      => 'integer',
        'can_check'         => 'integer',
        'can_transfer'      => 'integer',
        'can_convert'       => 'integer',
        'created_at'        => 'datetime:Y-m-d H:i:s',
        'updated_at'        => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 按库管 ID 查询.
     */
    public function scopeManagerId($query, $value): void
    {
        is_array($value) ? $query->whereIn('manager_id', $value) : $query->where('manager_id', $value);
    }

    /**
     * 按仓库 ID 查询.
     */
    public function scopeWarehouseId($query, $value): void
    {
        is_array($value) ? $query->whereIn('warehouse_id', $value) : $query->where('warehouse_id', $value);
    }
}
