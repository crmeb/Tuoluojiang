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

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Model\Customer\Traits\CustomFormCasts;
use App\Http\Service\Customer\PaymentService;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同订单
 * Class Order.
 */
class Order extends BaseModel
{
    use CustomFormCasts;
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $table = 'contract';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'contract_category'     => 'array',
        'id'                    => 'integer',
        'value'                 => 'integer',
        'uid'                   => 'integer',
        'eid'                   => 'integer',
        'creator_uid'           => 'integer',
        'outbound_warehouse_id' => 'integer',
        'contract_price'        => 'decimal:2',
        'received'              => 'decimal:2',
        'surplus'               => 'decimal:2',
        'renew'                 => 'integer',
        'start_date'            => 'date:Y-m-d',
        'end_date'              => 'date:Y-m-d',
        'area_cascade'          => 'array',
        'is_abnormal'           => 'integer',
        'created_at'            => 'datetime:Y-m-d H:i:s',
        'updated_at'            => 'datetime:Y-m-d H:i:s',
        'deleted_at'            => 'datetime:Y-m-d H:i:s',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'eid', 'id');
    }

    /**
     * 一对一关联客户.
     * @return HasOne
     */
    public function client()
    {
        return $this->hasOne(Customer::class, 'id', 'eid')->select([
            'customer.id',
            'customer.customer_name',
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
     * 续费状态作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeRenew($query, $value)
    {
        $query->where('renew', $value);
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
     * created_at作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCreatedAt($query, $value)
    {
        $this->setTimeField('created_at')->scopeTime($query, $value);
    }

    /**
     * category_id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCategoryId($query, $value)
    {
        is_array($value) ? $query->whereIn('category_id', $value) : $query->where('category_id', $value);
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
     * signing_status作用域
     * @param mixed $value
     */
    public function scopeSigningStatus(Builder $query, $value): void
    {
        if ($value !== '') {
            $query->where('signing_status', $value);
        }
    }

    /**
     * 状态作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value)
    {
        if ($value == '') {
            return;
        }
        switch ($value) {
            case 0:
                $query->where('is_abnormal', 1);
                break;
            case 1:
                $query->whereDate('start_date', '>', now(config('app.timezone'))->toDateString());
                break;
            case 2:
                $query->whereDate('start_date', '<', now(config('app.timezone'))->toDateString())->whereDate('end_date', '>', now(config('app.timezone'))->toDateString());
                break;
            case 3:
                $query->whereDate('end_date', '<', now(config('app.timezone'))->toDateString());
                break;
        }
    }

    /**
     * 结款状态
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeAbnormal($query, $value): void
    {
        if ($value !== '') {
            switch ($value) {
                case 1:
                    $query->where('is_abnormal', 1);
                    break;
                case 3:
                    $query->whereDate('start_date', '>', now()->toDateString())->where('is_abnormal', 0);
                    break;
                case 2:
                    $query->whereDate('end_date', '<', now()->toDateString())->where('is_abnormal', 0)->whereNotNull('end_date');
                    break;
                default:
                    $query->where('is_abnormal', 0)->where(function ($query) {
                        $query->where(function ($query) {
                            $query->whereNotNull('end_date')->whereDate('start_date', '<=', now()->toDateString())->whereDate('end_date', '>', now()->toDateString());
                        })->orWhere(function ($query) {
                            $query->whereNull('end_date')->whereDate('start_date', '<=', now()->toDateString());
                        });
                    });
            }
        }
    }

    /**
     * not_id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereNotIn('id', $value);
        } elseif ($value !== '') {
            $query->where('id', '<>', $value);
        }
    }

    /**
     * 结束时间.
     * @param mixed $value
     */
    public function setEndDateAttribute($value): void
    {
        $this->attributes['end_date'] = $value ?: null;
    }

    /**
     * 一对多关联续费.
     * @return HasMany
     */
    public function bills()
    {
        return $this->hasMany(Payment::class, 'cid', 'id')->where('types', 1)->select([
            'client_bill.id',
            'client_bill.cid',
            'client_bill.cate_id',
            'client_bill.num',
            'client_bill.date',
        ])->with(['renew'])->limit(3);
    }

    /**
     * signing_status作用域
     * @param mixed $value
     */
    public function scopeSigningStatusLt(Builder $query, $value): void
    {
        if ($value !== '') {
            $query->where('signing_status', '<', $value);
        }
    }

    /**
     * 结款状态
     * @param mixed $query
     * @param mixed $value
     */
    public function scopePayStatus($query, $value)
    {
        if ($value !== '') {
            switch ($value) {
                case 0:
                    $query->where('surplus', '>', 0);
                    break;
                case 1:
                    $query->where('surplus', 0);
                    break;
            }
        }
    }

    /**
     * contract_category 作用域（支持 JSON 数组查询）
     * 同时匹配字符串和整数类型（兼容历史数据）.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeContractCategory($query, $value): void
    {
        if (is_array($value) && ! empty($value)) {
            $query->where(function ($q) use ($value) {
                foreach ($value as $item) {
                    $q->orWhereJsonContains('contract_category', $item);
                    // 兼容数字字符串类型
                    if (ctype_digit((string) $item)) {
                        $q->orWhereJsonContains('contract_category', (int) $item);
                    }
                }
            });
        } elseif ($value !== '' && ! is_array($value)) {
            $query->whereJsonContains('contract_category', $value);
            // 兼容数字字符串类型
            if (ctype_digit((string) $value)) {
                $query->orWhereJsonContains('contract_category', (int) $value);
            }
        }
    }

    /**
     * start_date 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStartDateGt($query, $value): void
    {
        if ($value) {
            $query->whereDate('start_date', '>=', $value);
        }
    }

    /**
     * end_date 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEndDateGt($query, $value): void
    {
        if ($value != '') {
            $query->whereDate('end_date', '>=', $value);
        }
    }

    /**
     * end_date lt 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEndDateLt($query, $value): void
    {
        if ($value != '') {
            $query->whereDate('end_date', '<', $value);
        }
    }

    /**
     * contract_status 作用域
     * @param mixed $value
     * @param mixed $query
     */
    public function scopeContractStatusLt($query, $value): void
    {
        $query->where('contract_status', '<', $value);
    }

    /**
     * contract_name 作用域
     * @param \Illuminate\Database\Query\Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        $query->where('contract_name', 'like', '%' . $value . '%');
    }

    public function product()
    {
        return $this->hasMany(ProductAssist::class, 'link_id', 'id')->where('link_type', CustomEnum::CONTRACT);
    }

    protected static function boot()
    {
        parent::boot();
        self::saved(function ($model) {
            if ($model->isDirty('contract_price')) {
                app(PaymentService::class)->contractPrice($model->id, customerStatusReload: $model->eid <= 0);
            }
        });
    }
}
