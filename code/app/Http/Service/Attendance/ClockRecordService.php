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

namespace App\Http\Service\Attendance;

use App\Http\Contract\Attendance\ClockRecordInterface;
use App\Http\Dao\Attendance\ClockRecordDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;

/**
 * 打卡记录
 * Class ClockRecordService.
 */
class ClockRecordService extends BaseService implements ClockRecordInterface
{
    use ResourceServiceTrait;

    public function __construct(ClockRecordDao $dao)
    {
        $this->dao = $dao;
    }
}
