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
use App\Http\Model\Attach\SystemAttach;
use App\Http\Model\Customer\InvoiceCategory;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 客户发票
 * Class ClientInvoice.
 */
class Invoice extends BaseModel
{
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $table = 'client_invoice';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'entid'       => 'integer',
        'eid'         => 'integer',
        'cid'         => 'integer',
        'value'       => 'integer',
        'category_id' => 'integer',
        'price'       => 'decimal:2',
        'amount'      => 'decimal:2',
        'status'      => 'integer',
        'invalid'     => 'integer',
        'bill_date'   => 'date:Y-m-d',
        'real_date'   => 'date:Y-m-d',
        'link_id'     => 'integer',
        'revoke_id'   => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
    ];

    public function getLinkBillAttribute($value)
    {
        return $value && is_string($value) ? json_decode($value, true) : $value;
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
        } else {
            $query->where('cid', '<>', '0')->where(function ($q) use ($value) {
                $q->orWhere('cid', $value)
                    ->orWhere('cid', "[{$value}]")
                    ->orWhere('cid', 'like', "%,{$value}]")
                    ->orWhere('cid', 'like', "[{$value},%")
                    ->orWhere('cid', 'like', ",{$value},%");
            });
        }
    }

    /**
     * 关联订单.
     * @return mixed
     */
    public function treaty()
    {
        return $this->hasOne(Order::class, 'id', 'cid')->withTrashed()
            ->select(['contract.contract_name as title', 'contract.contract_price as price', 'start_date', 'end_date', 'id']);
    }

    /**
     * 关联客户.
     * @return mixed
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'eid');
    }

    /**
     * 关联客户.
     * @return HasOne
     */
    public function client()
    {
        return $this->hasOne(Customer::class, 'id', 'eid')->select(['customer_name as name', 'id']);
    }

    /**
     * 一对一远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid']);
    }

    public function enterprise()
    {
        return $this->hasOne(Admin::class, 'id', 'uid');
    }

    /**
     * 附件一对多关联.
     *
     * @return HasMany
     */
    public function attachs()
    {
        return $this->hasMany(SystemAttach::class, 'relation_id', 'id')
            ->where('relation_type', 6)->select(['id', 'name', 'att_dir as url', 'relation_id', 'name', 'real_name', 'att_type']);
    }

    /**
     * 一对一关联发票类目.
     * @return HasOne
     */
    public function category()
    {
        return $this->hasOne(
            InvoiceCategory::class,
            'id',
            'category_id'
        )->select(['id', 'name']);
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

    public function scopeEid($query, $value)
    {
        is_array($value) ? $query->whereIn('eid', $value) : $query->where('eid', $value);
    }

    public function scopeIsAbnormal($query, $value)
    {
        $query->where('is_abnormal', $value);
    }

    /**
     * status作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value)
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }

    /**
     * 一对多关联付款记录.
     * @return HasMany
     */
    public function clientBill()
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'id')->select([
            'client_bill.id',
            'client_bill.cid',
            'client_bill.bill_no',
            'client_bill.invoice_id',
            'client_bill.num',
        ]);
    }

    /**
     * status作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNoStatus($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('status', $value);
        } elseif ($value !== '') {
            $query->where('status', '<>', $value);
        }
    }

    /**
     * bill_date作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeBillDate($query, $value)
    {
        $this->setTimeField('bill_date')->scopeTime($query, $value);
    }

    /**
     * real_date作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeRealDate($query, $value)
    {
        $this->setTimeField('real_date')->scopeTime($query, $value);
    }

    public function scopeUids($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }

    /**
     * created_at作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCreatedAt($query, $value)
    {
        $this->setTimeField('created_at')->scopeTime($query, $value);
    }

    /**
     * 模糊搜索.
     * @param mixed $query
     * @param mixed $value
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function scopeNameLike($query, $value)
    {
        if ($value !== '') {
            $uidLike = app(AdminService::class)->column(['name' => $value], 'id');
            $eids    = app(CustomerService::class)->column(['name_like' => $value], 'id') ?? [];
            $query->where(function ($q) use ($eids, $value, $uidLike) {
                $q->orWhereIn('eid', $eids)
                    ->orWhereIn('uid', $uidLike)
                    ->orWhere('title', 'like', '%' . $value . '%')
                    ->orWhere(function ($query) use ($value) {
                        $query->whereIn('id', function ($query) use ($value) {
                            $query->from('client_bill')->select(['invoice_id'])->where('bill_no', 'like', "%{$value}%");
                        });
                    });
            });
        }
    }
}
