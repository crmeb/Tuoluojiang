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

namespace App\Http\Model\Category;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * 消息分类
 * Class MessageCategory.
 */
class MessageCategory extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'message_category';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'value'       => 'integer',
        'pid'        => 'integer',
        'sort'       => 'integer',
        'is_show'    => 'integer',
        'uni_show'   => 'integer',
        'level'      => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * path修改器.
     */
    public function setPathAttribute($value)
    {
        $this->attributes['path'] = $value ? implode('/', $value) : '';
    }

    /**
     * path获取器.
     * @return false|string[]
     */
    public function getPathAttribute($value)
    {
        return $value ? array_map('intval', explode('/', $value)) : [];
    }

    /**
     * 关键词作用域
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsShow($query, $value)
    {
        return $value !== '' ? $query->where('is_show', $value) : null;
    }

    /**
     * 分类名称作用域
     * @param Builder $query
     * @return Builder
     */
    public function scopeCateName($query, $value)
    {
        return $value !== '' ? $query->where('cate_name', 'LIKE', "%{$value}%") : null;
    }

    /**
     * 分类名称作用域
     * @param Builder $query
     * @return Builder
     */
    public function scopeEqCateName($query, $value)
    {
        $query->where('cate_name', $value);
    }

    /**
     * 屏蔽ID作用域
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotId($query, $value)
    {
        return $value ? $query->where('id', '<>', $value) : null;
    }

    /**
     * 屏蔽ID作用域
     * @param Builder $query
     * @return Builder
     */
    public function scopePid($query, $value)
    {
        return $value ? $query->where('pid', $value) : null;
    }

    /**
     * 屏蔽ID作用域
     * @param Builder $query
     * @return Builder
     */
    public function scopeLtLevel($query, $value)
    {
        if ($value !== '') {
            $query->where('level', 'LT', $value);
        }
    }
}
