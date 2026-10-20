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

use App\Http\Model\Admin\Admin;
use App\Http\Model\Config\GroupData;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Remind.
 */
class Remind extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'entid'       => 'integer',
        'eid'         => 'integer',
        'cid'         => 'integer',
        'cate_id'     => 'integer',
        'user_id'     => 'integer',
        'bill_id'     => 'integer',
        'num'         => 'decimal:2',
        'time'        => 'datetime:Y-m-d H:i:s',
        'this_period' => 'datetime:Y-m-d H:i:s',
        'next_period' => 'datetime:Y-m-d H:i:s',
        'rate'        => 'integer',
        'period'      => 'integer',
        'types'       => 'integer',
        'status'      => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
        'deleted_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'client_remind';

    /**
     * 一对一远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

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
     * 关联合同订单.
     * @return mixed
     */
    public function treaty()
    {
        return $this->hasOne(Order::class, 'id', 'cid')->withTrashed()
            ->select(['contract.contract_name as title', 'contract_price as price', 'start_date', 'end_date', 'id']);
    }

    /**
     * 一对一关联合同订单.
     * @return HasOne
     */
    public function contract()
    {
        return $this->hasOne(Order::class, 'id', 'cid')->select([
            'contract.id',
            'contract.contract_name as title',
        ]);
    }

    /**
     * 续费类型.
     * @return HasOne
     */
    public function renew()
    {
        return $this->hasOne(GroupData::class, 'id', 'cate_id')->select(['value->title as title', 'id']);
    }

    public function scopeEid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('eid', $value);
        } elseif ($value !== '') {
            $query->where('eid', $value);
        }
    }

    /**
     * 合同订单ID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('cid', $value);
        } elseif ($value !== '') {
            $query->where('cid', $value);
        }
    }

    public function scopeTypes($query, $value)
    {
        if ($value !== '') {
            $query->where('types', $value);
        }
    }

    public function scopeUniqued($query, $value)
    {
        if ($value !== '') {
            $query->where('uniqued', $value);
        }
    }

    /**
     * user_id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUserId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('user_id', $value);
        } elseif ($value !== '') {
            $query->where('user_id', $value);
        }
    }

    /**
     * next_period作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNextPeriodIt($query, $value)
    {
        if ($value !== '') {
            $query->where('next_period', '<', $value);
        }
    }

    /**
     * bill_id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeBillId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('bill_id', $value);
        } elseif ($value !== '') {
            $query->where('bill_id', $value);
        }
    }

    /**
     * time作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeTimeLt($query, $value)
    {
        if ($value !== '') {
            $query->where('time', '<', $value);
        }
    }
}
