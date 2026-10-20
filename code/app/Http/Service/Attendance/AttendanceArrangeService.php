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

use App\Http\Contract\Attendance\AttendanceArrangeInterface;
use App\Http\Dao\Attendance\AttendanceArrangeDao;
use App\Http\Dao\Attendance\AttendanceArrangeRecordDao;
use App\Jobs\Attend\AttendRangeJob;
use Carbon\CarbonPeriod;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 考勤排班记录
 * Class AttendanceArrangeService.
 */
class AttendanceArrangeService extends BaseService implements AttendanceArrangeInterface
{
    protected const CACHE_KEY = 'attendance_arrange';

    private AttendanceArrangeRecordDao $recordDao;

    public function __construct(AttendanceArrangeDao $dao, AttendanceArrangeRecordDao $recordDao)
    {
        $this->dao       = $dao;
        $this->recordDao = $recordDao;
    }

    /**
     * 列表.
     * @param string $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['id', 'group_id', 'uid', 'date', 'created_at', 'updated_at'], $sort = 'id', array $with = ['group']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $groupService   = app()->get(AttendanceGroupService::class);
        foreach ($list as &$item) {
            $item['group']['members'] = $groupService->getMemberUsersById($item['group_id'], true, true);
        }
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 保存排班.
     * @throws BindingResolutionException
     */
    public function saveArrange(array $data, int $uid): bool
    {
        $tz           = config('app.timezone');
        $data['date'] = $data['date'] . '-01 00:00';
        if (Carbon::parse($data['date'], $tz)->format('Ym') < now($tz)->format('Ym')) {
            throw $this->exception('今日及以前的日期禁止调整');
        }

        if (app()->get(AttendanceGroupService::class)->count(['id' => $data['groups']]) != count($data['groups'])) {
            throw $this->exception('考勤组异常');
        }

        return $this->transaction(function () use ($data, $uid) {
            foreach ($data['groups'] as $group) {
                $res = $this->dao->firstOrCreate(['group_id' => $group, 'date' => $data['date']], ['group_id' => $group, 'uid' => $uid, 'date' => $data['date']]);
                if (! $res) {
                    throw $this->exception(__('common.insert.fail'));
                }
            }
            Cache::tags([self::CACHE_KEY])->flush();
            return true;
        });
    }

    /**
     * 解析考勤日期：不允许早于当前自然月
     * @param string|null $date 日期字符串
     * @throws \Exception
     */
    public function parseDate(?string $date)
    {
        $timezone = config('app.timezone');
        $currentMonthStart = now($timezone)->startOfMonth();

        // 空值默认返回当月首日
        if (empty($date)) {
            return $currentMonthStart;
        }

        $parsedDate = Carbon::parse($date, $timezone);
        if ($parsedDate->isBefore($currentMonthStart)) {
            throw $this->exception('考勤时间不能为过去月份');
        }

        return $parsedDate;
    }
    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(int $groupId, string $name = '', string $date = ''): array
    {
        $dateObj = $this->parseDate($date);
        $restMap = $arrange = $calendar = [];
        $month   = $dateObj->format('Y-m');
        $where   = ['group_id' => $groupId, 'month' => $month];

        // 日历配置
        $getRestList = app()->get(CalendarConfigService::class)->getRestList($month);
        foreach ($getRestList as $rest) {
            $restMap[$rest] = 1;
        }

        $temp     = [];
        $timeZone = CarbonPeriod::create($dateObj->toDateString(), $dateObj->endOfMonth()->toDateString())->toArray();
        foreach ($timeZone as $item) {
            $date        = $item->toDateString();
            $calendar[]  = ['date' => $date, 'is_rest' => isset($restMap[$date]) ? 1 : 0];
            $temp[$date] = 0;
        }

        // 考勤组人员
        $members = app()->get(AttendanceGroupService::class)->getGroupMember($groupId, $name, true);
        foreach ($members as $item) {
            $shifts       = [];
            $where['uid'] = $item['id'];
            $this->recordDao->setDefaultSort(['date' => 'asc'])->setTrashed()->select($where, ['shift_id', DB::raw("DATE_FORMAT(date,'%Y-%m-%d') as `date`")])->each(function ($item) use (&$shifts) {
                $shifts[$item->date] = $item->shift_id;
            });

            if (count($shifts) != $dateObj->daysInMonth) {
                $shifts = array_merge($temp, $shifts);
            }

            $arrange[] = ['uid' => $item['id'], 'shifts' => array_values($shifts)];
        }

        return compact('arrange', 'calendar', 'members');
    }

