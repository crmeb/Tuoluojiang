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
use App\Http\Model\Customer\Product;
use App\Http\Model\Customer\ProductAttrValue;
use App\Http\Model\System\Attach;
use App\Http\Model\Warehouse\Traits\WarehouseSearchTrait;
use App\Http\Model\BaseModel;

/**
 * 正残品转换明细模型.
 */
class WarehouseConvertDetail extends BaseModel
{
    use WarehouseSearchTrait;

    protected $table = 'warehouse_convert_detail';

    protected $casts = [
        'id'         => 'integer',
        'convert_id' => 'integer',
        'product_id' => 'integer',
        'sku_id'     => 'integer',
        'quantity'   => 'integer',
        'images'     => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 按转换单 ID 查询.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeConvertId($query, $value): void
    {
        is_array($value) ? $query->whereIn('convert_id', $value) : $query->where('convert_id', $value);
    }

    /**
     * 关联商品资料.
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    /**
     * 关联商品规格.
     */
    public function sku()
    {
        return $this->hasOne(ProductAttrValue::class, 'id', 'sku_id');
    }

    /**
     * 关联商品凭证附件.
     */
    public function files()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')
            ->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_WAREHOUSE_CONVERT_DETAIL])
            ->select(['id', 'att_dir as url', 'relation_id', 'real_name as name', 'att_size as size', 'att_type', 'up_type']);
    }
}
