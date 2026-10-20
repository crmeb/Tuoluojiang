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

namespace App\Http\Model\System;

use App\Http\Model\Company\Assist;
use App\Http\Service\Company\CompanyService;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;

/**
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
    protected $table = 'system_role';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'     => 'integer',
        'entid'  => 'integer',
        'level'  => 'integer',
        'status' => 'integer',
    ];

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
        return $value ? json_decode($value, true) : [];
    }

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
     * 关联辅助表.
     * @return HasMany
     */
    public function admin()
    {
        return $this->hasMany(Assist::class, 'aux_id', 'id')->where('type', 'systemAdmin');
    }

    /**
     * 关联辅助表.
     * @return HasMany
     */
    public function user()
    {
        return $this->hasMany(Assist::class, 'aux_id', 'id')->where('type', 'userEnterprise');
    }

    /**
     * ID作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeId($query, $value)
    {
        if (is_array($value) && $value) {
            return $query->whereIn('id', $value);
        }
        if ($value) {
            return $query->where('id', $value);
        }
    }

    /**
     * 排除指定ID.
     */
    public function scopeNotId($query, $value)
    {
        if (is_array($value) && $value) {
            return $query->whereNotIn('id', $value);
        }
        if ($value) {
            return $query->where('id', '<>', $value);
        }
    }

    /**
     * @param Builder $query
     */
    public function scopeEntid($query, $value)
    {
        if ($value !== '') {
            if (strlen((string) $value) > 15) {
                $query->where('uniqued', $value);
            } else {
                $query->where('uniqued', app()->get(CompanyService::class)->value($value, 'uniqued'));
            }
        }
    }

    /**
     * @param Builder $query
     */
    public function scopeEntidLike($query, $value)
    {
        if (is_bool($value)) {
            if ($value) {
                $query->whereNot('entid', 0);
            } else {
                $query->where('entid', 0);
            }
        } elseif ($value !== '') {
            $query->where('entid', $value);
        }
    }

    /**
     * @param Builder $query
     */
    public function scopeType($query, $value)
    {
        if (is_array($value) && $value) {
            return $query->whereIn('type', $value);
        }
        return $query->where('type', $value);
    }
}