    /**
     * 更新排班.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateArrangeV1(int $groupId, string $date, array $data): mixed
    {
        $tz      = config('app.timezone');
        $nowObj  = now($tz);
        $dateObj = Carbon::parse($date, $tz);
        if ($dateObj->startOfMonth()->lt(now($tz)->startOfMonth())) {
            throw $this->exception('排班时间不能为过去月份');
        }

        if (! app()->get(AttendanceGroupService::class)->exists(['id' => $groupId])) {
            throw $this->exception('操作失败，考勤组记录不存在');
        }

        $nowObj->startOfDay();
        $shiftIds  = app()->get(AttendanceGroupService::class)->getShiftIds($groupId);
        $memberIds = app()->get(AttendanceGroupService::class)->getMemberIdsById($groupId);

        return $this->transaction(function () use ($groupId, $date, $data, $nowObj, $dateObj, $memberIds, $shiftIds) {
            $id = $this->dao->value(['group_id' => $groupId, 'month' => $dateObj->format('Y-m')], 'id');
            if (! $id) {
                $id = $this->dao->create(['uid' => uuid_to_uid($this->uuId(false)), 'date' => $dateObj->toDateString(), 'group_id' => $groupId])->id;
                if (! $id) {
                    throw $this->exception('排班失败');
                }
            }

            foreach ($data as $item) {
                $dateObj->startOfMonth();
                if (count($item['shifts']) != $dateObj->daysInMonth) {
                    throw $this->exception('排班数据异常');
                }

                $uid = (int) $item['uid'];
                if (! in_array($uid, $memberIds)) {
                    throw $this->exception('排班人员异常');
                }

                $recordData = $this->recordDao->column(['uid' => $uid, 'month' => $date, 'group_id' => $groupId], 'id', 'date');
                foreach (array_map('intval', $item['shifts']) as $key => $shift) {
                    $dateObj->addDays($key > 0 ? 1 : 0);
                    $date = $dateObj->toDateString();
                    if (isset($recordData[$date])) {
                        unset($recordData[$date]);
                    }
                    if ($nowObj->diffInDays($dateObj, false) < 1) {
                        continue;
                    }
                    if (! in_array($shift, $shiftIds) && ! in_array($shift, [0, 1])) {
                        throw $this->exception('排班班次异常');
                    }
                    $this->saveRecord($id, $groupId, $shift, $uid, $date);
                }
                $recordData && $this->recordDao->forceDelete(['id' => array_values($recordData)]);
            }
            Cache::tags([self::CACHE_KEY])->flush();
            return true;
        });
    }

    /**
     * 更新排班.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateArrange(int $groupId, string $date, array $data, int $uid): mixed
    {
        $tz      = config('app.timezone');
        $nowObj  = now($tz);
        $dateObj = Carbon::parse($date, $tz);
        if ($dateObj->startOfMonth()->lt(now($tz)->startOfMonth())) {
            throw $this->exception('排班时间不能为过去月份');
        }
        $groupService = app()->get(AttendanceGroupService::class);
        if (! $groupService->exists(['id' => $groupId])) {
            throw $this->exception('操作失败，考勤组记录不存在');
        }
        $nowObj->startOfDay();
        $shiftIds  = $groupService->getShiftIds($groupId);
        $memberIds = $groupService->getMemberIdsById($groupId);
        // 获取排班记录ID
        $id = $this->dao->value(['group_id' => $groupId, 'month' => $dateObj->format('Y-m')], 'id');
        if (! $id) {
            $id = $this->dao->create(['uid' => $uid, 'date' => $dateObj->toDateString(), 'group_id' => $groupId])->id;
            if (! $id) {
                throw $this->exception('排班失败');
            }
        }
        // 初始化日期对象为月初
        $dateObj->startOfMonth();
        $recordsToSave = $delIds = [];
        foreach ($data as $item) {
            // 验证排班数据长度
            if (count($item['shifts']) != $dateObj->daysInMonth) {
                throw $this->exception('排班数据异常: 排班数据长度与月份天数不符');
            }
            // 验证排班人员
            $uid = (int) $item['uid'];
            if (! in_array($uid, $memberIds)) {
                throw $this->exception('排班人员异常: 用户ID不在成员列表中');
            }
            // 提前处理班次数据
            $shifts = array_map('intval', $item['shifts']);
            // 获取已有记录
            $recordData = $this->recordDao->column(['uid' => $uid, 'month' => $date, 'group_id' => $groupId], 'id', 'date');
            foreach ($shifts as $key => $shift) {
                $date = $dateObj->copy()->addDays($key)->toDateString();
                if (isset($recordData[$date])) {
                    unset($recordData[$date]);
                }
                if ($nowObj->diffInDays($dateObj->copy()->addDays($key), false) < 0) {
                    continue;
                }
                if (! $this->isValidShift($shift, $shiftIds)) {
                    throw $this->exception('排班班次异常: 无效班次ID');
                }
                $recordsToSave[] = ['id' => $id, 'group_id' => $groupId, 'shift_id' => $shift, 'uid' => $uid, 'date' => $date];
            }
            // 删除多余的记录
            if (! empty($recordData)) {
                $delIds[] = array_merge($delIds, array_values($recordData));
            }
        }
        AttendRangeJob::dispatch($recordsToSave);
        $this->recordDao->forceDelete(['id' => $delIds]);
        return Cache::tags([self::CACHE_KEY])->flush();
    }

    /**
     * 根据考勤组清除未来排班数据.
     */
    public function clearFutureArrangeByGroupId(int $groupId, bool $isForce = false): int
    {
        Cache::tags([self::CACHE_KEY])->flush();
        return $this->recordDao->{$isForce ? 'forceDelete' : 'delete'}(['group_id' => $groupId, 'gt_date' => now(config('app.timezone'))->toDateString()]);
    }

