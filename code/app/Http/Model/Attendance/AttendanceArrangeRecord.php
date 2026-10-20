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

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * 排班数据.
 */
class AttendanceArrangeRecord extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_arrange_record';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'arrange_id' => 'integer',
        'group_id'   => 'integer',
        'uid'        => 'integer',
        'shift_id'   => 'integer',
        'date'       => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 排班日期.
     */
    public function getDateAttribute($value): string
    {
        return $value && ! str_contains($value, '0000-00-00') ? date('Y-m-d', datetime_timestamp($value)) : '0000-00-00';
    }

    /**
     * date作用域
     */
    public function scopeMonth($query, $value): void
    {
        [$year,$month] = explode('-', $value);
        $query->whereMonth('date', $month)->whereYear('date', $year);
    }

    /**
     * date作用域
     */
    public function scopeDate($query, $value): void
    {
        if ($value !== '') {
            $query->whereDate('date', $value);
        }
    }

    /**
     * date作用域
     */
    public function scopeGtDate($query, $value): void
    {
        if ($value !== '') {
            $query->whereDate('date', '>', $value);
        }
    }

    /**
     * id作用域
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
     * 一对一远程关联用户.
     * @return HasManyThrough
     */
    public function card(): HasOne
    {
        return $this->hasOne(Admin::class, 'id', 'card_id')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 一对一关联班次
     */
    public function shift(): HasOne
    {
        return $this->hasOne(AttendanceShift::class, 'id', 'shift_id')->select(['id', 'name', 'color']);
    }

    /**
     * shift_id 作用域
     */
    public function scopeShiftIdGt($query, $value): void
    {
        if ($value !== '') {
            $query->where('shift_id', '>', $value);
        }
    }

    /**
     * uid 作用域
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
     * date作用域
     */
    public function scopeDateGt($query, $value): void
    {
        if ($value !== '') {
            $query->whereDate('date', '>', $value);
        }
    }
}
