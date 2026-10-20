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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 考勤班次规则.
 */
class AttendanceShiftRule extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_shift_rule';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'               => 'integer',
        'shift_id'         => 'integer',
        'number'           => 'integer',
        'first_day_after'  => 'integer',
        'second_day_after' => 'integer',
        'late'             => 'integer',
        'extreme_late'     => 'integer',
        'late_lack_card'   => 'integer',
        'early_card'       => 'integer',
        'early_leave'      => 'integer',
        'early_lack_card'  => 'integer',
        'delay_card'       => 'integer',
        'free_clock'       => 'integer',
        'created_at'       => 'datetime:Y-m-d H:i:s',
        'updated_at'       => 'datetime:Y-m-d H:i:s',
        'deleted_at'       => 'datetime:Y-m-d H:i:s',
    ];
}
