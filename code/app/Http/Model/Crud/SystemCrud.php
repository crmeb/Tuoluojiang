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

namespace App\Http\Model\Crud;

use App\Http\Model\Admin\Admin;
use App\Http\Model\System\Menus;
use App\Observers\SystemCrudObserver;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SystemCrud.
 * @email 136327134@qq.com
 * @date 2024/2/26
 */
class SystemCrud extends BaseModel
{
    use SoftDeletes;

    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_crud';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'crud_id'         => 'integer',
        'user_id'         => 'integer',
        'list_type'       => 'integer',
        'is_update_form'  => 'integer',
        'is_update_table' => 'integer',
        'show_log'        => 'integer',
        'show_comment'    => 'integer',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
        'deleted_at'      => 'datetime:Y-m-d H:i:s',
        'is_form_table'   => 'integer',
        'table_field'     => 'array',
    ];

    const USER_ID_KEY = 'user_id';

    const UPDATE_USER_ID_KEY = 'update_user_id';

    const FRAME_ID_KEY = 'frame_id';

    const OWNER_USER_ID_KEY = 'owner_user_id';

    protected $appends = ['menu_name_path'];

    protected $hidden = ['menuNamePathData', 'menu_name_path_data'];

    public static function boot()
    {
        parent::boot();
        static::observe(SystemCrudObserver::class);
    }

    /**
     * 字段表.
     * @return HasMany
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function field()
    {
        return $this->hasMany(SystemCrudField::class, 'crud_id', 'id');
    }

    /**
     * 表单信息.
     * @return HasOne
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function form()
    {
        return $this->hasOne(SystemCrudForm::class, 'id', 'crud_id');
    }

    /**
     * 表格信息.
     * @return HasOne
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function table()
    {
        return $this->hasOne(SystemCrudTable::class, 'id', 'crud_id');
    }

    /**
     * 辅助表.
     * @return HasMany
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function children()
    {
        return $this->hasMany(self::class, 'crud_id', 'id');
    }

    /**
     * 辅助表.
     * @return HasOne
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function child()
    {
        return $this->hasOne(self::class, 'crud_id', 'id');
    }

    /**
     * 创建者.
     * @return HasOne
     * @email 136327134@qq.com
     * @date 2024/2/26
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    /**
     * 关联事件.
     * @return HasMany
     * @email 136327134@qq.com
     * @date 2024/2/28
     */
    public function event()
    {
        return $this->hasMany(SystemCrudEvent::class, 'id', 'crud_id')->orderByDesc('sort')->orderByDesc('id');
    }

    /**
     * 关联流程.
     * @return HasMany
     * @email 136327134@qq.com
     * @date 2024/2/28
     */
    public function approve()
    {
        return $this->hasMany(SystemCrudApprove::class, 'id', 'crud_id');
    }

    /**
     * @return array
     * @email 136327134@qq.com
     * @date 2024/3/5
     */
    public function getCateIdsAttribute($value)
    {
        $value = explode('/', $value);
        return array_map('intval', array_merge(array_filter($value)));
    }

    /**
     * 关联查询权限.
     * @return HasOne
     */
    public function role()
    {
        return $this->hasOne(SystemCrudRole::class, 'crud_id', 'id');
    }

    /**
     * 菜单关联.
     * @return HasOne
     */
    public function menu()
    {
        return $this->hasOne(Menus::class, 'crud_id', 'id');
    }

    public function menuNamePathData()
    {
        return $this->hasOne(Menus::class, 'crud_id', 'id')->with('ancestors');
    }

    public function getMenuNamePathAttribute(): array
    {
        return $this->relationLoaded('menuNamePathData') ? ($this->menuNamePathData?->name_path ?? []) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setFormFieldsAttribute($value)
    {
        $this->attributes['form_fields'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getFormFieldsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function setTableFieldAttribute($value)
    {
        $this->attributes['table_field'] = json_encode($value);
    }

    /**
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/24
     */
    public function getTableFieldAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function scopeNotName($query, $value)
    {
        $query->whereNotIn('table_name_en', $value);
    }
}
