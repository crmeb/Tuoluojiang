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

namespace App\Http\Service\Attendance;

use App\Constants\ApproveEnum;
use App\Constants\AttendanceClockEnum;
use App\Constants\CacheEnum;
use App\Constants\ModuleEnum;
use App\Http\Dao\Attendance\AttendanceClockDao;
use App\Http\Dao\Attendance\AttendanceHandleRecordDao;
use App\Http\Dao\Attendance\AttendanceStatisticsDao;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Approve\ApproveHolidayTypeService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\utils\Regex;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤统计
 * Class AttendancePersonnelStatisticsService.
 */
class AttendancePersonnelStatisticsService extends BaseService
{
    private AttendanceHandleRecordDao $handleRecordDao;

    public function __construct(AttendanceStatisticsDao $dao, AttendanceHandleRecordDao $handleRecordDao)
    {
        $this->dao             = $dao;
        $this->handleRecordDao = $handleRecordDao;
    }

    /**
     * 过滤where条件.
     */
    public function filterWhere(int $uid, array $where, int $type): array
    {
        if (! $type && ($where['scope'] ?? '') === '') {
            $where['scope'] = 1;
        }

        $cacheKey = CacheEnum::TAG_ATTENDANCE . ':filter_where:' . md5(serialize(['uid' => $uid, 'where' => $where, 'type' => $type]));
        return Cache::tags([CacheEnum::TAG_ATTENDANCE])->remember($cacheKey, 30, function () use ($uid, $where, $type) {
            $groupService = app()->get(AttendanceGroupService::class);
            $whiteMembers = $groupService->getWhiteListMemberIds();
            if ($type) {
                $userId = 0;
                if (isset($where['user_id'])) {
                    $userId = $where['user_id'];
                    unset($where['user_id']);
                }
                $members = array_unique(array_merge($groupService->getTeamMember($uid), $whiteMembers));
                if ($userId && $uid != $userId && array_diff($userId, $members)) {
                    throw $this->exception('不能查看该员工考勤数据');
                }
                $where['uid'] = $userId ?: $uid;
            } else {
                $attendanceMembers = $groupService->getAttendanceMemberIds();
                $userId = [];
                if (isset($where['user_id'])) {
                    $userId = array_filter(array_map('intval', $where['user_id']));
                    unset($where['user_id']);
                }

                $where['uid'] = $uid;

                $members = array_unique(array_merge(app()->get(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::ATTENDANCE), $whiteMembers, [$uid]));
                if ($userId) {
                    $where['uid'] = array_intersect($userId, $members, $attendanceMembers);
                } else {
                    $frameService = app()->get(FrameService::class);

                    $frameId = intval($where['frame_id'] ?? '');
                    $groupId = intval($where['group_id'] ?? '');
                    $isAdmin = $groupService->isWhiteListAdmin($uid);

                    // 部门人员筛选（管理员也需要按选定的部门筛选）
                    if ($frameId) {
                        $frameUserIds = $frameService->getIdsByFrameIds($uid, [$frameId]);
                        // 管理员：直接使用部门人员；非管理员：与权限范围内人员取交集
                        $members = $isAdmin ? $frameUserIds : array_intersect($frameUserIds, $members);
                        $members = array_intersect($members, $attendanceMembers);
                        unset($where['frame_id']);
                    }
                    // 考勤组人员筛选
                    if ($groupId) {
                        $groupUserIds = $groupService->getMemberIdsById($groupId);
                        // 管理员：直接使用考勤组人员；非管理员：与权限范围内人员取交集
                        $members = $isAdmin ? $groupUserIds : array_intersect($groupUserIds, $members);
                        $members = array_intersect($members, $attendanceMembers);
                        unset($where['group_id']);
                    }

                    // 如果已通过 frame_id 或 group_id 筛选，直接使用筛选后的成员
                    if ($frameId || $groupId) {
                        $where['uid'] = $members;
                    } elseif (isset($where['scope'])) {
                        // scope: 1-在职员工, 2-离职员工, 0-全部
                        $departUserIds = app()->get(AdminInfoService::class)->column(['type' => 4], 'id');
                        $where['uid']  = match ((int) $where['scope']) {
                            1       => array_intersect(array_diff($members, $departUserIds), $attendanceMembers),
                            2       => $departUserIds,
                            default => array_merge(array_intersect($members, $attendanceMembers), $departUserIds)
                        };
                    }
                }
            }
            return $where;
        });
    }

