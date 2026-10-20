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

use App\Http\Model\Admin\Admin;
use App\Http\Model\Warehouse\Traits\WarehouseSearchTrait;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 库管模型.
 */
class WarehouseManager extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;
    use WarehouseSearchTrait;

    protected $table = 'warehouse_manager';

    protected $casts = [
        'id'             => 'integer',
        'manager_type'   => 'integer',
        'can_view_other' => 'integer',
        'status'         => 'integer',
        'sort'           => 'integer',
        'creator'        => 'integer',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
        'deleted_at'     => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联库管员工.
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(Admin::class, WarehouseManagerUser::class, 'manager_id', 'id', 'id', 'user_id')
            ->select(['admin.id', 'admin.uid', 'admin.name', 'admin.avatar', 'admin.phone']);
    }

    /**
     * 关联库管员工关系.
     */
    public function userRelations(): HasMany
    {
        return $this->hasMany(WarehouseManagerUser::class, 'manager_id', 'id');
    }

    /**
     * 关联库管仓库.
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(WarehouseManagerWarehouse::class, 'manager_id', 'id');
    }

    /**
     * 远程关联仓库基础信息.
     */
    public function warehouseInfos(): HasManyThrough
    {
        return $this->hasManyThrough(Warehouse::class, WarehouseManagerWarehouse::class, 'manager_id', 'id', 'id', 'warehouse_id')
            ->select(['warehouse.id', 'warehouse.name']);
    }

    /**
     * 按库管角色名称或员工名称模糊查询.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameLike($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            $query->where('name', 'like', '%' . $value . '%')
                ->orWhereHas('users', fn ($query) => $query->where('admin.name', 'like', '%' . $value . '%'));
        });
    }
}
