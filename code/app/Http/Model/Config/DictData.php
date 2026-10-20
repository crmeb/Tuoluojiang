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

namespace App\Http\Model\Config;

use App\Http\Model\BaseModel;

/**
 * 字典数据.
 */
class DictData extends BaseModel
{
    protected $table = 'dict_data';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'type_id'    => 'integer',
        'level'      => 'integer',
        'sort'       => 'integer',
        'status'     => 'integer',
        'is_default' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function type()
    {
        $this->hasOne(DictType::class, 'id', 'type_id');
    }

    /**
     * id 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    public function scopeTypeId($query, $value)
    {
        is_array($value) ? $query->whereIn('type_id', $value) : $query->where('type_id', $value);
    }

    public function scopeNotId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '<>', $value);
    }

    /**
     * pid gt 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopePidGt($query, $value): void
    {
        $query->where('pid', '>', $value);
    }

    /**
     * level_lt Lt 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeLevelLt($query, $value): void
    {
        $query->where('level', '<=', $value);
    }

    /**
     * level_lt Lt 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeLevel($query, $value): void
    {
        $query->where('level', $value);
    }

    /**
     * value 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeDictValue($query, $value): void
    {
        is_array($value) ? $query->whereIn('value', is_nested_array($value) ? array_merge(...$value) : $value) : $query->where('value', $value);
    }

    /**
     * value 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotValue($query, $value): void
    {
        is_array($value) ? $query->whereNotIn('value', $value) : $query->where('value', '<>', $value);
    }

    public function scopeTypeName($query, $value)
    {
        is_array($value) ? $query->whereIn('type_name', $value) : $query->where('type_name', $value);
    }

    public function scopeStatus($query, $value)
    {
        $query->where('status', $value);
    }

    public function scopePid($query, $value)
    {
        is_array($value) ? $query->whereIn('pid', $value) : $query->where('pid', $value);
    }

    public function scopeValues($query, $value)
    {
        $query->whereIn('value', $value);
    }

    public function scopeNameLike($query, $value)
    {
        $query->where(fn ($q) => $q->orWhere('name', 'like', "%{$value}%")->orWhere('value', 'like', "%{$value}%")->orWhere('mark', 'like', "%{$value}%"));
    }

    public function scopeIsCityShow($query, $value)
    {
        if ($value === 'city') {
            $query->where('level', '<=', 2);
        }
    }

    /**
     * name 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameEq($query, $value): void
    {
        is_array($value) ? $query->whereIn('name', $value) : $query->where('name', $value);
    }
}
