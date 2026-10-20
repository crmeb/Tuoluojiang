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

use App\Http\Model\Customer\Customer;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 客户关注
 * Class ClientSubscribe.
 */
class Subscribe extends BaseModel
{
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $table = 'client_subscribe';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'               => 'integer',
        'entid'            => 'integer',
        'uid'              => 'integer',
        'eid'              => 'integer',
        'types'            => 'integer',
        'subscribe_status' => 'integer',
        'created_at'       => 'datetime:Y-m-d H:i:s',
        'updated_at'       => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 一对一关联客户.
     * @return HasOne
     */
    public function client()
    {
        return $this->hasOne(Customer::class, 'id', 'eid')->select([
            'customer.id',
            'customer.customer_name as name',
        ]);
    }

    /**
     * 客户ID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEid($query, $value)
    {
        is_array($value) ? $query->whereIn('eid', $value) : $query->where('eid', $value);
    }

    /**
     * 用户ID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value)
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }
}