    /**
     * 每日统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getDailyStatistics(int $uid, array $where, int $type = 0): array
    {
        [$page, $limit]  = $this->getPageValue();
        $where           = $this->filterWhere($uid, $where, $type);
        $list            = $this->dao->getList($where, ['*'], $page, $limit, 'id', ['card', 'frame']);
        $holidayTypeList = [];
        $this->appendDailyStatisticsData($list, $holidayTypeList);
        $count = $this->dao->count($where);
        return $this->listData($list, $count, ['holiday_type' => $holidayTypeList]);
    }

    /**
     * 个人每日统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUserStatistics(int $uid, array $where, int $type = 0): array
    {
        if (! in_array($where['uid'], app(FrameAssistService::class)->getScopeUid($uid))) {
            throw $this->exception('暂无权限查看该人员的出勤统计信息');
        }
        [$page, $limit]  = $this->getPageValue();
        $list            = $this->dao->getList($where, ['*'], $page, $limit, 'id', ['card', 'frame']);
        $holidayTypeList = [];
        $this->appendDailyStatisticsData($list, $holidayTypeList);
        $count = $this->dao->count($where);
        return $this->listData($list, $count, ['holiday_type' => $holidayTypeList]);
    }

    /**
     * 获取处理记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getRecordList(string $uuid, int $statisticsId): array
    {
        $where['uid']   = app()->get(AttendanceGroupService::class)->getTeamMember($uuid);
        [$page, $limit] = $this->getPageValue();
        $where          = ['statistics_id' => $statisticsId];
        $list           = $this->handleRecordDao->getList($where, ['id', 'shift_number', 'result', 'remark', 'source', 'uid', 'created_at'], $page, $limit, 'id', ['card']);
        $count          = $this->handleRecordDao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 月度统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getMonthlyStatistics(int $uid, array $where, array $field = ['id', 'uid', 'frame_id'], mixed $sort = null, array $with = ['card']): array
    {
        if ($where['month'] && ! preg_match(Regex::MONTH_TIME_RULE, $where['month'])) {
            throw $this->exception('考勤时间格式错误');
        }
        $tz      = config('app.timezone');
        $where   = $this->filterWhere($uid, $where, 0);
        $dateObj = ($where['month'] ? Carbon::parse($where['month'], $tz) : Carbon::now($tz));

        $month          = $dateObj->format('Y-m');
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getStatisticsMemberList($where, $field, $with, $page, $limit, $sort);
        $requiredDays   = app()->get(AttendanceArrangeService::class)->getRequiredDaysByUids(
            array_column($list->toArray(), 'uid'),
            Carbon::parse($dateObj, $tz)->startOfMonth(),
            Carbon::parse($dateObj, $tz)->endOfMonth()
        );

        $frameService  = app()->get(FrameService::class);
        $recordService = app()->get(AttendanceApplyRecordService::class);
        $leaveService  = app()->get(AttendanceStatisticsLeaveService::class);
        $monthStart    = Carbon::parse($dateObj, $tz)->startOfMonth()->format('Y-m-d H:i:s');
        $monthEnd      = Carbon::parse($dateObj, $tz)->endOfMonth()->format('Y-m-d H:i:s');
        $listUids      = array_values(array_unique(array_filter(array_map('intval', array_column($list->toArray(), 'uid')))));
        $clockCounts   = [];
        if ($listUids !== []) {
            $clockCounts = app()->get(AttendanceClockDao::class)
                ->search(['uid' => $listUids, 'date' => [$monthStart, $monthEnd]], false)
                ->selectRaw('uid, COUNT(*) as clock_count')
                ->groupBy('uid')
                ->pluck('clock_count', 'uid')
                ->toArray();
        }

        // holiday type list with destroy
        $prefetchTypeIds = $leaveService->getHolidayTypeIdByMonth($month, $where['uid']);
        $holidayTypeList = app()->get(ApproveHolidayTypeService::class)->getStatisticsListWithDestroy($prefetchTypeIds);
        foreach ($list as $item) {
            [$absenteeism, $lateCard, $earlyCard, $actualDays] = $this->getLackCardAndAbsenteeismNum($item->uid, $month);
            $item['required_days']                             = $requiredDays[$item->uid] ?? 0;
            $item['late']                                      = $this->dao->count(['month' => $month, 'uid' => $item->uid, 'status' => AttendanceClockEnum::ALL_LATE]);
            $item['leave_early']                               = $this->dao->count(['month' => $month, 'uid' => $item->uid, 'status' => AttendanceClockEnum::LEAVE_EARLY]);
            $item['late_card']                                 = $lateCard;
            $item['early_card']                                = $earlyCard;
            $item['absenteeism']                               = $absenteeism;
            $item['overtime_hours']                            = $recordService->getSumByMonth($item->uid, $month, ApproveEnum::PERSONNEL_OVERTIME);
            $item['trip_hours']                                = $recordService->getSumByMonth($item->uid, $month, ApproveEnum::PERSONNEL_TRIP);
            $item['out_hours']                                 = $recordService->getSumByMonth($item->uid, $month, ApproveEnum::PERSONNEL_OUT);
            $item['frame']                                     = toArray($frameService->get(['id' => $this->dao->value(['uid' => $item->uid], 'frame_id')], ['id', 'name']));
            $item['group']                                     = $this->dao->value(['uid' => $item->uid], 'group');
            $item['clock_count']                               = (int) ($clockCounts[$item->uid] ?? 0);

            $holidayData = [];
            foreach ($holidayTypeList as $type) {
                $duration = $leaveService->getMonthLeaveDurationByHolidayTypeId($item->uid, $month, $type['id'], $type['duration_type']);
                $holidayData[] = [
                    'id'       => $type['id'],
                    'name'     => $type['name'],
                    'duration' => $duration,
                ];
            }
            $item['actual_days']  = $actualDays;
            $item['holiday_data'] = $holidayData;
        }

        $count = $this->dao->search($where)->distinct('uid')->count();
        return $this->listData($list, $count, ['holiday_type' => $holidayTypeList]);
    }

    /**
     * 更新打卡结果.
     * @throws BindingResolutionException
     */
    public function saveStatisticsResult(string $uuid, int $statisticsId, array $data): mixed
    {
        $uid  = uuid_to_uid($uuid);
        $info = $this->dao->get($statisticsId);
        if (! $info) {
            throw $this->exception('common.operation.noExists');
        }

        return $this->transaction(function () use ($uid, $statisticsId, $data, $info) {
            $shifts = AttendanceClockEnum::SHIFT_CLASS;

            // 内勤卡去掉地点状态
            if ($info->{$shifts[$data['number']] . '_shift_location_status'} < 1) {
                $data['location_status'] = 0;
            }

            $beforeStatus         = $info->{$shifts[$data['number']] . '_shift_status'};
            $beforeLocationStatus = $info->{$shifts[$data['number']] . '_shift_location_status'};

            $number = (int) $data['number'];
            if ($data['status'] != $beforeStatus) {
                if ($info->shift_id > 1) {
                    [$time, $isAfter]                                     = $this->getClockData($info, $number, (int) $data['status']);
                    $info->{$shifts[$data['number']] . '_shift_time'}     = $time ?? null;
                    $info->{$shifts[$data['number']] . '_shift_is_after'} = $isAfter;
                }
                $info->{$shifts[$data['number']] . '_shift_status'} = $data['status'];
            }

            if ($data['location_status'] && $data['location_status'] != $beforeLocationStatus) {
                $info->{$shifts[$data['number']] . '_shift_location_status'} = $data['location_status'];
            }

            $info->actual_work_hours = app()->get(AttendanceShiftService::class)->getActualWorkHours($info, $info->created_at->toDateString());
            $info->save();

            return $this->handleRecordDao->create([
                'statistics_id'          => $statisticsId,
                'shift_number'           => $data['number'],
                'remark'                 => $data['remark'],
                'result'                 => $this->getResultText($data, $beforeStatus, $beforeLocationStatus),
                'source'                 => 0,
                'before_status'          => $beforeStatus,
                'before_location_status' => $beforeLocationStatus,
                'after_status'           => $data['status'],
                'after_location_status'  => $data['location_status'],
                'uid'                    => $uid,
            ]);
        });
    }