    /**
     * 根据班次清除未来排班数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function clearFutureArrangeByShiftId(int $shiftId): ?int
    {
        Cache::tags([self::CACHE_KEY])->flush();
        return $this->recordDao->delete(['shift_id' => $shiftId, 'gt_date' => now(config('app.timezone'))->toDateString()]);
    }

    /**
     * 排班数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRecordByUid(int $uid, string $date): array
    {
        $info = toArray($this->recordDao->get(['uid' => $uid, 'date' => $date], ['group_id', 'shift_id']));
        return $info ? [(int) $info['group_id'], (int) $info['shift_id']] : [0, 0];
    }

    /**
     * 是否休息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function dayIsRest(int $uid, string $date): bool
    {
        $calcIsRest = app()->get(CalendarConfigService::class)->dayIsRest($date);
        $info       = $this->recordDao->get(['uid' => $uid, 'date' => $date], ['shift_id']);

        return $this->isRestByShift($info ? (int) $info->shift_id : null, $calcIsRest);
    }

    /**
     * 批量判断日期范围内是否全员休息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function allUsersRestByDate(array $uids, Carbon $startObj, Carbon $endObj): array
    {
        $timeZone = CarbonPeriod::create($startObj->toDateString(), $endObj->toDateString())->toArray();
        if (! $uids) {
            return array_fill_keys(array_map(fn ($day) => $day->toDateString(), $timeZone), true);
        }

        $restMap = app()->get(CalendarConfigService::class)->getRestListByPeriod(clone $startObj, clone $endObj);
        $records = $this->recordDao->setTimeField('date')->getList([
            'uid'  => $uids,
            'time' => $startObj->format('Y/m/d') . '-' . $endObj->format('Y/m/d'),
        ], ['uid', 'date', 'shift_id']);

        $recordMap = [];
        foreach ($records as $record) {
            $recordMap[$record['date']][(int) $record['uid']] = (int) $record['shift_id'];
        }

        $data = [];
        foreach ($timeZone as $day) {
            $date           = $day->toDateString();
            $calendarIsRest = isset($restMap[$date]);
            $allRest        = true;

            foreach ($uids as $uid) {
                $shiftId = $recordMap[$date][(int) $uid] ?? null;
                if (! $this->isRestByShift($shiftId, $calendarIsRest)) {
                    $allRest = false;
                    break;
                }
            }

            $data[$date] = $allRest;
        }

        return $data;
    }

    /**
     * 应出勤天数.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRequiredDays(int $uid, string $time, Carbon $startObj, Carbon $endObj): int
    {
        // 日历配置
        $restMap = app()->get(CalendarConfigService::class)->getRestListByPeriod($startObj, $endObj);

        // 排班数据
        $recordData = $this->recordDao->setTimeField('date')->column(['uid' => $uid, 'time' => $time], 'shift_id', 'date');

        // 时间范围
        $timeZone = CarbonPeriod::create($startObj->toDateString(), $endObj->toDateString())->toArray();
        return $this->getAttendanceDays($timeZone, $recordData, $restMap);
    }

    /**
     * 应出勤天数.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRequiredDaysByUids(array $uids, Carbon $startObj, Carbon $endObj): array
    {
        $key = md5(json_encode(['uuid' => $uids, 'start' => $startObj->toDateString(), 'end' => $endObj->toDateString()]));
        return Cache::tags([self::CACHE_KEY])->remember($key, (int) sys_config('system_cache_ttl', 3600), function () use ($uids, $startObj, $endObj) {
            $data = [];
            if (empty($uids)) {
                return $data;
            }

            $timeZone = CarbonPeriod::create($startObj->toDateString(), $endObj->toDateString())->toArray();

            // 日历配置
            $where   = ['time' => $startObj->format('Y/m/d') . '-' . $endObj->format('Y/m/d')];
            $restMap = app()->get(CalendarConfigService::class)->getRestListByPeriod($startObj, $endObj);
            foreach ($uids as $uid) {
                // 排班数据
                $recordData = $this->recordDao->setTimeField('date')->column(array_merge($where, ['uid' => $uid]), 'shift_id', 'date');

                // 时间范围
                $data[$uid] = $this->getAttendanceDays($timeZone, $recordData, $restMap);
            }
            return $data;
        });
    }

    /**
     * 获取班次考勤人员ID.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMemberIdsByShiftId(int $shiftId, string $date): array
    {
        return array_unique($this->recordDao->column(['shift_id' => $shiftId, 'date' => $date], 'uid'));
    }

    /**
     * 清除考勤人员排班数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function clearFutureArrangeByMembers(array $members, bool $isWhitelist = false): int
    {
        $dateObj = now(config('app.timezone'));
        $isWhitelist && $dateObj->subDay();
        Cache::tags([self::CACHE_KEY])->flush();

        return $this->recordDao->forceDelete(['uid' => $members, 'gt_date' => $dateObj->toDateString()]);
    }

    /**
     * 生成考勤排班数据.
     * @throws BindingResolutionException
     */
    public function generateGroupArrange(int $groupId, string $date, int $uid = 0): Model
    {
        $info = $this->dao->get(['group_id' => $groupId, 'date' => $date]);
        if (! $info) {
            $info = $this->dao->create(['group_id' => $groupId, 'uid' => $uid, 'date' => $date]);
        }
        return $info;
    }

