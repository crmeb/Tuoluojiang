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

namespace App\Http\Model\Finance;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\PathAttrTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 资金流水分类
 * Class BillCategory.
 */
class BillCategory extends BaseModel
{
    use PathAttrTrait;

    /**
     * 自动写入时间关闭.
     * @var bool
     */
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'bill_category';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'value'      => 'integer',
        'entid'      => 'integer',
        'level'      => 'integer',
        'sort'       => 'integer',
        'pid'        => 'integer',
        'types'      => 'integer',
        'contact_id' => 'integer',
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
            $query->where('name', 'like', '%' . $value . '%');
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
        if ($value) {
            $query->where('name', $value);
        }
    }

    /**
     * types作用域
     */
    public function scopeTypesLike($query, $value)
    {
        $query->where('types', $value);
    }

    /**
     * level作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeLevel($query, $value)
    {
        if ($value !== '') {
            return $query->where('level', $value);
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
        if (is_array($value)) {
            return $query->whereIn('pid', $value);
        }
        if ($value !== '') {
            return $query->where('pid', $value);
        }
    }

    /**
     * ids作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeIds($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('id', $value);
        }
    }

    /**
     * level之后作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeGtLevel($query, $value)
    {
        if ($value !== '') {
            return $query->where('level', '>=', $value);
        }
    }

    /**
     * level之后作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeLevelLt($query, $value)
    {
        $query->where('level', '<=', $value);
    }

    /**
     * id小于作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeIdIt($query, $value): mixed
    {
        return $query->where('id', '<', $value);
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotid($query, $value)
    {
        $query->where('id', '<>', $value);
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

    /**
     * path作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotPath($query, $value)
    {
        $query->where('path', 'not like', "%/{$value}/%");
    }
}
