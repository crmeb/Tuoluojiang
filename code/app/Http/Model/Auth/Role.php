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

use App\Http\Model\BaseModel;
use Illuminate\Database\Query\Builder;

/**
 * 企业角色
 * Class Role.
 */
class Role extends BaseModel
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
    protected $table = 'enterprise_role';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'module_permissions' => 'array',
        'id'                 => 'integer',
        'user_count'         => 'integer',
        'entid'              => 'integer',
        'data_level'         => 'integer',
        'directly'           => 'integer',
        'status'             => 'integer',
    ];

    /**
     * 权限修改器.
     * @param mixed $value
     */
    public function setRulesAttribute($value)
    {
        $this->attributes['rules'] = json_encode($value);
    }

    /**
     * 权限修改器.
     * @param mixed $value
     */
    public function setFrameIdAttribute($value)
    {
        $this->attributes['frame_id'] = json_encode($value);
    }

    /**
     * 权限提取.
     * @param mixed $value
     * @return mixed
     */
    public function getRulesAttribute($value)
    {
        return $value ? array_map('intval', json_decode($value, true)) : [];
    }

    /**
     * 权限修改器.
     * @param mixed $value
     */
    public function setApisAttribute($value)
    {
        $this->attributes['apis'] = json_encode($value);
    }

    /**
     * 权限提取.
     * @param mixed $value
     * @return mixed
     */
    public function getApisAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 权限提取.
     * @param mixed $value
     * @return mixed
     */
    public function getFrameIdAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 权限标识修改器.
     * @param mixed $value
     */
    public function setRuleUniqueAttribute($value)
    {
        $this->attributes['rule_unique'] = json_encode($value);
    }

    /**
     * 权限标识提取.
     * @param mixed $value
     * @return mixed
     */
    public function getRuleUniqueAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 权限标识修改器.
     * @param mixed $value
     */
    public function setApiUniqueAttribute($value)
    {
        $this->attributes['api_unique'] = json_encode($value);
    }

    /**
     * 权限标识提取.
     * @param mixed $value
     * @return mixed
     */
    public function getApiUniqueAttribute($value)
    {
        return (object) ($value ? json_decode($value, true) : []);
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotEntids($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('entid', $value);
        }
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '<>', $value);
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeIds($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('id', $value);
        }
        if ($value) {
            return $query->where('id', $value);
        }
    }

    public function scopeRuleApi($query, $value)
    {
        $query->where(function ($q) use ($value) {
            $q->orWhereJsonContains('rules', $value)->orWhereJsonContains('rules', $value);
        });
    }
}
