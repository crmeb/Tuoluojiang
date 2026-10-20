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
use App\Constants\AttendanceGroupEnum;
use App\Constants\NoticeEnum;
use App\Events\SystemMessageEvent;
use App\Http\Dao\Attendance\AttendanceRemindDao;
use App\Http\Dao\Attendance\AttendanceShortRemindDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Company\CompanyService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 考勤提醒
 * Class AttendanceRemindService.
 */
class AttendanceRemindService extends BaseService
{
    protected const CACHE_REMIND_PUSH_KEY = 'attendance_remind_push';

    protected string $enterpriseName = '';

    protected string $dateFormat = 'n月j日';

    protected string $timeFormat = 'H:i';

    private AttendanceShortRemindDao $shortRemindDao;

    public function __construct(AttendanceRemindDao $dao, AttendanceShortRemindDao $shortRemindDao)
    {
        $this->dao            = $dao;
        $this->shortRemindDao = $shortRemindDao;
    }

    /**
     * 获取企业名称.
     * @throws BindingResolutionException
     */
    public function getEnterpriseName(): string
    {
        if (! empty($this->enterpriseName)) {
            return $this->enterpriseName;
        }

        $this->enterpriseName = app()->get(CompanyService::class)->value(1, 'enterprise_name');
        return $this->enterpriseName;
    }

