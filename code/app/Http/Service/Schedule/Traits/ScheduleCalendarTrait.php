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

namespace App\Http\Service\Schedule\Traits;

use App\Constants\CacheEnum;
use App\Constants\ScheduleEnum;
use App\Http\Model\Customer\FollowUp;
use App\Http\Model\Customer\Remind;
use App\Http\Service\Attendance\AttendanceArrangeService;
use App\Http\Service\Attendance\CalendarConfigService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 日程列表、计数与日历展开相关方法.
 */
trait ScheduleCalendarTrait
{
    /**
     * 获取日程列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function scheduleList(int $userId, int $entId, string $start, string $end, array $cid = [], int $period = 1): array
    {
        $detailKey = md5($userId . $period . json_encode($cid) . $start . $end . 'schedule');
        $data      = Cache::tags($this->scheduleCacheTags($userId))->remember(
            $detailKey,
            (int) sys_config('system_cache_ttl', 3600),
            function () use ($period, $start, $end, $userId, $cid) {
                $assistUid    = toArray($this->userDao->column(['uid' => $userId], 'schedule_id'));
                $masterUid    = $this->dao->column(['uid' => $userId], 'id');
                $where['id']  = array_unique(array_merge($assistUid, $masterUid));
                $where['cid'] = $cid;
                if (in_array(ScheduleEnum::TYPE_PERSONAL, $cid)) {
                    $assistCid = $this->dao->column(['id' => $assistUid], 'cid') ?: [];
                    $userCid   = $this->typeDao->column(['user_id' => $userId], 'id') ?: [];
                    $assistCid = array_values(array_filter($assistCid, function ($item) use ($userCid) {
                        return ! in_array($item, [
                            ScheduleEnum::TYPE_PERSONAL,
                            ScheduleEnum::TYPE_CLIENT_TRACK,
                            ScheduleEnum::TYPE_CLIENT_RENEW,
                            ScheduleEnum::TYPE_CLIENT_RETURN,
                            ScheduleEnum::TYPE_REPORT_RENEW,
                        ]) && ! in_array($item, $userCid);
                    }));
                    $where['cid'] = array_unique(array_merge($cid, $assistCid));
                }
                $field = ['id', 'uid', 'cid', 'color', 'title', 'content', 'all_day', 'start_time', 'end_time', 'period', 'rate', 'days', 'link_id', 'fail_time'];
                $list  = $this->listHandler(toArray($this->dao->select($where, $field, [
                    'master' => fn ($query) => $query->select(['admin.id', 'name', 'avatar', 'phone']),
                    'type'   => fn ($query) => $query->select(['id', 'name', 'color', 'info']),
                    'user'   => fn ($query) => $query->select(['admin.id', 'name', 'avatar', 'phone']),
                    'remind' => fn ($query) => $query->select(['schedule_remind.uniqued', 'schedule_remind.sid', 'schedule_remind.remind_day', 'schedule_remind.remind_time']),
                ])));
                $data = $this->checkPeriod($period, $list, $start, $end, $userId);
                return json_encode(array_values($data), JSON_UNESCAPED_UNICODE);
            }
        );
        return json_decode($data, true);
    }

    /**
     * 获取日程未完成量列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function scheduleCount(int $userId, int $entId, string $start, string $end, array $cid = [], int $period = 3): array
    {
        $detailKey = md5($userId . $period . json_encode($cid) . $start . $end . 'schedule_count');
        $result    = Cache::tags($this->scheduleCacheTags($userId))->remember(
            $detailKey,
            (int) sys_config('system_cache_ttl', 3600),
            function () use ($period, $start, $end, $userId) {
                $field    = ['id', 'uid', 'cid', 'color', 'title', 'content', 'all_day', 'start_time', 'end_time', 'period', 'rate', 'days', 'link_id', 'fail_time'];
                $list     = $this->dao->select(['exists_uid' => $userId], $field, ['taskOne' => fn ($q) => $q->where('schedule_task.uid', $userId)])?->toArray();
                $data     = $this->checkPeriod($period, $list, $start, $end, $userId);
                $timeZone = array_map(fn ($val) => $val->toDateString(), CarbonPeriod::create(Carbon::parse($start, $this->timeZone)->toDateTimeString(), Carbon::parse($end, $this->timeZone)->toDateTimeString())->toArray());
                $restMap  = $result = [];
                // 日历配置
                $getRestList = app()->get(CalendarConfigService::class)->getRestList(Carbon::parse($end, config('app.timezone'))->format('Y'));
                foreach ($getRestList as $rest) {
                    $restMap[$rest] = 1;
                }
                foreach ($timeZone as $time) {
                    $count = $no_submit = 0;
                    if (Carbon::parse($time)->endOfDay()->timestamp <= today()->endOfDay()->timestamp) {
                        foreach ($data as $val) {
                            $have = $this->hasOverlap(Carbon::parse($time)->timestamp, Carbon::parse($time)->endOfDay()->timestamp, Carbon::parse($val['start_time'])->timestamp, Carbon::parse($val['end_time'])->timestamp);
                            if ($have) {
                                ++$count;
                                if ($val['finish'] != 3 && $val['finish'] != 2) {
                                    ++$no_submit;
                                }
                            }
                        }
                    }

                    $result[] = [
                        'time'      => $time,
                        'no_submit' => $count ? $no_submit : -1,
                        'is_rest'   => $restMap[$time] ?? 0,
                    ];
                }
                return json_encode($result, JSON_UNESCAPED_UNICODE);
            }
        );
        return json_decode($result, true);
    }

    /**
     * 获取日程重复频率.
     */
    public function getScheduleDays(int $types, int $period, string $time): array
    {
        $days                = [];
        $carbon              = Carbon::parse($time, config('app.timezone'));
        $types == 1 && $days = match ($period) {
            1       => [$carbon->dayOfWeekIso],
            2       => [$carbon->format('d')],
            default => []
        };
        return $days;
    }

