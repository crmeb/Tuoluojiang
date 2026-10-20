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

use App\Constants\AttendanceClockEnum;
use App\Http\Dao\Attendance\AttendanceClockDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameAssistService;
use App\Jobs\Attend\AttendanceImportJob;
use App\Jobs\Attend\AttendMediaJob;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\GroupDataService;
use Tuoluojiang\Open\services\wechat\Work;
use EasyWeChat\Kernel\Exceptions\BadResponseException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 考勤打卡记录
 * Class AttendanceClockService.
 */
class AttendanceClockService extends BaseService
{
    public function __construct(AttendanceClockDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 基础数据.
     * @return null[]
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getBasic(string $uuid, string $date = '', int $userId = 0): array
    {
        $uid = $this->getStatisticsUserId($uuid, $userId);
        $tz  = config('app.timezone');
        if (! $date) {
            $dateObj = now($tz);
            $date    = $dateObj->toDateString();
        } else {
            $dateObj = Carbon::parse($date, $tz);
            $date    = $dateObj->toDateString();
        }
        $clockDateObj = $dateObj->isSameDay(now($tz)) ? now($tz) : $dateObj;
        $groupService = app()->get(AttendanceGroupService::class);

        $isWhitelist = $groupService->isWhitelist($uid);

        // 考勤排班
        $clockGroup = $this->getClockBasicByUid($uid, $date);

        $group = $groupService->getMemberClockGroup($uid, $clockGroup['group_id'], true, true) ?: null;

        // 无需考勤
        if ($isWhitelist) {
            return ['group' => $group, 'shift' => null, 'whitelist' => $isWhitelist, 'adjacent_shifts' => []];
        }
        app()->get(AttendanceStatisticsService::class)->renewStatisticsByDate($uid, $date);

        $isRest        = app()->get(AttendanceArrangeService::class)->dayIsRest($uid, $date);
        $shift['prev'] = $this->getDayInfo($uid, $dateObj->copy()->subDay()->toDateString(), $tz, $clockDateObj);
        $shift['now']  = $this->getDayInfo($uid, $date, $tz, $clockDateObj);
        $shift['next'] = $this->getDayInfo($uid, $dateObj->copy()->addDay()->toDateString(), $tz, $clockDateObj);

        if ($isRest && ! $shift['prev'] && ! $shift['now'] && ! $shift['next']) {
            return ['group' => $group, 'shift' => null, 'whitelist' => $isWhitelist, 'adjacent_shifts' => []];
        }

        return [
            'shift'           => $shift,
            'group'           => $group,
            'whitelist'       => $isWhitelist,
            'adjacent_shifts' => [],
        ];
    }

    /**
     * 打卡
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function shiftClock(int $uid, Carbon $dateObj, array $data, mixed $statistics = null): mixed
    {
        $tz = config('app.timezone');
        if (! in_array((int) $data['is_external'], [0, 1])) {
            throw $this->exception('打卡类型异常');
        }

        // 班次打卡
        $statisticsService = app()->get(AttendanceStatisticsService::class);
        $statistics        = $statistics ?: $statisticsService->renewStatistics($uid, $dateObj);

        // 检测跨天时间窗重叠（双向：昨日下班窗 ↔ 今日上班窗），需同时处理两天统计
        $today     = $dateObj->toDateString();
        $yesterday = Carbon::parse($today, $tz)->subDay()->toDateString();
        // 确保两天的统计记录都存在于数据库
        $statisticsService->renewStatisticsByDate($uid, $today);
        $statisticsService->renewStatisticsByDate($uid, $yesterday);
        $overlapRecord = $statisticsService->findOverlappingRecord($uid, $dateObj, $today, $statistics, $tz);

        $clockType = $this->checkClock($uid, $data, $statistics);

        if ($data['update_number'] !== '') {
            $updateNumber = (int) $data['update_number'];
            $this->checkClockNumber($statistics, $updateNumber);
            if (! $this->hasClockRecord($statistics, $updateNumber)) {
                $data['number']        = $data['number'] === '' ? $updateNumber : $data['number'];
                $data['update_number'] = '';
            }
        }

        // 更新打卡
        $this->checkIsUpdate($uid, $data['update_number'], $statistics, $tz);

        $clockNumber = 0;
        if ($data['update_number'] === '') {
            // 打卡班次
            [$status, $clockNumber] = $statisticsService->getClockNumber($dateObj, $statistics, $tz);
            if (! $status) {
                throw $this->exception('未到打卡时间');
            }
            if ($data['number'] !== '') {
                $targetClockNumber = (int) $data['number'];
                $this->checkClockNumber($statistics, $targetClockNumber);
                if ($targetClockNumber !== $clockNumber) {
                    throw $this->exception('打卡班次已变化, 请刷新后重试');
                }
                $clockNumber = $targetClockNumber;
            }
            $statisticsService->checkWorkTime($uid, $dateObj, $statistics, $clockNumber, $tz);
        }

        if (! app()->get(AttendanceGroupService::class)->isWhitelist($uid, $statistics->group_id) && $data['is_external']) {
            $data['location_status'] = 2;
        }

        return $this->transaction(function () use ($uid, $dateObj, $data, $statistics, $statisticsService, $clockNumber, $clockType, $overlapRecord, $tz) {
            $res = $this->dao->create([
                'uid'         => $uid,
                'frame_id'    => $statistics->frame_id,
                'group_id'    => $statistics->group_id,
                'group'       => $statistics->group,
                'shift_id'    => $statistics?->shift_data['id'] ?? 0,
                'shift_data'  => $statistics->shift_data,
                'is_external' => $data['is_external'],
                'address'     => $data['address'],
                'lat'         => $data['lat'],
                'lng'         => $data['lng'],
                'remark'      => $data['remark'],
                'image'       => $data['image'],
                'mac'         => $data['mac'],
                'clock_type'  => $clockType,
            ]);

            if (! $res) {
                throw $this->exception('打卡异常');
            }

            $data['record_id'] = $res->id;
            $statisticsService->updateShiftStatistics($statistics, $dateObj, $clockNumber, $data);

            // 重叠处理：同时更新今日的重叠统计记录
            if ($overlapRecord) {
                $this->processOverlapRecord($uid, $dateObj, $overlapRecord, $statisticsService, $data, $tz);
            }

            return true;
        });
    }

    /**
     * 打卡
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function saveClock(int $uid, array $data): array
    {
        if ($data['update_number'] !== '' && ! in_array($data['update_number'], [0, 1, 2, 3])) {
            throw $this->exception('打卡班次错误');
        }
        if ($data['number'] !== '' && ! in_array($data['number'], [0, 1, 2, 3])) {
            throw $this->exception('打卡班次错误');
        }

        $tz                  = config('app.timezone');
        $dateObj             = now($tz);
        $statisticsService   = app()->get(AttendanceStatisticsService::class);
        $statistics          = $this->resolveClockStatistics($uid, $dateObj, $data, $statisticsService, $tz);
        $attendanceGroupServ = app()->get(AttendanceGroupService::class);

        // 无需打卡
        if ($attendanceGroupServ->isWhitelist($uid, $statistics->group_id) || $statistics->shift_id < 2) {
            $this->defaultClock($uid, $dateObj, $data, $statistics);
        } else {
            $this->shiftClock($uid, $dateObj, $data, $statistics);
        }

        return ['clock_time' => $dateObj->format('H:i:s')];
    }

    /**
     * 获取考勤班次
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getClockBasicByUid(int $uid, string $date): array
    {
        $groupName = '';
        $groupId   = 0;
        $shiftId   = 0;
        // 优先使用排班记录的班次
        [$arrangeGroupId, $arrangeShiftId] = app()->get(AttendanceArrangeService::class)->getRecordByUid($uid, $date);
        if ($arrangeGroupId) {
            $groupId = $arrangeGroupId;
        }
        if ($arrangeShiftId) {
            $shiftId = $arrangeShiftId;
        }
        // 获取用户所属考勤组
        $group = app()->get(AttendanceGroupService::class)->getGroupByUidAndGroupId($uid, $groupId);
        if ($group) {
            $groupId   = $group->id;
            $groupName = $group->name;
        }
        // 无排班班次时，使用考勤组关联的默认班次或日历休息判断
        if (! $shiftId) {
            $shiftId = app(CalendarConfigService::class)->dayIsRest($date) ? 1 : 2;
        }
        return ['group_id' => $groupId, 'shift_id' => $shiftId, 'group_name' => $groupName];
    }

    /**
     * 获取考勤人员数据.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getAttendanceUser(string $uuid, int $userId): array
    {
        $this->checkMemberByUserId($userId, app()->get(AttendanceGroupService::class)->getTeamMember($uuid));
        $field = ['id', 'name as real_name', 'avatar', 'job'];
        $with  = ['job' => fn ($q) => $q->select(['id', 'name'])];
        $user  = app()->get(AdminService::class)->get($userId, $field, $with);
        if (! $user) {
            throw $this->exception('人员数据异常');
        }

        $user['frames'] = app()->get(FrameAssistService::class)->getUserFrames($userId ? uid_to_uuid($userId) : $uuid);
        return toArray($user);
    }

    /**
     * 获取考勤人员数据.
     * @return Application|array|GroupDataService|int|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getStatisticsUserId(string $uuid, int $userId): mixed
    {
        $uid = uuid_to_uid($uuid);
        if (! $userId) {
            return $uid;
        }

        // 考勤范围
        $this->checkMemberByUserId($userId, app()->get(AttendanceGroupService::class)->getTeamMember($uuid));
        return $userId;
    }

    /**
     * 打卡记录.
     * @param string $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = []): array
    {
        $field = ['id', 'frame_id', 'group_id', 'group', 'shift_id', 'uid', 'created_at'];
        return parent::getList($where, $field, $sort, ['card', 'frame']);
    }

    /**
     * 打卡详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(int $id): array
    {
        return toArray($this->dao->get($id, ['*'], ['card', 'frame']));
    }

    /**
     * 导入打卡记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \ReflectionException
     */
    public function saveRecord(array $data): void
    {
        $count = count($data);
        if (! $count) {
            throw $this->exception('导入内容不能为空');
        }

        $shifts = ['第一次上班', '第一次下班', '第二次上班', '第二次下班'];
        $fields = array_merge(['时间', '姓名'], $shifts);
        foreach ($fields as $field) {
            if (! isset($data[0][$field])) {
                throw $this->exception($field . '数据不存在');
            }
        }
        $limit = 10;
        $page  = $count < $limit ? 1 : ceil($count / $limit);
        for ($i = 1; $i <= $page; ++$i) {
            AttendanceImportJob::dispatch('singleImport', collect($data)->forPage($i, $limit)->toArray());
        }
    }

    /**
     * 默认打卡
     * @param mixed $data
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function defaultClock(int $uid, Carbon $dateObj, $data, mixed $statistics = null): void
    {
        $time              = $dateObj->toDateTimeString();
        $statisticsService = app()->get(AttendanceStatisticsService::class);
        $statistics        = $statistics ?: $statisticsService->renewStatistics($uid, $dateObj);

        $recordData = [
            'uid'        => $uid,
            'frame_id'   => $statistics->frame_id,
            'group_id'   => $statistics->group_id,
            'group'      => $statistics->group,
            'shift_id'   => $statistics->shift_data['id'] ?? 0,
            'shift_data' => $statistics->shift_data,
            'lat'        => $data['lat'],
            'lng'        => $data['lng'],
            'address'    => $data['address'],
            'remark'     => $data['remark'],
            'image'      => $data['image'],
            'created_at' => $time,
            'updated_at' => $time,
        ];

        if ($data['is_external']) {
            $data['location_status'] = 1;
        }

        $this->transaction(function () use ($dateObj, $data, $statisticsService, $statistics, $recordData) {
            $res = $this->dao->create($recordData);
            if (! $res) {
                throw $this->exception('打卡异常');
            }

            $data['record_id'] = $res->id;
            if (! $statisticsService->updateDefaultStatistics($statistics, $dateObj, $data)) {
                throw $this->exception('打卡异常');
            }
        });
    }

    /**
     * 导入打卡数据.
     * @param mixed $statistics
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function importClock($statistics, Carbon $dateObj, int $uid, int $clockNumber, bool $freeClock): void
    {
        $time = $dateObj->toDateTimeString();
        $res  = $this->dao->create([
            'uid'         => $uid,
            'frame_id'    => $statistics->frame_id,
            'group_id'    => $statistics->group_id,
            'group'       => $statistics->group,
            'shift_id'    => $statistics?->shift_data['id'] ?? 0,
            'shift_data'  => $statistics->shift_data,
            'is_external' => 0,
            'address'     => '',
            'lat'         => '',
            'lng'         => '',
            'remark'      => '',
            'image'       => '',
            'created_at'  => $time,
            'updated_at'  => $time,
        ]);

        if (! $res) {
            throw $this->exception('打卡数据导入异常');
        }

        $data['record_id'] = $res->id;
        app()->get(AttendanceStatisticsService::class)->updateShiftStatistics($statistics, $dateObj, $clockNumber, $data, $freeClock);
    }

    /**
     * 单条导入.
     */
    public function singleImport(array $record): void
    {
        try {
            $this->transaction(function () use ($record) {
                $tz               = config('app.timezone');
                $shifts           = ['第一次上班', '第一次下班', '第二次上班', '第二次下班'];
                $shiftStatus      = ['one_shift_status', 'two_shift_status', 'three_shift_status', 'four_shift_status'];
                $defaultClockData = ['remark' => '', 'image' => '', 'lat' => '', 'lng' => '', 'address' => '', 'is_external' => 0, 'update_number' => ''];

                $adminService      = app()->get(AdminService::class);
                $shiftService      = app()->get(AttendanceShiftService::class);
                $groupService      = app()->get(AttendanceGroupService::class);
                $statisticsService = app()->get(AttendanceStatisticsService::class);
                $adminId           = (int) $adminService->value(['name' => $record['姓名']], 'id');
                if (! $adminId) {
                    return;
                }

                $timeData = explode(' ', $record['时间']);
                if (count($timeData) != 2) {
                    Log::error('打卡记录导入时间格式异常', ['record' => $record]);
                    return;
                }
                $dateObj    = Carbon::parse(substr($timeData[0], 0, 10), $tz);
                $statistics = $statisticsService->renewStatistics($adminId, $dateObj);
                if ($groupService->isWhitelist($adminId) || $statistics->shift_data['number'] <= 0) {
                    for ($i = 0; $i < 4; ++$i) {
                        $shiftTime = trim($record[$shifts[$i]] ?? '');
                        if (! $shiftTime || $shiftTime == '--' || $shiftTime == '未打卡') {
                            continue;
                        }

                        if (strlen($shiftTime) < 10) {
                            $shiftTime = $dateObj->format('Y/m/d ') . $shiftTime;
                        }

                        // 默认打卡
                        $this->defaultClock($adminId, Carbon::parse($shiftTime, $tz), $defaultClockData);
                    }
                } else {
                    $dateString = $statistics->created_at->toDateString();
                    for ($i = 0; $i < $statistics->shift_data['number'] * 2; ++$i) {
                        $shiftTime   = trim($record[$shifts[$i]] ?? '');
                        $invalidTime = ! $shiftTime || $shiftTime == '--' || $shiftTime == '未打卡';
                        $rule        = $statistics->shift_data['rules'][$i > 1 ? 1 : 0];

                        // associated approve
                        [$approveFreeClock, $approveLocationStatus] = $statisticsService->calcAssociatedApprove($statistics, $dateString, $i, $rule, $tz);
                        if ($approveLocationStatus == 1 && $statistics->{$shifts[$i] . '_shift_location_status'} == 2) {
                            $statistics->{$shifts[$i] . '_shift_location_status'} = $approveLocationStatus;
                        }

                        if ($approveFreeClock || (in_array($i, [1, 3]) && $rule['free_clock'] > 0)) {
                            $workObj = $statisticsService->{in_array($i, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $dateString, $tz);
                            $data    = [
                                'status'   => AttendanceClockEnum::NORMAL,
                                'time'     => $workObj->toDateTimeString(),
                                'is_after' => $workObj->gt($statistics->created_at->endOfDay()) ? 1 : 0,
                            ];
                            if ($invalidTime) {
                                $clockTime = $statisticsService->{in_array($i, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $statistics->created_at->toDateString(), $tz)->toDateTimeString();
                            } else {
                                if (strlen($shiftTime) < 10) {
                                    $shiftTime = $dateObj->format('Y/m/d ') . $shiftTime;
                                }
                                $clockTime = Carbon::parse($shiftTime, $tz)->toDateTimeString();
                            }

                            $res = $this->dao->create([
                                'uid'         => $adminId,
                                'frame_id'    => $statistics->frame_id,
                                'group_id'    => $statistics->group_id,
                                'group'       => $statistics->group,
                                'shift_id'    => $statistics?->shift_data['id'] ?? 0,
                                'shift_data'  => $statistics->shift_data,
                                'is_external' => 0,
                                'address'     => '',
                                'lat'         => '',
                                'lng'         => '',
                                'remark'      => '',
                                'image'       => '',
                                'created_at'  => $clockTime,
                                'updated_at'  => $clockTime,
                            ]);

                            if (! $res) {
                                throw $this->exception('打卡数据导入异常');
                            }
                            $data['record_id'] = $res->id;
                            $this->updateStatisticsStatusAndTime($statistics, $shifts[$i], $data, $tz);
                            continue;
                        }

                        if ($invalidTime) {
                            $statistics->{$shiftStatus[$i]} = AttendanceClockEnum::LACK_CARD;
                            $statistics->actual_work_hours  = $shiftService->getActualWorkHours($statistics, $dateString, $tz);
                            $statistics->save();
                            continue;
                        }

                        if (strlen($shiftTime) < 10) {
                            $shiftTime = $dateObj->format('Y/m/d ') . $shiftTime;
                        }

                        $clockObj = Carbon::parse($shiftTime, $tz);

                        // 导入打卡数据
                        $this->importClock($statistics, $clockObj, $adminId, $i, $rule['free_clock'] > 0);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('打卡记录导入失败：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'func' => 'singleImport', 'record' => $record]);
        }
    }

    /**
     * 导入三方考勤记录.
     */
    public function importThirdParty(int $type, array $originalRecords): void
    {
        $importName = match ($type) {
            1       => 'dingTalkImport',
            2       => 'qyImport',
            default => throw $this->exception('导入类型错误')
        };

        $data = [];
        if ($type == 1) {
            foreach ($originalRecords as $item) {
                $data[] = [
                    'name'       => $item['姓名'] ?? '',
                    'date'       => $item['考勤日期'] ?? '',
                    'clock_time' => $item['打卡时间'] ?? '',
                    'address'    => $item['打卡地址'] ?? '',
                    'remark'     => $item['打卡备注'] ?? '',
                ];
            }
        } else {
            foreach ($originalRecords as $item) {
                $data[] = [
                    'name'             => $item['姓名'] ?? '',
                    'date'             => $item['时间'] ?? '',
                    'one_shift_time'   => $item['上班1'] ?? '',
                    'two_shift_time'   => $item['下班1'] ?? '',
                    'three_shift_time' => $item['上班2'] ?? '',
                    'four_shift_time'  => $item['下班2'] ?? '',
                ];
            }
        }

        $count = count($data);
        if (! $count) {
            throw $this->exception('导入内容不能为空');
        }

        unset($originalRecords);

        $limit = 10;
        $page  = $count < $limit ? 1 : ceil($count / $limit);
        for ($i = 1; $i <= $page; ++$i) {
            AttendanceImportJob::dispatch($importName, collect($data)->forPage($i, $limit)->toArray());
        }
    }

    /**
     * 钉钉导入.
     */
    public function dingTalkImport(array $record): void
    {
        if (! $record['name'] ?? '' || ! $record['date'] ?? '') {
            return;
        }
        try {
            $tz             = config('app.timezone');
            $adminService   = app()->get(AdminService::class);
            $groupService   = app()->get(AttendanceGroupService::class);
            $arrangeService = app()->get(AttendanceArrangeService::class);
            $adminId        = (int) $adminService->value(['name' => $record['name']], 'id');
            if (! $adminId) {
                return;
            }

            $timeData = explode(' ', $record['date']);
            if (count($timeData) != 2) {
                Log::error('打卡记录导入日期格式异常', ['record' => $record]);
                return;
            }

            $dateObj = Carbon::parse(substr($timeData[0], 0, 10), $tz); // 考勤日期

            $clockObj = Carbon::parse($record['clock_time'], $tz);
            if ($clockObj->diffInDays($dateObj) > 2) {
                Log::error('打卡记录导入时间格式异常', ['record' => $record]);
                return;
            }

            // 白名单 || 休息
            if ($groupService->isWhitelist($adminId) || $arrangeService->dayIsRest($adminId, $dateObj->toDateString())) {
                $this->defaultClock($adminId, $clockObj, ['remark' => $record['remark'] ?? '', 'image' => '', 'lat' => '', 'lng' => '', 'address' => $record['address'] ?? [], 'is_external' => 0, 'update_number' => '']);
                return;
            }

            $statisticsService = app()->get(AttendanceStatisticsService::class);
            $statistics        = $statisticsService->renewStatistics($adminId, $dateObj);
            $clockTime         = $clockObj->toDateTimeString();
            $res               = $this->dao->create([
                'uid'         => $adminId,
                'frame_id'    => $statistics->frame_id,
                'group_id'    => $statistics->group_id,
                'group'       => $statistics->group,
                'shift_id'    => $statistics?->shift_data['id'] ?? 0,
                'shift_data'  => $statistics->shift_data,
                'is_external' => $record['is_external'] ?? '',
                'address'     => $record['address'] ?? '',
                'lat'         => '',
                'lng'         => '',
                'image'       => '',
                'remark'      => $record['remark'] ?? '',
                'created_at'  => $clockTime,
                'updated_at'  => $clockTime,
            ]);

            if (! $res) {
                throw $this->exception('打卡记录导入异常');
            }

            // 计算考勤
            $this->calcClockTimeWithAttendance($adminId, $dateObj);
        } catch (\Throwable $e) {
            Log::error('打卡记录文件导入失败：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'func' => 'dingTalkImport', 'record' => $record]);
        }
    }

    /**
     * 计算考勤数据.
     */
    public function calcClockTimeWithAttendance(int $userId, Carbon $dateObj): void
    {
        try {
            $statisticsService = app()->get(AttendanceStatisticsService::class);
            $statistics        = $statisticsService->renewStatistics($userId, $dateObj);
            $this->transaction(function () use ($userId, $statistics, $statisticsService) {
                $tz     = config('app.timezone');
                $shifts = AttendanceClockEnum::SHIFT_CLASS;
                for ($i = 0; $i <= $statistics->shift_data['number'] * 2 - 1; ++$i) {
                    if (in_array($i, [0, 2]) && $statistics->{$shifts[$i] . '_shift_status'} == 1) {
                        continue;
                    }

                    $rule    = $statistics->shift_data['rules'][$i > 1 ? 1 : 0];
                    $workObj = $statisticsService->{in_array($i, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $statistics->created_at->toDateString(), $tz);

                    match ($i) {
                        1, 3    => $this->clockWithImport($userId, $statistics, $i, $workObj, (clone $workObj)->addSeconds((int) $rule['delay_card']), $tz, AttendanceClockEnum::NORMAL, 'desc') || $this->clockWithImport($userId, $statistics, $i, $statisticsService->getClockEndTime($userId, $statistics, $i - 1, $tz, false), (clone $workObj)->subSeconds(min((int) $rule['early_leave'], (int) $rule['early_lack_card'])), $tz, 0, 'desc'),
                        default => $this->clockWithImport($userId, $statistics, $i, (clone $workObj)->subSeconds((int) $rule['early_card']), (clone $workObj)->addSeconds(min((int) $rule['late'], (int) $rule['extreme_late'], (int) $rule['late_lack_card'])), $tz, AttendanceClockEnum::NORMAL) || $this->clockWithImport($userId, $statistics, $i, $workObj, (clone $workObj)->addSeconds(max((int) $rule['late'], (int) $rule['extreme_late'], (int) $rule['late_lack_card'])), $tz),
                    };
                }
            });
        } catch (\Throwable $e) {
            Log::error('考勤数据更新失败：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'data' => [
                'user_id' => $userId,
                'date'    => $dateObj->toDateString(),
            ]]);
        }
    }

    /**
     * 企业微信导入.
     */
    public function qyImport(array $record): void
    {
        if (! $record['name'] ?? '' || ! $record['date'] ?? '') {
            return;
        }
        try {
            $this->transaction(function () use ($record) {
                $tz                = config('app.timezone');
                $adminService      = app()->get(AdminService::class);
                $shiftService      = app()->get(AttendanceShiftService::class);
                $groupService      = app()->get(AttendanceGroupService::class);
                $statisticsService = app()->get(AttendanceStatisticsService::class);
                $shifts            = AttendanceClockEnum::SHIFT_CLASS;
                $defaultClockData  = ['remark' => '', 'image' => '', 'lat' => '', 'lng' => '', 'address' => '', 'is_external' => 0, 'update_number' => ''];
                $adminId           = (int) $adminService->value(['name' => $record['name']], 'id');
                if (! $adminId) {
                    return;
                }

                $timeData = explode(' ', $record['date']);
                if (count($timeData) != 2) {
                    Log::error('打卡记录导入日期格式异常', ['record' => $record]);
                    return;
                }

                $dateObj    = Carbon::parse(substr($timeData[0], 0, 10), $tz);
                $statistics = $statisticsService->renewStatistics($adminId, $dateObj);
                if ($groupService->isWhitelist($adminId) || $statistics->shift_data['number'] <= 0) {
                    for ($i = 0; $i < 4; ++$i) {
                        $shiftTime = trim($record[$shifts[$i] . '_shift_time'] ?? '');
                        if (! $shiftTime || $shiftTime == '--' || $shiftTime == '未打卡') {
                            continue;
                        }

                        if (strlen($shiftTime) < 10) {
                            $shiftTime = $dateObj->format('Y/m/d ') . $shiftTime;
                        }

                        // 默认打卡
                        $this->defaultClock($adminId, Carbon::parse($shiftTime, $tz), $defaultClockData);
                    }
                } else {
                    $dateString = $statistics->created_at->toDateString();
                    for ($i = 0; $i < $statistics->shift_data['number'] * 2; ++$i) {
                        $shiftTime   = trim($record[$shifts[$i] . '_shift_time'] ?? '');
                        $invalidTime = ! $shiftTime || $shiftTime == '--' || $shiftTime == '未打卡';
                        $rule        = $statistics->shift_data['rules'][$i > 1 ? 1 : 0];

                        // associated approve
                        [$approveFreeClock, $approveLocationStatus] = $statisticsService->calcAssociatedApprove($statistics, $dateString, $i, $rule, $tz);
                        if ($approveLocationStatus == 1 && $statistics->{$shifts[$i] . '_shift_location_status'} == 2) {
                            $statistics->{$shifts[$i] . '_shift_location_status'} = $approveLocationStatus;
                        }

                        if ($approveFreeClock || (in_array($i, [1, 3]) && $rule['free_clock'] > 0)) {
                            $workObj = $statisticsService->{in_array($i, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $dateString, $tz);
                            $data    = [
                                'status'   => AttendanceClockEnum::NORMAL,
                                'time'     => $workObj->toDateTimeString(),
                                'is_after' => $workObj->gt($statistics->created_at->endOfDay()) ? 1 : 0,
                            ];
                            if ($invalidTime) {
                                $clockTime = $statisticsService->{in_array($i, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $statistics->created_at->toDateString(), $tz)->toDateTimeString();
                            } else {
                                if (strlen($shiftTime) < 10) {
                                    $shiftTime = $dateObj->format('Y/m/d ') . $shiftTime;
                                }
                                $clockTime = Carbon::parse($shiftTime, $tz)->toDateTimeString();
                            }

                            $res = $this->dao->create([
                                'uid'         => $adminId,
                                'frame_id'    => $statistics->frame_id,
                                'group_id'    => $statistics->group_id,
                                'group'       => $statistics->group,
                                'shift_id'    => $statistics?->shift_data['id'] ?? 0,
                                'shift_data'  => $statistics->shift_data,
                                'is_external' => 0,
                                'address'     => '',
                                'lat'         => '',
                                'lng'         => '',
                                'remark'      => '',
                                'image'       => '',
                                'created_at'  => $clockTime,
                                'updated_at'  => $clockTime,
                            ]);

                            if (! $res) {
                                throw $this->exception('打卡数据导入异常');
                            }
                            $data['record_id'] = $res->id;
                            $this->updateStatisticsStatusAndTime($statistics, $shifts[$i], $data, $tz);
                            continue;
                        }

                        if ($invalidTime) {
                            $statistics->{$shifts[$i] . '_shift_status'} = AttendanceClockEnum::LACK_CARD;
                            $statistics->actual_work_hours               = $shiftService->getActualWorkHours($statistics, $dateString, $tz);
                            $statistics->save();
                            continue;
                        }

                        if (strlen($shiftTime) < 10) {
                            $shiftTime = $dateObj->format('Y/m/d ') . $shiftTime;
                        }
                        $clockObj = Carbon::parse($shiftTime, $tz);

                        // 导入打卡数据
                        $this->importClock($statistics, $clockObj, $adminId, $i, $rule['free_clock'] > 0);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('打卡记录导入失败：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'func' => 'qyImport', 'record' => $record]);
        }
    }

    /**
     * 同步打卡记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BadResponseException
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function syncWorkClockRecord(Carbon $dateStart, Carbon $dateEnd): void
    {
        $tz   = config('app.timezone');
        $work = app()->get(Work::class);
        $user = collect(app()->get(AdminService::class)->select(['is_work' => 1], ['id', 'work_member_id'], with: ['work'])?->toArray())->pluck('id', 'work.userid')
            ->filter(function ($item, $key) {
                return $item && $key;
            });
        $daysDiff = $dateStart->diffInDays($dateEnd);
        $date     = collect(range(0, $daysDiff))->map(function ($day) use ($dateStart) {
            return $dateStart->copy()->addDays($day)->format('Y-m-d');
        })->push($dateStart->copy()->subDay()->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();
        $statisticsService = app()->get(AttendanceStatisticsService::class);
        $statistic         = $statisticsService->batchRenewStatistics($user->values()->all(), $date);
        $data              = $work->getCheckInData($dateStart->toDateTimeString(), $dateEnd->toDateTimeString(), $user->keys()->all())?->toArray();
        if (! $data['errcode'] && $data['checkindata']) {
            $statusService = app()->get(AttendanceStatusService::class);
            collect($data['checkindata'])->filter(function ($item) {
                return ! str_contains($item['exception_type'], '未打卡');
            })->flatMap(function ($item) use ($user, $statistic, $statisticsService, $tz) {
                $uid            = $user->get($item['userid']);
                $checkInTimeObj = Carbon::createFromTimestamp((int) $item['checkin_time'], $tz);
                $checkInTime    = $checkInTimeObj->toDateTimeString();
                $clockDate      = $checkInTimeObj->toDateString();
                $key            = $uid . '_' . $clockDate;
                $statistics     = $statisticsService->renewStatistics($uid, $checkInTimeObj->copy());
                $statisticsDate = $statistics ? Carbon::parse($statistics->created_at, $tz)->toDateString() : $clockDate;
                $res            = $this->dao->firstOrCreate(['uid' => $uid, 'created_at' => $checkInTime], [
                    'uid'         => $uid,
                    'frame_id'    => $statistics->frame_id ?? ($statistic[$key]['frame_id'] ?? 0),
                    'group_id'    => $statistics->group_id ?? ($statistic[$key]['group_id'] ?? 0),
                    'group'       => $statistics->group ?? ($statistic[$key]['group'] ?? ''),
                    'shift_id'    => $statistics->shift_id ?? ($statistic[$key]['shift_id'] ?? 2),
                    'shift_data'  => $statistics->shift_data ?? ($statistic[$key]['shift_data'] ?? ''),
                    'address'     => $item['location_detail'],
                    'lat'         => $item['lat'] ? $item['lat'] / 1000000 : '',
                    'lng'         => $item['lng'] ? $item['lng'] / 1000000 : '',
                    'remark'      => $item['notes'],
                    'is_external' => $item['checkin_type'] == '外出打卡' ? 1 : 0,
                    'mac'         => $item['wifimac'],
                    'created_at'  => $checkInTime,
                ]);
                if ($item['mediaids'] && Carbon::make($res->created_at)->isAfter(now()->subDays(3))) {
                    AttendMediaJob::dispatch($res->id, $item['mediaids']);
                }
                $dates = [[
                    'uid'  => $uid,
                    'date' => $clockDate,
                ]];
                if ($statisticsDate !== $clockDate) {
                    $dates[] = [
                        'uid'  => $uid,
                        'date' => $statisticsDate,
                    ];
                }
                return $dates;
            })->filter()->unique(function ($item) {
                return $item['uid'] . '_' . $item['date'];
            })->each(function ($item) use ($statusService) {
                $statusService->checkUserClockStatus($item['uid'], $item['date']);
            });
        }
    }

    public function getUserClock(int $userId, array|string $date)
    {
        return $this->dao->select([
            'uid'  => $userId,
            'date' => $date,
        ])?->toArray();
    }

    /**
     * 处理时间窗重叠的统计记录.
     * 根据重叠方向自动填充正确的槽位：
     * - 主记录为今日 → 重叠记录为昨日 → 填充昨日的下班槽位
     * - 主记录为昨日 → 重叠记录为今日 → 填充今日的上班槽位.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function processOverlapRecord(int $uid, Carbon $dateObj, mixed $overlapRecord, AttendanceStatisticsService $statisticsService, array $data, string $tz): void
    {
        // 获取重叠记录当前应填充的槽位（昨日下班记录返回 1，今日上班记录返回 0）
        [$overlapStatus, $overlapClockNumber] = $statisticsService->getClockNumber($dateObj, $overlapRecord, $tz);
        if (! $overlapStatus) {
            return;
        }

        $shifts = AttendanceClockEnum::SHIFT_CLASS;
        $slot   = $shifts[$overlapClockNumber] ?? null;
        if (! $slot) {
            return;
        }

        // 检查该槽位是否已被填充
        if (! is_null($overlapRecord->{$slot . '_shift_time'})) {
            return;
        }

        // 自动为重叠记录填充打卡数据（使用实际打卡时间）
        $overlapRecord->{$slot . '_shift_time'}            = $dateObj->toDateTimeString();
        $overlapRecord->{$slot . '_shift_status'}          = $statisticsService->getClockStatus($overlapClockNumber, $dateObj, $overlapRecord);
        $overlapRecord->{$slot . '_shift_record_id'}       = $data['record_id'] ?? 0;
        $overlapRecord->{$slot . '_shift_location_status'} = $data['location_status'] ?? 0;
        $overlapRecord->actual_work_hours                  = app()->get(AttendanceShiftService::class)->getActualWorkHours(
            $overlapRecord,
            Carbon::parse($overlapRecord->created_at, $tz)->toDateString(),
            $tz
        );
        $overlapRecord->save();
    }

    /**
     * 判断指定打卡槽位是否已有有效记录.
     */
    private function hasClockRecord(mixed $statistics, int $clockNumber): bool
    {
        $slot = AttendanceClockEnum::SHIFT_CLASS[$clockNumber] ?? null;
        if (! $slot) {
            return false;
        }

        return ! is_null($statistics->{$slot . '_shift_time'})
            || (int) ($statistics->{$slot . '_shift_record_id'} ?? 0) > 0;
    }

    /**
     * 解析本次打卡应写入的统计记录.
     *
     * 前端在跨天班次中会传入归属日期和班次 ID，用来明确“今天打的是昨天班次的下班卡”。
     * 未传归属日期时沿用当前时间窗自动匹配逻辑。
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function resolveClockStatistics(int $uid, Carbon $dateObj, array $data, AttendanceStatisticsService $statisticsService, string $tz): mixed
    {
        if (empty($data['date'])) {
            return $statisticsService->renewStatistics($uid, $dateObj);
        }

        try {
            $statisticsDate = Carbon::parse((string) $data['date'], $tz)->toDateString();
        } catch (\Throwable) {
            throw $this->exception('打卡日期异常');
        }

        $statistics = $statisticsService->renewStatisticsByDate($uid, $statisticsDate);
        $shiftId    = (int) ($data['shift_id'] ?? 0);
        if ($shiftId && (int) $statistics->shift_id !== $shiftId) {
            throw $this->exception('班次数据已变化, 请刷新后重试');
        }

        return $statistics;
    }

    /**
     * 核对前端指定的打卡槽位是否存在.
     *
     * @param mixed $statistics
     */
    private function checkClockNumber($statistics, int $clockNumber): void
    {
        $shiftNum = (int) (($statistics->shift_data['number'] ?? 0) * 2);
        if ($clockNumber < 0 || $clockNumber >= $shiftNum) {
            throw $this->exception('打卡班次错误');
        }
    }

    /**
     * 获取后一天的跨天班次信息（待上班打卡）.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function getDayInfo(int $uid, string $nextDate, string $tz, Carbon $dateObj): ?array
    {
        $statisticsService = app(AttendanceStatisticsService::class);
        $nextStatistics    = $statisticsService->getUserRecordByDate($uid, $nextDate);
        // 检查是否为有效的跨天班次（必须有 id 字段，表示是数据库中的真实记录）
        if (! $nextStatistics || ! is_object($nextStatistics) || ! isset($nextStatistics->id) || $nextStatistics->shift_id <= 1) {
            return null;
        }

        $shiftData          = $nextStatistics->shift_data ?? [];
        $shiftData['rules'] = collect($shiftData['rules'] ?? [])->filter(function ($rules) use ($nextDate) {
            return $rules['work_date'] == $nextDate || $rules['off_date'] == $nextDate;
        })->all();
        if (empty($shiftData['rules'])) {
            return null;
        }

        // 计算后一日的打卡状态
        [$nextStatus, $nextClockNumber]                  = $statisticsService->getClockNumber($dateObj, $nextStatistics, $tz);
        [$nextStatistics, $nextStatus, $nextClockNumber] = $statisticsService->checkClockRecord($nextStatistics, $dateObj, $nextStatus, $nextClockNumber, $tz);
        $nextClockStatus                                 = $nextStatus == 0 ? $nextStatus : $statisticsService->getClockStatus($nextClockNumber, $dateObj, $nextStatistics);
        $nextClockTimestamp                              = $statisticsService->getClockTime($uid, $nextStatistics, $nextClockNumber, $tz, $dateObj);

        return [
            'date'            => $nextDate,
            'shift_id'        => $nextStatistics->shift_id,
            'shift_data'      => $shiftData,
            'clock_status'    => $nextClockStatus,
            'clock_timestamp' => $nextClockTimestamp,
            'list'            => $statisticsService->getStatisticsList($nextStatistics, $nextClockNumber, $tz),
        ];
    }

    /**
     * 判断统计记录是否为跨天打卡（统计记录日期与今日不同）.
     * @param mixed $info
     */
    private function isCrossDayStatistics($info, Carbon $dateObj, string $tz): bool
    {
        if (! $info || ! isset($info->created_at)) {
            return false;
        }
        $statisticsDate = Carbon::parse($info->created_at, $tz)->toDateString();
        return $statisticsDate !== $dateObj->toDateString();
    }

    /**
     * 判断统计记录归属类型.
     */
    private function determineBelongType(string $belongDate, string $today): string
    {
        if ($belongDate === $today) {
            return 'today';
        }
        return $belongDate < $today ? 'yesterday' : 'tomorrow';
    }

    /**
     * 是否更新打卡
     * @param mixed $statistics
     * @param mixed $number
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function checkIsUpdate(int $uid, $number, $statistics, string $tz = ''): void
    {
        if ($number === '') {
            return;
        }

        $clockNumber = (int) $number;
        if (! in_array($clockNumber, [0, 1, 2, 3])) {
            throw $this->exception('班次错误');
        }

        if ($clockNumber > 1 && count($statistics->shift_data['rules']) < 2) {
            throw $this->exception('更新班次错误');
        }

        if (now($tz)->timestamp > app()->get(AttendanceStatisticsService::class)->getClockEndTime($uid, $statistics, $clockNumber, $tz)) {
            throw $this->exception('无法更新打卡, 请刷新后重试');
        }
    }

    /**
     * 打卡核对.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function checkClock(int $uid, array $data, mixed $statistics): int
    {
        if ($statistics->group_id < 1) {
            return 0;
        }
        $groupService = app()->get(AttendanceGroupService::class);
        $group        = $groupService->dao->setTrashed()->get($statistics->group_id);
        if (! $group) {
            throw $this->exception('考勤组异常');
        }
        if (! in_array($uid, $groupService->getMemberIdsById($statistics->group_id))) {
            return 0;
        }
        $canClock  = false;
        $clockType = 0;
        if ($group->is_wifi && $data['mac'] && app()->get(AttendanceWifiService::class)->exists(['group_id' => $statistics->group_id, 'mac' => $data['mac']])) {
            $canClock  = true;
            $clockType = 1;
        }
        if ($group->is_map && $data['lat'] && $data['lng'] && $this->calcDistance((float) $data['lat'], (float) $data['lng'], (float) $group['lat'], (float) $group['lng']) <= $group['effective_range']) {
            $canClock = true;
        }
        if (! $canClock) {
            if (! $group->is_external) {
                throw $this->exception('不允许外勤打卡！');
            }
            // 拍照
            if ($group->is_photo) {
                $this->checkImages($data['image']);
                if (count($data['image']) > 9) {
                    throw $this->exception('上传拍照最多9张！');
                }
            }
            if ($group->is_external_note && ! $data['remark']) {
                throw $this->exception('请填写备注！');
            }
            if ($group->is_external_photo) {
                $this->checkImages($data['image']);
            }
        } else {
            if ($group->is_photo) {
                $this->checkImages($data['image']);
                if (count($data['image']) > 9) {
                    throw $this->exception('上传拍照最多9张！');
                }
            }
        }
        return $clockType;
    }

    /**
     * 获取坐标距离.
     */
    private function calcDistance(float $rLat, float $rLng, float $gLat, float $gLng): float
    {
        $pi      = pi();
        $earthR  = 6378.137 * 1000;
        $radRLat = $rLat * $pi / 180.;
        $radGLat = $gLat * $pi / 180.;
        $s       = cos($radRLat) * cos($radGLat) * cos(($rLng - $gLng) * $pi / 180.0) + sin($radRLat) * sin($radGLat);
        if ($s > 1) {
            $s = 1;
        }
        if ($s < -1) {
            $s = -1;
        }
        return ceil(acos($s) * $earthR);
    }

    /**
     * 图片验证
     */
    private function checkImages(array $images): void
    {
        if (count($images) < 1) {
            throw $this->exception('请进行拍照打卡！');
        }
    }

    /**
     * 核对考勤权限.
     */
    private function checkMemberByUserId(int $userId, array $members): void
    {
        if ($userId && ! in_array($userId, $members)) {
            throw $this->exception('不能查看该员工考勤数据');
        }
    }

    /**
     * 上班卡
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function clockWithImport(int $userId, mixed $statistics, int $clockNumber, Carbon $startObj, Carbon $endObj, string $tz, int $clockStatus = 0, string $sort = 'asc'): bool
    {
        $statisticsService = app()->get(AttendanceStatisticsService::class);
        $shifts            = AttendanceClockEnum::SHIFT_CLASS;
        $dateString        = $statistics->created_at->toDateString();
        $rule              = $statistics->shift_data['rules'][$clockNumber > 1 ? 1 : 0];

        // associated approve
        [$approveFreeClock, $approveLocationStatus] = $statisticsService->calcAssociatedApprove($statistics, $dateString, $clockNumber, $rule, $tz);
        if ($approveLocationStatus == 1 && $statistics->{$shifts[$clockNumber] . '_shift_location_status'} == 2) {
            $statistics->{$shifts[$clockNumber] . '_shift_location_status'} = $approveLocationStatus;
        }

        // 是否免打卡
        if ($approveFreeClock || (in_array($clockNumber, [1, 3]) && $rule['free_clock'] > 0)) {
            $workObj = $statisticsService->{in_array($clockNumber, [0, 2]) ? 'getWorkObj' : 'getOffWorkObj'}($rule, $dateString, $tz);
            $data    = [
                'status'   => AttendanceClockEnum::NORMAL,
                'time'     => $workObj->toDateTimeString(),
                'is_after' => $workObj->gt($statistics->created_at->endOfDay()) ? 1 : 0,
            ];
            $this->updateStatisticsStatusAndTime($statistics, $shifts[$clockNumber], $data, $tz);
            return true;
        }
        $record = $this->dao->get([
            'uid'      => $userId,
            'shift_id' => $statistics->shift_id,
            'time'     => $startObj->format('Y/m/d H:i:s') . '-' . $endObj->format('Y/m/d H:i:s'),
        ], ['id', 'created_at'], sort: ['created_at' => $sort]);
        if ($record) {
            $data = [
                'status'    => $clockStatus ?: $statisticsService->getClockStatus($clockNumber, $record->created_at, $statistics),
                'time'      => $record->created_at->toDateTimeString(),
                'is_after'  => $record->created_at->gt($statistics->created_at->endOfDay()) ? 1 : 0,
                'record_id' => $record->id,
            ];
            $this->updateStatisticsStatusAndTime($statistics, $shifts[$clockNumber], $data, $tz);
            return true;
        }

        return false;
    }

    /**
     * 更新打卡状态和时间.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function updateStatisticsStatusAndTime(mixed $statistics, string $prefix, array $data, string $tz): void
    {
        $statistics->{$prefix . '_shift_time'}      = $data['time'];
        $statistics->{$prefix . '_shift_status'}    = $data['status'];
        $statistics->{$prefix . '_shift_is_after'}  = $data['is_after'];
        $statistics->{$prefix . '_shift_record_id'} = $data['record_id'] ?? 0;

        $statistics->actual_work_hours = app()->get(AttendanceShiftService::class)->getActualWorkHours($statistics, $statistics->created_at->toDateString(), $tz);
        if (! $statistics->save()) {
            throw $this->exception('打卡数据更新异常, 请稍后再试');
        }
    }
}
