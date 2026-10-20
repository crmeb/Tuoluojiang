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

use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Warehouse\Traits\WarehouseSearchTrait;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 库存流水模型.
 */
class WarehouseStockFlow extends BaseModel
{
    use TimeDataTrait;
    use WarehouseSearchTrait;

    protected $table = 'warehouse_stock_flow';

    protected $casts = [
        'id'                  => 'integer',
        'warehouse_id'        => 'integer',
        'target_warehouse_id' => 'integer',
        'product_id'          => 'integer',
        'sku_id'              => 'integer',
        'flow_type'           => 'integer',
        'stock_quality'       => 'integer',
        'source_type'         => 'integer',
        'source_id'           => 'integer',
        'change_num'          => 'integer',
        'before_num'          => 'integer',
        'after_num'           => 'integer',
        'operator'            => 'integer',
        'created_at'          => 'datetime:Y-m-d H:i:s',
        'updated_at'          => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 按库存流水类型查询.
     */
    public function scopeFlowType($query, $value): void
    {
        if ($value !== '') {
            $query->where('flow_type', (int) $value);
        }
    }

    /**
     * 按来源单号模糊查询.
     */
    public function scopeSourceNoLike($query, $value): void
    {
        $query->where('source_no', 'like', '%' . $value . '%');
    }

    /**
     * 只查询仍占用库存的待出库来源单据.
     */
    public function scopeOccupyPendingSource(Builder $query, $value): void
    {
        if (! $value) {
            return;
        }

        $query->where(function (Builder $query) {
            $query->where(function (Builder $query) {
                $query->where('source_type', WarehouseEnum::SOURCE_OUTBOUND)
                    ->whereHasMorph('sourceBill', [WarehouseOutbound::class], function (Builder $query) {
                        $query->where('status', WarehouseEnum::OUTBOUND_STATUS_PENDING);
                    });
            })->orWhere(function (Builder $query) {
                $query->where('source_type', WarehouseEnum::SOURCE_TRANSFER)
                    ->whereHasMorph('sourceBill', [WarehouseTransfer::class], function (Builder $query) {
                        $query->where('status', WarehouseEnum::TRANSFER_PENDING_OUT);
                    });
            });
        });
    }

    /**
     * 关联操作人基础信息.
     */
    public function operatorInfo()
    {
        return $this->hasOne(Admin::class, 'id', 'operator')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 关联库存来源单据.
     */
    public function sourceBill(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id')->select(['id', 'status']);
    }
}
