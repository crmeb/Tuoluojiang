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

/**
 * 考勤缺卡提醒.
 */
class AttendanceShortRemind extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_short_remind';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'shift_id'    => 'integer',
        'uid'         => 'integer',
        'short_type'  => 'integer',
        'work_time'   => 'datetime:Y-m-d H:i:s',
        'remind_time' => 'datetime:Y-m-d H:i:s',
        'is_push'     => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
    ];
}
