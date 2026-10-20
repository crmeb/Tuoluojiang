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

namespace App\Http\Model\Finance;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Customer\Customer;
use App\Http\Model\Customer\Order;
use App\Http\Model\Customer\Payment;
use App\Http\Model\System\Attach;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Query\Builder;

/**
 * 资金流水
 * Class Bill.
 */
class Bill extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'bill_list';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'entid'      => 'integer',
        'user_id'    => 'integer',
        'cate_id'    => 'integer',
        'num'        => 'decimal:2',
        'edit_time'  => 'datetime:Y-m-d H:i:s',
        'types'      => 'integer',
        'type_id'    => 'integer',
        'link_id'    => 'integer',
        'order_id'   => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 一对一关联企业用户.
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'uid');
    }

    /**
     * 一对一关联财务流水类型.
     * @return HasOne
     */
    public function cate()
    {
        return $this->hasOne(BillCategory::class, 'id', 'cate_id');
    }

    /**
     * types作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeTypes($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('types', $value);
        } else {
            $query->where('types', $value);
        }
    }

    /**
     * types作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeCateId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('cate_id', $value);
        } else {
            $query->where('cate_id', $value);
        }
    }

    /**
     * typeId作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeTypeId($query, $value)
    {
        $query->where('type_id', $value);
    }

    /**
     * uid作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } else {
            $query->where('uid', $value);
        }
    }

    public function scopeUserId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('user_id', $value);
        } elseif ($value !== '') {
            $query->where('user_id', $value);
        }
    }

    /**
     * 附件一对多关联.
     *
     * @return HasMany
     */
    public function attachs()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'link_id')
            ->where('relation_type', 2)->where('relation_id', '>', 0);
    }

    /**
     * 财务附件一对多关联.
     *
     * @return HasMany
     */
    public function files()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')
            ->where('relation_type', 10)->where('relation_id', '>', 0);
    }

    /**
     * 财务附件一对多关联.
     * @return HasOne
     */
    public function file()
    {
        return $this->hasOne(Attach::class, 'relation_id', 'id')
            ->where('relation_type', 10)->where('relation_id', '>', 0);
    }

    /**
     * 一对一关联付款单.
     * @return HasOne
     */
    public function clientBill()
    {
        return $this->hasOne(Payment::class, 'id', 'link_id');
    }

    /**
     * 一对一关联客户.
     * @return HasOneThrough
     */
    public function client()
    {
        return $this->hasOneThrough(Customer::class, Payment::class, 'id', 'id', 'link_id', 'eid');
    }

    /**
     * 一对一关联订单.
     * @return HasOneThrough
     */
    public function contract()
    {
        return $this->hasOneThrough(Order::class, Payment::class, 'id', 'id', 'link_id', 'cid');
    }

    /**
     * 模糊查询.
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        return $query->where(function ($q) use ($value) {
            $q->orWhere('num', 'like', '%' . $value . '%')->orWhere('mark', 'like', '%' . $value . '%')->orWhereIn('id', function () use ($value) {
                return Admin::query()->where('name', 'like', '%' . $value . '%')->pluck('id');
            });
        });
    }
}
