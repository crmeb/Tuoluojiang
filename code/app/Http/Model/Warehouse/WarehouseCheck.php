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
use App\Http\Model\Warehouse\Traits\WarehouseSearchTrait;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 盘点单模型.
 */
class WarehouseCheck extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;
    use WarehouseSearchTrait;

    protected $table = 'warehouse_check';

    protected $casts = [
        'id'           => 'integer',
        'warehouse_id' => 'integer',
        'status'       => 'integer',
        'source_type'  => 'integer',
        'source_id'    => 'integer',
        'images'       => 'array',
        'creator'      => 'integer',
        'operator'     => 'integer',
        'operated_at'  => 'datetime:Y-m-d H:i:s',
        'finished_at'  => 'datetime:Y-m-d H:i:s',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
        'deleted_at'   => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联单据明细.
     */
    public function details()
    {
        return $this->hasMany(WarehouseCheckDetail::class, 'check_id', 'id');
    }

    /**
     * 关联仓库资料.
     */
    public function warehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'warehouse_id');
    }

    /**
     * 关联创建人基础信息.
     */
    public function creatorInfo()
    {
        return $this->hasOne(Admin::class, 'id', 'creator')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 关联盘点凭证附件.
     */
    public function files()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')
            ->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_WAREHOUSE_CHECK])
            ->select(['id', 'att_dir as url', 'relation_id', 'real_name as name', 'att_size as size', 'att_type', 'up_type']);
    }
}
