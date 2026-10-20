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

namespace App\Http\Model\Company;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Frame\Frame;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * 企业管理.
 */
class Company extends BaseModel
{
    use SoftDeletes;

    /**
     * 伪删除字段.
     */
    public const DELETED_AT = 'delete';

    /**
     * 表名.
     * @var string
     */
    protected $table = 'enterprise';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'scale'      => 'integer',
        'type'       => 'integer',
        'level'      => 'integer',
        'sort'       => 'integer',
        'verify'     => 'integer',
        'remind'     => 'integer',
        'init_data'  => 'integer',
        'disk_size'  => 'integer',
        'status'     => 'integer',
        'delete'     => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 用户关联.
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'uid', 'uid');
    }

    /**
     * 部门关联(一对多).
     * @return HasMany
     */
    public function frames()
    {
        return $this->hasMany(Frame::class, 'entid', 'id');
    }

    /**
     * 部门关联(一对一).
     * @return HasOne
     */
    public function frame()
    {
        return $this->hasOne(Frame::class, 'entid', 'id');
    }

    /**
     * other字段转json.
     */
    public function setOtherAttribute($value)
    {
        $this->attributes['other'] = json_encode($value);
    }

    /**
     * other字段转回数组.
     * @return mixed
     */
    public function getOtherAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * enterprise_name作用域
     * @param Builder $query
     */
    public function scopeEnterpriseName($query, $value)
    {
        if ($value) {
            return $query->where(function ($query) use ($value) {
                $query->where('enterprise_name', 'like', '%' . $value . '%')->orWhere('phone', 'like', '%' . $value . '%');
            });
        }
    }

    /**
     * enterprise_name作用域
     * @param Builder $query
     */
    public function scopeName($query, $value)
    {
        if ($value !== '') {
            $query->where('enterprise_name', $value);
        }
    }

    /**
     * type作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeType($query, $value)
    {
        if ($value) {
            return $query->where('type', $value);
        }
    }

    /**
     * status作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeStatus($query, $value)
    {
        if ($value !== '') {
            return $query->where('status', $value);
        }
    }

    /**
     * level作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeLevel($query, $value)
    {
        if ($value) {
            return $query->where('level', $value);
        }
    }

    /**
     * scale作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeScale($query, $value)
    {
        if ($value) {
            return $query->where('scale', $value);
        }
    }

    /**
     * scale作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeUid($query, $value)
    {
        if ($value) {
            return $query->where('uid', $value);
        }
    }

    /**
     * uniqued作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeUniqued($query, $value)
    {
        if ($value !== '') {
            return $query->where('uniqued', $value);
        }
    }

    /**
     * Verify作用域
     * @return mixed
     */
    public function scopeVerifys($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('verify', $value);
        }
    }
}
