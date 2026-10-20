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

use App\Http\Model\Approve\ApproveApply;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 审批记录.
 */
class AttendanceApplyRecord extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_apply_record';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'others'     => 'array',
        'start_time' => 'datetime:Y-m-d H:i:s',
        'end_time'   => 'datetime:Y-m-d H:i:s',
        'work_hours' => 'float',
        'id'         => 'integer',
        'uid'        => 'integer',
        'apply_type' => 'integer',
        'date_type'  => 'integer',
        'calc_type'  => 'integer',
        'apply_id'   => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联审批申请.
     * @return BelongsTo
     */
    public function approveApply()
    {
        return $this->belongsTo(ApproveApply::class, 'apply_id', 'id')->withTrashed();
    }

    /**
     * ID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } elseif ($value !== '') {
            $query->where('id', $value);
        }
    }

    /**
     * created_at作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeDate($query, $value): void
    {
        if ($value !== '') {
            $query->whereDate('start_time', $value);
        }
    }

    /**
     * date作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeMonth($query, $value): void
    {
        [$year,$month] = explode('-', $value);
        $query->whereMonth('start_time', $month)->whereYear('start_time', $year);
    }

    /**
     * date_type 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeDateType($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('date_type', $value);
        } elseif ($value !== '') {
            $query->where('date_type', $value);
        }
    }

    /**
     * uid 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }

    /**
     * apply_type 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeApplyType($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('apply_type', $value);
        } elseif ($value !== '') {
            $query->where('apply_type', $value);
        }
    }

    /**
     * type_unique 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeTypeUnique($query, $value): void
    {
        if ($value !== '') {
            $query->where('others->type_unique', $value);
        }
    }

    /**
     * calc_type 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCalcType($query, $value): void
    {
        if (is_array($value)) {
            $query->where(function ($query) use ($value) {
                $query->whereIn('calc_type', $value)
                    ->orWhereIn('others->calc_type', $value);
            });
        } elseif ($value !== '') {
            $query->where(function ($query) use ($value) {
                $query->where('calc_type', $value)
                    ->orWhere('others->calc_type', $value);
            });
        }
    }

    /**
     * 加班核算类型.
     * 历史数据只写入 others.calc_type，优先兼容该字段，避免旧记录统计丢失.
     *
     * @param mixed $value
     */
    public function getCalcTypeAttribute($value): int
    {
        $calcType = (int) $value;
        if ($calcType > 0) {
            return $calcType;
        }

        $others = $this->others;
        return (int) ($others['calc_type'] ?? 0);
    }

    /**
     * 其他数据.
     * @param mixed $value
     */
    public function setOthersAttribute($value): void
    {
        $this->attributes['others'] = json_encode($value);
    }

    /**
     * 其他数据.
     * @param mixed $value
     * @return array|mixed
     */
    public function getOthersAttribute($value): mixed
    {
        return $value ? json_decode($value, true) : [];
    }
}
