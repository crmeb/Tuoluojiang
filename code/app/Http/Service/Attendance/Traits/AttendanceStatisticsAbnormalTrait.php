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

use App\Constants\ApproveEnum;
use App\Constants\AttendanceClockEnum;
use App\Http\Service\Attendance\AttendanceApplyRecordService;
use App\Http\Service\Attendance\AttendanceGroupService;
use App\Http\Service\Attendance\AttendanceShiftService;
use App\Http\Service\Approve\ApproveApplyService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计异常处理方法
 */
trait AttendanceStatisticsAbnormalTrait
{
    /**
     * 获取异常日期
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAbnormalDateList(int $uid, bool $withRecord = false): array
    {
        $group = app()->get(AttendanceGroupService::class)->getGroupByUid($uid);
        if ($group && ! $this->checkGroupRepairAllowed($uid, $group)) {
            return [];
        }

        $where  = ['uid' => $uid];
        $select = $repairCondition = $shiftStatus = [];

        if ($group) {
            if (in_array(5, $group->repair_type)) {
                $repairCondition['location_status'] = AttendanceClockEnum::OFFICE_ABNORMAL;
            }

            if (in_array(1, $group->repair_type)) {
                $shiftStatus = array_merge($shiftStatus, AttendanceClockEnum::ALL_LACK_CARD);
            }

            if (in_array(2, $group->repair_type)) {
                $shiftStatus = array_merge($shiftStatus, [AttendanceClockEnum::LATE]);
            }

            if (in_array(3, $group->repair_type)) {
                $shiftStatus = array_merge($shiftStatus, [AttendanceClockEnum::EXTREME_LATE]);
            }

            if (in_array(4, $group->repair_type)) {
                $shiftStatus = array_merge($shiftStatus, [AttendanceClockEnum::LEAVE_EARLY]);
            }

            // limit time repair allowed
            if ($group->is_limit_time) {
                $tz     = config('app.timezone');
                $nowObj = now($tz);

                if ($group->limit_time < 1) {
                    $where['date'] = $nowObj->toDateString();
                } else {
                    $where['time'] = $nowObj->subDays($group->limit_time)->startOfDay()->format('Y/m/d') . '-' . now($tz)->startOfDay()->format('Y/m/d');
                }
            }
            if ($shiftStatus) {
                $repairCondition['status'] = $shiftStatus;
            }
        } else {
            $repairCondition = [
                'status'          => array_merge(AttendanceClockEnum::ALL_LACK_CARD, AttendanceClockEnum::LATE_AND_LEAVE_EARLY),
                'location_status' => AttendanceClockEnum::OFFICE_ABNORMAL,
            ];
        }

        $list = $this->dao->select(array_merge($where, ['repair_condition' => $repairCondition]));
        foreach ($list as $item) {
            $option = ['value' => $item->id, 'label' => $item->date];
            if ($withRecord) {
                $option['record'] = $this->getAbnormalRecordListWithApprove($item);
            }
            $select[] = $option;
        }
        return $select;
    }

    /**
     * 获取审批异常打卡记录.
     */
    public function getAbnormalRecordListWithApprove(mixed $abnormal): array
    {
        $select = [];
        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        $rule   = $abnormal->shift_data['rules'][0];
        for ($i = 0; $i <= $abnormal->shift_data['number'] * 2 - 1; ++$i) {
            // no need clock
            if (in_array($i, [1, 3]) && $rule['free_clock'] && $abnormal->{$shifts[$i] . '_shift_status'} == 0) {
                continue;
            }

            if ($abnormal->{$shifts[$i] . '_shift_status'} > 0 && $abnormal->{$shifts[$i] . '_shift_status'} < 2 && $abnormal->{$shifts[$i] . '_shift_location_status'} < 2) {
                continue;
            }

            if ($i > 1) {
                $rule = $abnormal->shift_data['rules'][1];
            }

            $workType = in_array($i, [0, 2]);
            $select[] = ['value' => $i + 1, 'label' => ($workType ? '上班' : '下班') . ' ' . $rule[$workType ? 'work_hours' : 'off_hours']];
        }

        return $select;
    }