    /**
     * 生成推送数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function generateShiftRemind(Carbon $dateObj): void
    {
        $messageService        = app()->get(MessageService::class);
        $statisticsService     = app()->get(AttendanceStatisticsService::class);
        $clockRemindMessage    = $messageService->getMessageContent(1, NoticeEnum::CLOCK_REMIND);
        $afterRemindMessage    = $messageService->getMessageContent(1, NoticeEnum::CLOCK_REMIND_AFTER_WORK);
        $workShortMessage      = $messageService->getMessageContent(1, NoticeEnum::REMIND_WORK_CARD_SHORT);
        $afterWorkShortMessage = $messageService->getMessageContent(1, NoticeEnum::REMIND_AFTER_WORK_CARD_SHORT);

        $date   = $dateObj->toDateString();
        $tz     = config('app.timezone');
        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        $list   = $statisticsService->getShiftsByDate($date);

        // 默认提醒时间5分钟
        $remindTime = 60 * 5;
        foreach ($list as $item) {
            if ($item['shift_id'] < 2) {
                continue;
            }

            $num        = 0;
            $remindData = [];
            foreach ($item['shift_data']['rules'] as $rule) {
                $workObj                                   = $statisticsService->getWorkObj($rule, $date, $tz);
                $remindData[$shifts[$num] . '_shift_time'] = $workObj->toDateTimeString();
                if ($clockRemindMessage) {
                    $remindData[$shifts[$num] . '_shift_remind'] = $workObj->subSeconds($clockRemindMessage['remind_time']);
                } else {
                    $remindData[$shifts[$num] . '_shift_remind'] = $workObj->subSeconds($remindTime);
                }

                if ($workShortMessage) {
                    $remindData[$shifts[$num] . '_shift_remind_short'] = $statisticsService->getWorkObj($rule, $date, $tz)->addSeconds($rule['late_lack_card'] + $workShortMessage['remind_time']);
                } else {
                    $remindData[$shifts[$num] . '_shift_remind_short'] = $statisticsService->getWorkObj($rule, $date, $tz)->addSeconds($rule['late_lack_card'] + $remindTime);
                }

                ++$num;
                $offWorkObj                                = $statisticsService->getOffWorkObj($rule, $date, $tz);
                $remindData[$shifts[$num] . '_shift_time'] = $offWorkObj->toDateTimeString();
                if ($afterRemindMessage) {
                    $remindData[$shifts[$num] . '_shift_remind'] = $offWorkObj->addSeconds($afterRemindMessage['remind_time']);
                } else {
                    $remindData[$shifts[$num] . '_shift_remind'] = $offWorkObj->addSeconds($remindTime);
                }

                // 下班缺卡时间
                if ($rule['delay_card']) {
                    $earlyLackObj = $statisticsService->getOffWorkObj($rule, $date, $tz)->addSeconds($rule['delay_card']);
                } else {
                    $earlyLackObj = $statisticsService->getLastShiftClockTimeObj($item['uid'], $rule, $date, $tz);
                }

                if ($afterWorkShortMessage) {
                    $twoShiftShortRemind = Carbon::parse($earlyLackObj->format('Y-m-d ' . $afterWorkShortMessage['remind_time'] . ':00'), $tz);
                    if ($earlyLackObj->gt($twoShiftShortRemind)) {
                        $twoShiftShortRemind->addDay();
                    }
                    $remindData[$shifts[$num] . '_shift_remind_short'] = $twoShiftShortRemind;
                } else {
                    $remindData[$shifts[$num] . '_shift_remind_short'] = $earlyLackObj->addSeconds($remindTime);
                }
                ++$num;
            }

            if ($remindData && ! $this->dao->exists(['shift_id' => $item['shift_data']['id'], 'date' => $date])) {
                $remindData['shift_id']  = $item['shift_data']['id'];
                $remindData['shift_num'] = $item['shift_data']['number'];
                $remind                  = $this->dao->create($remindData);
                if (! $remind) {
                    Log::error('考勤推送数据保存失败', $remindData);
                    continue;
                }

                if (! $clockRemindMessage && ! $afterRemindMessage) {
                    continue;
                }
                $this->sendClockRemindMessage($dateObj, $tz, $remind->toArray(), $clockRemindMessage, $afterRemindMessage);
            }
        }
    }

    /**
     * 上下班提醒推送
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendClockRemindMessage(Carbon $dateObj, string $tz, array $remind, array $clockRemindMessage, array $afterRemindMessage): void
    {
        $arrangeService = app()->get(AttendanceArrangeService::class);
        $dateString     = $dateObj->toDateString();
        // 根据班次ID获取用户数据
        $members            = $arrangeService->getMemberIdsByShiftId($remind['shift_id'], $dateString);
        $shifts             = AttendanceClockEnum::SHIFT_CLASS;
        $applyRecordService = app()->get(AttendanceApplyRecordService::class);
        foreach (['one_shift_remind', 'two_shift_remind', 'three_shift_remind', 'four_shift_remind'] as $key => $field) {
            $remindTime = $remind[$field] ?? '';
            if (! $remindTime) {
                continue;
            }
            if (! $clockRemindMessage && in_array($field, ['one_shift_remind', 'three_shift_remind'])) {
                continue;
            }
            if (! $afterRemindMessage && in_array($field, ['two_shift_remind', 'four_shift_remind'])) {
                continue;
            }
            $type     = NoticeEnum::CLOCK_REMIND;
            $workType = '上班时间';
            if (in_array($field, ['two_shift_remind', 'four_shift_remind'])) {
                $type     = NoticeEnum::CLOCK_REMIND_AFTER_WORK;
                $workType = '下班时间';
            }
            $compareTime = $remind[$shifts[$key] . '_shift_time'];
            $members     = collect($members ?: [])->each(function ($member) use ($compareTime, $applyRecordService) {
                $applyRecordService->updateAbnormalShiftStatusByApplyRecord($member, $compareTime);
            })->filter(function ($member) use ($applyRecordService, $compareTime, $arrangeService, $dateString) {
                return ! $applyRecordService->getCountByApplyType($member, $compareTime, ApproveEnum::PERSONNEL_HOLIDAY) && ! $arrangeService->dayIsRest($member, $dateString);
            })->all();
            event(new SystemMessageEvent(
                type: $type,
                params: [$workType => $remindTime, ''],
                receiverIds: $members,
                other: ['id' => $remind['id']],
                linkId: $remind['id'],
                linkStatus: 0,
            ));
            $this->dao->update(['id' => $remind['id'], $field => $remindTime], [$field . '_push' => 1]);
        }
    }

    /**
     * 获取班次数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getPushShiftsWithWorkTime(string $date, int $shiftId = 0): array
    {
        $key = self::CACHE_REMIND_PUSH_KEY . '_' . $date . '_' . $shiftId;
        return Cache::tags([self::CACHE_REMIND_PUSH_KEY])->remember($key, (int) sys_config('system_cache_ttl', 3600), function () use ($date, $shiftId) {
            $where = ['date' => $date];
            if ($shiftId) {
                $where['shift_id'] = $shiftId;
            }
            return $this->dao->select($where)->toArray();
        });
    }

    /**
     * 获取团队出勤推送数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTeamPushParam(array|int $members, string $date, string $timeType): array
    {
        $recordService     = app()->get(AttendanceApplyRecordService::class);
        $statisticsService = app()->get(AttendanceStatisticsService::class);

        // 平均工时
        $workHours = sprintf('%.1f', $statisticsService->avg([$timeType => $date, 'uid' => $members], 'actual_work_hours'));

        // 请假人数
        $holidayNum = $recordService->getLeaveNumByTimeByUid($members, ApproveEnum::PERSONNEL_HOLIDAY, $date, $timeType, true);

        // 参加考勤人数
        $attendanceNum = $statisticsService->getCountByUid([$timeType => $date, 'uid' => $members]);

        // 迟到人数
        $lateNum = $statisticsService->getCountByUid([$timeType => $date, 'uid' => $members, 'status' => AttendanceClockEnum::ALL_LATE]);

        // 早退人数
        $leaveEarlyNum = $statisticsService->getCountByUid(['uid' => $members, $timeType => $date, 'status' => AttendanceClockEnum::LEAVE_EARLY]);

        // 旷工人数 缺卡人数  旷工人次  缺卡人次
        [$absenteeismNum, $lackCardNum, $absenteeismTime, $lackCardTime] = $statisticsService->getLackCardAndAbsenteeismPeopleNum($members, $date, $timeType);

        // 外勤卡人数
        $externalNum = $statisticsService->getCountByUid([$timeType => $date, 'uid' => $members, 'location_status_gt' => AttendanceClockEnum::NO_NEED_CLOCK]);

        // 加班人数 加班人次
        [$overTimeNum, $overTimeTime] = $statisticsService->getOverTimeStatistics($members, $date, $timeType);

        $holidayTime = $lateTime = $leaveEarlyTime = $externalTime = 0;
        if ($timeType != 'date') {
            // 请假人次
            $holidayTime = $recordService->getLeaveNumByTimeByUid($members, ApproveEnum::PERSONNEL_HOLIDAY, $date, $timeType);

            // 迟到人次
            $lateTime = $statisticsService->count([$timeType => $date, 'uid' => $members, 'status' => AttendanceClockEnum::ALL_LATE]);

            // 早退人次
            $leaveEarlyTime = $statisticsService->count(['uid' => $members, $timeType => $date, 'status' => AttendanceClockEnum::LEAVE_EARLY]);

            // 外勤卡人次
            $externalTime = $statisticsService->count([$timeType => $date, 'uid' => $members, 'location_status_gt' => AttendanceClockEnum::NO_NEED_CLOCK]);
        }

        return compact(
            'workHours',
            'holidayNum',
            'attendanceNum',
            'lateNum',
            'leaveEarlyNum',
            'absenteeismNum',
            'lackCardNum',
            'externalNum',
            'overTimeNum',
            'holidayTime',
            'overTimeTime',
            'lateTime',
            'leaveEarlyTime',
            'absenteeismTime',
            'lackCardTime',
            'externalTime'
        );
    }

    /**
     * 团队出勤日报推送
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendTeamDayRemindMessage(string $date, string $timeType = 'date'): void
    {
        $adminMap     = [];
        $groupService = app()->get(AttendanceGroupService::class);
        $admins       = app()->get(AdminService::class)->column(['status' => 1], 'id');
        foreach ($groupService->column([], 'id') as $group) {
            $groupAdmins = $groupService->getMembersById($group, AttendanceGroupEnum::ADMIN);
            if (! $groupAdmins) {
                continue;
            }

            foreach ($groupAdmins as $item) {
                $adminMap[$item] = $group;
            }

            $admins = array_merge($admins, $groupAdmins);
        }

        $admins = array_unique($admins);
        foreach ($admins as $admin) {
            // team member
            $member = $groupService->getTeamMember($admin);

            if (count($member) > 1) {
                // group member
                if (isset($adminMap[$admin])) {
                    $member = array_unique(array_merge($groupService->getMemberIdsById($adminMap[$admin]), $member));
                }

                $this->sendTeamRemindMessage($admin, $member, $date, $timeType);
            }
        }
    }

    /**
     * 团队出勤推送
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendTeamRemindMessage(int $admin, array $members, string $date, string $timeType = 'date'): void
    {
        $params     = $this->getTeamPushParam($members, $date, $timeType);
        $timeField  = $timeType == 'date' ? '日期' : '日期范围';
        $timeFormat = $this->getDateFormat($date, $timeType);
        event(new SystemMessageEvent(
            type: match ($timeType) {
                'month' => NoticeEnum::TEAM_ATTENDANCE_MONTHLY_REMIND,
                'time'  => NoticeEnum::TEAM_ATTENDANCE_WEEKLY_REMIND,
                default => NoticeEnum::TEAM_ATTENDANCE_DAILY_REMIND,
            },
            params: [
                $timeField => $timeFormat, '企业名称' => $this->getEnterpriseName(),
                '迟到人数' => $params['lateNum'], '平均工时' => $params['workHours'],
                '请假人数' => $params['holidayNum'], '外勤卡人数' => $params['externalNum'],
                '缺卡人数' => $params['lackCardNum'], '加班打卡人数' => $params['overTimeNum'],
                '旷工人数' => $params['absenteeismNum'], '参加考勤人数' => $params['attendanceNum'],
                '早退人数' => $params['leaveEarlyNum'], '外勤卡人次' => $params['externalTime'],
                '请假人次' => $params['holidayTime'], '缺卡人次' => $params['lackCardTime'],
                '迟到人次' => $params['lateTime'], '旷工人次' => $params['absenteeismTime'],
                '早退人次' => $params['leaveEarlyTime'], '加班打卡人次' => $params['overTimeTime'],
            ],
            receiverIds: $admin
        ));
    }

    /**
     * 发送团队出勤日报.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendTeamDailyPush(string $date): void
    {
        $this->sendTeamDayRemindMessage($date);
    }

    /**
     * 发送团队出勤周报.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendTeamWeeklyPush(string $date): void
    {
        $this->sendTeamDayRemindMessage($date, 'time');
    }

    /**
     * 发送团队出勤月报.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendTeamMonthlyPush(string $date): void
    {
        $this->sendTeamDayRemindMessage($date, 'month');
    }

    /**
     * 处理日期
     */
    public function getDateFormat(string $date, string $timeType = 'date'): string
    {
        $tz = config('app.timezone');
        switch ($timeType) {
            case 'time':
                [$startTime, $endTime] = explode('-', $date);
                return Carbon::parse($startTime, $tz)->format($this->dateFormat) . '-' . Carbon::parse($endTime, $tz)->format($this->dateFormat);
            case 'month':
                $dateObj = Carbon::parse($date, $tz);
                return $dateObj->firstOfMonth()->format($this->dateFormat) . '-' . $dateObj->lastOfMonth()->format($this->dateFormat);
            default:
                return Carbon::parse($date, $tz)->format($this->dateFormat);
        }
    }

