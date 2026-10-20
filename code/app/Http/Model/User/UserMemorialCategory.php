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

namespace App\Http\Model\User;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\PathAttrTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 笔记分类
 * Class UserMemorialCategory.
 */
class UserMemorialCategory extends BaseModel
{
    use PathAttrTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'user_memorial_category';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'value'       => 'integer',
        'pid'        => 'integer',
        'types'      => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @return HasOne
     */
    public function cate()
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
     * name作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeName($query, $value)
    {
        if ($value !== '') {
            return $query->where('name', $value);
        }
    }

    /**
     * name作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeUid($query, $value)
    {
        if ($value !== '') {
            return $query->where('uid', $value);
        }
    }

    /**
     * pid作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopePidLike($query, $value)
    {
        if ($value !== '') {
            return $query->where('pid', $value);
        }
    }

    /**
     * pid作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopePid($query, $value)
    {
        if ($value !== '') {
            $query->where('pid', $value);
        }
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNotId($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('id', $value);
        } elseif ($value !== '') {
            $query->where('id', '<>', $value);
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
        if ($value !== '') {
            return $query->where('id', $value);
        }
    }

    /**
     * types作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeTypes($query, $value)
    {
        $query->where('types', $value);
    }

    /**
     * 父级.
     * @return HasOne
     */
    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'pid');
    }

    /**
     * path作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopePath($query, $value)
    {
        if (! is_array($value) && $value !== '') {
            $query->where('path', 'like', "%/{$value}/%");
        }
    }
}