    /**
     * 下个工作日待办标题数就.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getNextWorkDayPlan(string $uuid): array
    {
        return array_column($this->getNextWorkWorkByCache($uuid), 'title');
    }

    /**
     * 下个工作日待办数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getNextWorkWorkByCache(string $uuid)
    {
        $uid        = uuid_to_uid($uuid);
        $workDay    = app()->get(AttendanceArrangeService::class)->getNextArrangeDayByUid($uid, Carbon::now($this->timeZone)->toDateString());
        $workDayObj = Carbon::parse($workDay, $this->timeZone);
        $start      = $workDayObj->startOfDay()->toDateTimeString();
        $end        = $workDayObj->endOfDay()->toDateTimeString();

        $detailKey = md5($uid . $start . $end . 'schedule');
        $res       = Cache::tags($this->scheduleCacheTags($uid))->remember(
            $detailKey,
            (int) sys_config('system_cache_ttl', 3600),
            function () use ($start, $end, $uid) {
                $field = ['id', 'uid', 'cid', 'color', 'title', 'content', 'all_day', 'start_time', 'end_time', 'period', 'rate', 'days', 'link_id', 'fail_time'];
                $list  = toArray($this->dao->select(['id' => array_unique(array_merge(
                    toArray($this->userDao->column(['uid' => $uid], 'schedule_id')),
                    $this->dao->column(['uid' => $uid], 'id')
                ))], $field));

                $data = $this->checkPeriod(1, $list, $start, $end, $uid);
                return json_encode(array_filter($data, fn ($item) => in_array($item['finish'], [-1, 0, 1])), JSON_UNESCAPED_UNICODE);
            }
        );
        return json_decode($res, true);
    }

    /**
     * 按日期获取日程列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function scheduleDateList(int $uid, int $entId, string $start, string $end, array $cid, int $period = 1): array
    {
        $res = Cache::tags($this->scheduleCacheTags($uid))->remember(
            md5($uid . $period . json_encode($cid) . $start . $end . 'schedule_date'),
            (int) sys_config('system_cache_ttl', 3600),
            function () use ($start, $end, $uid, $cid) {
                $assistUid    = $this->userDao->column(['uid' => $uid], 'schedule_id');
                $masterUid    = $this->dao->column(['uid' => $uid], 'id');
                $where['id']  = array_unique(array_merge($assistUid, $masterUid));
                $where['cid'] = $cid;
                if (in_array(ScheduleEnum::TYPE_PERSONAL, $cid)) {
                    $assistCid = $this->dao->column(['id' => $assistUid], 'cid') ?: [];
                    $userCid   = $this->typeDao->column(['user_id' => $uid], 'id') ?: [];
                    $assistCid = array_values(array_filter($assistCid, function ($item) use ($userCid) {
                        return ! in_array($item, [
                            ScheduleEnum::TYPE_PERSONAL,
                            ScheduleEnum::TYPE_CLIENT_TRACK,
                            ScheduleEnum::TYPE_CLIENT_RENEW,
                            ScheduleEnum::TYPE_CLIENT_RETURN,
                            ScheduleEnum::TYPE_REPORT_RENEW,
                        ]) && ! in_array($item, $userCid);
                    }));
                    $where['cid'] = array_unique(array_merge($cid, $assistCid));
                }
                $field = ['id', 'uid', 'cid', 'color', 'title', 'content', 'all_day', 'start_time', 'end_time', 'period', 'rate', 'days', 'link_id', 'fail_time'];
                $list  = $this->listHandler($this->dao->select($where, $field, [
                    'master' => fn ($query) => $query->select(['admin.id', 'name', 'avatar', 'phone']),
                    'type'   => fn ($query) => $query->select(['id', 'name', 'color', 'info']),
                    'user'   => fn ($query) => $query->select(['admin.id', 'name', 'avatar', 'phone']),
                    'remind' => fn ($query) => $query->select(['schedule_remind.uniqued', 'schedule_remind.sid', 'schedule_remind.remind_day', 'schedule_remind.remind_time']),
                ])?->toArray());
                $ctx  = $this->buildScheduleStatusContext($list, (int) $uid);
                $data = [];
                do {
                    $data[] = [
                        'date' => Carbon::parse($start, $this->timeZone)->toDateString(),
                        'list' => $this->getDaySchedule($list, $start, $uid, $ctx),
                    ];
                    $start = Carbon::make($start)->addDay()->toDateString();
                } while (Carbon::parse($start)->isBefore($end) || $start == $end);
                return json_encode($data, JSON_UNESCAPED_UNICODE);
            }
        );
        return json_decode($res, true);
    }

    /**
     * 按单个日程展开日期列表，用于待办增量同步，避免编辑一条日程时重扫用户全部日程.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function scheduleDateListByScheduleId(int $uid, int $scheduleId, string $start, string $end): array
    {
        $field = ['id', 'uid', 'cid', 'color', 'title', 'content', 'all_day', 'start_time', 'end_time', 'period', 'rate', 'days', 'link_id', 'fail_time'];
        $list  = toArray($this->dao->select(['id' => $scheduleId], $field));
        if (! $list) {
            return [];
        }

        $ctx    = $this->buildScheduleStatusContext($list, (int) $uid);
        $data   = [];
        $day    = Carbon::parse($start, $this->timeZone)->startOfDay();
        $endDay = Carbon::parse($end, $this->timeZone)->startOfDay();
        while ($day->lessThanOrEqualTo($endDay)) {
            $date   = $day->toDateString();
            $data[] = [
                'date' => $date,
                'list' => $this->getDaySchedule($list, $date, $uid, $ctx),
            ];
            $day->addDay();
        }

        return $data;
    }

    protected function checkPeriod($period, $list, $start, $end, $userId)
    {
        $ctx = $this->buildScheduleStatusContext($list, (int) $userId);

        return match ($period) {
            1       => $this->getDaySchedule($list, $start, $userId, $ctx),
            2       => $this->getWeekSchedule($list, $start, $userId, $ctx),
            3       => $this->getMonthSchedule($list, $start, $end, $userId, $ctx),
            default => [],
        };
    }

    /**
     * 批量预热日程完成状态与参与关系，避免日历展开 N+1.
     *
     * @param array<int, array<string, mixed>> $list
     *
     * @return array{task: array<string, int|string>, user: array<int, bool>}
     */
    protected function buildScheduleStatusContext(array $list, int $userId): array
    {
        $scheduleIds = array_values(array_unique(array_filter(array_map(
            static fn ($item) => (int) ($item['id'] ?? 0),
            $list
        ))));
        if ($scheduleIds === [] || $userId <= 0) {
            return ['task' => [], 'user' => []];
        }

        $taskMap = [];
        $tasks   = toArray($this->taskDao->select([
            'pid' => $scheduleIds,
            'uid' => $userId,
        ], ['pid', 'uid', 'start_time', 'end_time', 'status']));
        foreach ($tasks as $task) {
            $start = $this->normalizeScheduleDateTime($task['start_time'] ?? '');
            $end   = $this->normalizeScheduleDateTime($task['end_time'] ?? '');
            $taskMap[$this->buildScheduleStatusKey((int) $task['pid'], $start, $end)] = $task['status'];
        }

        $userMap = [];
        $userIds = toArray($this->userDao->column([
            'uid'         => $userId,
            'schedule_id' => $scheduleIds,
        ], 'schedule_id'));
        foreach ($userIds as $scheduleId) {
            $userMap[(int) $scheduleId] = true;
        }

        return ['task' => $taskMap, 'user' => $userMap];
    }

