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
use App\Http\Service\Attendance\AttendanceApplyRecordService;
use App\Http\Service\Attendance\AttendanceClockService;
use App\Http\Service\Attendance\AttendanceGroupService;
use App\Http\Service\Attendance\AttendanceShiftService;
use App\Http\Service\Attendance\AttendanceStatisticsLeaveService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计团队方法
 */
trait AttendanceStatisticsTeamTrait
{
    /**
     * 团队统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTeamStatistics(string $uuid, string $date = ''): array
    {
        $uid          = uuid_to_uid($uuid);
        $tz           = config('app.timezone');
        $dateObj      = $date ? Carbon::parse($date, $tz) : now($tz);
        $date         = $dateObj->toDateString();
        $groupService = app()->get(AttendanceGroupService::class);
        $where        = ['date' => $date, 'uid' => $groupService->getTeamMember($uid)];
        $normalWhere  = ['date' => $date, 'uid' => $groupService->getTeamMember($uid, filter: false)];
        $statistics   = [
            'deadline'    => '',
            'total'       => $this->dao->getCountByUid($normalWhere),
            'work_hours'  => sprintf('%.1f', $this->dao->avg($normalWhere, 'actual_work_hours')),
            'leave_early' => $this->getShiftStatusCount($where['uid'], $date, [AttendanceClockEnum::LEAVE_EARLY]),
            'late'        => $this->getShiftStatusCount($where['uid'], $date, AttendanceClockEnum::ALL_LATE),
            'lack_card'   => $this->getShiftStatusCount($where['uid'], $date, AttendanceClockEnum::ALL_LACK_CARD),
            'abnormal'    => $this->dao->getCountByUid(array_merge($where, ['abnormal_status' => AttendanceClockEnum::NORMAL])),
        ];

        $statistics['normal'] = $statistics['total'] - $statistics['abnormal'];
        return $statistics;
    }

    /**
     * 团队外勤统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTeamExternalStatistics(string $uuid, string $date = ''): array
    {
        $tz             = config('app.timezone');
        $members        = app()->get(AttendanceGroupService::class)->getTeamMember($uuid);
        $dateObj        = $date ? Carbon::parse($date, $tz) : now($tz);
        [$page, $limit] = $this->getPageValue();
        $statistics     = $this->dao->select(
            ['date' => $dateObj->toDateString(), 'uid' => $members, 'location_status_gt' => AttendanceClockEnum::NO_NEED_CLOCK],
            ['*'],
            ['card' => fn ($q) => $q->select(['id', 'name', 'job', 'avatar', 'phone'])->with(['job' => fn ($q) => $q->select(['id', 'name']), 'frame' => fn ($q) => $q->select(['frame.id', 'name'])]),
            ],
            $page,
            $limit
        );

        $list   = [];
        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        foreach ($statistics as $item) {
            $external = [];
            for ($i = 0; $i < $item->shift_data['number'] * 2; ++$i) {
                $status                = $item->{$shifts[$i] . '_shift_location_status'};
                $status && $external[] = ['location_status' => $status, 'type' => in_array($i, [0, 2]) ? 1 : 2];
            }
            $list[] = ['card' => $item->card, 'external' => $external];
        }
        return $list;
    }

    /**
     * 团队上下班明细.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTeamCommuteDetails(string $uuid, string $date = '', array $status = []): array
    {
        $tz      = config('app.timezone');
        $dateObj = $date ? Carbon::parse($date, $tz) : now($tz);

        [$page, $limit] = $this->getPageValue();
        $statistics     = $this->dao->select(
            ['date' => $dateObj->toDateString(), 'uid' => app()->get(AttendanceGroupService::class)->getTeamMember($uuid)],
            ['*'],
            ['card' => fn ($q) => $q->select(['id', 'name', 'job', 'avatar', 'phone'])->with(['job' => fn ($q) => $q->select(['id', 'name']), 'frame' => fn ($q) => $q->select(['frame.id', 'name'])]),
            ],
            $page,
            $limit
        );

        $list   = [];
        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        $leaveDays = app()->get(AttendanceStatisticsLeaveService::class)->getLeaveDaysByStatistics($where['uid'], $date, 'date');
        foreach ($statistics as $item) {
            $lackNum  = 0;
            $external = $status = $locationStatus = [];
            $shiftNum = $item->shift_id < 2 ? 2 : $item->shift_data['number'] * 2;
            $leaveDay = (string) ($leaveDays[$item->id] ?? '0.00');
            for ($i = 0; $i < $shiftNum; ++$i) {
                $shiftStatus = $item->{$shifts[$i] . '_shift_status'};
                if ($shiftStatus == AttendanceClockEnum::LACK_CARD) {
                    ++$lackNum;
                }
                $shiftStatus > 1 && $status[$shiftStatus]                         = $shiftStatus;
                $shiftLocationStatus                                              = $item->{$shifts[$i] . '_shift_location_status'};
                $shiftLocationStatus > 0 && $locationStatus[$shiftLocationStatus] = $shiftLocationStatus;
                $external                                                         = ['status' => array_values($status), 'location_status' => array_values($locationStatus)];
            }

            $list[] = ['card' => $item->card, 'absenteeism' => ($item->shift_id > 1 && $lackNum == $shiftNum && bccomp($leaveDay, '0', 2) <= 0) ? 1 : 0, 'external' => $external];
        }
        return $list;
    }

    /**
     * 团队考勤明细
     * 1：异常；2：迟到；3：严重迟到；4：早退；5：缺卡；6：旷工；7：外勤卡；8：异常外勤；.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTeamAttendanceStatistics(string $uuid, string $date = '', array $status = []): array
    {
        $tz   = config('app.timezone');
        $date = ($date ? Carbon::parse($date, $tz) : now($tz))->format('Y-m');

        [$page, $limit] = $this->getPageValue();
        $statistics     = $this->dao->getStatisticsMemberList(
            ['month' => $date, 'uid' => app()->get(AttendanceGroupService::class)->getTeamMember($uuid)],
            ['uid'],
            ['card' => fn ($q) => $q->select(['id', 'name', 'job', 'avatar', 'phone'])->with(['job' => fn ($q) => $q->select(['id', 'name']), 'frame' => fn ($q) => $q->select(['frame.id', 'name'])])],
            $page,
            $limit
        );

        $list   = [];
        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        $leaveDays = app()->get(AttendanceStatisticsLeaveService::class)->getLeaveDaysByStatistics($where['uid'], $date, 'month');
        foreach ($statistics as $item) {
            $num          = 0; // 异常数量
            $status       = 0; // 异常状态
            $recordStatus = 0; // 临时状态
            $leaveDay     = (string) ($leaveDays[$item->id] ?? '0.00');
            $records      = toArray($this->dao->select(['month' => $date, 'uid' => $item->uid], [
                'id', 'uid', 'shift_id', 'shift_data',
                'one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status',
                'one_shift_location_status', 'two_shift_location_status', 'three_shift_location_status', 'four_shift_location_status',
            ]));
            foreach ($records as $record) {
                if ($record['shift_id'] < 2) {
                    continue;
                }
                $recordAbnormal = 0; // 当前异常数量
                $recordAbsent   = 0; //  当前缺卡数量
                $shiftNum       = $record['shift_data']['number'] * 2;
                for ($i = 0; $i < $shiftNum; ++$i) {
                    $shiftStatus         = $record[$shifts[$i] . '_shift_status'];
                    $shiftLocationStatus = $record[$shifts[$i] . '_shift_location_status'];
                    if ($shiftStatus > AttendanceClockEnum::NORMAL || $shiftLocationStatus > AttendanceClockEnum::OFFICE_OUTSIDE) {
                        ++$recordAbnormal;
                        if ($shiftStatus == AttendanceClockEnum::LACK_CARD) {
                            ++$recordAbsent;
                        }

                        if ($shiftStatus) {
                            $tmpStatus    = in_array($shiftStatus, AttendanceClockEnum::ALL_LACK_CARD) ? 5 : $shiftStatus;
                            $recordStatus = $recordStatus == 0 ? $tmpStatus : ($recordStatus != $tmpStatus ? 1 : $tmpStatus);
                        }

                        if ($shiftLocationStatus) {
                            $recordStatus = $recordStatus == 0 ? 8 : ($recordStatus != 8 ? 1 : 8);
                        }
                    }
                }

                // 旷工
                if ($recordAbsent == $shiftNum && bccomp($leaveDay, '0', 2) <= 0) {
                    $recordStatus = 6;
                    ++$num;
                } else {
                    $num += $recordAbnormal;
                }

                if ($recordStatus && ! $status) {
                    $status = $recordStatus;
                }
            }

            $list[] = ['card' => $item->card, 'num' => $num, 'status' => $status == $recordStatus ? $status : 1];
        }
        return $list;
    }

    /**
     * 团队加班明细.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTeamOvertimeStatistics(string $uuid, array $where): array
    {
        $list         = [];
        $uid          = app()->get(AttendanceGroupService::class)->getTeamMember($uuid);
        $overTimeData = app()->get(AttendanceApplyRecordService::class)->getOverTimeData($where['month'], $where['date_type'], $uid);
        if (empty($overTimeData)) {
            return $list;
        }

        [$page, $limit] = $this->getPageValue();
        $statistics     = $this->dao->getStatisticsMemberList(
            ['month' => $where['month'], 'uid' => array_keys($overTimeData)],
            ['id', 'uid'],
            ['card' => fn ($q) => $q->select(['id', 'name', 'job', 'avatar', 'phone'])->with(['job' => fn ($q) => $q->select(['id', 'name']), 'frame' => fn ($q) => $q->select(['frame.id', 'name'])])],
            $page,
            $limit
        );

        foreach ($statistics as $item) {
            $list[] = ['card' => $item->card, 'num' => $overTimeData[$item->uid] ?? 0];
        }
        return $list;
    }

    /**
     * 团队假勤明细.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTeamLeaveStatistics(string $uuid, array $where): array
    {
        $list  = [];
        $tz    = config('app.timezone');
        $month = ($where['month'] ? Carbon::parse($where['month'], $tz) : now($tz))->format('Y-m');

        $uid       = app()->get(AttendanceGroupService::class)->getTeamMember($uuid);
        $LeaveData = app()->get(AttendanceApplyRecordService::class)->getLeaveData($uid, $month, (array) $where['status']);
        if (empty($LeaveData)) {
            return $list;
        }
        [$page, $limit] = $this->getPageValue();
        $statistics     = $this->dao->getStatisticsMemberList(
            ['month' => $month, 'uid' => array_keys($LeaveData)],
            ['id', 'uid'],
            ['card' => fn ($q) => $q->select(['id', 'name', 'job', 'avatar', 'phone'])->with(['job' => fn ($q) => $q->select(['id', 'name']), 'frame' => fn ($q) => $q->select(['frame.id', 'name'])])],
            $page,
            $limit
        );

        foreach ($statistics as $item) {
            $list[] = ['card' => $item->card, 'num' => $LeaveData[$item->uid] ?? 0];
        }
        return $list;
    }

    /**
     * 获取团队月报打卡统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getTeamMonthStatistics(string $uuid, string $month): array
    {
        $uid          = uuid_to_uid($uuid);
        $groupService = app()->get(AttendanceGroupService::class);

        // team member
        $teamMember = $groupService->getTeamMember($uid);
        $where      = ['month' => $month, 'uid' => $teamMember];

        [$absenteeism, $lackCard, $locationAbnormal] = $this->getTeamLackCardAndAbsenteeismNum($teamMember, $month);

        // all member
        $allMember       = $groupService->getTeamMember($uid, filter: false);
        $clockStatistics = [
            'total'             => count($allMember),
            'work_hours'        => sprintf('%.2f', $this->dao->avg(['month' => $month, 'uid' => $allMember], 'actual_work_hours')),
            'late'              => $this->dao->getCountByUid(array_merge($where, ['status' => AttendanceClockEnum::ALL_LATE])),
            'absenteeism'       => $absenteeism,
            'leave_early'       => $this->dao->getCountByUid(array_merge($where, ['status' => AttendanceClockEnum::LEAVE_EARLY])),
            'lack_card'         => $lackCard,
            'location_abnormal' => $locationAbnormal,
            'abnormal'          => $this->dao->getCountByUid(array_merge($where, ['abnormal_status' => AttendanceClockEnum::NORMAL])),
        ];

        $recordService      = app()->get(AttendanceApplyRecordService::class);
        $overtimeStatistics = [
            'work'    => $recordService->getOvertimeNumByDateType($teamMember, $month, 1),
            'rest'    => $recordService->getOvertimeNumByDateType($teamMember, $month, 2),
            'holiday' => 0,
        ];

        return [$clockStatistics, $overtimeStatistics, $recordService->getPersonLeaveMonthStatistics($teamMember, $month)];
    }

    /**
     * 获取团队旷工/缺卡异常人数.
     * @return int[]
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    private function getTeamLackCardAndAbsenteeismNum(array|int $uid, string $date): array
    {
        $lackCard         = 0;
        $absenteeism      = $locationAbnormal = [];
        $shifts           = AttendanceClockEnum::SHIFT_CLASS;
        $records          = $this->dao->select(['month' => $date, 'uid' => $uid], [
            'id', 'uid', 'shift_id', 'shift_data',
            'one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status',
            'one_shift_location_status', 'two_shift_location_status', 'three_shift_location_status', 'four_shift_location_status',
        ]);
        $leaveDays        = app()->get(AttendanceStatisticsLeaveService::class)->getLeaveDaysByStatistics($uid, $date, 'month');
        foreach ($records as $record) {
            if ($record->shift_id < 2) {
                continue;
            }
            $absentNum = 0;
            $lackNum   = 0;
            $shiftNum  = $record->shift_data['number'] * 2;
            $leaveDay  = (string) ($leaveDays[$record->id] ?? '0.00');
            for ($i = 0; $i < $shiftNum; ++$i) {
                $shiftStatus         = $record->{$shifts[$i] . '_shift_status'};
                $shiftLocationStatus = $record->{$shifts[$i] . '_shift_location_status'};

                if ($shiftStatus == AttendanceClockEnum::LACK_CARD) {
                    ++$absentNum;
                }

                if (in_array($shiftStatus, AttendanceClockEnum::ALL_LACK_CARD)) {
                    ++$lackNum;
                }

                if ($shiftLocationStatus == AttendanceClockEnum::OFFICE_ABNORMAL) {
                    $locationAbnormal[$record->uid] = 1;
                }
            }

            if (bccomp($leaveDay, '1', 2) >= 0) {
                continue;
            }

            if ($absentNum == $shiftNum && bccomp($leaveDay, '0', 2) <= 0) {
                $absenteeism[$record->uid] = 1;
            } else {
                $lackCard += $lackNum;
            }
        }
        return [count($absenteeism), $lackCard, count($locationAbnormal)];
    }

    /**
     * 统计指定日期内命中状态的打卡槽位次数.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getShiftStatusCount(array|int $uid, string $date, array $statuses): int
    {
        $count   = 0;
        $shifts  = AttendanceClockEnum::SHIFT_CLASS;
        $records = $this->dao->select(['date' => $date, 'uid' => $uid], [
            'id', 'uid', 'shift_id', 'shift_data',
            'one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status',
        ]);

        foreach ($records as $record) {
            if ($record->shift_id < 2) {
                continue;
            }

            $shiftNum = $record->shift_data['number'] * 2;
            for ($i = 0; $i < $shiftNum; ++$i) {
                if (in_array($record->{$shifts[$i] . '_shift_status'}, $statuses)) {
                    ++$count;
                }
            }
        }
        return $count;
    }
}
