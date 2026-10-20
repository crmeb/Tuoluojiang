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

namespace App\Http\Model\Auth;

use App\Constants\CacheEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * 角色用户
 * Class RoleUser.
 */
class RoleUser extends BaseModel
{
    /**
     * 自动写入时间.
     * @var bool
     */
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'enterprise_role_user';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';
    protected $casts = [
        'id'      => 'integer',
        'entid'   => 'integer',
        'role_id' => 'integer',
        'user_id' => 'integer',
        'status'  => 'integer',
    ];

    public static function updating($callback)
    {
        Cache::tags([CacheEnum::TAG_ROLE])->flush();
    }

    /**
     * 关联用户.
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    /**
     * user_ids作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeUserIds($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('user_id', $value);
        } elseif ($value) {
            $query->where('user_id', $value);
        }
    }

    /**
     * role_id作用域
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeRoleIds($query, $value)
    {
        if (is_array($value) && $value) {
            return $query->whereIn('role_id', $value);
        }
        if ($value) {
            return $query->where('role_id', $value);
        }
    }

    public function scopeRoleId($query, $value)
    {
        if ($value !== '') {
            $query->where('role_id', $value);
        }
    }

    public function scopeUserId($query, $value)
    {
        if ($value !== '') {
            $query->where('user_id', $value);
        }
    }
}
