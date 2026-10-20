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

namespace App\Http\Service\Schedule;

use App\Http\Contract\Schedule\ScheduleInterface;
use App\Http\Dao\Schedule\ScheduleDao;
use App\Http\Dao\Schedule\ScheduleRemindDao;
use App\Http\Dao\Schedule\ScheduleReplyDao;
use App\Http\Dao\Schedule\ScheduleTaskDao;
use App\Http\Dao\Schedule\ScheduleTypeDao;
use App\Http\Dao\Schedule\ScheduleUserDao;
use App\Http\Service\Schedule\Traits\ScheduleCalendarTrait;
use App\Http\Service\Schedule\Traits\ScheduleCrudTrait;
use App\Http\Service\Schedule\Traits\SchedulePeriodTrait;
use App\Http\Service\Schedule\Traits\ScheduleRemindTrait;
use App\Http\Service\Schedule\Traits\ScheduleReplyTrait;
use App\Http\Service\Schedule\Traits\ScheduleTypeTrait;
use App\Http\Service\BaseService;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

/**
 * 日程相关业务门面：对外接口与 DI 绑定不变，具体能力按职责拆至 Traits.
 * @mixin ScheduleDao
 */
class ScheduleService extends BaseService implements ScheduleInterface
{
    use ScheduleCalendarTrait;
    use ScheduleCrudTrait;
    use SchedulePeriodTrait;
    use ScheduleRemindTrait;
    use ScheduleReplyTrait;
    use ScheduleTypeTrait;

    public ScheduleRemindDao $remindDao;

    public int $cacheTtl;

    protected ScheduleTypeDao $typeDao;

    protected ScheduleUserDao $userDao;

    protected ScheduleTaskDao $taskDao;

    protected ScheduleReplyDao $replyDao;

    /**
     * @var Application|mixed|Repository
     */
    protected mixed $timeZone;

    public function __construct(
        ScheduleDao $dao,
        ScheduleRemindDao $remindDao,
        ScheduleTypeDao $typeDao,
        ScheduleUserDao $userDao,
        ScheduleTaskDao $taskDao,
        ScheduleReplyDao $replyDao,
    ) {
        $this->dao       = $dao;
        $this->remindDao = $remindDao;
        $this->typeDao   = $typeDao;
        $this->userDao   = $userDao;
        $this->taskDao   = $taskDao;
        $this->replyDao  = $replyDao;
        $this->timeZone  = config('app.timezone');
        $this->cacheTtl  = (int) sys_config('system_cache_ttl', 3600);
    }

    public function userDao()
    {
        return $this->userDao;
    }

    public function remindDao()
    {
        return $this->remindDao;
    }
}
