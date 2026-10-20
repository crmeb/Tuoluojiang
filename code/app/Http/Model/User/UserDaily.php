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

namespace App\Http\Model\User;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Attach\SystemAttach;
use App\Http\Model\Company\DailyReportMember;
use App\Http\Model\Company\UserDailyReply;
use App\Http\Model\Frame\Frame;
use App\Http\Model\Frame\FrameAssist;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * 用户日报
 * Class UserDaily.
 */
class UserDaily extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     *
     * @var string
     */
    protected $table = 'enterprise_user_daily';

    /**
     * 主键.
     *
     * @var string
     */
    protected $primaryKey = 'daily_id';

    protected $casts = [
        'daily_id'   => 'integer',
        'entid'      => 'integer',
        'user_id'    => 'integer',
        'status'     => 'integer',
        'types'      => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 评论回复一对多关联.
     *
     * @return HasMany
     */
    public function replys()
    {
        return $this->hasMany(UserDailyReply::class, 'daily_id', 'daily_id');
    }

    /**
     * 评论回复一对一关联.
     *
     * @return HasOne
     */
    public function reply()
    {
        return $this->hasOne(UserDailyReply::class, 'daily_id', 'daily_id');
    }

    /**
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    /**
     * 一对多远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    /**
     * 一对一远程关联部门.
     *
     * @return HasOneThrough
     */
    public function frame()
    {
        return $this->hasOneThrough(
            Frame::class,
            FrameAssist::class,
            'user_id',
            'id',
            'user_id',
            'frame_id'
        )
            ->where('frame_assist.is_mastart', 1)
            ->select(['frame.id', 'frame.name']);
    }

    /**
     * 附件一对多关联.
     *
     * @return HasMany
     */
    public function attachs()
    {
        return $this->hasMany(SystemAttach::class, 'relation_id', 'daily_id')->where('relation_type', 1);
    }

    /**
     * uid作用域
     *
     * @return mixed
     */
    public function scopeUid($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('uid', $value);
        }
        return $query->where('uid', $value);
    }

    /**
     * types作用域
     *
     * @return mixed
     */
    public function scopeTypes($query, $value)
    {
        $query->where('types', $value);
    }

    /**
     * entid作用域
     *
     * @return mixed
     */
    public function scopeEntid($query, $value)
    {
        if ($value !== '') {
            return $query->where('entid', $value);
        }
    }

    /**
     * status作用域
     *
     * @return mixed
     */
    public function scopeStatus($query, $value)
    {
        if ($value !== '') {
            return $query->where('status', $value);
        }
    }

    /**
     * user_id作用域
     *
     * @return mixed
     */
    public function scopeUserIds($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('user_id', $value);
        }
    }

    public function scopeUserId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('user_id', $value);
        } else {
            $query->where('user_id', $value);
        }
    }

    /**
     * user_id作用域
     *
     * @return mixed
     */
    public function scopeDay($query, $value)
    {
        if ($value !== '') {
            $query->whereDate('created_at', $value);
        }
    }

    /**
     * finish|plan 作用域
     */
    public function scopeFinishLike($query, $value)
    {
        $query->where(function ($query) use ($value) {
            $query->where('finish', 'LIKE', "%{$value}%")->orWhere('plan', 'LIKE', "%{$value}%");
        });
    }

    /**
     * 汇报人.
     */
    public function members(): HasManyThrough
    {
        return $this->hasManyThrough(
            Admin::class,
            DailyReportMember::class,
            'daily_id',
            'id',
            'daily_id',
            'member'
        )->select(['admin.id', 'admin.name', 'admin.avatar', 'admin.uid']);
    }

    /**
     * daily_id作用域
     */
    public function scopeDailyId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('daily_id', $value);
        } elseif ($value !== '') {
            $query->where('daily_id', $value);
        }
    }

    /**
     * plan修改器.
     */
    public function setPlanAttribute($value): void
    {
        $this->attributes['plan'] = json_encode($value,JSON_UNESCAPED_UNICODE);
    }

    /**
     * plan提取.
     */
    public function getPlanAttribute($value): mixed
    {
        return $value ? (json_decode($value, true) ?: [$value]) : [];
    }

    /**
     * finish修改器.
     */
    public function setFinishAttribute($value): void
    {
        $this->attributes['finish'] = json_encode($value,JSON_UNESCAPED_UNICODE);
    }

    /**
     * finish提取.
     */
    public function getFinishAttribute($value): mixed
    {
        return $value ? (json_decode($value, true) ?: [$value]) : [];
    }
}
