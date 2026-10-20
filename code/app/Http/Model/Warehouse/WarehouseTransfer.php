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

use App\Constants\AttachEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\System\Attach;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 调拨单模型.
 */
class WarehouseTransfer extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    protected $table = 'warehouse_transfer';

    protected $casts = [
        'id'               => 'integer',
        'out_warehouse_id' => 'integer',
        'in_warehouse_id'  => 'integer',
        'transfer_type'    => 'integer',
        'status'           => 'integer',
        'images'           => 'array',
        'creator'          => 'integer',
        'operator'         => 'integer',
        'out_at'           => 'datetime:Y-m-d H:i:s',
        'in_at'            => 'datetime:Y-m-d H:i:s',
        'created_at'       => 'datetime:Y-m-d H:i:s',
        'updated_at'       => 'datetime:Y-m-d H:i:s',
        'deleted_at'       => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联单据明细.
     */
    public function details()
    {
        return $this->hasMany(WarehouseTransferDetail::class, 'transfer_id', 'id');
    }

    /**
     * 关联调出仓库.
     */
    public function outWarehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'out_warehouse_id');
    }

    /**
     * 关联调入仓库.
     */
    public function inWarehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'in_warehouse_id');
    }

    /**
     * 关联创建人基础信息.
     */
    public function creatorInfo()
    {
        return $this->hasOne(Admin::class, 'id', 'creator')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 关联调拨凭证附件.
     */
    public function files()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')
            ->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_WAREHOUSE_TRANSFER])
            ->select(['id', 'att_dir as url', 'relation_id', 'real_name as name', 'att_size as size', 'att_type', 'up_type']);
    }

    /**
     * 按主键 ID 查询.
     */
    public function scopeId($query, $value): void
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * 按状态查询.
     */
    public function scopeStatus($query, $value): void
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }

    /**
     * 按单据编号模糊查询.
     */
    public function scopeBillNoLike($query, $value): void
    {
        $query->where('bill_no', 'like', '%' . $value . '%');
    }

    /**
     * 按调出或调入仓库范围查询.
     */
    public function scopeWarehouseIds($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            $query->whereIn('out_warehouse_id', (array) $value)->orWhereIn('in_warehouse_id', (array) $value);
        });
    }

    /**
     * 按仓库类型和仓库ID查询.
     */
    public function scopeWarehouseType($query, $value): void
    {
        if (is_array($value) && isset($value['type'])) {
            $warehouseType = (string) $value['type'];
            $warehouseIds  = array_values(array_filter(array_map('intval', (array) ($value['ids'] ?? $value['id'] ?? []))));
            if ($warehouseType === 'out') {
                $warehouseIds ? $query->whereIn('out_warehouse_id', $warehouseIds) : $query->whereRaw('1 = 0');
            } elseif ($warehouseType === 'in') {
                $warehouseIds ? $query->whereIn('in_warehouse_id', $warehouseIds) : $query->whereRaw('1 = 0');
            }
        }
    }

    /**
     * 按商品名称、编码、条码查询.
     */
    public function scopeProductKeyword($query, $value): void
    {
        if ($value) {
            $query->whereHas('details', function ($q) use ($value) {
                $q->whereHas('product', function ($qq) use ($value) {
                    $qq->where('name', 'like', '%' . $value . '%')
                       ->orWhere('number', 'like', '%' . $value . '%');
                })->orWhereHas('sku', function ($qq) use ($value) {
                    $qq->where('bar_code', 'like', '%' . $value . '%');
                });
            });
        }
    }
}
