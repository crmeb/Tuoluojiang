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

namespace App\Http\Dao\Schedule;

use App\Http\Model\Schedule\ScheduleRecord;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * 日程提醒记录表.
 */
class ScheduleRecordDao extends BaseDao
{
    use ListSearchTrait;

    protected function setModel()
    {
        return ScheduleRecord::class;
    }
}