    /**
     * 归一化出勤统计时间参数为 month 或 Y/m/d-Y/m/d.
     * AI/MCP 常传 YYYY-MM；TimeDataTrait 会把「2026-09」拆成 2026/09 导致 Carbon 解析失败.
     */
    public function normalizeStatisticsTime(string $time): string
    {
        $time = trim($time);
        if ($time === '' || strtolower($time) === 'month') {
            return 'month';
        }
        // 已是 Y/m/d-Y/m/d
        if (preg_match('#^\d{4}/\d{1,2}/\d{1,2}-\d{4}/\d{1,2}/\d{1,2}$#', $time)) {
            return $time;
        }
        // YYYY-MM-DD~YYYY-MM-DD 或带 - 的日期区间
        if (preg_match('#^(\d{4})[-/](\d{1,2})[-/](\d{1,2})\s*[-~到至]\s*(\d{4})[-/](\d{1,2})[-/](\d{1,2})$#u', $time, $m)) {
            return sprintf(
                '%04d/%02d/%02d-%04d/%02d/%02d',
                (int) $m[1],
                (int) $m[2],
                (int) $m[3],
                (int) $m[4],
                (int) $m[5],
                (int) $m[6]
            );
        }
        // 单日 YYYY-MM-DD / YYYY/M/D → 同日区间（须在 YYYY-MM 之前匹配）
        if (preg_match('#^(\d{4})[-/](\d{1,2})[-/](\d{1,2})$#', $time, $m)) {
            $day = sprintf('%04d/%02d/%02d', (int) $m[1], (int) $m[2], (int) $m[3]);

            return $day . '-' . $day;
        }
        // YYYY-MM / YYYY/MM → 当月起止
        if (preg_match('#^(\d{4})[-/](\d{1,2})$#', $time, $m)) {
            $month = (int) $m[2];
            if ($month < 1 || $month > 12) {
                return $time;
            }
            $start = Carbon::create((int) $m[1], $month, 1)->startOfMonth();

            return $start->format('Y/m/d') . '-' . $start->copy()->endOfMonth()->format('Y/m/d');
        }
        // 仅月份数字 09 / 9 → 当年该月
        if (preg_match('#^(\d{1,2})$#', $time, $m)) {
            $month = (int) $m[1];
            if ($month >= 1 && $month <= 12) {
                $start = Carbon::now()->startOfYear()->month($month)->startOfMonth();

                return $start->format('Y/m/d') . '-' . $start->copy()->endOfMonth()->format('Y/m/d');
            }
        }

        return $time;
    }