    /**
     * 发送个人周/月统计消息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendPersonalPush(string $date, string $type, string $timeType): void
    {
        $timeFormat   = $this->getDateFormat($date, 'time');
        $groupService = app()->get(AttendanceGroupService::class);
        $members      = array_diff(app()->get(AdminService::class)->column(['status' => 1], 'id'), $groupService->getWhiteListMemberIds());
        foreach ($members as $member) {
            $params = $this->getTeamPushParam($member, $date, $timeType);
            event(new SystemMessageEvent(
                type: $type,
                params: [
                    '日期范围' => $timeFormat, '迟到次数' => $params['lateTime'], '平均工时' => $params['workHours'],
                    '请假次数' => $params['holidayTime'], '加班打卡次数' => $params['overTimeTime'],
                    '早退次数' => $params['leaveEarlyTime'], '缺卡次数' => $params['lackCardTime'],
                    '旷工次数' => $params['absenteeismTime'], '外勤卡次数' => $params['externalTime'],
                ],
                receiverIds: (int) $member
            ));
        }
    }

    /**
     * 发送缺卡提醒.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function sendShortRemindMessage(Carbon $dateObj): void
    {
        $messageService        = app()->get(MessageService::class);
        $statisticsService     = app()->get(AttendanceStatisticsService::class);
        $workShortMessage      = $messageService->getMessageContent(1, NoticeEnum::REMIND_WORK_CARD_SHORT);
        $afterWorkShortMessage = $messageService->getMessageContent(1, NoticeEnum::REMIND_AFTER_WORK_CARD_SHORT);

        $isFlush         = false;
        $tz              = config('app.timezone');
        $date            = $dateObj->toDateString();
        $shifts          = AttendanceClockEnum::SHIFT_CLASS;
        $shiftRemindList = $this->getPushShiftsWithWorkTime($date);
        $shiftTimes      = ['one_shift_time', 'two_shift_time'];
        $shiftReminds    = [
            'one_shift_time'   => 'one_shift_remind_short',
            'two_shift_time'   => 'two_shift_remind_short',
            'three_shift_time' => 'three_shift_remind_short',
            'four_shift_time'  => 'four_shift_remind_short',
        ];

        $statisticsWhere = ['status' => AttendanceClockEnum::LACK_CARD, 'month' => $dateObj->format('Y-m')];
        foreach ($shiftRemindList as $remind) {
            $remindShiftTimes = $remind['shift_num'] == 1 ? $shiftTimes : array_merge($shiftTimes, ['three_shift_time', 'four_shift_time']);
            $statisticsList   = $statisticsService->select([
                'date'                     => $date,
                'shift_id'                 => $remind['shift_id'],
                'lack_card_with_shift_num' => $remind['shift_num'],
            ]);

            foreach ($statisticsList as $record) {
                foreach ($remindShiftTimes as $key => $remindShiftTime) {
                    $remindTime = $remind[$shiftReminds[$remindShiftTime]];
                    if ($dateObj->lt($remindTime)) {
                        continue;
                    }

                    if ($record->{$shifts[$key] . '_shift_status'} != 0 || ! is_null($record->{$shifts[$key] . '_shift_time'})) {
                        continue;
                    }

                    if (in_array($key, [1, 3]) && $record->shift_data['rules'][$key < 2 ? 0 : 1]['free_clock']) {
                        $record->{$shifts[$key] . '_shift_status'} = AttendanceClockEnum::NORMAL;
                        $record->{$remindShiftTime}                = $remind[$remindShiftTime];
                        $record->save();
                    } else {
                        if ($dateObj->timestamp > $statisticsService->getClockEndTime($record->id, $record, $key, $tz)) {
                            $statisticsService->updateShiftStatistics($record, $dateObj, $key, withFreeClock: true);
                        }
                    }

                    $shortType = in_array($remindShiftTime, ['one_shift_time', 'three_shift_time']) ? 0 : 1;

                    $remindData = [
                        'uid'         => $record->uid,
                        'shift_id'    => $remind['shift_id'],
                        'short_type'  => $shortType,
                        'work_time'   => $remind[$remindShiftTime],
                        'remind_time' => $remindTime,
                    ];

                    if ($this->shortRemindDao->exists($remindData)) {
                        continue;
                    }

                    $shortRemind = $this->shortRemindDao->create($remindData);
                    if (! $shortRemind) {
                        Log::error('缺卡提醒数据保存失败', $remindData);
                        continue;
                    }

                    if ($shortType == 0 && ! $workShortMessage) {
                        continue;
                    }

                    if ($shortType == 1 && ! $afterWorkShortMessage) {
                        continue;
                    }

                    $timeObj = Carbon::parse($remindData['work_time'], $tz);
                    event(new SystemMessageEvent(
                        type: $remindData['short_type'] == 0 ? NoticeEnum::REMIND_WORK_CARD_SHORT : NoticeEnum::REMIND_AFTER_WORK_CARD_SHORT,
                        params: [
                            '缺卡日期'     => $timeObj->format($this->dateFormat),
                            '缺卡班次'     => $shortType == 0 ? '上班卡' : '下班卡',
                            '上班时间'     => $timeObj->format($this->timeFormat),
                            '累计缺卡次数' => $statisticsService->getCountByUid(array_merge(['uid' => $remindData['uid']], $statisticsWhere)),
                        ],
                        receiverIds: (int) $remindData['uid'],
                        other: ['id' => $shortRemind->id],
                        linkId: $shortRemind->id,
                        linkStatus: 0
                    ));
                    ! $isFlush && $isFlush = true;
                }
            }
        }

        $isFlush && Cache::tags([self::CACHE_REMIND_PUSH_KEY])->flush();
    }

    /**
     * 更新缺卡推送状态
     * @throws BindingResolutionException
     */
    public function updateShortPushStatus(int $shortRemindId): int
    {
        return $this->shortRemindDao->update($shortRemindId, ['is_push' => 1]);
    }
}