    /**
     * 生成考勤用户默认排班.
     * @param mixed $arrangeDate
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function generateAttendanceRecordByMember(int $member, int $shiftId, string $date, $arrangeDate): void
    {
        $groupId   = 0;
        $arrangeId = 0;

        $groupService = app()->get(AttendanceGroupService::class);

        // white list
        $whitelist = $groupService->getWhiteListMemberIds();
        if (in_array($member, $whitelist)) {
            $shiftId = 0;
        } else {
            // get member group
            $group = $groupService->getGroupByUid($member);
            if ($group) {
                $groupId   = $group->id;
                $arrangeId = $this->generateGroupArrange($group->id, $arrangeDate)->id;
            }
        }
        $this->generateRecord($arrangeId, $groupId, $shiftId, $member, $date);
    }

    /**
     * 生成考勤组默认排班数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function generateAttendGroupRecord(string $date): void
    {
        $arrangeDate = Carbon::parse($date, config('app.timezone'))->firstOfMonth()->toDateTimeString();

        $arrangeService = app()->get(AttendanceArrangeService::class);
        foreach (app()->get(AttendanceGroupService::class)->getSelectList() as $group) {
            if (! $arrangeService->generateGroupArrange($group['id'], $arrangeDate)) {
                Log::error('生成考勤组默认排班失败', ['group_id' => $group->id, 'date' => $arrangeDate]);
            }
        }
    }

    /**
     * 获取下个工作日.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getNextArrangeDayByUid(int $uid, string $date = ''): string
    {
        $workDate = '';
        $continue = true;
        $tz       = config('app.timezone');
        $dateObj  = $date ? Carbon::parse($date, $tz) : Carbon::now($tz);

        $calendarConfigService = app()->get(CalendarConfigService::class);

        while ($continue) {
            $date = $dateObj->addDay()->toDateString();
            $info = toArray($this->recordDao->get(['uid' => $uid, 'date' => $date], ['date', 'shift_id']));
            if ($info && $info['shift_id'] > 0) {
                if ($info['shift_id'] > 1) {
                    $workDate = $date;
                    $continue = false;
                }
            } else {
                $dayIsRest = $calendarConfigService->dayIsRest($date);
                if (! $dayIsRest) {
                    $workDate = $date;
                    $continue = false;
                }
            }
        }

        return $workDate;
    }

    /**
     * 保存考勤排班数据、处理打卡统计状态.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveRecords(array $recordsToSave)
    {
        $newRecord = [];
        foreach ($recordsToSave as $record) {
            $newRecord[$record['uid']][] = $record;
        }
        $statisticsService = app(AttendanceStatisticsService::class);
        $groupService      = app(AttendanceGroupService::class);
        $shiftService      = app(AttendanceShiftService::class);
        $statusService     = app(AttendanceStatusService::class);
        foreach ($newRecord as $k => $v) {
            foreach ($v as $item) {
                $this->recordDao->updateOrCreate([
                    'uid'  => $k,
                    'date' => $item['date'],
                ], [
                    'uid'        => $k,
                    'date'       => $item['date'],
                    'arrange_id' => $item['id'],
                    'group_id'   => $item['group_id'],
                    'shift_id'   => $item['shift_id'],
                ]);
                if ($item['date'] == now()->tz('Asia/Shanghai')->toDateString()) {
                    if ($item['shift_id']) {
                        $info      = $statisticsService->renewStatisticsByDate((int) $k, $item['date']);
                        $shiftData = $shiftService->getArrangeShiftById($item['shift_id'], $item['date']);
                        if ($info->shift_id != $item['shift_id'] || $info->group_id != $item['group_id']) {
                            $info->shift_id   = $item['shift_id'];
                            $info->group_id   = $item['group_id'];
                            $info->group      = $groupService->value(['id' => $item['group_id']], 'name');
                            $info->shift_data = $shiftData;
                            $info->save();
                        }
                        $statusService->changeStatus($k, $item['date'], $shiftData);
                    }
                }
            }
        }
    }

    /**
     * 是否为考勤管理员.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function isAdmin(int $uid): bool
    {
        if (app()->get(AttendanceWhitelistService::class)->exists(['uid' => $uid, 'type' => 1])) {
            return true;
        }
        return (bool) app()->get(AttendanceGroupMemberService::class)->exists(['member' => $uid, 'type' => 2]);
    }

    /**
     * 排班数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUserShift(int $uid, string $date): array
    {
        $info = $this->recordDao->get(['uid' => $uid, 'date' => $date], ['shift_id'], ['shift' => fn ($q) => $q->select(['*'])->with(['rules'])])?->toArray();
        return $info ? ($info['shift'] ?: []) : [];
    }

    /**
     * 提取班次验证逻辑.
     * @param mixed $shift
     * @param mixed $shiftIds
     * @return bool
     */
    private function isValidShift($shift, $shiftIds)
    {
        return in_array($shift, $shiftIds) || in_array($shift, [0, 1]);
    }

