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
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 省市区
 * Class City.
 */
class City extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_city';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'        => 'integer',
        'value'       => 'integer',
        'city_id'   => 'integer',
        'level'     => 'integer',
        'parent_id' => 'integer',
        'is_show'   => 'integer',
    ];

    /**
     * 获取子集分类查询条件.
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'city_id')->orderBy('id', 'ASC');
    }

    /**
     * name 作用域
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        if (is_array($value)) {
            $query->where(function ($query) use ($value) {
                foreach ($value as $val) {
                    $query->orWhere('name', 'like', '%' . $val . '%');
                }
            });
        } elseif ($value !== '') {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'city_id');
    }

    /**
     * 预加载所有祖先节点（递归关联）.
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * 优化版：通过预加载获取层级名称.
     */
    public function getHierarchyNameAttribute(): string
    {
        $names   = [];
        $current = $this;

        // 利用预加载的ancestors递归获取所有节点
        while ($current) {
            $names[] = $current->name;
            $current = $current->parent ?? null;
        }

        return implode('/', array_reverse($names));
    }

    public function scopeCityId($query, $value)
    {
        is_array($value) ? $query->whereIn('city_id', $value) : $query->where('city_id', $value);
    }
}
