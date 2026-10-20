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

namespace App\Http\Model\Program;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Customer\Customer;
use App\Http\Model\Customer\Order;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 项目
 * Class Program.
 */
class Program extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'program';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'value'       => 'integer',
        'uid'         => 'integer',
        'eid'         => 'integer',
        'cid'         => 'integer',
        'creator_uid' => 'integer',
        'start_date'  => 'date:Y-m-d',
        'end_date'    => 'date:Y-m-d',
        'status'      => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
        'deleted_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 隐藏字段.
     * @var string[]
     */
    protected $hidden = [
        'deleted_at',
    ];

    public function setStartDateAttribute($value): void
    {
        $this->attributes['start_date'] = $value ?: null;
    }

    public function setEndDateAttribute($value): void
    {
        $this->attributes['end_date'] = $value ?: null;
    }

    /**
     * 一对多远程关联成员.
     * @return HasManyThrough
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function members()
    {
        $table = app()->get(Admin::class);
        return $this->hasManyThrough(
            $table,
            ProgramMember::class,
            'program_id',
            'id',
            'id',
            'uid'
        )->where([$table->getTable() . '.status' => 1])->select([
            $table->getTable() . '.id', $table->getTable() . '.name', $table->getTable() . '.avatar',
        ]);
    }

    /**
     * 关联客户.
     * @return HasOne
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'eid')->select(['customer_name', 'id']);
    }

    /**
     * 关联订单.
     * @return HasOne
     */
    public function contract()
    {
        return $this->hasOne(Order::class, 'id', 'cid')->select(['contract_name', 'id']);
    }

    /**
     * 关联负责人.
     * @return HasMany
     */
    public function admins()
    {
        return $this->hasMany(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    /**
     * name作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameLike($query, $value): void
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * id 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } elseif ($value !== '') {
            $query->where('id', $value);
        }
    }

    /**
     * uid 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }

    /**
     * admins 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeAdminUids($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }

    /**
     * cid 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCid($query, $value): void
    {
        $query->whereIn('cid', function ($que) use ($value) {
            $que->from('contract')->select('id')->where('contract_name', 'like', '%' . $value . '%');
        });
//        if (is_array($value)) {
//            $query->whereIn('cid', $value);
//        } elseif ($value !== '') {
//            $query->where('cid', $value);
//        }
    }

    /**
     * eid 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEid($query, $value): void
    {
        $query->whereIn('eid', function ($que) use ($value) {
            $que->from('customer')->select('id')->where('customer_name', 'like', '%' . $value . '%');
        });
//        if (is_array($value)) {
//            $query->whereIn('eid', $value);
//        } elseif ($value !== '') {
//            $query->where('eid', $value);
//        }
    }

    /**
     * uid 和 creator_uid 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUidOrCreatorUid($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value)->orWhereIn('creator_uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value)->orWhere('creator_uid', $value);
        }
    }
}
