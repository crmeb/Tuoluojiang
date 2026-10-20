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

namespace App\Http\Model\Attendance;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * 请假工时.
 */
class AttendanceStatisticsLeave extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_statistics_leave';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'statistics_id'   => 'integer',
        'apply_record_id' => 'integer',
        'uid'             => 'integer',
        'leave_duration'  => 'decimal:2',
        'holiday_type_id' => 'integer',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * created_at作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeDate($query, $value): void
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->where(function ($query) use ($value) {
                    foreach ($value as $date) {
                        $query->orWhereDate('created_at', $date);
                    }
                });
            } else {
                $query->whereDate('created_at', $value);
            }
        }
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
        if ($value !== '') {
            $query->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $value);
        }
    }

    /**
     * uid 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }

    /**
     * apply_record_id 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeApplyRecordId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('apply_record_id', $value);
        } elseif ($value !== '') {
            $query->where('apply_record_id', $value);
        }
    }

    /**
     * 一对一关联考勤.
     * @return HasOne
     */
    public function statistics()
    {
        return $this->hasOne(AttendanceStatistics::class, 'id', 'statistics_id')->select([
            'attendance_statistics.id',
            'attendance_statistics.required_work_hours',
        ]);
    }

    /**
     * 关联考勤申请记录.
     * @return BelongsTo
     */
    public function applyRecord()
    {
        return $this->belongsTo(AttendanceApplyRecord::class, 'apply_record_id', 'id');
    }
}
