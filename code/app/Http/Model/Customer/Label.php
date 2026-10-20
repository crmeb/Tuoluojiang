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

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 客户标签
 * Class ClientLabel.
 */
class Label extends BaseModel
{
    /**
     * @var string
     */
    protected $id = 'id';

    /**
     * @var string
     */
    protected $table = 'client_label';

    protected $casts = [
        'id'         => 'integer',
        'value'       => 'integer',
        'entid'      => 'integer',
        'sort'       => 'integer',
        'pid'        => 'integer',
        'group_type' => 'integer',
        'is_work'    => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }

    /**
     * @return HasOne
     */
    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'pid');
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
            return $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * pid作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopePid($query, $value)
    {
        $query->where('pid', $value);
    }

    /**
     * pid作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNotPid($query, $value)
    {
        $query->whereNotIn('pid', [$value]);
    }

    /**
     * not_id作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNotId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '<>', $value);
    }

    public function scopeWorkGroupId($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('work_group_id', $value);
            } else {
                $query->where('work_group_id', $value);
            }
        }
    }

    /**
     * name 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameEq($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('name', $value);
        } elseif ($value !== '') {
            $query->where('name', $value);
        }
    }

    public function scopeWorkTagId($query, $value)
    {
        is_array($value) ? $query->whereIn('work_tag_id', $value) : $query->where('work_tag_id', $value);
    }

    public function scopeGroupType($query, $value): void
    {
        is_array($value) ? $query->whereIn('group_type', $value) : $query->where('group_type', $value);
    }
}