    /**
     * 出勤统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAttendanceStatistics(int $uid, int $userId, string $time): array
    {
        if (! in_array($userId, app(FrameAssistService::class)->getScopeUid($uid))) {
            throw $this->exception('暂无权限查看该人员的出勤统计信息');
        }
        $time = $this->normalizeStatisticsTime($time);
        [$absenteeism, $lateCard, $earlyCard, $actualDays, $locationAbnormal] = $this->getLackCardAndAbsenteeismNum($userId, $time, false);
        $tz                                                                   = config('app.timezone');
        if ($time == 'month') {
            $startObj = Carbon::today($tz)->startOfMonth();
            $endObj   = Carbon::today($tz)->endOfMonth();
        } else {
            [$startTime, $endTime] = explode('-', $time, 2);
            $startObj              = Carbon::parse($startTime, $tz);
            $endObj                = Carbon::parse($endTime, $tz);
        }
        $time          = $startObj->format('Y/m/d') . '-' . $endObj->format('Y/m/d');
        $where         = ['uid' => $userId, 'time' => $time];
        $recordService = app()->get(AttendanceApplyRecordService::class);
        $leaveService  = app()->get(AttendanceStatisticsLeaveService::class);
        return [
            'required_days'     => app()->get(AttendanceArrangeService::class)->getRequiredDays($userId, $time, $startObj, $endObj),
            'normal_days'       => $this->dao->count(['uid' => $userId, 'time' => $time, 'status_gt' => 0]),
            'work_hours'        => sprintf('%.1f', $this->dao->avg($where, 'actual_work_hours')),
            'leave_hours'       => $leaveService->getLeaveDurationByDate($userId, $time, 'time'),
            'out_hours'         => $recordService->getSumByTime($userId, $time, ApproveEnum::PERSONNEL_OUT, 'hour'),
            'trip_hours'        => $recordService->getSumByTime($userId, $time, ApproveEnum::PERSONNEL_TRIP),
            'overtime_hours'    => $recordService->getSumByTime($userId, $time, ApproveEnum::PERSONNEL_OVERTIME, 'hour'),
            'sign'              => $recordService->getLeaveNumByTime($userId, $time, ApproveEnum::PERSONNEL_SIGN),
            'late'              => $this->dao->count(array_merge($where, ['status' => AttendanceClockEnum::LATE])),
            'extreme_late'      => $this->dao->count(array_merge($where, ['status' => AttendanceClockEnum::EXTREME_LATE])),
            'early_leave'       => $this->dao->count(array_merge($where, ['status' => AttendanceClockEnum::LEAVE_EARLY])),
            'lack_card'         => $lateCard + $earlyCard,
            'absenteeism'       => $absenteeism,
            'location_abnormal' => $locationAbnormal,
        ];
    }

    /**
     * 补充每日统计派生数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function appendDailyStatisticsData(array &$list, array &$holidayTypeList): void
    {
        $tz            = config('app.timezone');
        $shifts        = AttendanceClockEnum::SHIFT_CLASS;
        $shiftService  = app()->get(AttendanceShiftService::class);
        $recordService = app()->get(AttendanceApplyRecordService::class);
        $leaveService  = app()->get(AttendanceStatisticsLeaveService::class);
        $dates         = [];
        $uids          = [];

        foreach ($list as $item) {
            if (! empty($item['created_at'])) {
                $dates[] = Carbon::parse($item['created_at'], $tz)->toDateString();
            }
            if (isset($item['uid'])) {
                $uids[] = (int) $item['uid'];
            }
        }

        $dates           = array_values(array_unique($dates));
        $uids            = array_values(array_unique($uids));
        $prefetchTypeIds = ($dates && $uids)
            ? array_values(array_unique(array_merge(
                $leaveService->getHolidayTypeIdByDate($dates, $uids),
                $recordService->getApprovedHolidayTypeIdByDate($dates, $uids)
            )))
            : [];
        $holidayTypeList = app()->get(ApproveHolidayTypeService::class)->getStatisticsListWithDestroy($prefetchTypeIds);

        foreach ($list as &$item) {
            for ($n = 0; $n < 4; ++$n) {
                $item[$shifts[$n] . '_shift_normal'] = 0;
            }

            $date              = Carbon::parse($item['created_at'], $tz)->toDateString();
            $requiredWorkHours = (string) ($item['required_work_hours'] ?? '0.00');
            $leaveTime         = $leaveService->getLeaveDurationByDate((int) $item['uid'], $date);
            if (bccomp($leaveTime, '0', 2) <= 0) {
                $leaveTime = $recordService->getApprovedSumByDate([
                    'uid'        => $item['uid'],
                    'apply_type' => ApproveEnum::PERSONNEL_HOLIDAY,
                ], $date, 'hour', $requiredWorkHours, true);
            }

            $item['leave_time']          = $leaveTime;
            $item['overtime_work_hours'] = $recordService->getApprovedSumByDate([
                'uid'        => $item['uid'],
                'apply_type' => ApproveEnum::PERSONNEL_OVERTIME,
            ], $date, 'hour', $requiredWorkHours);

            $holidayData = [];
            foreach ($holidayTypeList as $type) {
                $duration = $leaveService->getDateLeaveDurationByHolidayTypeId((int) $item['uid'], $date, $type['id'], $type['duration_type']);
                if (bccomp($duration, '0', 2) <= 0) {
                    $duration = $recordService->getApprovedSumByDate([
                        'uid'                     => $item['uid'],
                        'apply_type'              => ApproveEnum::PERSONNEL_HOLIDAY,
                        'others->holiday_type_id' => $type['id'],
                    ], $date, $type['duration_type'] == 1 ? 'hour' : 'day', $requiredWorkHours, true);
                }
                $holidayData[] = [
                    'id'       => $type['id'],
                    'name'     => $type['name'],
                    'duration' => $duration,
                ];
            }
            $item['holiday_data'] = $holidayData;

            $item['shift_data'] = is_array($item['shift_data'] ?? null) ? $item['shift_data'] : [];
            if (($item['shift_id'] ?? 0) < 2) {
                // 无考勤班次时，设置空的shift_data.name为空字符串，避免前端导出时显示undefined
                if (! isset($item['shift_data']['name'])) {
                    $item['shift_data']['name'] = '';
                }
                continue;
            }

            $shiftNum = ($item['shift_data']['number'] ?? 1) * 2;
            for ($i = 0; $i < $shiftNum; ++$i) {
                $field  = $shifts[$i];
                $status = (int) ($item[$field . '_shift_status'] ?? 0);
                $minute = in_array($status, AttendanceClockEnum::LATE_AND_LEAVE_EARLY)
                    ? $shiftService->getNormalMinutes($item['created_at'], $item['shift_data'], $status, $i, (string) ($item[$field . '_shift_time'] ?? ''), $tz) : 0;
                $minute && $item[$field . '_shift_normal'] = $minute;
            }
        }
        unset($item);
    }

    /**
     * 获取打卡结果.
     */
    private function getResultText(array $data, int $beforeStatus, int $beforeLocationStatus): string
    {
        if ($beforeStatus == $data['status']
            && ($beforeLocationStatus && $beforeLocationStatus == $data['location_status'])) {
            throw $this->exception('打卡状态不能更新异常');
        }

        $beforeLocationText = AttendanceClockEnum::getLocationStatusText($beforeLocationStatus);
        $afterLocationText  = AttendanceClockEnum::getLocationStatusText((int) $data['location_status']);

        return AttendanceClockEnum::getStatusText($beforeStatus)
               . ($beforeLocationText ? '(' . $beforeLocationText . ')' : '') . ' > '
               . AttendanceClockEnum::getStatusText((int) $data['status'])
               . ($afterLocationText ? '(' . $afterLocationText . ')' : '');
    }

