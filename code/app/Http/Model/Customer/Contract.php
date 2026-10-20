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

use App\Constants\AttachEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Approve\ApproveApply;
use App\Http\Model\Approve\ApproveRule;
use App\Http\Model\System\Attach;
use App\Observers\ContractDocObserver;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同签约
 * Class Contract.
 */
class Contract extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    protected $casts = [
        'start_date'  => 'datetime:Y-m-d',
        'end_date'    => 'datetime:Y-m-d',
        'sign_date'   => 'date:Y-m-d',
        'cid'         => 'array',
        'oid'         => 'array',
        'sign_file'   => 'array',
        'id'          => 'integer',
        'uid'         => 'integer',
        'eid'         => 'integer',
        'link_type'   => 'integer',
        'status'      => 'integer',
        'sign_type'   => 'integer',
        'term_type'   => 'integer',
        'date_count'  => 'integer',
        'sign_status' => 'string',
        'approve_id'  => 'integer',
        'is_verify'   => 'integer',
        'fail_time'   => 'datetime:Y-m-d H:i:s',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
        'deleted_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'contract_doc';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'uid', 'name', 'avatar', 'phone', 'e_userid']);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'eid')->select(['customer.id', 'customer.customer_name', 'customer.customer_tel', 'customer.area_cascade']);
    }

    public function signatory()
    {
        return $this->hasMany(ContractSignatory::class, 'cid', 'id')->select(['id', 'cid', 'name', 'user_id', 'phone', 'company_name', 'types', 'e_userid', 'e_openid', 'sign_status'])->with(['admin']);
    }

    public function attach()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_SIGN])->select(['id', 'att_dir as url', 'relation_id', 'real_name as name', 'att_size as size', 'att_type', 'up_type']);
    }

    public function result()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_SIGN_RESULT])->select(['id', 'att_dir as url', 'relation_id', 'real_name as name', 'att_size as size', 'att_type', 'up_type']);
    }

    public function products()
    {
        return $this->hasMany(ProductAssist::class, 'link_id', 'id')->where('link_type', CustomEnum::DOC);
    }

    public function scopeSignTime($query, $value)
    {
        $this->setTimeField('sign_date')->scopeTime($query, $value);
    }

    public function rules()
    {
        return $this->hasOneThrough(ApproveRule::class, ApproveApply::class, 'id', 'approve_id', 'approve_id', 'approve_id');
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

    public function scopeNameLike($query, $value)
    {
        if ($value === '') {
            return;
        }
        $query->where(function ($q) use ($value) {
            // 合同名称
            $q->orWhere('doc_name', 'like', '%' . $value . '%');
            // 客户名称
            $q->orWhereHas('customer', function ($q2) use ($value) {
                $q2->where('customer_name', 'like', '%' . $value . '%');
            });
            // 产品名称
            $q->orWhereHas('products', function ($q2) use ($value) {
                $q2->where('product_name', 'like', '%' . $value . '%');
            });
        });
    }

    /**
     * ID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    public function scopeUid($query, $value)
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }

    /**
     * start_date作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStartDate($query, $value)
    {
        $this->setTimeField('start_date')->scopeTime($query, $value);
    }

    /**
     * end_date作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEndDate($query, $value): void
    {
        $this->setTimeField('end_date')->scopeTime($query, $value);
    }

    /**
     * sign_status作用域
     * @param mixed $value
     * @param mixed $query
     */
    public function scopeSignStatus($query, $value): void
    {
        $query->where('sign_status', $value);
    }

    /**
     * 过期状态作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeFailStatus($query, $value): void
    {
        switch ($value) {
            case 0:
                $query->where(function ($que) {
                    $que->whereNull('end_date')->orWhere('end_date', '>', date('Y-m-d'));
                });
                break;
            case 1:
                $query->whereNotNull('start_date')->where('start_date', '>', date('Y-m-d'));
                break;
            case 2:
                $query->whereNotNull('end_date')->where('end_date', '<', date('Y-m-d'));
                break;
        }
    }

    /**
     * not_id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotId($query, $value): void
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '<>', $value);
    }

    /**
     * 结束时间.
     * @param mixed $value
     */
    public function setEndDateAttribute($value): void
    {
        $this->attributes['end_date'] = $value ?: null;
    }

    public function scopeCid($query, $value)
    {
        is_array($value) ? $query->where(function ($q) use ($value) {
            foreach ($value as $v) {
                $q->orWhereJsonContains('cid', $v)->orWhereJsonContains('cid', (string) $v);
            }
        }) : $query->where(function ($q) use ($value) {
            $q->orWhereJsonContains('cid', $value)->orWhereJsonContains('cid', (string) $value);
        });
    }

    public function scopeOid($query, $value)
    {
        is_array($value) ? $query->where(function ($q) use ($value) {
            foreach ($value as $v) {
                $q->orWhereJsonContains('oid', $v)->orWhereJsonContains('oid', (string) $v);
            }
        }) : $query->where(function ($q) use ($value) {
            $q->orWhereJsonContains('oid', $value)->orWhereJsonContains('oid', (string) $value);
        });
    }

    public function scopeStatus($query, $value)
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }

    protected static function boot()
    {
        parent::boot();
        static::observe(ContractDocObserver::class);
    }
}
