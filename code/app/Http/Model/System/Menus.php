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

namespace App\Http\Model\System;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\App;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * 菜单模型
 * Class Menus.
 */
class Menus extends BaseModel
{
    use HasRecursiveRelationships;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_menus';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'                => 'integer',
        'value'       => 'integer',
        'pid'               => 'integer',
        'menu_type'         => 'integer',
        'crud_id'           => 'integer',
        'position'          => 'integer',
        'level'             => 'integer',
        'sort'              => 'integer',
        'entid'             => 'integer',
        'is_show'           => 'integer',
        'status'            => 'integer',
        'created_at'        => 'datetime:Y-m-d H:i:s',
        'updated_at'        => 'datetime:Y-m-d H:i:s',
        'deleted_at'        => 'datetime:Y-m-d H:i:s',
        'crud_app_id'       => 'integer',
        'crud_dashboard_id' => 'integer',
    ];

    public function getParentKeyName()
    {
        return 'parent_uniqued';
    }

    public function getLocalKeyName()
    {
        return 'uniqued';
    }

    public function getIdPathAttribute()
    {
        // 加载祖先节点（如果未加载）
        if (! $this->relationLoaded('ancestors')) {
            $this->load('ancestors');
        }
        // 拼接自身及所有祖先的 ID
        return $this->ancestors?->pluck('id');
    }

    public function getNamePathAttribute(): array
    {
        $names  = [$this->menu_name];
        $parent = $this->relationLoaded('ancestors') ? $this->ancestors : null;
        while ($parent) {
            $names[] = $parent->menu_name;
            $parent  = $parent->relationLoaded('ancestors') ? $parent->ancestors : null;
        }

        return array_reverse(array_values(array_filter($names, fn ($name) => $name !== null && $name !== '')));
    }

    /**
     * 预加载所有祖先节点（递归关联）.
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * other字段转json.
     * @param mixed $value
     */
    public function setOtherAttribute($value)
    {
        $this->attributes['other'] = json_encode($value);
    }

    /**
     * other字段转回数组.
     * @param mixed $value
     * @return mixed
     */
    public function getOtherAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function getMenuPathAttribute($value)
    {
        return get_roule_mobu($value, 1);
    }

    /**
     * 自动转换多语言
     * @param mixed $value
     * @return mixed
     */
    public function getMenuNameAttribute($value)
    {
        $this->other = is_string($this->other) ? $this->getOtherAttribute($this->other) : $this->other;
        if (App::getLocale() === 'en') {
            return $data['other']['menu_name_en'] ?? $value;
        }
        return $value;
    }

    /**
     * 格式化path字段.
     * @param mixed $value
     * @return false|string[]
     */
    public function setPathsAttribute($value)
    {
        if(is_array($value)){
            $this->attributes['paths'] = $value ? implode('/', $value) : '';
        } else {
            $this->attributes['paths'] = $value;
        }
    }

    /**
     * 格式化path字段.
     * @param mixed $value
     * @return false|string[]
     */
    public function getPathsAttribute($value)
    {
        return $value ? array_map('intval', explode('/', $value)) : [];
    }

    /**
     * api查询作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeApi($query, $value)
    {
        if ($value) {
            return $query->where('api', $value);
        }
        return $query->where('api', '!=', '');
    }

    /**
     * api模糊查询作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeApiLike($query, $value)
    {
        return $query->where('api', 'like', "%{$value}%");
    }

    /**
     * menu_name查询作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeMenuName($query, $value)
    {
        if ($value) {
            return $query->where('menu_name', 'like', '%' . $value . '%');
        }
    }

    public function scopeMenuPath($query, $value)
    {
        if ($value) {
            return $query->where('menu_path', 'like', '%' . $value . '%');
        }
    }

    /**
     * entid查询作用域
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    public function scopeEntid($query, $value)
    {
        return $query->where('entid', $value);
    }

    /**
     * entid查询作用域
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    public function scopeType($query, $value)
    {
        is_array($value) ? $query->whereIn('type', $value) : $query->where('type', $value);
    }

    /**
     * ids查询作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeIds($query, $value)
    {
        if ($value) {
            return $query->whereIn('id', $value);
        }
        return $query->where('id', 0);
    }

    /**
     * ids查询作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    public function scopeUniqueAuth($query, $value)
    {
        is_array($value) ? $query->whereIn('unique_auth', $value) : $query->where('unique_auth', $value);
    }

    /**
     * pids查询作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopePid($query, $value)
    {
        is_array($value) ? $query->whereIn('pid', $value) : $query->where('pid', $value);
    }

    /**
     * path_like查询作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopePathLike($query, $value)
    {
        $query->where(function ($q) use ($value) {
            $q->orWhere('paths', 'like', $value . '/%')
                ->orWhere('paths', $value)
                ->orWhere('paths', 'like', '%/' . $value)
                ->orWhere('paths', 'like', '%/' . $value . '/%');
        });
    }

    public function scopeUniPath($query, $value)
    {
        if (is_bool($value)) {
            $query->where('uni_path', '<>', '');
        }
    }

    /**
     * 名称作用域
     * @param Builder $query
     * @param mixed $value
     * @return Builder|void
     */
    public function scopeNameLike($query, $value)
    {
        $query->where('menu_name', 'like', '%' . $value . '%');
    }

    public function scopeCrudIds($query, $value)
    {
        is_array($value) ? $query->whereIn('crud_id', $value) : $query->where('crud_id', $value);
    }

    public function scopeUniqued($query, $value)
    {
        is_array($value) ? $query->whereIn('uniqued', $value) : $query->where('uniqued', $value);
    }

    public function scopeNotId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '<>', $value);
    }

    /**
     * 父级关联.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_uniqued', 'uniqued');
    }
}
