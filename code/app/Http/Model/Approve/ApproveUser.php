<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model\Approve;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * 申请内容表
 * Class ApproveUser.
 */
class ApproveUser extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'approve_user';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'user_id'     => 'integer',
        'card_id'     => 'integer',
        'approve_id'  => 'integer',
        'apply_id'    => 'integer',
        'level'       => 'integer',
        'sort'        => 'integer',
        'verify'      => 'integer',
        'status'      => 'integer',
        'is_sign'     => 'integer',
        'is_transfer' => 'integer',
        'parent'      => 'integer',
        'types'       => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 表单配置提取.
     * @param mixed $value
     * @return mixed
     */
    public function setInfoAttribute($value)
    {
        $this->attributes['info'] = $value ? json_encode($value) : '';
    }

    /**
     * 表单配置提取.
     * @param mixed $value
     * @return mixed
     */
    public function getInfoAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单配置提取.
     * @param mixed $value
     * @return mixed
     */
    public function getProcessInfoAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单配置提取.
     * @param mixed $value
     * @return mixed
     */
    public function setProcessInfoAttribute($value)
    {
        $this->attributes['process_info'] = $value ? (is_array($value) ? json_encode($value) : $value) : '';
    }

    /**
     * 表单配置提取.
     * @param mixed $value
     * @return mixed
     */
    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d H:i') : '';
    }

    /**
     * 一对一关联.
     * @return HasOne
     */
    public function approve()
    {
        return $this->hasOne(Approve::class, 'id', 'approve_id');
    }

    /**
     * 一对一关联.
     * @return HasOne
     */
    public function process()
    {
        return $this->hasOne(ApproveProcess::class, 'uniqued', 'node_id');
    }

    /**
     * 一对一关联用户名片.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } elseif ($value !== '') {
            $query->where('id', $value);
        }
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotCard($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('card_id', $value);
        }
    }

    /**
     * node_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNodeId($query, $value)
    {
        if ($value !== '') {
            $query->where('node_id', $value);
        }
    }

    /**
     * node_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNodeIds($query, $value)
    {
        if ($value !== '') {
            $query->whereIn('node_id', $value);
        }
    }

    /**
     * card_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeCardId($query, $value)
    {
        if ($value !== '') {
            $query->where('card_id', $value);
        }
    }

    /**
     * user_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeUserId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('user_id', $value);
        } else {
            $query->where('user_id', $value);
        }
    }

    /**
     * apply_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeApplyId($query, $value)
    {
        if ($value !== '') {
            $query->where('apply_id', $value);
        }
    }

    /**
     * approve_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeApproveId($query, $value)
    {
        if ($value !== '') {
            $query->where('approve_id', $value);
        }
    }

    /**
     * types作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeTypes($query, $value)
    {
        if ($value !== '') {
            $query->where('types', $value);
        }
    }

    /**
     * status作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('status', $value);
        } else {
            $query->where('status', $value);
        }
    }

    /**
     * status作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotStatus($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('status', $value);
        } else {
            $query->whereNot('status', $value);
        }
    }

    /**
     * level作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeLevel($query, $value)
    {
        if ($value !== '') {
            $query->where('level', $value);
        }
    }

    /**
     * sort作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeSort($query, $value)
    {
        if ($value !== '') {
            $query->where('sort', $value);
        }
    }

    /**
     * level作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeLevelGt($query, $value)
    {
        if ($value !== '') {
            $query->where('level', '>', $value);
        }
    }

    /**
     * IsSign作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeIsSign($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('is_sign', $value);
        } else {
            $query->where('is_sign', $value);
        }
    }

    /**
     * IsTransfer作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeIsTransfer($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('is_transfer', $value);
        } else {
            $query->where('is_transfer', $value);
        }
    }
}
