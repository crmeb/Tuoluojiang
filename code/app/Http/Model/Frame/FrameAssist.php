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

namespace App\Http\Model\Frame;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Admin\AdminInfo;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * 组织架构关联企业用户id
 * Class FrameAssist.
 */
class FrameAssist extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     *
     * @var string
     */
    protected $table = 'frame_assist';

    /**
     * 主键.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'           => 'integer',
        'entid'        => 'integer',
        'frame_id'     => 'integer',
        'user_id'      => 'integer',
        'is_mastart'   => 'integer',
        'is_admin'     => 'integer',
        'superior_uid' => 'integer',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'deleted_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * user_ids作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopeUserIds($query, $value)
    {
        return $query->whereIn('user_id', $value);
    }

    /**
     * user_id作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopeUserId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('user_id', $value);
        } else {
            $query->where('user_id', $value);
        }
    }

    /**
     * 组织架构id作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopeFrameIds($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('frame_id', $value);
        }
    }

    /**
     * 组织架构id作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopeFrameId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('frame_id', $value);
        } else {
            $query->where('frame_id', $value);
        }
    }

    /**
     * 非组织架构id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotFrameId($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('frame_id', $value);
        } else {
            $query->where('frame_id', '<>', $value);
        }
    }

    /**
     * 关联部门.
     *
     * @return HasOne
     */
    public function framename()
    {
        return $this->hasOne(Frame::class, 'id', 'frame_id')->select(['id', 'name']);
    }

    /**
     * 排除user_id作用域
     *
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNotUserId($query, $value)
    {
        return $query->where('user_id', '<>', $value);
    }

    /**
     * 上级主管ID作用域
     *
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeSuperiorUid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('superior_uid', $value);
        } else {
            $query->where('superior_uid', $value);
        }
    }

    /**
     * 上级主管ID排除作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotSuperiorUid($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('superior_uid', $value);
        } else {
            $query->whereNot('superior_uid', $value);
        }
    }

    /**
     * is_admin作用域
     *
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeIsAdmin($query, $value)
    {
        $query->where('is_admin', $value);
    }

    /**
     * is_mastart作用域
     *
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeIsMastart($query, $value)
    {
        $query->where('is_mastart', $value);
    }

    /**
     * 一对一关联用户信息.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(AdminInfo::class, 'id', 'user_id');
    }

    /**
     * 一对一关联上级用户.
     * @return HasOne
     */
    public function super()
    {
        return $this->hasOne(Admin::class, 'id', 'superior_uid')->select(['name', 'id', 'uid', 'avatar']);
    }

    /**
     * 一对一关联上级用户.
     * @return HasOne
     */
    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['name', 'id', 'uid', 'avatar', 'phone']);
    }

    /**
     * 一对一关联部门.
     *
     * @return HasOne
     */
    public function frame()
    {
        return $this->hasOne(Frame::class, 'id', 'frame_id');
    }
}
