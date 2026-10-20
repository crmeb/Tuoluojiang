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
 * 申请内容表
 * Class ApproveContent.
 */
class ApproveContent extends BaseModel
{
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'approve_content';

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
        'apply_id'   => 'integer',
        'required'   => 'integer',
        'sort'       => 'integer',
    ];

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
     * apply_id作用域
     * @param Builder $query
     */
    public function scopeApplyId($query, $value)
    {
        if ($value !== '') {
            $query->where('apply_id', $value);
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
     * 非uniqued作用域
     * @param Builder $query
     */
    public function scopeNotUniqued($query, $value)
    {
        if (is_array($value) && $value) {
            $query->whereNotIn('uniqued', $value);
        }
    }

    /**
     * cate_id作用域
     * @param Builder $query
     */
    public function scopeCateId($query, $value)
    {
        if ($value !== '') {
            $query->where('cate_id', $value);
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

    /**
     * 职级.
     * @param Builder $query
     * @return mixed
     */
    public function scopeValueLike($query, $value)
    {
        if ($value !== '') {
            $query->where('value', 'like', '%' . $value . '%');
        }
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setInfoAttribute($value)
    {
        $this->attributes['info'] = $value ? json_encode($value) : '';
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setValueAttribute($value)
    {
        $this->attributes['value'] = $value ? json_encode($value) : '';
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function getValueAttribute($value)
    {
        return $value ? json_decode($value, true) : '';
    }

    /**
     * 表单信息获取器.
     * @return array|mixed
     */
    protected function getInfoAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setUserListAttribute($value)
    {
        $this->attributes['user_list'] = $value ? json_encode($value) : '';
    }

    /**
     * @return array|mixed
     */
    protected function getUserListAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setConditionListAttribute($value)
    {
        $this->attributes['condition_list'] = $value ? json_encode($value) : '';
    }

    /**
     * @return array|mixed
     */
    protected function getConditionListAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setDepHeadAttribute($value)
    {
        $this->attributes['dep_head'] = $value ? json_encode($value) : '';
    }

    /**
     * @return array|mixed
     */
    protected function getDepHeadAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setContentAttribute($value)
    {
        $this->attributes['content'] = $value ? json_encode($value) : '';
    }

    /**
     * @return array|mixed
     */
    protected function getContentAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setPropsAttribute($value)
    {
        $this->attributes['props'] = $value ? json_encode($value) : '';
    }

    /**
     * @return array|mixed
     */
    protected function getPropsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setOptionsAttribute($value)
    {
        $this->attributes['options'] = $value ? json_encode($value) : '';
    }

    /**
     * @return array|mixed
     */
    protected function getOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 表单信息存储器.
     * @return false|string
     */
    protected function setConfigAttribute($value)
    {
        $this->attributes['config'] = $value ? json_encode($value) : '';
    }

    /**
     * @return array|mixed
     */
    protected function getConfigAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