    /**
     * @param mixed $value
     */
    protected function normalizeScheduleDateTime($value): string
    {
        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        try {
            return Carbon::parse($value, $this->timeZone)->toDateTimeString();
        } catch (\Throwable) {
            return $value;
        }
    }

    protected function buildScheduleStatusKey(int $scheduleId, string $start, string $end): string
    {
        return $scheduleId . '|' . $start . '|' . $end;
    }

    /**
     * 日重复提醒.
     * @param mixed $list
     * @param mixed $start
     * @param mixed $userId
     * @param array{task?: array<string, int|string>, user?: array<int, bool>}|null $ctx
     * @return array
     */
    protected function getDaySchedule($list, $start, $userId, ?array $ctx = null)
    {
        $ctx ??= $this->buildScheduleStatusContext(is_array($list) ? $list : [], (int) $userId);
        $timeZone = [Carbon::parse($start, $this->timeZone)->toDateString()];
        $data     = [];
        foreach ($timeZone as $day) {
            if ($list) {
                foreach ($list as &$value) {
                    [$isHave, $status, $startTime, $endTime] = $this->haveSchedule($value, $day, $userId, $ctx);
                    if ($isHave) {
                        $value['start_time'] = $startTime;
                        $value['end_time']   = $endTime;
                        $value['finish']     = $status;
                        $unique              = md5($startTime . $value['id'] . $endTime);
                        $data[$unique]       = $value;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 周重复提醒.
     * @param mixed $list
     * @param mixed $start
     * @param mixed $userId
     * @param array{task?: array<string, int|string>, user?: array<int, bool>}|null $ctx
     * @return array
     */
    protected function getWeekSchedule($list, $start, $userId, ?array $ctx = null)
    {
        $ctx ??= $this->buildScheduleStatusContext(is_array($list) ? $list : [], (int) $userId);
        $timeZone = CarbonPeriod::create(Carbon::parse($start, $this->timeZone)->startOfWeek()->toDateString(), Carbon::parse($start, $this->timeZone)->endOfWeek()->toDateString())->toArray();
        $data     = [];
        foreach ($timeZone as $day) {
            $day = $day->toDateString();
            if ($list) {
                foreach ($list as &$value) {
                    [$isHave, $status, $startTime, $endTime] = $this->haveSchedule($value, $day, $userId, $ctx);
                    if ($isHave) {
                        $value['start_time'] = $startTime;
                        $value['end_time']   = $endTime;
                        $value['finish']     = $status;
                        $unique              = md5($startTime . $value['id'] . $endTime);
                        $data[$unique]       = $value;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 月重复提醒.
     *
     * @param mixed $userId
     * @param mixed $list
     * @param mixed $start
     * @param mixed $end
     * @param array{task?: array<string, int|string>, user?: array<int, bool>}|null $ctx
     *
     * @return array
     */
    protected function getMonthSchedule($list, $start, $end, $userId, ?array $ctx = null)
    {
        $ctx ??= $this->buildScheduleStatusContext(is_array($list) ? $list : [], (int) $userId);
        $timeZone = CarbonPeriod::create(Carbon::parse($start, $this->timeZone)->toDateString(), Carbon::parse($end, $this->timeZone)->toDateString())->toArray();
        $data     = [];
        foreach ($timeZone as $day) {
            if ($list) {
                foreach ($list as &$value) {
                    [$isHave, $status, $startTime, $endTime] = $this->haveSchedule($value, $day->toDateString(), $userId, $ctx);
                    if ($isHave) {
                        $value['start_time'] = $startTime;
                        $value['end_time']   = $endTime;
                        $value['finish']     = $status;
                        $unique              = md5($startTime . $value['id'] . $endTime);
                        $data[$unique]       = $value;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 指定日期是否有日程提醒.
     * @param mixed $userId
     * @param mixed $schedule
     * @param mixed $day
     * @param array{task?: array<string, int|string>, user?: array<int, bool>}|null $ctx
     * @return bool
     */
    protected function haveSchedule($schedule, $day, $userId, ?array $ctx = null): array
    {
        $isHava    = false;
        $status    = -1;
        $startTime = Carbon::parse($schedule['start_time'], $this->timeZone);
        $endTime   = Carbon::parse($schedule['end_time'], $this->timeZone);
        if ($schedule['days'] && is_string($schedule['days'])) {
            $schedule['days'] = json_decode($schedule['days'], true);
        }
        if (! $schedule['fail_time']) {
            $failTime = Carbon::parse($schedule['fail_time'], $this->timeZone)->addYears(5)->timestamp;
        } else {
            $failTime = Carbon::parse($schedule['fail_time'], $this->timeZone)->timestamp;
        }
        $dayTime = Carbon::parse($day, $this->timeZone)->endOfDay();
        $start   = $startTime->copy()->toDateTimeString();
        $end     = $endTime->copy()->toDateTimeString();
        switch ($schedule['period']) {
            case ScheduleEnum::REPEAT_DAY:// 天重复
                $period = bcdiv(bcsub((string) $dayTime->timestamp, (string) $startTime->copy()->startOfDay()->timestamp), bcmul((string) $schedule['rate'], '86400'));
                if ((int) $period >= 0) {
                    $start = $startTime->copy()->addDays($period * $schedule['rate']);
                    $end   = $endTime->copy()->addDays($period * $schedule['rate']);
                    if ($this->hasOverlap($dayTime->startOfDay()->timestamp, $dayTime->endOfDay()->timestamp, $start->timestamp, $end->timestamp) && $failTime >= $dayTime->endOfDay()->timestamp) {
                        $status = $this->getScheduleStatus($userId, $schedule['uid'], $schedule['id'], $start, $end, $ctx);
                        $isHava = true;
                    }
                    $start = $start->toDateTimeString();
                    $end   = $end->toDateTimeString();
                }
                break;
            case ScheduleEnum::REPEAT_WEEK:// 周重复
                $period = bcdiv(bcsub((string) $dayTime->timestamp, (string) $startTime->copy()->startOfDay()->timestamp), bcmul((string) $schedule['rate'], '604800'));
                if ($period >= 0) {
                    foreach ($schedule['days'] as $v) {
                        $dayOfWeek = $startTime->dayOfWeekIso;
                        if ($dayTime->dayOfWeekIso == (int) $v) {
                            if ($dayOfWeek > $v) {
                                $period = $period + 1;
                                $start  = $startTime->copy()->addWeeks($period * $schedule['rate'])->subDays($dayOfWeek - $v);
                                $end    = $endTime->copy()->addWeeks($period * $schedule['rate'])->subDays($dayOfWeek - $v);
                            } else {
                                $start = $startTime->copy()->addWeeks($period * $schedule['rate'])->addDays($v - $dayOfWeek);
                                $end   = $endTime->copy()->addWeeks($period * $schedule['rate'])->addDays($v - $dayOfWeek);
                            }
                            if ($this->hasOverlap($dayTime->startOfDay()->timestamp, $dayTime->endOfDay()->timestamp, $start->timestamp, $end->timestamp) && $failTime >= $dayTime->endOfDay()->timestamp) {
                                $status = $this->getScheduleStatus($userId, $schedule['uid'], $schedule['id'], $start, $end, $ctx);
                                $isHava = true;
                            }
                            $start = $start->toDateTimeString();
                            $end   = $end->toDateTimeString();
                            break;
                        }
                    }
                }
                break;
            case ScheduleEnum::REPEAT_MONTH:// 月重复
                $period = bcdiv(bcsub($dayTime->format('m'), $startTime->copy()->format('m')), (string) $schedule['rate']);
                if ($period >= 0) {
                    foreach ($schedule['days'] as $v) {
                        $dayOfMonth = (int) $startTime->copy()->format('d');
                        if ($dayTime->format('d') == $v) {
                            if ($dayOfMonth > $v) {
                                $start = $startTime->copy()->addMonths($period * $schedule['rate'])->subDays($dayOfMonth - $v);
                                $end   = $endTime->copy()->addMonths($period * $schedule['rate'])->subDays($dayOfMonth - $v);
                            } else {
                                $start = $startTime->copy()->addMonths($period * $schedule['rate'])->addDays($v - $dayOfMonth);
                                $end   = $endTime->copy()->addMonths($period * $schedule['rate'])->addDays($v - $dayOfMonth);
                            }
                            if ($start->timestamp >= $startTime->copy()->timestamp
                                && $this->hasOverlap($dayTime->startOfDay()->timestamp, $dayTime->endOfDay()->timestamp, $start->timestamp, $end->timestamp)
                                && $failTime >= $dayTime->endOfDay()->timestamp) {
                                $status = $this->getScheduleStatus($userId, $schedule['uid'], $schedule['id'], $start, $end, $ctx);
                                $isHava = true;
                            }
                            $start = $start->toDateTimeString();
                            $end   = $end->toDateTimeString();
                            break;
                        }
                    }
                    if ($this->hasOverlap($dayTime->startOfDay()->timestamp, $dayTime->endOfDay()->timestamp, $startTime->copy()->timestamp, $endTime->copy()->timestamp) && $failTime >= $startTime->copy()->timestamp) {
                        $status = $this->getScheduleStatus($userId, $schedule['uid'], $schedule['id'], $start, $end, $ctx);
                        $isHava = true;
                        break;
                    }
                }
                break;
            case ScheduleEnum::REPEAT_YEAR:// 年重复
                $period = bcdiv(bcsub($dayTime->format('y'), $startTime->copy()->format('y')), (string) $schedule['rate']);
                if ($period >= 0) {
                    $start = $startTime->copy()->addYears($period * $schedule['rate']);
                    $end   = $endTime->copy()->addYears($period * $schedule['rate']);
                    if ($start->timestamp >= $startTime->copy()->timestamp
                        && $this->hasOverlap($dayTime->startOfDay()->timestamp, $dayTime->endOfDay()->timestamp, $start->timestamp, $end->timestamp)
                        && $failTime >= $dayTime->endOfDay()->timestamp) {
                        $status = $this->getScheduleStatus($userId, $schedule['uid'], $schedule['id'], $start, $end, $ctx);
                        $isHava = true;
                    }
                    $start = $start->toDateTimeString();
                    $end   = $end->toDateTimeString();
                }
                break;
            default:
                if ($this->hasOverlap($dayTime->startOfDay()->timestamp, $dayTime->endOfDay()->timestamp, $startTime->copy()->timestamp, $endTime->copy()->timestamp) && $failTime >= $startTime->copy()->timestamp) {
                    $status = $this->getScheduleStatus($userId, $schedule['uid'], $schedule['id'], $start, $end, $ctx);
                    $isHava = true;
                }
        }
        return [$isHava, $status, $start, $end];
    }

    /**
     * 获取日程状态
     * @param mixed $userId
     * @param mixed $masterId
     * @param mixed $scheduleId
     * @param mixed $start
     * @param mixed $end
     * @param array{task?: array<string, int|string>, user?: array<int, bool>}|null $ctx
     * @return null|int|mixed
     * @throws BindingResolutionException
     */
    protected function getScheduleStatus($userId, $masterId, $scheduleId, $start, $end, ?array $ctx = null)
    {
        $startKey = $this->normalizeScheduleDateTime($start);
        $endKey   = $this->normalizeScheduleDateTime($end);
        $mapKey   = $this->buildScheduleStatusKey((int) $scheduleId, $startKey, $endKey);

        if (is_array($ctx) && array_key_exists('task', $ctx) && array_key_exists($mapKey, $ctx['task'])) {
            return $ctx['task'][$mapKey];
        }

        $status = null;
        if (! is_array($ctx) || ! array_key_exists('task', $ctx)) {
            $status = $this->taskDao->value([
                'pid'        => $scheduleId,
                'start_time' => $startKey ?: $start,
                'end_time'   => $endKey ?: $end,
                'uid'        => $userId,
            ], 'status');
        }

        if (is_null($status)) {
            if ($userId == $masterId) {
                if (is_array($ctx) && array_key_exists('user', $ctx)) {
                    return isset($ctx['user'][(int) $scheduleId]) ? 1 : 3;
                }
                if (! $this->userDao->exists(['uid' => $userId, 'schedule_id' => $scheduleId])) {
                    return 3;
                }
                return 1;
            }
            return -1;
        }
        return $status;
    }

    /**
     * 处理列表数据.
     * @param mixed $list
     * @return array|mixed
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function listHandler($list = [])
    {
        if (! $list) {
            return [];
        }

        $followUniqueds = [];
        $remindUniqueds = [];
        foreach ($list as $item) {
            $uniqued = $item['remind']['uniqued'] ?? '';
            if (! $uniqued) {
                continue;
            }
            if ((int) ($item['cid'] ?? 0) === ScheduleEnum::TYPE_CLIENT_TRACK) {
                $followUniqueds[] = $uniqued;
            } elseif (in_array((int) ($item['cid'] ?? 0), [ScheduleEnum::TYPE_CLIENT_RENEW, ScheduleEnum::TYPE_CLIENT_RETURN], true)) {
                $remindUniqueds[] = $uniqued;
            }
        }

        $followMap = [];
        if ($followUniqueds) {
            $rows = FollowUp::query()
                ->whereIn('uniqued', array_values(array_unique($followUniqueds)))
                ->where('status', 0)
                ->where('types', 1)
                ->get(['id', 'uniqued']);
            foreach ($rows as $row) {
                $followMap[$row->uniqued] = ['follow_id' => $row->id];
            }
        }

        $remindMap = [];
        if ($remindUniqueds) {
            $rows = Remind::query()
                ->whereIn('uniqued', array_values(array_unique($remindUniqueds)))
                ->where('status', 0)
                ->get(['id', 'bill_id', 'uniqued']);
            foreach ($rows as $row) {
                $remindMap[$row->uniqued] = [
                    'remind_id' => $row->id,
                    'bill_id'   => $row->bill_id,
                ];
            }
        }

        foreach ($list as &$item) {
            $uniqued = $item['remind']['uniqued'] ?? '';
            if ($uniqued) {
                $item['relation'] = match ((int) ($item['cid'] ?? 0)) {
                    ScheduleEnum::TYPE_CLIENT_TRACK => $followMap[$uniqued] ?? null,
                    ScheduleEnum::TYPE_CLIENT_RENEW, ScheduleEnum::TYPE_CLIENT_RETURN => $remindMap[$uniqued] ?? null,
                    default => null
                };
            }
            unset($item['remind']['uniqued']);
        }
        return $list;
    }
}
