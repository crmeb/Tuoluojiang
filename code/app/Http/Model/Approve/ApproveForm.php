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

/**
 * 审核流程表单
 * Class ApproveForm.
 */
class ApproveForm extends BaseModel
{
    /**
     * 关闭自动写入时间.
     * @var bool
     */
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'approve_form';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'card_id'    => 'integer',
        'approve_id' => 'integer',
        'required'   => 'integer',
        'sort'       => 'integer',
    ];

    /**
     * 完整表单内容提取.
     * @return mixed
     */
    public function getContentAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 完整表单内容提取.
     * @return mixed
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 表单信息提取.
     * @return mixed
     */
    public function getPropsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单信息提取.
     * @return mixed
     */
    public function setPropsAttribute($value)
    {
        $this->attributes['props'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 表单信息提取.
     * @return mixed
     */
    public function getUniquedAttribute($value)
    {
        return $value ? trim($value, '\"') : '';
    }

    /**
     * 表单选项提取.
     * @return mixed
     */
    public function getOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单选项提取.
     * @return mixed
     */
    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 表单配置提取.
     * @return mixed
     */
    public function getConfigAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单配置提取.
     * @return mixed
     */
    public function setConfigAttribute($value)
    {
        $this->attributes['config'] = is_array($value) ? json_encode($value) : $value;
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
        return $this->hasOne(Admin::class, 'id', 'card_id');
    }

    /**
     * id作用域
     * @param Builder $query
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
     */
    public function scopeNotid($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('id', $value);
        }
    }

    /**
     * approve_id作用域
     * @param Builder $query
     */
    public function scopeApproveId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('approve_id', $value);
        }
        if ($value !== '') {
            $query->where('approve_id', $value);
        }
    }

    /**
     * entid作用域
     * @param Builder $query
     */
    public function scopeEntid($query, $value)
    {
        if ($value !== '') {
            $query->where('entid', $value);
        }
    }

    /**
     * uniqued作用域
     * @param Builder $query
     */
    public function scopeUniqued($query, $value)
    {
        if ($value !== '') {
            $query->where('uniqued', $value);
        }
    }

    /**
     * 非uniqued作用域
     * @param Builder $query
     */
    public function scopeNotUniqued($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('uniqued', $value);
        }
    }

    /**
     * 职级.
     * @param Builder $query
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        if ($value) {
            return $query->where('name', 'like', '%' . $value . '%');
        }
    }
}
