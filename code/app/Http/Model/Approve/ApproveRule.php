<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
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

/**
 * 审核规则表
 * Class ApproveRule.
 */
class ApproveRule extends BaseModel
{
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'approve_rule';

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
        'abnormal'    => 'integer',
        'recall'      => 'integer',
        'is_transfer' => 'integer',
        'is_sign'     => 'integer',
    ];

    protected $hidden = [
        'range',
    ];

    /**
     * 修改权限.
     * @param mixed $value
     * @return mixed
     */
    public function getEditAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 修改权限.
     * @param mixed $value
     * @return mixed
     */
    public function setEditAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['edit'] = json_encode($value);
        } else {
            $this->attributes['edit'] = json_encode(array_map('intval', explode(',', $value)));
        }
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
     * 一对一关联用户名片.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'abnormal')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    /**
     * 一对一关联用户.
     * @return HasOne
     */
    public function abCard()
    {
        return $this->hasOne(Admin::class, 'id', 'abnormal');
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotid($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('id', $value);
        }
    }

    /**
     * cate_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeCateId($query, $value)
    {
        if ($value !== '') {
            $query->where('cate_id', $value);
        }
    }

    /**
     * approve_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeApproveId($query, $value)
    {
        is_array($value) ? $query->whereIn('approve_id', $value) : $query->where('approve_id', $value);
    }

    /**
     * entid作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeEntid($query, $value)
    {
        if ($value !== '') {
            $query->where('entid', $value);
        }
    }

    /**
     * 职级.
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        if ($value) {
            return $query->where('name', 'like', '%' . $value . '%');
        }
    }
}
