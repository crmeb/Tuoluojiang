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

namespace App\Http\Model\Attendance;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Frame\Frame;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * 考勤统计.
 */
class AttendanceStatistics extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_statistics';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'shift_data'                  => 'array',
        'id'                          => 'integer',
        'uid'                         => 'integer',
        'frame_id'                    => 'integer',
        'group_id'                    => 'integer',
        'shift_id'                    => 'integer',
        'one_shift_time'              => 'datetime:Y-m-d H:i:s',
        'one_shift_is_after'          => 'integer',
        'one_shift_status'            => 'integer',
        'one_shift_location_status'   => 'integer',
        'one_shift_record_id'         => 'integer',
        'two_shift_time'              => 'datetime:Y-m-d H:i:s',
        'two_shift_is_after'          => 'integer',
        'two_shift_status'            => 'integer',
        'two_shift_location_status'   => 'integer',
        'two_shift_record_id'         => 'integer',
        'three_shift_time'            => 'datetime:Y-m-d H:i:s',
        'three_shift_is_after'        => 'integer',
        'three_shift_status'          => 'integer',
        'three_shift_location_status' => 'integer',
        'three_shift_record_id'       => 'integer',
        'four_shift_time'             => 'datetime:Y-m-d H:i:s',
        'four_shift_is_after'         => 'integer',
        'four_shift_status'           => 'integer',
        'four_shift_location_status'  => 'integer',
        'four_shift_record_id'        => 'integer',
        'required_work_hours'         => 'decimal:2',
        'actual_work_hours'           => 'decimal:2',
        'created_at'                  => 'datetime:Y-m-d H:i:s',
        'updated_at'                  => 'datetime:Y-m-d H:i:s',
        'deleted_at'                  => 'datetime:Y-m-d H:i:s',
        'statistics_date'             => 'date:Y-m-d',
        'active_flag'                 => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * created_at作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeDate($query, $value): void
    {
        is_array($value) ? $query->where(function ($q) use ($value) {
            foreach ($value as $v) {
                $q->orWhereDate('created_at', $v);
            }
            return $q;
        }) : $query->whereDate('created_at', $value);
    }

    public function getDateAttribute(): string
    {
        return date('Y-m-d', datetime_timestamp($this->attributes['created_at']));
    }

    /**
     * date作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeMonth($query, $value): void
    {
        $query->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $value);
    }

    /**
     * uid 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value): void
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }

    /**
     * status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            if (is_array($value)) {
                $query->whereIn('one_shift_status', $value)
                    ->orWhereIn('two_shift_status', $value)
                    ->orWhereIn('three_shift_status', $value)
                    ->orWhereIn('four_shift_status', $value);
            } else {
                $query->where('one_shift_status', $value)
                    ->orWhere('two_shift_status', $value)
                    ->orWhere('three_shift_status', $value)
                    ->orWhere('four_shift_status', $value);
            }
        });
    }

    /**
     * location_status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeLocationStatus($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            if (is_array($value)) {
                $query->whereIn('one_shift_location_status', $value)
                    ->orWhereIn('two_shift_location_status', $value)
                    ->orWhereIn('three_shift_location_status', $value)
                    ->orWhereIn('four_shift_location_status', $value);
            } else {
                $query->where('one_shift_location_status', $value)
                    ->orWhere('two_shift_location_status', $value)
                    ->orWhere('three_shift_location_status', $value)
                    ->orWhere('four_shift_location_status', $value);
            }
        });
    }

    /**
     * location_status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeLocationStatusLt($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            $query->where('one_shift_location_status', '<', $value)
                ->orWhere('two_shift_location_status', '<', $value)
                ->orWhere('three_shift_location_status', '<', $value)
                ->orWhere('four_shift_location_status', '<', $value);
        });
    }

    /**
     * location_status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeLocationStatusGt($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            $query->where('one_shift_location_status', '>', $value)
                ->orWhere('two_shift_location_status', '>', $value)
                ->orWhere('three_shift_location_status', '>', $value)
                ->orWhere('four_shift_location_status', '>', $value);
        });
    }

    /**
     * 一对一远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatusGt($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            $query->where('one_shift_status', '>', $value)
                ->orWhere('two_shift_status', '>', $value)
                ->orWhere('three_shift_status', '>', $value)
                ->orWhere('four_shift_status', '>', $value);
        });
    }

    /**
     * status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatusLt($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            $query->where('one_shift_status', '<', $value)
                ->orWhere('two_shift_status', '<', $value)
                ->orWhere('three_shift_status', '<', $value)
                ->orWhere('four_shift_status', '<', $value);
        });
    }

    /**
     * shift_id 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeShiftIdGt($query, $value): void
    {
        $query->where('shift_id', '>', $value);
    }

    /**
     * 一对一关联考勤组.
     * @return HasOne
     */
    public function group()
    {
        return $this->hasOne(AttendanceGroup::class, 'id', 'group_id')->select(['attendance_group.id', 'attendance_group.name']);
    }

    /**
     * 一对一关联部门.
     *
     * @return HasOne
     */
    public function frame()
    {
        return $this->hasOne(Frame::class, 'id', 'frame_id')->select(['id', 'name']);
    }

    /**
     * created_at 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeGtDate($query, $value): void
    {
        $query->whereDate('created_at', '>', $value);
    }

    /**
     *  abnormal_status 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeAbnormalStatus($query, $value): void
    {
        $query->where(function ($query) use ($value) {
            $query->where(function ($query) use ($value) {
                $query->where('one_shift_status', '>', $value)
                    ->orWhere('two_shift_status', '>', $value)
                    ->orWhere('three_shift_status', '>', $value)
                    ->orWhere('four_shift_status', '>', $value);
            })->orWhere(function ($query) {
                $query->where('one_shift_location_status', 2)
                    ->orWhere('two_shift_location_status', 2)
                    ->orWhere('three_shift_location_status', 2)
                    ->orWhere('four_shift_location_status', 2);
            });
        });
    }
}
