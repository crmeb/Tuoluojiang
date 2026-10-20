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

namespace App\Http\Model\Customer;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 产品分类.
 */
class ProductCategory extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'value'       => 'integer',
        'uid'        => 'integer',
        'pid'        => 'integer',
        'level'      => 'integer',
        'status'     => 'integer',
        'sort'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'customer_product_category';

    public function getPathAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setPathAttribute($value)
    {
        $this->attributes['path'] = $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    public function scopeNotPath(Builder $query, $value)
    {
        $query->where('path', 'not like', '/' . $value . '/%')
            ->where('path', 'not like', '%/' . $value . '/')
            ->where('path', 'not like', '%/' . $value . '/%')
            ->where('pid', '<>', $value);
    }

    public function scopeNameLike($query, $value)
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    public function scopeLevelLt($query, $value)
    {
        $query->where('level', '<=', $value);
    }

    public function scopeNotId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '<>', $value);
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    public function scopePid($query, $value)
    {
        is_array($value) ? $query->whereIn('pid', $value) : $query->where('pid', $value);
    }
}
