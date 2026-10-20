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
use App\Http\Model\Customer\Traits\CustomFormCasts;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * 客户
 * Class Customer.
 */
class Customer extends BaseModel
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
    protected $table = 'customer';

    protected $casts = [
        'customer_label'      => 'array',
        'member'              => 'array',
        'area_cascade'        => 'array',
        'id'                  => 'integer',
        'value'               => 'integer',
        'uid'                 => 'integer',
        'before_uid'          => 'integer',
        'creator_uid'         => 'integer',
        'un_followed_days'    => 'integer',
        'amount_recorded'     => 'decimal:2',
        'amount_expend'       => 'decimal:2',
        'invoiced_amount'     => 'decimal:2',
        'contract_num'        => 'integer',
        'invoice_num'         => 'integer',
        'attachment_num'      => 'integer',
        'return_num'          => 'integer',
        'last_follow_up_time' => 'datetime:Y-m-d H:i:s',
        'collect_time'        => 'datetime:Y-m-d H:i:s',
        'created_at'          => 'datetime:Y-m-d H:i:s',
        'updated_at'          => 'datetime:Y-m-d H:i:s',
        'deleted_at'          => 'datetime:Y-m-d H:i:s',
    ];

    public function getCustomerLabelAttribute($value)
    {
        if ($value) {
            return $this->recursiveJsonDecode($value);
        }
        return [];
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

    /**
     * 一对一远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 一对多关联联系人.
     * @return HasMany
     */
    public function liaison()
    {
        return $this->hasMany(Liaison::class, 'eid', 'id')->select([
            'customer_liaison.name',
            'customer_liaison.id',
            'customer_liaison.eid',
            'customer_liaison.job',
            'customer_liaison.gender',
            'customer_liaison.tel',
            'customer_liaison.mail',
        ]);
    }

    public function getMemberAttribute($value)
    {
        if ($value) {
            return is_array($value) ? $value : json_decode($value, true);
        }
        return [];
    }

    /**
     * 客户来源.
     * @return HasOne
     */
    public function way()
    {
        return $this->hasOne(GroupData::class, 'id', 'source')->select(['value->title as title', 'id']);
    }

    /**
     * 客户来源.
     * @return HasOne
     */
    public function track()
    {
        return $this->hasOne(FollowUp::class, 'eid', 'id')->select(['eid', 'content', 'created_at'])->where('link_type', 'customer')->orderByDesc('client_follow.created_at');
    }

    public function follows()
    {
        return $this->hasMany(FollowUp::class, 'eid', 'id')->where('link_type', 'customer');
    }

    /**
     * 客户分类.
     * @return HasOne
     */
    public function cate()
    {
        return $this->hasOne(GroupData::class, 'id', 'cid')->select(['value->title as title', 'id']);
    }

    /**
     * 分类ID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCid($query, $value)
    {
        is_array($value) ? $query->whereIn('cid', $value) : $query->where('cid', $value);
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

    /**
     * status作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * UID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value)
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }

    /**
     * name作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        if ($value) {
            $query->where(fn($q)=>$q->orWhere('customer_name', 'like', '%' . $value . '%')->orWhere('customer_no', 'like', '%' . $value . '%'));
        }
    }

    /**
     * email作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeEmailLike($query, $value)
    {
        $query->where('email', 'like', '%' . $value . '%');
    }

    /**
     * phone作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopePhoneLike($query, $value)
    {
        $query->where('phone', 'like', '%' . $value . '%');
    }

    /**
     * label作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeLabelLike($query, $value)
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $query->orWhere('label', 'like', '%"' . $item . '"%');
            }
        } else {
            return $query->where('label', 'like', '%"' . $value . '"%');
        }
    }

    /**
     * client_no 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeClientNoLike($query, $value)
    {
        if ($value) {
            return $query->where('client_no', 'like', '%' . $value . '%');
        }
    }

    /**
     * 未完成待办作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeIncompleteScheduleId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } elseif ($value !== '') {
            return $query->where('id', $value);
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
     * not_uid作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotUId($query, $value): void
    {
        is_array($value) ? $query->whereNotIn('uid', $value) : $query->where('uid', '<>', $value);
    }

    /**
     * customer_status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCustomerStatusLt($query, $value): void
    {
        $query->where('customer_status', '<', $value);
    }

    /**
     * 负责人.
     * @return HasOne
     */
    public function salesman()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'avatar', 'name', 'phone']);
    }

    /**
     * name 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameEq($query, $value)
    {
        is_array($value) ? $query->whereIn('customer_name', $value) : $query->where('customer_name', $value);
    }

    /**
     * external_userid 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeExternalUserid($query, $value)
    {
        is_array($value) ? $query->whereIn('external_userid', $value) : $query->where('external_userid', $value);
    }

    /**
     * 参与人员 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeInvolved($query, $value)
    {
        if (is_array($value)) {
            $query->where(function ($q) use ($value) {
                foreach ($value as $item) {
                    $q->orWhereJsonContains('member', $item);
                }
                $q->orWhereIn('uid', $value);
            });
        } else {
            $query->where(function ($q) use ($value) {
                $q->whereJsonContains('member', $value)->orWhere('uid', $value);
            });
        }
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

    private function recursiveJsonDecode(string $value)
    {
        // 尝试解析JSON
        $decoded = json_decode($value, true);
        // 解析失败，返回原始值
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }
        // 如果解析结果仍是字符串，继续递归解析
        if (is_string($decoded)) {
            return $this->recursiveJsonDecode($decoded);
        }
        // 如果解析结果是数组，检查数组元素是否需要递归解析
        if (is_array($decoded)) {
            foreach ($decoded as &$item) {
                if (is_string($item)) {
                    $item = $this->recursiveJsonDecode($item);
                }
            }
            unset($item); // 释放引用
        }
        return $decoded;
    }
}
