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
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SystemCrudDashboard.
 */
class SystemCrudDashboard extends BaseModel
{
    use SoftDeletes;

    protected $appends = ['menu_name_path'];

    protected $hidden = ['menuNamePathData', 'menu_name_path_data'];

    /**
     * 表名.
     * @var string
     */
    protected $table = 'system_crud_dashboard';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'             => 'integer',
        'user_id'        => 'integer',
        'update_user_id' => 'integer',
        'status'         => 'integer',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
        'deleted_at'     => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联用户.
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'name', 'avatar']);
    }

    /**
     * 关联用修改户.
     * @return HasOne
     */
    public function updateUser()
    {
        return $this->hasOne(Admin::class, 'id', 'update_user_id')->select(['id', 'name', 'avatar']);
    }

    public function menu()
    {
        return $this->hasOne(Menus::class, 'crud_dashboard_id', 'id');
    }

    public function menuNamePathData()
    {
        return $this->hasOne(Menus::class, 'crud_dashboard_id', 'id')->with('ancestors');
    }

    public function getMenuNamePathAttribute(): array
    {
        return $this->relationLoaded('menuNamePathData') ? ($this->menuNamePathData?->name_path ?? []) : [];
    }

    /**
     * name作用域
     */
    public function scopeNameLike($query, $value): void
    {
        if ($value !== '') {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * notId作用域
     */
    public function scopeNotId($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('id', $value);
        } else {
            $query->where('id', '<>', $value);
        }
    }

    /**
     * configure 获取器.
     * @return array
     */
    public function getConfigureAttribute($value): mixed
    {
        return $value ? stripslashes(htmlspecialchars_decode($value)) : '';
    }
}
