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
use App\Http\Model\Customer\Traits\CustomFormCasts;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 线索模型.
 */
class Lead extends BaseModel
{
    use CustomFormCasts;
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string
     */
    protected $table = 'customer_clue';

    protected $casts = [
        'customer_label' => 'array',
        'area_cascade'   => 'array',
        'id'             => 'integer',
        'value'          => 'integer',
        'uid'            => 'integer',
        'before_uid'     => 'integer',
        'creator_uid'    => 'integer',
        'createtime'     => 'date:Y-m-d',
        'return_num'     => 'integer',
        'claim_time'     => 'datetime:Y-m-d H:i:s',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
        'deleted_at'     => 'datetime:Y-m-d H:i:s',
    ];

    public function getCreatetimeAttribute($value)
    {
        return $value != '0000-00-00 00:00:00' ? $value : '';
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
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
     * not_uid作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotUid($query, $value): void
    {
        is_array($value) ? $query->whereNotIn('uid', $value) : $query->where('uid', '<>', $value);
    }

    /**
     * name作用域
     * @param mixed $value
     * @param mixed $query
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    public function getCustomerLabelAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setCustomerLabelAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['customer_label'] = null;
            return;
        }
        // 如果是数组，直接编码
        if (is_array($value)) {
            $this->attributes['customer_label'] = json_encode($value, JSON_UNESCAPED_UNICODE);
            return;
        }
        // 如果是字符串，尝试解码后再编码，确保格式正确
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // 已经是有效的 JSON 字符串，重新编码确保格式统一
            $this->attributes['customer_label'] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        } else {
            // 不是有效的 JSON，尝试作为单个值处理
            $this->attributes['customer_label'] = json_encode([$value], JSON_UNESCAPED_UNICODE);
        }
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'external_userid', 'external_userid')->where('external_userid', '<>', '');
    }

    public function scopeIsWork($query, $value)
    {
        $query->where(function ($que) use ($value) {
            if ($value) {
                $que->whereNotNull('userid')->where('userid', '<>', '');
            } else {
                $que->whereNull('userid')->orWhere('userid', '');
            }
        });
    }

    public function follows()
    {
        return $this->hasMany(FollowUp::class, 'eid', 'id')->where('link_type', 'clue');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'uid');
    }

    public function scopeCustomerLabel($query, $value)
    {
        $query->where(function ($q) use ($value) {
            // 只查询 customer_label 是有效 JSON 的记录
            $q->whereRaw('JSON_VALID(customer_label) = 1')->where(function ($subQ) use ($value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $subQ->orWhereJsonContains('customer_label', (string) $v)->orWhereJsonContains('customer_label', $v);
                    }
                } else {
                    $subQ->orWhereJsonContains('customer_label', (string) $value)->orWhereJsonContains('customer_label', $value);
                }
            });
        });
    }
}
