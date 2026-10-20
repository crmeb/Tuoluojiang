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

namespace App\Http\Model\Customer;

use App\Http\Model\Customer\Traits\CustomFormCasts;
use App\Http\Model\Warehouse\WarehouseSkuStock;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 产品.
 */
class Product extends BaseModel
{
    use CustomFormCasts;
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'value'      => 'integer',
        'uid'        => 'integer',
        'pid'        => 'integer',
        'path'       => 'array',
        'spec_type'  => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'customer_product';

    public function getPathAttribute($value): array
    {
        if (! $value) {
            return [];
        }
        $path = json_decode($value, true);
        if (is_string($path)) {
            $path = json_decode($path, true);
        }
        return is_array($path) ? $path : [];
    }

    public function setPathAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }
        $this->attributes['path'] = json_encode(is_array($value) ? array_values($value) : [], JSON_UNESCAPED_UNICODE);
    }

    public function scopeNameLike($query, $value)
    {
        $query->where('name', 'like', "%{$value}%");
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    public function scopeFilterStock($query, $value, array $where = []): void
    {
        $filterStock = is_array($value) ? (int) ($value['value'] ?? 0) : (int) $value;
        $warehouseId = is_array($value) ? (int) ($value['warehouse_id'] ?? 0) : (int) ($where['warehouse_id'] ?? 0);
        if (! $filterStock || ! $warehouseId) {
            return;
        }
        $query->whereExists(function ($query) use ($warehouseId) {
            $query->selectRaw('1')
                ->from((new WarehouseSkuStock())->getTable())
                ->whereColumn('warehouse_sku_stock.product_id', 'customer_product.id')
                ->where('warehouse_sku_stock.warehouse_id', $warehouseId)
                ->whereColumn('good_stock', '>', 'occupy_stock');
        });
    }

    public function attr()
    {
        return $this->hasMany(ProductAttr::class, 'product_id', 'id');
    }

    public function attrValue()
    {
        return $this->hasMany(ProductAttrValue::class, 'product_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'pid', 'id');
    }
}
