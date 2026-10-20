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

namespace App\Providers;

use App\Http\Contract\Approve\ApproveApplyInterface;
use App\Http\Contract\Attendance\AttendanceArrangeInterface;
use App\Http\Contract\Attendance\AttendanceGroupInterface;
use App\Http\Contract\Attendance\AttendanceShiftInterface;
use App\Http\Contract\Attendance\CalendarConfigInterface;
use App\Http\Contract\Attendance\ClockRecordInterface;
use App\Http\Contract\Attendance\RosterCycleInterface;
use App\Http\Contract\Customer\CustomerContractSubscribeInterface;
use App\Http\Contract\Customer\CustomerFollowInterface;
use App\Http\Contract\Customer\CustomerSubscribeInterface;
use App\Http\Contract\Common\CommonInterface;
use App\Http\Contract\Company\CompanyInterface;
use App\Http\Contract\Company\EmployeeTrainInterface;
use App\Http\Contract\Company\HayGroupInterface;
use App\Http\Contract\Company\PromotionDataInterface;
use App\Http\Contract\Company\PromotionInterface;
use App\Http\Contract\Config\AssessConfigInterface;
use App\Http\Contract\Frame\FrameInterface;
use App\Http\Contract\Notice\NoticeInterface;
use App\Http\Contract\Schedule\ScheduleInterface;
use App\Http\Contract\System\LogInterface;
use App\Http\Contract\System\MenusInterface;
use App\Http\Contract\System\RolesInterface;
use App\Http\Contract\User\UserInterface;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Attendance\AttendanceArrangeService;
use App\Http\Service\Attendance\AttendanceGroupService;
use App\Http\Service\Attendance\AttendanceShiftService;
use App\Http\Service\Attendance\CalendarConfigService;
use App\Http\Service\Attendance\ClockRecordService;
use App\Http\Service\Attendance\RosterCycleService;
use App\Http\Service\CommonService;
use App\Http\Service\Company\CompanyService;
use App\Http\Service\Config\AssessConfigService;
use App\Http\Service\Customer\FollowUpService;
use App\Http\Service\Customer\SubscribeService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\Notice\NoticeService;
use App\Http\Service\Schedule\ScheduleService;
use App\Http\Service\System\LogService;
use App\Http\Service\System\MenusService;
use App\Http\Service\System\RolesService;
use App\Http\Service\Train\EmployeeTrainService;
use App\Http\Service\Train\HayGroupService;
use App\Http\Service\Train\PromotionDataService;
use App\Http\Service\Train\PromotionService;
use Illuminate\Support\ServiceProvider;

/**
 * 项目服务绑定.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(UserInterface::class, AdminService::class);
        $this->app->singleton(LogInterface::class, LogService::class);
        $this->app->singleton(CommonInterface::class, CommonService::class);
        $this->app->singleton(AssessConfigInterface::class, AssessConfigService::class);
        $this->app->singleton(FrameInterface::class, FrameService::class);
        $this->app->singleton(CompanyInterface::class, CompanyService::class);
        $this->app->singleton(NoticeInterface::class, NoticeService::class);
        $this->app->singleton(ScheduleInterface::class, ScheduleService::class);
        $this->app->singleton(MenusInterface::class, MenusService::class);
        $this->app->singleton(RolesInterface::class, RolesService::class);
        $this->app->singleton(ApproveApplyInterface::class, ApproveApplyService::class);
        $this->app->singleton(CustomerSubscribeInterface::class, SubscribeService::class);
        $this->app->singleton(CustomerContractSubscribeInterface::class, SubscribeService::class);
        $this->app->singleton(CustomerFollowInterface::class, FollowUpService::class);
        $this->app->singleton(EmployeeTrainInterface::class, EmployeeTrainService::class);
        $this->app->singleton(PromotionInterface::class, PromotionService::class);
        $this->app->singleton(PromotionDataInterface::class, PromotionDataService::class);
        $this->app->singleton(HayGroupInterface::class, HayGroupService::class);
        $this->app->singleton(AttendanceGroupInterface::class, AttendanceGroupService::class);
        $this->app->singleton(AttendanceShiftInterface::class, AttendanceShiftService::class);
        $this->app->singleton(ClockRecordInterface::class, ClockRecordService::class);
        $this->app->singleton(RosterCycleInterface::class, RosterCycleService::class);
        $this->app->singleton(CalendarConfigInterface::class, CalendarConfigService::class);
        $this->app->singleton(AttendanceArrangeInterface::class, AttendanceArrangeService::class);
    }
}