    /**
     * 异常时间.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRecordTimeWithAbnormalId(int $abnormalId, int $recordId): string
    {
        $abnormal = $this->dao->get(['id' => $abnormalId]);
        $shifts   = AttendanceClockEnum::SHIFT_CLASS;
        $rule     = $abnormal?->shift_data['rules'][$recordId > 1 ? 1 : 0];
        if (is_null($rule)) {
            return '';
        }

        if (in_array($recordId, [1, 3]) && $rule['free_clock'] && $abnormal->{$shifts[$recordId] . '_shift_status'} == 0) {
            return '';
        }

        if ($recordId >= 0 && $abnormal->{$shifts[$recordId] . '_shift_status'} > 0 && $abnormal->{$shifts[$recordId] . '_shift_status'} < 2 && $abnormal->{$shifts[$recordId] . '_shift_location_status'} < 2) {
            return '';
        }

        $workType = in_array($recordId, [0, 2]);
        return ($workType ? '上班' : '下班') . ' ' . $rule[$workType ? 'work_hours' : 'off_hours'];
    }

    /**
     * 获取异常记录.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getAbnormalRecordList(string $uuid, int $id): array
    {
        $info = $this->dao->get(['id' => $id, 'uid' => uuid_to_uid($uuid)]);
        if (! $info) {
            throw $this->exception('暂无可操作记录！');
        }

        $select = [];
        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        $rule   = $info->shift_data['rules'][0];
        for ($i = 0; $i <= $info->shift_data['number'] * 2 - 1; ++$i) {
            // no need clock
            if (in_array($i, [1, 3]) && $rule['free_clock'] && $info->{$shifts[$i] . '_shift_status'} == 0) {
                continue;
            }

            if ($info->{$shifts[$i] . '_shift_status'} > 0 && $info->{$shifts[$i] . '_shift_status'} < 2 && $info->{$shifts[$i] . '_shift_location_status'} < 2) {
                continue;
            }

            if ($i > 1) {
                $rule = $info->shift_data['rules'][1];
            }

            $workType = in_array($i, [0, 2]);
            $select[] = ['shift_number' => $i, 'title' => $workType ? '上班' : '下班', 'time' => $rule[$workType ? 'work_hours' : 'off_hours']];
        }

        return $select;
    }

    /**
     * 更新异常班次
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateAbnormalShiftStatus(int $userId, int $type, string $startTime, string $endTime, string $tz = '', array $others = []): bool
    {
        $tz         = $tz ?: config('app.timezone');
        $abnormalId = $others['abnormal_id'] ?? 0;
        $recordId   = $others['record_id'] ?? 0;
        $where      = $type == ApproveEnum::PERSONNEL_SIGN ? ['id' => $abnormalId] : ['abnormal_status' => AttendanceClockEnum::NORMAL];
        $list       = $this->dao->select(array_merge($where, ['uid' => $userId]));
        if ($list->isEmpty()) {
            return true;
        }

        $isShiftStatus = $isLocationStatus = $isSignStatus = false;

        // out and trip
        if (in_array($type, [ApproveEnum::PERSONNEL_OUT, ApproveEnum::PERSONNEL_TRIP])) {
            $isLocationStatus = true;
        }

        // holiday
        if ($type == ApproveEnum::PERSONNEL_HOLIDAY) {
            $isLocationStatus = $isShiftStatus = true;
        }

        // sign
        if ($type == ApproveEnum::PERSONNEL_SIGN) {
            $isSignStatus = true;
        }

        return $this->transaction(function () use ($startTime, $endTime, $tz, $isShiftStatus, $isLocationStatus, $list, $isSignStatus, $recordId) {
            if (! $isSignStatus) {
                $startObj = Carbon::parse($startTime, $tz);
                $endObj   = Carbon::parse($endTime, $tz);
            }

            $shiftService = app()->get(AttendanceShiftService::class);
            $shifts       = AttendanceClockEnum::SHIFT_CLASS;

            foreach ($list as $item) {
                $isUpdate = false;
                $rule     = $item->shift_data['rules'][0] ?? [];
                if (! $rule) {
                    continue;
                }

                $dateString = Carbon::parse($item->created_at, $tz)->toDateString();

                for ($i = 0; $i <= ($item->shift_data['number'] ?? 1) * 2 - 1; ++$i) {
                    if ($i > 1) {
                        $rule = $item->shift_data['rules'][1];
                    }

                    $workObj = $this->{in_array($i, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $dateString, $tz);
                    if (! $isSignStatus && ($workObj->lt($startObj) || $workObj->gte($endObj))) {
                        continue;
                    }

                    // clock status
                    if (($isShiftStatus || ($isSignStatus && $i == $recordId)) && $item->{$shifts[$i] . '_shift_status'} > 1) {
                        $isUpdate = true;

                        $item->{$shifts[$i] . '_shift_status'} = 1;
                        $item->{$shifts[$i] . '_shift_time'}   = $workObj->toDateTimeString();
                    }

                    // location status
                    if ((($isShiftStatus || $isLocationStatus) || ($isSignStatus && $i == $recordId)) && $item->{$shifts[$i] . '_shift_location_status'} == 2) {
                        $isUpdate                                                                                               = true;
                        $item->{$shifts[$i] . '_shift_location_status'} == 2 && $item->{$shifts[$i] . '_shift_location_status'} = 1;
                    }
                }

                if (! $isUpdate) {
                    continue;
                }

                // work hours
                $item->actual_work_hours = $shiftService->getActualWorkHours($item, $dateString, $tz);
                $item->save();
            }
            return true;
        });
    }

    /**
     * 核对补卡条件.
     * @param mixed $group
     * @throws BindingResolutionException
     */
    private function checkGroupRepairAllowed(int $uid, $group): bool
    {
        if (! $group || ! $group->repair_allowed || empty($group->repair_type)) {
            return false;
        }

        $applyCount = app()->get(ApproveApplyService::class)->getApplyNumByTypes($uid, ApproveEnum::PERSONNEL_SIGN);
        if ($group->is_limit_number && (! $group->limit_number || $applyCount >= $group->limit_number)) {
            return false;
        }

        return true;
    }
}
