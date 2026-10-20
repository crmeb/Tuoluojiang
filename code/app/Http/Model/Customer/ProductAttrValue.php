<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model\Customer;

use App\Http\Model\BaseModel;

/**
 * 产品属性值.
 */
class ProductAttrValue extends BaseModel
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'product_id' => 'integer',
        'ot_price'   => 'decimal:2',
        'price'      => 'decimal:2',
        'cost'       => 'decimal:2',
    ];

    /**
     * @var string
     */
    protected $table = 'customer_product_attr_value';

    public function setDetailAttribute($value)
    {
        $this->attributes['detail'] = json_encode($value);
    }

    /**
     * @param mixed $value
     * @return array|mixed
     */
    public function getDetailAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function scopeUnique($query, $value)
    {
        is_array($value) ? $query->whereIn('unique', $value) : $query->where('unique', $value);
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function scopeProductId($query, $value)
    {
        is_array($value) ? $query->whereIn('product_id', $value) : $query->where('product_id', $value);
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    public function scopeAttrLike($query, $value)
    {
        is_array($value) ? $query->whereIn('sku', $value) :
        $query->where(function ($query) use ($value) {
            $query->where('sku', 'like', '%' . $value . '%')
                ->orWhere('bar_code', 'like', '%' . $value . '%');
        });
    }
}
