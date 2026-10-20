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
 * 商机.
 */
class ProductAssist extends BaseModel
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'product_id'  => 'integer',
        'price'       => 'decimal:2',
        'ot_price'    => 'decimal:2',
        'total_price' => 'decimal:2',
        'count'       => 'integer',
        'discount'    => 'integer',
        'link_id'     => 'integer',
        'link_type'   => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'customer_product_assist';

    public function scopeUnique($query, $value)
    {
        is_array($value) ? $query->whereIn('unique', $value) : $query->where('unique', $value);
    }

    public function scopeLinkId($query, $value)
    {
        is_array($value) ? $query->whereIn('link_id', $value) : $query->where('link_id', $value);
    }

    public function scopeLinkType($query, $value)
    {
        is_array($value) ? $query->whereIn('link_type', $value) : $query->where('link_type', $value);
    }
}
