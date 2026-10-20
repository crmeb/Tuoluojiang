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

namespace App\Http\Model\Warehouse\Traits;

use App\Constants\Warehouse\WarehouseEnum;

/**
 * 仓储模型通用搜索作用域.
 */
trait WarehouseSearchTrait
{
    /**
     * 按多个主键 ID 查询.
     */
    public function scopeIds($query, $value): void
    {
        $query->whereIn($this->getKeyName(), (array) $value);
    }

    /**
     * 按单个或多个主键 ID 查询.
     */
    public function scopeId($query, $value): void
    {
        is_array($value) ? $query->whereIn($this->getKeyName(), $value) : $query->where($this->getKeyName(), $value);
    }

    /**
     * 按仓库 ID 查询.
     */
    public function scopeWarehouseId($query, $value): void
    {
        is_array($value) ? $query->whereIn('warehouse_id', $value) : $query->where('warehouse_id', $value);
    }

    /**
     * 按多个仓库 ID 查询.
     */
    public function scopeWarehouseIds($query, $value): void
    {
        $query->whereIn('warehouse_id', (array) $value);
    }

    /**
     * 按状态查询.
     */
    public function scopeStatus($query, $value): void
    {
        if ($value !== '') {
            is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
        }
    }

    /**
     * 按名称模糊查询.
     */
    public function scopeNameLike($query, $value): void
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    /**
     * 按编码模糊查询.
     */
    public function scopeCodeLike($query, $value): void
    {
        $query->where('code', 'like', '%' . $value . '%');
    }

    /**
     * 按单据编号模糊查询.
     */
    public function scopeBillNoLike($query, $value): void
    {
        $query->where('bill_no', 'like', '%' . $value . '%');
    }

    /**
     * 按来源订单号模糊查询.
     */
    public function scopeSourceNoLike($query, $value): void
    {
        $query->where('source_no', 'like', '%' . $value . '%');
    }

    /**
     * 按商品 ID 查询.
     */
    public function scopeProductId($query, $value): void
    {
        is_array($value) ? $query->whereIn('product_id', $value) : $query->where('product_id', $value);
    }

    /**
     * 按商品规格 ID 查询.
     */
    public function scopeSkuId($query, $value): void
    {
        is_array($value) ? $query->whereIn('sku_id', $value) : $query->where('sku_id', $value);
    }

    /**
     * 按来源类型查询.
     */
    public function scopeSourceType($query, $value): void
    {
        if ($value !== '') {
            $query->where('source_type', WarehouseEnum::sourceTypeValue((int) $value));
        }
    }

    /**
     * 按来源 ID 查询.
     */
    public function scopeSourceId($query, $value): void
    {
        $query->where('source_id', $value);
    }

    /**
     * 按创建时间范围查询.
     */
    public function scopeCreatedAt($query, $value): void
    {
        $this->setTimeField('created_at')->scopeTime($query, $value);
    }
}
