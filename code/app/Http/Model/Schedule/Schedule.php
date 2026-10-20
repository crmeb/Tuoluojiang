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

namespace App\Http\Model\Schedule;

use App\Http\Model\Admin\Admin;
use Carbon\Carbon;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 日程表.
 */
class Schedule extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'schedule';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';
    protected $casts = [
        'days'       => 'array',
        'id'         => 'integer',
        'uid'        => 'integer',
        'cid'        => 'integer',
        'all_day'    => 'integer',
        'start_time' => 'datetime:Y-m-d H:i:s',
        'end_time'   => 'datetime:Y-m-d H:i:s',
        'period'     => 'integer',
        'rate'       => 'integer',
        'remind'     => 'integer',
        'fail_time'  => 'datetime:Y-m-d H:i:s',
        'pid'        => 'integer',
        'link_id'    => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    /**
     * 关联查询相关用户.
     * @return HasManyThrough
     */
    public function user()
    {
        return $this->hasManyThrough(Admin::class, ScheduleUser::class, 'schedule_id', 'id', 'id', 'uid')
            ->where('schedule_user.is_master', 0);
    }
    /**
     * 关联查询相关用户.
     */
    public function schedule_user()
    {
        return $this->hasMany(ScheduleUser::class, 'schedule_id', 'id');
    }
    /**
     * 关联查询相关用户.
     */
    public function scheduleUser()
    {
        return $this->hasMany(ScheduleUser::class, 'schedule_id', 'id');
    }

    public function setFailTimeAttribute($value)
    {
        $this->attributes['fail_time'] = $value ? Carbon::parse($value, config('app.timezone'))->endOfDay()->toDateTimeString() : null;
    }

    /**
     * 关联查询单个完成记录.
     * @return HasOne
     */
    public function taskOne()
    {
        return $this->hasOne(ScheduleTask::class, 'pid', 'id');
    }

    /**
     * 关联查询完成记录.
     * @return HasMany
     */
    public function task()
    {
        return $this->hasMany(ScheduleTask::class, 'pid', 'id');
    }

    /**
     * 关联查询日程类型.
     * @return HasOne
     */
    public function type()
    {
        return $this->hasOne(ScheduleType::class, 'id', 'cid');
    }

    public function scopeId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else{
            $query->where('id', $value);
        }
    }

    public function scopeCid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('cid', $value);
        } else {
            $query->where('cid', $value);
        }
    }

    public function scopeLinkId($query, $value)
    {
        $query->where('link_id', $value);
    }

    public function master()
    {
        return $this->hasOne(Admin::class, 'id', 'uid');
    }

    public function remindData()
    {
        return $this->hasOne(ScheduleRemind::class, 'sid', 'id');
    }

    public function remind()
    {
        return $this->hasOne(ScheduleRemind::class, 'sid', 'id');
    }

    /**
     * status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('status', $value);
        } else {
            $query->where('status', $value);
        }
    }

    public function scopeExistsUid($query, $value)
    {
        $query->whereExists(function ($que) use ($value) {
            $que->from('schedule_user')
                ->whereColumn('schedule_user.schedule_id', $this->table . '.id')
                ->where('schedule_user.uid', $value)->groupBy('schedule_user.schedule_id');
        });
    }
}
