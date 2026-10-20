<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
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

/**
 * 考勤打卡.
 */
class AttendanceClockRecord extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_clock_record';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'frame_id'    => 'integer',
        'group_id'    => 'integer',
        'shift_id'    => 'integer',
        'clock_type'  => 'integer',
        'uid'         => 'integer',
        'is_external' => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
        'deleted_at'  => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * 设置image数据.
     * @param mixed $value
     */
    public function setImageAttribute($value): void
    {
        $this->attributes['image'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 获取image数据转换.
     * @param mixed $value
     */
    public function getImageAttribute($value): mixed
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置班次数据.
     * @param mixed $value
     */
    public function setShiftDataAttribute($value): void
    {
        $this->attributes['shift_data'] = json_encode($value);
    }

    /**
     * 获取班次数据.
     * @param mixed $value
     */
    public function getShiftDataAttribute($value): mixed
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * ID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeId($query, $value): void
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * 一对一关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
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
     * ID作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value): void
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }

    public function scopeDate($query, $value)
    {
        is_array($value) ? $query->whereBetween('created_at', $value) : $query->whereDate('created_at', $value);
    }

}
