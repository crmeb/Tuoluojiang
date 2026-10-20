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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 考勤班次.
 */
class AttendanceShift extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_shift';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'               => 'integer',
        'number'           => 'integer',
        'rest_time'        => 'integer',
        'rest_start_after' => 'integer',
        'rest_end_after'   => 'integer',
        'overtime'         => 'integer',
        'sort'             => 'integer',
        'uid'              => 'integer',
        'types'            => 'integer',
        'created_at'       => 'datetime:Y-m-d H:i:s',
        'updated_at'       => 'datetime:Y-m-d H:i:s',
        'deleted_at'       => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * 一对一远程关联用户.
     * @return HasManyThrough
     */
    public function card(): HasOne
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 一对多关联考勤时间.
     */
    public function times(): HasMany
    {
        return $this->hasMany(AttendanceShiftRule::class, 'shift_id', 'id')
            ->orderBy('number')
            ->select([
                'attendance_shift_rule.work_hours',
                'attendance_shift_rule.off_hours',
                'attendance_shift_rule.shift_id',
                'attendance_shift_rule.number',
                'attendance_shift_rule.first_day_after',
                'attendance_shift_rule.second_day_after',
            ]);
    }

    /**
     * 一对多关联班次规则.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(AttendanceShiftRule::class, 'shift_id', 'id')->orderBy('number');
    }

    /**
     * ID 作用域
     */
    public function scopeIdGt($query, $value): void
    {
        $query->where('id', '>', $value);
    }

    /**
     * ID 作用域
     */
    public function scopeNotId($query, $value): void
    {
        $query->where('id', '<>', $value);
    }
}