    /**
     * 获取打卡数据.
     * @param mixed $info
     */
    private function getClockData($info, int $number, int $status): array
    {
        $tz      = config('app.timezone');
        $isWork  = in_array($number, [0, 2]);
        $date    = Carbon::parse($info->created_at, $tz)->toDateString();
        $rule    = $info->shift_data['rules'][$number > 1 ? 1 : 0];
        $dateObj = Carbon::parse($date . ' ' . $rule[$isWork ? 'work_hours' : 'off_hours'] . ':00', $tz);
        $isAfter = 0;
        if ($rule[['first', 'second'][$isWork ? 0 : 1] . '_day_after']) {
            $isAfter = 1;
            $dateObj->addDay();
        }

        if ($status == 5) {
            return ['', $isAfter];
        }

        return [(match ($status) {
            2       => $dateObj->addSeconds($rule['late'] + 1),
            3       => $dateObj->addSeconds($rule['extreme_late'] + 1),
            4       => $dateObj->subSeconds($rule['early_leave'] - 1),
            default => $dateObj
        })->toDateTimeString(), $isAfter];
    }

    /**
     * 获取出勤天次/旷工/缺卡数量.
     * @return int[]
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    private function getLackCardAndAbsenteeismNum(array|int $uid, string $date, bool $isMonth = true): array
    {
        $actualDays = '0.0';
        $lateCard   = $absenteeism = $earlyCard = $locationAbnormal = 0;
        $shifts     = AttendanceClockEnum::SHIFT_CLASS;
        $where      = ['uid' => $uid];
        if ($isMonth) {
            $where['month'] = $date;
        } else {
            $where['time'] = $date;
        }
        $records = $this->dao->select($where, [
            'id', 'uid', 'shift_id', 'shift_data', 'required_work_hours',
            'created_at',
            'one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status',
            'one_shift_location_status', 'two_shift_location_status', 'three_shift_location_status', 'four_shift_location_status',
        ]);
        $tz        = config('app.timezone');
        $statDates = [];
        foreach ($records as $record) {
            $statDates[] = Carbon::parse($record->created_at, $tz)->toDateString();
        }
        $clockDays = [];
        if ($statDates) {
            sort($statDates);
            $clockRecords = app()->get(AttendanceClockDao::class)->search([
                'uid'  => $uid,
                'date' => [reset($statDates) . ' 00:00:00', end($statDates) . ' 23:59:59'],
            ], false)->selectRaw('uid, DATE(created_at) as clock_date')
                ->groupBy('uid', DB::raw('DATE(created_at)'))
                ->get();
            foreach ($clockRecords as $clockRecord) {
                $clockDays[$clockRecord->uid . '_' . $clockRecord->clock_date] = true;
            }
        }
        $leaveDays = app()->get(AttendanceStatisticsLeaveService::class)->getLeaveDaysByStatistics($uid, $date, $isMonth ? 'month' : 'time');

        foreach ($records as $record) {
            $statDate = Carbon::parse($record->created_at, $tz)->toDateString();
            if (isset($clockDays[$record->uid . '_' . $statDate])) {
                $leaveDay   = (string) ($leaveDays[$record->id] ?? '0.00');
                $actualDays = bcadd($actualDays, (string) max(0, 1 - min(1, (float) $leaveDay)), 1);
            }

            if ($record->shift_id < 2) {
                continue;
            }
            $leaveDay      = (string) ($leaveDays[$record->id] ?? '0.00');
            $hasLeave      = bccomp($leaveDay, '0', 2) > 0;
            $isFullLeave   = bccomp($leaveDay, '1', 2) >= 0;
            $lateNum  = $absentNum = $earlyNum = $locationAbnormalNum = 0;
            $shiftNum = $record->shift_data['number'] * 2;
            for ($i = 0; $i < $shiftNum; ++$i) {
                $shiftStatus         = $record->{$shifts[$i] . '_shift_status'};
                $shiftLocationStatus = $record->{$shifts[$i] . '_shift_location_status'};
                if ($shiftStatus == AttendanceClockEnum::LACK_CARD) {
                    if (in_array($i, [0, 2])) {
                        ++$lateNum;
                    } else {
                        ++$earlyNum;
                    }
                    ++$absentNum;
                }

                if ($shiftStatus == AttendanceClockEnum::LATE_LACK_CARD) {
                    ++$lateNum;
                }

                if ($shiftStatus == AttendanceClockEnum::EARLY_LACK_CARD) {
                    ++$earlyNum;
                }

                if ($shiftLocationStatus == AttendanceClockEnum::OFFICE_ABNORMAL) {
                    ++$locationAbnormalNum;
                }
            }

            if ($isFullLeave) {
                continue;
            }

            if ($absentNum == $shiftNum && ! $hasLeave) {
                ++$absenteeism;
            } else {
                $lateCard += $lateNum;
                $earlyCard += $earlyNum;
                $locationAbnormal += $locationAbnormalNum;
            }
        }
        return [$absenteeism, $lateCard, $earlyCard, $actualDays, $locationAbnormal];
    }
}
