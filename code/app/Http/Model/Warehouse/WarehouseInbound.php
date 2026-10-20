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
use App\Http\Model\Customer\Order;
use App\Http\Model\System\Attach;
use App\Http\Model\Warehouse\Traits\WarehouseSearchTrait;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 入库单模型.
 */
class WarehouseInbound extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;
    use WarehouseSearchTrait;

    protected $table = 'warehouse_inbound';

    protected $casts = [
        'id'           => 'integer',
        'warehouse_id' => 'integer',
        'inbound_type' => 'integer',
        'status'       => 'integer',
        'source_type'  => 'integer',
        'source_id'    => 'integer',
        'total_amount' => 'decimal:2',
        'images'       => 'array',
        'creator'      => 'integer',
        'operator'     => 'integer',
        'operated_at'  => 'datetime:Y-m-d H:i:s',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
        'deleted_at'   => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联单据明细.
     */
    public function details()
    {
        return $this->hasMany(WarehouseInboundDetail::class, 'inbound_id', 'id');
    }

    /**
     * 关联仓库资料.
     */
    public function warehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'warehouse_id');
    }

    /**
     * 关联来源订单.
     */
    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'source_id')
            ->select(['id', 'contract_name', 'contract_no', 'contract_price', 'received', 'surplus']);
    }

    /**
     * 按来源单号兜底关联订单.
     */
    public function orderByNo()
    {
        return $this->hasOne(Order::class, 'contract_no', 'source_no')
            ->where('contract_no', '<>', '')
            ->select(['id', 'contract_name', 'contract_no', 'contract_price', 'received', 'surplus']);
    }

    /**
     * 关联创建人信息.
     */
    public function creatorInfo()
    {
        return $this->hasOne(Admin::class, 'id', 'creator')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 关联操作人信息.
     */
    public function operatorInfo()
    {
        return $this->hasOne(Admin::class, 'id', 'operator')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 关联入库凭证附件.
     */
    public function files()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')
            ->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_WAREHOUSE_INBOUND])
            ->select(['id', 'att_dir as url', 'relation_id', 'real_name as name', 'att_size as size', 'att_type', 'up_type']);
    }

    public function scopeBillNoLike($query, $value): void
    {
        $query->where('bill_no', 'like', "%{$value}%");
    }
}
