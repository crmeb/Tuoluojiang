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

namespace App\Http\Service\Attendance\Traits;

use App\Constants\AttendanceClockEnum;
use App\Http\Service\Attendance\AttendanceArrangeService;
use App\Http\Service\Attendance\AttendanceShiftService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计更新方法.
 */
trait AttendanceStatisticsUpdateTrait
{
    /**
     * 核对打卡记录.
     * @param mixed $info
     * @param mixed $tz
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function checkClockRecord($info, Carbon $dateObj, int $status, int $clockNumber, $tz): array
    {
        if (! app()->get(AttendanceArrangeService::class)->dayIsRest($info->uid, $dateObj->toDateString()) && $info->shift_id > 1) {
            $isUpdate  = false;
            $shifts    = AttendanceClockEnum::SHIFT_CLASS;
            $isSameDay = now($tz)->dayOfYear == $dateObj->dayOfYear;
            for ($i = 0; $i <= $info->shift_data['number'] * 2 - 1; ++$i) {
                if ($isSameDay
                    && $info->{$shifts[$i] . '_shift_status'} < 1
                    && is_null($info->{$shifts[$i] . '_shift_time'})
                    && $dateObj->timestamp > $this->getClockEndTime((int) $info->uid, $info, $i, $tz)) {
                    $info     = $this->updateShiftStatistics($info, $dateObj, $i, withFreeClock: true);
                    $isUpdate = true;
                }
            }

            if ($isUpdate) {
                [$status, $clockNumber] = $this->getClockNumber($dateObj, $info, $tz);
            }
        }
        return [$info, $status, $clockNumber];
    }

    /**
     * 更新默认考勤.
     * @param mixed $info
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updateDefaultStatistics($info, Carbon $dateObj, array $data): mixed
    {
        $date   = $dateObj->toDateTimeString();
        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        if ($data['update_number'] != '') {
            $clockNumber = (int) $data['update_number'];
            if ($clockNumber == 0 && ! is_null($info->two_shift_time)) {
                throw $this->exception('无法更新打卡');
            }

            $info->{$shifts[$clockNumber] . '_shift_time'}            = $date;
            $info->{$shifts[$clockNumber] . '_shift_record_id'}       = $data['record_id'];
            $info->{$shifts[$clockNumber] . '_shift_location_status'} = $data['location_status'] ?? 0;
        } else {
            $number                                     = is_null($info->one_shift_time) ? 'one' : 'two';
            $info->{$number . '_shift_time'}            = $date;
            $info->{$number . '_shift_record_id'}       = $data['record_id'];
            $info->{$number . '_shift_location_status'} = $data['location_status'] ?? 0;
        }
        $info->actual_work_hours = app()->get(AttendanceShiftService::class)->getActualWorkHours($info, $dateObj->toDateString());
        return $info->save();
    }

    /**
     * 更新考勤数据.
     * @param mixed $info
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateShiftStatistics($info, Carbon $dateObj, int $clockNumber, array $data = [], bool $withFreeClock = false): mixed
    {
        // 当日次日
        $isAfter      = 0;
        $clockStatus  = 1;
        $freeClock    = false;
        $tz           = config('app.timezone');
        $startTimeObj = Carbon::parse($info->created_at, $tz)->endOfDay();
        $dateString   = $startTimeObj->toDateString();
        $shifts       = AttendanceClockEnum::SHIFT_CLASS;

        if (isset($data['update_number']) && $data['update_number'] !== '') {
            $clockNumber = (int) $data['update_number'];
            $slot        = $shifts[$clockNumber] ?? '';
            $hasRecord   = $slot
                && (! is_null($info->{$slot . '_shift_time'}) || (int) ($info->{$slot . '_shift_record_id'} ?? 0) > 0);
            if ($hasRecord && in_array($clockNumber, [0, 2])) {
                $locationStatus = $data['location_status'] ?? 0;
                $clockStatus    = $this->getClockStatus($clockNumber, $dateObj, $info);
                if ($data['is_external'] < 1 && $info->{$shifts[$clockNumber] . '_shift_location_status'} == 2 && $locationStatus < 2 && $clockStatus == 1) {
                    $info->{$shifts[$clockNumber] . '_shift_is_after'}        = $dateObj->gt($startTimeObj) ? 1 : 0;
                    $info->{$shifts[$clockNumber] . '_shift_time'}            = $dateObj->toDateTimeString();
                    $info->{$shifts[$clockNumber] . '_shift_record_id'}       = $data['record_id'] ?? 0;
                    $info->{$shifts[$clockNumber] . '_shift_location_status'} = $locationStatus;
                    if (! $info->save()) {
                        throw $this->exception('打卡数据更新异常, 请稍后再试');
                    }
                    return $info;
                }

                throw $this->exception('无法更新打卡, 请刷新后重试');
            }
        } else {
            $withFreeClock && $freeClock = in_array($clockNumber, [1, 3]);
        }
        $rule      = $info->shift_data['rules'][$clockNumber < 2 ? 0 : 1];
        $freeClock = $freeClock && $rule['free_clock'] > 0;

        // associated approve
        [$approveFreeClock, $approveLocationStatus] = $this->calcAssociatedApprove($info, $dateString, $clockNumber, $rule, $tz);
        if ($approveFreeClock && ! $freeClock) {
            $freeClock = $approveFreeClock;
        }

        if (isset($data['location_status']) && $data['location_status'] == 2 && $approveLocationStatus == 1) {
            $data['location_status'] = $approveLocationStatus;
        }

        $workObj = $this->{in_array($clockNumber, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $dateString, $tz);
        // 当前班次免打卡
        if ($freeClock) {
            $info->{$shifts[$clockNumber] . '_shift_time'} = $workObj->toDateTimeString();
        } else {
            $isAfter     = $dateObj->gt($startTimeObj) ? 1 : 0;
            $clockStatus = $this->getClockStatus($clockNumber, $dateObj, $info);
            if ($clockStatus != AttendanceClockEnum::LACK_CARD) {
                $info->{$shifts[$clockNumber] . '_shift_time'} = $dateObj->toDateTimeString();
            }
        }

        $info->{$shifts[$clockNumber] . '_shift_status'}          = $clockStatus;
        $info->{$shifts[$clockNumber] . '_shift_is_after'}        = $isAfter;
        $info->{$shifts[$clockNumber] . '_shift_record_id'}       = $data['record_id'] ?? 0;
        $info->{$shifts[$clockNumber] . '_shift_location_status'} = $data['location_status'] ?? 0;

        $info->actual_work_hours = app()->get(AttendanceShiftService::class)->getActualWorkHours($info, $startTimeObj->toDateString(), $tz);
        if (! $info->save()) {
            throw $this->exception('打卡数据更新异常, 请稍后再试');
        }

        return $info;
    }
}
