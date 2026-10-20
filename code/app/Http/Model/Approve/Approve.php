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

namespace App\Http\Model\Approve;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * 审核流程表
 * Class Approve.
 */
class Approve extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'approve';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'value'      => 'integer',
        'user_id'    => 'integer',
        'card_id'    => 'integer',
        'entid'      => 'integer',
        'types'      => 'integer',
        'examine'    => 'integer',
        'status'     => 'integer',
        'sort'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * 一对一关联用户名片.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 一对多关联表单配置.
     * @return HasMany
     */
    public function form()
    {
        return $this->hasMany(ApproveForm::class, 'approve_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function process()
    {
        return $this->hasOne(ApproveProcess::class, 'approve_id', 'id')->where('is_initial', 1);
    }

    /**
     * 一对多关联规则配置.
     * @return HasOne
     */
    public function rule()
    {
        return $this->hasOne(ApproveRule::class, 'approve_id', 'id');
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
    public function scopeNotid($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('id', $value);
        }
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
     * status作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }

    /**
     * 名称模糊查询.
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        if ($value !== '') {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * types 作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeTypes($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('types', $value);
        } elseif ($value !== '') {
            $query->where('types', $value);
        }
    }

    /**
     * types 作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotTypes($query, $value): void
    {
        if (is_array($value)) {
            $query->whereNotIn('types', $value);
        }
    }

    /**
     * 表单信息存储器.
     * @param mixed $value
     * @return false|string
     */
    protected function setConfigAttribute($value)
    {
        $this->attributes['config'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 表单信息获取器.
     * @param mixed $value
     * @return array|mixed
     */
    protected function getConfigAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
