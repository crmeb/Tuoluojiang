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
class ProductAttr extends BaseModel
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'product_id' => 'integer',
    ];

    /**
     * @var string
     */
    protected $table = 'customer_product_attr';

    public function setAttrValuesAttribute($value)
    {
        $this->attributes['attr_values'] = implode('-!-', $value);
    }

    /**
     * @param mixed $value
     * @return array|mixed
     */
    public function getAttrValuesAttribute($value)
    {
        return $value ? explode('-!-', $value) : [];
    }
}
