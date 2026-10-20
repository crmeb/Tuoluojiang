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

namespace App\Http\Model\Auth;

use App\Http\Model\BaseModel;
use Illuminate\Database\Query\Builder;

/**
 * 企业成员角色权限
 * Class UserRole.
 */
class UserRole extends BaseModel
{
    /**
     * 关闭.
     * @var bool
     */
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'enterprise_user_role';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'      => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * 权限修改器.
     */
    public function setRulesAttribute($value)
    {
        $this->attributes['rules'] = json_encode($value);
    }

    /**
     * 权限提取.
     * @return mixed
     */
    public function getRulesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 权限修改器.
     */
    public function setApisAttribute($value)
    {
        $this->attributes['apis'] = json_encode($value);
    }

    /**
     * 权限提取.
     * @return mixed
     */
    public function getApisAttribute($value)
    {
        return (object) ($value ? json_decode($value, true) : []);
    }

    /**
     * user_id作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeUserId($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('user_id', $value);
        }
        return $query->where('user_id', $value);
    }
}