    /**
     * 保存记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function saveRecord(int $arrangeId, int $groupId, int $shiftId, int $uid, string $date): void
    {
        $where      = ['uid' => $uid, 'date' => $date];
        $updateData = array_merge($where, ['arrange_id' => $arrangeId, 'group_id' => $groupId, 'shift_id' => $shiftId]);
        if ($this->recordDao->exists($where)) {
            $this->recordDao->update($where, $updateData);
        } else {
            $this->recordDao->create($updateData);
        }
    }

    /**
     * 获取应出勤天数.
     */
    private function getAttendanceDays(array $timeZone, array $recordData, array $restMap): int
    {
        $num = 0;
        foreach ($timeZone as $item) {
            $dateString = $item->toDateString();
            if (isset($recordData[$dateString])) {
                $shiftId = $recordData[$dateString];
                if ($shiftId >= 2) {
                    ++$num;
                    continue;
                }

                if ($shiftId == 0 && ! isset($restMap[$dateString])) {
                    ++$num;
                }
            } else {
                if (! isset($restMap[$dateString])) {
                    ++$num;
                }
            }
        }
        return $num;
    }

    /**
     * 根据排班班次与日历结果判断是否休息.
     */
    private function isRestByShift(?int $shiftId, bool $calendarIsRest): bool
    {
        if ($shiftId === null) {
            return $calendarIsRest;
        }

        if ($shiftId > 1) {
            return false;
        }

        if ($shiftId == 1 || ($shiftId < 1 && $calendarIsRest)) {
            return true;
        }

        return false;
    }

    /**
     * 生成排班数据.
     * @throws BindingResolutionException
     */
    private function generateRecord(int $arrangeId, int $groupId, int $shiftId, int $member, string $date): void
    {
        $where = ['uid' => $member, 'date' => $date];
        $info  = $this->recordDao->get($where);
        if (! $info) {
            $this->recordDao->create(array_merge($where, ['arrange_id' => $arrangeId, 'group_id' => $groupId, 'shift_id' => $shiftId]));
        } else {
            if ($info->group_id < 1 && $groupId) {
                $info->group_id = $groupId;
                $info->save();
            }
        }
    }
}
