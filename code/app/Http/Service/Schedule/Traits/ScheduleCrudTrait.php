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
use App\Constants\CommonEnum;
use App\Constants\ScheduleEnum;
use App\Constants\TodoEnum;
use App\Http\Model\Schedule\Schedule;
use App\Http\Model\Schedule\ScheduleUser;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\FollowUpService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\RemindService;
use App\Http\Service\Todo\TodoItemService;
use App\Task\message\StatusChangeTask;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 日程增删改、状态、快捷创建与业务联动相关方法.
 */
trait ScheduleCrudTrait
{
    /**
     * 保存日程.
     *
     * @return bool|int 更新成功返回 true；新建成功返回日程 ID
     * @throws BindingResolutionException
     */
    public function saveSchedule(int $userId, int $entId, array $data, int $id = 0): bool|int
    {
        $remind  = $this->getRemindInfo($data);
        $members = $data['member'] ?? [];

        // 全天日程：结束时间设置为 23:59:59
        $startTime = $data['start_time'];
        $endTime   = $data['end_time'];
        if (! empty($data['all_day'])) {
            $endTime = Carbon::parse($data['end_time'], $this->timeZone)->endOfDay()->toDateTimeString();
        }

        $schedule = [
            'uid'        => $userId,
            'cid'        => $data['cid'],
            'color'      => $this->typeDao->value($data['cid'], 'color') ?: '',
            'title'      => $data['title'],
            'content'    => $data['content'],
            'all_day'    => $data['all_day'],
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'fail_time'  => $data['fail_time'] ?: null,
            'period'     => $remind ? $remind['period'] : 0,
            'rate'       => $remind ? $remind['rate'] : 1,
            'days'       => $remind ? $remind['days'] : null,
            'remind'     => (int) ($data['remind'] >= 0),
            'link_id'    => $data['link_id'] ?? 0,
        ];
        return $this->transaction(function () use ($remind, $entId, $members, $userId, $schedule, $id, $data) {
            if ($id) {
                Schedule::withoutEvents(fn () => $this->dao->update($id, $schedule));
                $this->remindDao->delete(['sid' => $id]);
                $memberIds = array_values(array_unique(array_filter(array_map('intval', $members))));
                if ($userId > 0 && ! in_array($userId, $memberIds, true)) {
                    $memberIds[] = $userId;
                }
                if ($data['remind'] >= 0) {
                    foreach ($memberIds as $member) {
                        $row            = $remind;
                        $row['sid']     = $id;
                        $row['uid']     = $member;
                        $row['entid']   = $entId;
                        $row['uniqued'] = $data['uniqued'] ?? '';
                        $this->remindDao->create($row);
                    }
                }
                $oldUids = $this->userDao->column(['schedule_id' => $id, 'is_master' => 0], 'uid');
                if ($diff = array_diff($oldUids, $memberIds)) {
                    $this->userDao->delete(['uid' => $diff, 'schedule_id' => $id]);
                    $this->clearScheduleCache($diff);
                }
                if ($add = array_diff($memberIds, array_map('intval', $oldUids))) {
                    $this->batchCreateScheduleUsers($id, $add);
                    $this->clearScheduleCache($add);
                }
                $this->clearScheduleCache($userId);
                $this->dispatchTodoSync($id, 'update', array_merge($oldUids, $memberIds));
                return true;
            }
            $save = Schedule::withoutEvents(fn () => $this->dao->create($schedule));
            $memberIds = array_values(array_filter(array_map('intval', $members)));
            $this->batchCreateScheduleUsers((int) $save->id, $memberIds);
            if ($data['remind'] >= 0) {
                foreach ($memberIds as $member) {
                    $row              = $remind;
                    $row['sid']       = (int) $save->id;
                    $row['uid']       = $member;
                    $row['entid']     = $entId;
                    $row['uniqued']   = $data['uniqued'] ?? '';
                    $this->remindDao->create($row);
                }
            }
            if (in_array($data['cid'], [ScheduleEnum::TYPE_CLIENT_RENEW, ScheduleEnum::TYPE_CLIENT_RETURN])) {
                app()->get(RemindService::class)->updatePeriod(1, $data['uniqued'], [$data['start_time'], $data['end_time']]);
            }
            $this->clearScheduleCache(array_merge([$userId], $memberIds));
            $this->dispatchTodoSync((int) $save->id, 'create', $memberIds);
            return (int) $save->id;
        });
    }

    /**
     * 快捷创建日程：仅需标题，可选参与人/截止时间/描述/提醒，补齐默认值后复用 saveSchedule.
     *
     * @param array<string, mixed> $data 快捷创建入参
     *
     * @return int 新建日程 ID
     * @throws BindingResolutionException
     */
    public function quickSaveSchedule(int $userId, int $entId, array $data): int
    {
        $payload = $this->buildQuickSchedulePayload($userId, $data);
        $result  = $this->saveSchedule($userId, $entId, $payload);

        return is_int($result) ? $result : 0;
    }

    /**
     * 将快捷创建入参归一化为完整 saveSchedule 载荷.
     *
     * @param array<string, mixed> $data 快捷创建入参
     *
     * @return array<string, mixed>
     */
    protected function buildQuickSchedulePayload(int $userId, array $data): array
    {
        $title   = trim((string) ($data['title'] ?? ''));
        $content = (string) ($data['content'] ?? '');
        $remind  = array_key_exists('remind', $data) && $data['remind'] !== '' && $data['remind'] !== null
            ? (int) $data['remind']
            : 0;

        $members = array_values(array_unique(array_filter(array_map('intval', (array) ($data['member'] ?? [])))));
        if ($userId > 0 && ! in_array($userId, $members, true)) {
            $members[] = $userId;
        }

        $endTimeInput = trim((string) ($data['end_time'] ?? ''));
        if ($endTimeInput === '') {
            $now       = Carbon::now($this->timeZone);
            $startTime = $now->toDateTimeString();
            $endTime   = $now->copy()->addHour()->toDateTimeString();
            $allDay    = 0;
        } else {
            $deadline = Carbon::parse($endTimeInput, $this->timeZone);
            // 仅传 Y-m-d 时按全天待办处理
            $isDateOnly = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $endTimeInput);
            if ($isDateOnly) {
                $allDay    = 1;
                $startTime = $deadline->copy()->startOfDay()->toDateTimeString();
                $endTime   = $deadline->copy()->endOfDay()->toDateTimeString();
            } else {
                $allDay    = 0;
                $startTime = $deadline->copy()->startOfDay()->toDateTimeString();
                $endTime   = $deadline->toDateTimeString();
            }
        }

        return [
            'title'      => $title,
            'content'    => $content,
            'cid'        => ScheduleEnum::TYPE_PERSONAL,
            'member'     => $members,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'all_day'    => $allDay,
            'remind'     => $remind,
            'period'     => ScheduleEnum::REPEAT_NOT,
            'rate'       => 1,
            'days'       => [],
            'fail_time'  => null,
            'link_id'    => 0,
        ];
    }

    protected function dispatchTodoSync(int $scheduleId, string $action, array $userIds = []): void
    {
        try {
            // 编辑/创建后直接走单条业务源待办同步，按实例完成态刷新状态（不经 markScheduleDone 全量误标）.
            $service = app()->get(TodoItemService::class);
            $uids    = array_values(array_unique(array_filter(array_map('intval', $userIds))));
            match ($action) {
                'create' => $service->createBySourceId(TodoEnum::TYPE_SCHEDULE, $scheduleId, $uids),
                'delete' => $service->deleteBySourceId(TodoEnum::TYPE_SCHEDULE, $scheduleId, $uids),
                default  => $service->updateBySourceId(TodoEnum::TYPE_SCHEDULE, $scheduleId, $uids),
            };
        } catch (\Throwable) {
            // 待办同步失败不能影响日程主流程.
        }
    }

    /**
     * 修改日程.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateSchedule(int $entId, int $id, array $data, int $userId = 0): bool
    {
        $res  = false;
        $info = toArray($this->dao->get($id, with: ['user', 'remind']));
        switch ($data['type']) {
            case ScheduleEnum::CHANGE_AFTER:
                if (datetime_timestamp($data['start']) <= datetime_timestamp($info['start_time'])) {
                    $res = $this->saveSchedule($userId ?: $info['uid'], $entId, $data, $id);
                } elseif (datetime_timestamp($data['start']) > datetime_timestamp($info['start_time'])) {
                    switch ($info['period']) {
                        case ScheduleEnum::REPEAT_WEEK:
                        case ScheduleEnum::REPEAT_MONTH:
                        case ScheduleEnum::REPEAT_YEAR:
                        case ScheduleEnum::REPEAT_DAY:
                            $this->saveSchedule($userId, $entId, $data);
                            break;
                    }
                    $failTime = $this->getPreviousPeriod($info['end_time'], $data['end'], $info['period'], $info['rate'], $info['days']);
                    $res      = $this->updateTime($id, failTime: $failTime);
                }
                break;
            case ScheduleEnum::CHANGE_NOW:
                if (datetime_timestamp($data['start']) == datetime_timestamp($info['start_time'])) {
                    [$startTime, $endTime] = $this->getNextPeriod($data['start'], $data['end'], $info['period'], $info['rate'], $info['days']);
                    if (! $info['fail_time'] || datetime_timestamp($startTime) <= datetime_timestamp($info['fail_time'])) {
                        $this->saveSchedule($userId, $entId, [
                            'title'       => $info['title'],
                            'content'     => $info['content'],
                            'cid'         => $info['cid'],
                            'color'       => $info['color'],
                            'remind'      => $info['remind'],
                            'remind_time' => $info['remind'] ? $info['remind']['remind_day'] . ' ' . $info['remind']['remind_time'] : now()->toDateTimeString(),
                            'period'      => $info['period'],
                            'rate'        => $info['rate'],
                            'days'        => $info['days'],
                            'all_day'     => $info['all_day'],
                            'start_time'  => $startTime,
                            'end_time'    => $endTime,
                            'fail_time'   => $info['fail_time'],
                            'member'      => $info['user'] ? array_column($info['user'], 'id') : [],
                        ]);
                    }
                    $data['fail_time'] = $data['end'];
                    $res               = $this->saveSchedule($userId, $entId, $data, $id);
                } elseif (datetime_timestamp($data['start']) > datetime_timestamp($info['start_time'])) {
                    switch ($info['period']) {
                        case ScheduleEnum::REPEAT_WEEK:
                        case ScheduleEnum::REPEAT_MONTH:
                        case ScheduleEnum::REPEAT_YEAR:
                        case ScheduleEnum::REPEAT_DAY:
                            $data['fail_time'] = Carbon::parse($data['end'], $this->timeZone)->endOfDay()->toDateTimeString();
                            $this->saveSchedule($userId, $entId, $data);
                            break;
                    }
                    $failTime              = $this->getPreviousPeriod($info['end_time'], $data['end'], $info['period'], $info['rate'], $info['days']);
                    $res                   = $this->updateTime($id, failTime: $failTime);
                    [$startTime, $endTime] = $this->getNextPeriod($data['start'], $data['end'], $info['period'], $info['rate'], $info['days']);
                    if (! $info['fail_time'] || datetime_timestamp($startTime) <= datetime_timestamp($info['fail_time'])) {
                        $this->saveSchedule($userId, $entId, [
                            'title'       => $info['title'],
                            'content'     => $info['content'],
                            'cid'         => $info['cid'],
                            'color'       => $info['color'],
                            'remind'      => $info['remind'],
                            'remind_time' => $info['remind'] ? $info['remind']['remind_day'] . ' ' . $info['remind']['remind_time'] : now()->toDateTimeString(),
                            'period'      => $info['period'],
                            'rate'        => $info['rate'],
                            'days'        => $info['days'],
                            'all_day'     => $info['all_day'],
                            'start_time'  => $startTime,
                            'end_time'    => $endTime,
                            'fail_time'   => $info['fail_time'],
                            'member'      => $info['user'] ? array_column($info['user'], 'id') : [],
                        ]);
                    }
                }
                break;
            default:
                $res = $this->saveSchedule($userId, $entId, $data, $id);
        }
        $userIds = array_column($info['user'], 'id');
        array_push($userIds, $userId);
        $this->clearScheduleCache($userIds);
        return $res;
    }

    /**
     * 删除日程
     * @param int $userId
     * @param int $entId
     * @param int $id
     * @param array $data
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteSchedule(int $userId, int $entId, int $id, array $data): bool
    {
        $res = false;
        $info = $this->dao->get($id, with: ['scheduleUser', 'remindData']);
        if (! $info) {
            throw $this->exception('未找到相关记录');
        }
        // 提取用户ID
        $userIds = collect($info->scheduleUser ?? [])->pluck('uid')->push($userId)->unique()->all();

        switch ($data['type']) {
            case ScheduleEnum::CHANGE_AFTER:
                $start = datetime_timestamp($data['start']);
                $end = datetime_timestamp($data['end']);
                $infoStart = datetime_timestamp($info->start_time);
                $infoEnd = datetime_timestamp($info->end_time);
                if ($start <= $infoStart && $end <= $infoEnd) {
                    $res = $this->deleteScheduleAllRelated($info);
                } elseif ($start > $infoStart) {
                    $period = $info->period;
                    if (in_array($period, [ScheduleEnum::REPEAT_WEEK, ScheduleEnum::REPEAT_MONTH, ScheduleEnum::REPEAT_YEAR, ScheduleEnum::REPEAT_DAY])) {
                        $failTime = $this->getPreviousPeriod($info->end_time, $data['end'], $period, $info->rate, $info->days);
                        $res = $this->updateTime($id, failTime: $failTime);
                    } else {
                        $res = $this->deleteScheduleAllRelated($info);
                    }
                }
                $this->changeNoticeStatus($entId, $info->toArray(), $data['start'], $data['end'], true);
                break;
            case ScheduleEnum::CHANGE_NOW:
                $start = datetime_timestamp($data['start']);
                $infoStart = datetime_timestamp($info->start_time);
                if ($start === $infoStart) {
                    if (! $info->period) {
                        $res = $this->deleteScheduleAllRelated($info);
                    } else {
                        [$nextStart, $nextEnd] = $this->getNextPeriod($data['start'], $data['end'], $info->period, $info->rate, $info->days);
                        $res = $this->updateTime($id, startTime: $nextStart, endTime: $nextEnd, failTime: $info->fail_time);
                    }
                } elseif ($start > $infoStart) {
                    $failTime = $this->getPreviousPeriod($info->end_time, $data['end'], $info->period, $info->rate, $info->days);
                    $res = $this->updateTime($id, failTime: $failTime);
                    [$nextStart, $nextEnd] = $this->getNextPeriod($data['start'], $data['end'], $info->period, $info->rate, $info->days);
                    if (is_null($info->fail_time) || datetime_timestamp($nextStart) <= datetime_timestamp($info->fail_time)) {
                        $this->saveSchedule($userId, $entId, [
                            'title'       => $info->title,
                            'content'     => $info->content,
                            'cid'         => $info->cid,
                            'color'       => $info->color,
                            'remind'      => $info->remind,
                            'remind_time' => $info->remindData ? "{$info->remindData->remind_day} {$info->remindData->remind_time}" : now()->toDateTimeString(),
                            'period'      => $info->period,
                            'rate'        => $info->rate,
                            'days'        => $info->days,
                            'all_day'     => $info->all_day,
                            'start_time'  => $nextStart,
                            'end_time'    => $nextEnd,
                            'fail_time'   => $info->fail_time,
                            'uniqued'     => $info->remindData?->uniqued ?? '',
                            'link_id'     => $info->link_id,
                            'member'      => collect($info->scheduleUser)->pluck('uid')->all(),
                        ]);
                    }
                }
                $this->changeNoticeStatus($entId, $info->toArray(), $data['start'], $data['end']);
                break;

            default:
                $res = $this->deleteScheduleAllRelated($info);
                $this->deleteSystemSchedule($info->toArray());
                $this->changeNoticeStatus($entId, $info->toArray(), $data['start'], $data['end'], true);
        }

        $this->clearScheduleCache($userIds);
        return (bool)$res;
    }

    /**
     * 统一删除：模型 ->delete() 方式
     */
    protected function deleteScheduleAllRelated($info): bool
    {
        return $this->transaction(function () use ($info) {
            // 主模型删除
            $info->delete();
            // 关联提醒 模型删除
            if ($info->remindData) {
                $info->remindData->delete();
            }
            // 关联任务
            $this->taskDao->delete(['pid' => $info->id]);
            // 关联用户 模型删除
            if ($info->scheduleUser) {
                collect($info->scheduleUser)->each->delete();
            }
            return true;
        });
    }

    /**
     * 修改日程状态
     * @throws BindingResolutionException
     */
    public function updateStatus(int $id, int $uid, int $entId, int $status, array $timeZone = []): bool
    {
        if (! $this->userDao->exists(['schedule_id' => $id, 'uid' => $uid])) {
            throw $this->exception('未找到相关记录');
        }
        $info = $this->dao->get($id, with: [
            'remind' => fn ($q) => $q->select(['id', 'sid', 'uniqued']),
        ])?->toArray();
        if (! $info) {
            throw $this->exception('未找到相关记录');
        }
        $timeZone = array_map(fn ($time) => $time instanceof Carbon ? $time->toDateTimeString() : (string) $time, $timeZone);

        // 付款提醒
        if (in_array($info['cid'], [ScheduleEnum::TYPE_CLIENT_RENEW, ScheduleEnum::TYPE_CLIENT_RETURN])) {
            $period = [];
            if ($status == 3 && $info['cid'] == ScheduleEnum::TYPE_CLIENT_RENEW) {
                $period = $this->getNextPeriod($timeZone[0], $timeZone[1], $info['period'], $info['rate'], $info['days']);
            }
            $remindService = app()->get(RemindService::class);
            $remindService->updatePeriod($status, $info['remind']['uniqued'], [$timeZone[0], $period[0] ?? '']);
        }
        $where = [
            'pid'        => $id,
            'uid'        => $uid,
            'start_time' => $timeZone[0],
            'end_time'   => $timeZone[1],
        ];
        $save = [
            'pid'        => $id,
            'uid'        => $uid,
            'status'     => $status,
            'start_time' => $timeZone[0],
            'end_time'   => $timeZone[1],
        ];
        $this->clearScheduleCache($uid);
        $res = $this->taskDao->exists($where)
            ? (bool) $this->taskDao->update($where, $save)
            : (bool) $this->taskDao->create($save);

        if ($res) {
            $this->syncScheduleTodoInstance($uid, $id, $info, (int) $status, $timeZone[0], $timeZone[1]);
        }
        return $res;
    }

    protected function syncScheduleTodoInstance(int $uid, int $id, array $info, int $status, string $startTime, string $endTime): void
    {
        try {
            app()->get(TodoItemService::class)->syncScheduleInstanceForUser(
                $uid,
                $id,
                (string) ($info['title'] ?? ''),
                $startTime,
                $endTime,
                $status,
                (int) ($info['all_day'] ?? 0)
            );
        } catch (\Throwable) {
            // 待办同步失败不能影响日程状态主流程.
        }
    }

    /**
     * 获取日程信息.
     * @param mixed $where
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function scheduleInfo(int $id, int $userId, array $field = ['*'], $where = []): array
    {
        if (! $this->userDao->exists(['schedule_id' => $id, 'uid' => $userId]) && ! $this->dao->exists(['id' => $id, 'uid' => $userId])) {
            throw $this->exception('未找到相关记录');
        }
        $info = Cache::tags($this->scheduleCacheTags($userId))->remember(
            md5('schedule_' . $id . json_encode($field) . json_encode($where)),
            $this->cacheTtl,
            function () use ($id, $field, $where, $userId) {
                $info = $this->dao->get($id, $field, [
                    'master' => fn ($query) => $query->select(['admin.id', 'name', 'avatar', 'phone']),
                    'user'   => fn ($query) => $query->select(['admin.id', 'name', 'avatar', 'phone']),
                    'task'   => fn ($query) => $query->where('schedule_task.start_time', $where['start_time'])->where('schedule_task.end_time', $where['end_time'])->select(['uid', 'status', 'pid', 'schedule_task.updated_at']),
                    'type'   => fn ($query) => $query->select(['id', 'name', 'color', 'info']),
                    'remind' => fn ($query) => $query->select(['sid', 'remind_day', 'remind_time']),
                ])?->toArray();
                if ($info) {
                    $info['linkName'] = match ($info['cid']) {
                        ScheduleEnum::TYPE_CLIENT_TRACK => app()->get(CustomerService::class)->value($info['link_id'], 'customer_name'),
                        ScheduleEnum::TYPE_CLIENT_RENEW, ScheduleEnum::TYPE_CLIENT_RETURN => app()->get(OrderService::class)->value($info['link_id'], 'contract_name'),
                        default => '',
                    };
                    $info['finish'] = $this->getScheduleStatus($userId, $info['uid'], $id, $where['start_time'], $where['end_time']);
                    if ($info['is_remind']) {
                        $info['remindInfo'] = $this->getRemindText($info['start_time'], Carbon::parse($info['remind']['remind_day'] . ' ' . $info['remind']['remind_time'], $this->timeZone)->toDateTimeString());
                    } else {
                        $info['remindInfo'] = [
                            'ident' => -1,
                            'text'  => '不提醒',
                        ];
                    }
                }
                return json_encode($info, JSON_UNESCAPED_UNICODE);
            }
        );
        return json_decode($info, true);
    }

    /**
     * 通过关联ID删除日程.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteFromLinkId(int $uid, int $linkId, int $type): bool
    {
        $scheduleId = $this->dao->column(['uid' => $uid, 'link_id' => $linkId, 'cid' => $type], 'id');
        if (! $scheduleId) {
            return true;
        }
        $res = $this->transaction(function () use ($scheduleId) {
            $res1 = $this->dao->delete(['id' => $scheduleId]);
            $res2 = $this->remindDao->delete(['sid' => $scheduleId]);
            $this->userDao->delete(['schedule_id' => $scheduleId]);
            $this->taskDao->delete(['pid' => $scheduleId]);
            return $res1 && $res2;
        });
        if ($res) {
            foreach ($scheduleId as $id) {
                $this->dispatchTodoSync((int) $id, 'delete');
            }
        }
        return $res;
    }

    /**
     * 获取汇报日程完成记录.
     * @throws BindingResolutionException
     */
    public function dailyCompleteRecord(string $uuid, int $type): array
    {
        $list = toArray($this->dao->getWithTask([
            'uid'        => uuid_to_uid($uuid),
            'status'     => 3,
            'start_time' => match ($type) {
                1       => Carbon::today($this->timeZone)->floorWeek()->toDateTimeString(),
                2       => Carbon::today($this->timeZone)->firstOfMonth()->toDateTimeString(),
                default => Carbon::today($this->timeZone)->toDateTimeString(),
            },
            'end_time' => match ($type) {
                1       => Carbon::today($this->timeZone)->endOfWeek()->endOfDay()->toDateTimeString(),
                2       => Carbon::today($this->timeZone)->endOfMonth()->endOfDay()->toDateTimeString(),
                default => Carbon::today($this->timeZone)->endOfDay()->toDateTimeString(),
            },
        ]));
        return $list ? array_column($list, 'title') : [];
    }

    /**
     * 按日/周/月汇总当前用户日程完成与待办（供 MCP / AI 汇报与自评）.
     *
     * 循环日程按主记录（id）聚合为 1 条，并用 occurrence_count / repeat_text 标明周期内展开次数；
     * 支持 focus/page/limit 分页：AI 列举未完成时须翻页取全后列表展示，禁止因截断回复「无法展示」.
     *
     * @param int         $userId 用户数据库 ID
     * @param string      $period day|week|month
     * @param string|null $date   锚点日期 Y-m-d，默认今天
     * @param string      $focus  all=两侧列表；pending|completed=只返回一侧；stats=仅统计
     * @param int         $page   页码，从 1 起
     * @param int         $limit  每页条数，范围 1–100，默认 10（首屏；更多靠会话表格翻页）
     *
     * @return array{
     *     period: string,
     *     start: string,
     *     end: string,
     *     completed: array<int, array<string, mixed>>,
     *     pending: array<int, array<string, mixed>>,
     *     stats: array{completed_count: int, pending_count: int, schedule_count: int, occurrence_total: int, recurring_count: int},
     *     paging: array<string, mixed>
     * }
     */
    public function workSummary(
        int $userId,
        string $period,
        ?string $date = null,
        string $focus = 'all',
        int $page = 1,
        int $limit = 10
    ): array {
        [$start, $end] = $this->resolveWorkSummaryRange($period, $date);
        // scheduleDateList 返回 [{date, list}]；scheduleList 为扁平列表，不能按日结构解析。
        // cid 需传用户可见类型，空数组会 whereIn([],) 导致整表查空。
        $typeIds = $this->typeList($userId, 'id') ?: [];
        $days    = $this->scheduleDateList($userId, 1, $start, $end, $typeIds);
        /** @var array<int, array<string, mixed>> $masters */
        $masters = [];
        $seen    = [];

        foreach (is_array($days) ? $days : [] as $day) {
            $list = $day['list'] ?? [];
            if (! is_array($list)) {
                continue;
            }
            foreach ($list as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id        = (int) ($item['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $startTime = (string) ($item['start_time'] ?? '');
                $dedupeKey = $id . '|' . $startTime;
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;

                $status  = (int) ($item['finish'] ?? -1);
                $content = (string) ($item['content'] ?? '');
                // 列表场景裁短正文，避免工具结果撑爆上下文；详情用 schedule_detail.
                if (mb_strlen($content) > 80) {
                    $content = mb_substr($content, 0, 80) . '…';
                }
                $repeatPeriod = (int) ($item['period'] ?? ScheduleEnum::REPEAT_NOT);
                $rate         = max(1, (int) ($item['rate'] ?? 1));
                $endTime      = (string) ($item['end_time'] ?? '');

                if (! isset($masters[$id])) {
                    $masters[$id] = [
                        'id'                          => $id,
                        'title'                       => (string) ($item['title'] ?? ''),
                        'content'                     => $content,
                        'start'                       => $startTime,
                        'end'                         => $endTime,
                        'cid'                         => (int) ($item['cid'] ?? 0),
                        'link_id'                     => (int) ($item['link_id'] ?? 0),
                        'period'                      => $repeatPeriod,
                        'rate'                        => $rate,
                        'days'                        => $item['days'] ?? null,
                        'is_recurring'                => $repeatPeriod > ScheduleEnum::REPEAT_NOT,
                        'occurrence_count'            => 0,
                        'completed_occurrence_count'  => 0,
                        'pending_occurrence_count'    => 0,
                        'status'                      => $status,
                    ];
                }

                $masters[$id]['occurrence_count']++;
                if ($status === 3) {
                    $masters[$id]['completed_occurrence_count']++;
                } else {
                    $masters[$id]['pending_occurrence_count']++;
                }
                // 保留周期内最早开始时间，便于列表排序与展示.
                if ($startTime !== '' && ($masters[$id]['start'] === '' || $startTime < (string) $masters[$id]['start'])) {
                    $masters[$id]['start'] = $startTime;
                    $masters[$id]['end']   = $endTime;
                }
            }
        }

        $completed = [];
        $pending   = [];
        $occurrenceTotal = 0;
        $recurringCount  = 0;
        foreach ($masters as $row) {
            $occurrenceTotal += (int) $row['occurrence_count'];
            if ($row['is_recurring']) {
                $recurringCount++;
            }
            // 有任一未完成实例 → 待办；全部完成 → 已完成.
            $row['status'] = ((int) $row['pending_occurrence_count'] > 0) ? 1 : 3;
            $row['repeat_text'] = $this->formatWorkSummaryRepeatText(
                (int) $row['period'],
                (int) $row['rate'],
                (int) $row['occurrence_count'],
                (int) $row['completed_occurrence_count'],
                (int) $row['pending_occurrence_count']
            );
            if ((int) $row['status'] === 3) {
                $completed[] = $row;
            } else {
                $pending[] = $row;
            }
        }

        $focus = strtolower(trim($focus));
        if (! in_array($focus, ['all', 'pending', 'completed', 'stats'], true)) {
            $focus = 'all';
        }
        $page  = max(1, $page);
        $limit = max(1, min(100, $limit));

        $completedTotal = count($completed);
        $pendingTotal   = count($pending);
        $offset         = ($page - 1) * $limit;

        $pendingPage   = [];
        $completedPage = [];
        if ($focus === 'stats') {
            // 仅统计，不带回明细.
        } elseif ($focus === 'pending') {
            $pendingPage = array_slice($pending, $offset, $limit);
        } elseif ($focus === 'completed') {
            $completedPage = array_slice($completed, $offset, $limit);
        } else {
            $pendingPage   = array_slice($pending, $offset, $limit);
            $completedPage = array_slice($completed, $offset, $limit);
        }

        $pendingHasMore   = $focus !== 'stats' && $focus !== 'completed' && ($offset + count($pendingPage)) < $pendingTotal;
        $completedHasMore = $focus !== 'stats' && $focus !== 'pending' && ($offset + count($completedPage)) < $completedTotal;
        $hasMore          = $pendingHasMore || $completedHasMore;

        return [
            'period'    => $period,
            'start'     => $start,
            'end'       => $end,
            'completed' => $completedPage,
            'pending'   => $pendingPage,
            'stats'     => [
                'completed_count'  => $completedTotal,
                'pending_count'    => $pendingTotal,
                'schedule_count'   => $completedTotal + $pendingTotal,
                'occurrence_total' => $occurrenceTotal,
                'recurring_count'  => $recurringCount,
            ],
            'paging' => [
                'focus'              => $focus,
                'page'               => $page,
                'limit'              => $limit,
                'pending_total'      => $pendingTotal,
                'completed_total'    => $completedTotal,
                'pending_has_more'   => $pendingHasMore,
                'completed_has_more' => $completedHasMore,
                'has_more'           => $hasMore,
                'next_page'          => $hasMore ? $page + 1 : null,
            ],
        ];
    }

    /**
     * 生成循环日程汇总说明文案（供 MCP / 模型朗读）.
     */
    protected function formatWorkSummaryRepeatText(
        int $period,
        int $rate,
        int $occurrenceCount,
        int $completedCount,
        int $pendingCount
    ): string {
        $rate = max(1, $rate);
        $rule = match ($period) {
            ScheduleEnum::REPEAT_DAY   => $rate === 1 ? '每天重复' : ('每' . $rate . '天重复'),
            ScheduleEnum::REPEAT_WEEK  => $rate === 1 ? '每周重复' : ('每' . $rate . '周重复'),
            ScheduleEnum::REPEAT_MONTH => $rate === 1 ? '每月重复' : ('每' . $rate . '月重复'),
            ScheduleEnum::REPEAT_YEAR  => $rate === 1 ? '每年重复' : ('每' . $rate . '年重复'),
            default                    => '不重复',
        };

        if ($period <= ScheduleEnum::REPEAT_NOT) {
            return $rule;
        }

        return sprintf(
            '%s，本周期共%d次（已完成%d/待处理%d）',
            $rule,
            max(1, $occurrenceCount),
            max(0, $completedCount),
            max(0, $pendingCount)
        );
    }

    /**
     * 解析工作汇总的起止时间.
     *
     * @return array{0: string, 1: string}
     */
    protected function resolveWorkSummaryRange(string $period, ?string $date = null): array
    {
        $anchor = $date
            ? Carbon::parse($date, $this->timeZone)
            : Carbon::now($this->timeZone);

        return match ($period) {
            'week' => [
                $anchor->copy()->startOfWeek()->toDateTimeString(),
                $anchor->copy()->endOfWeek()->endOfDay()->toDateTimeString(),
            ],
            'month' => [
                $anchor->copy()->startOfMonth()->toDateTimeString(),
                $anchor->copy()->endOfMonth()->endOfDay()->toDateTimeString(),
            ],
            default => [
                $anchor->copy()->startOfDay()->toDateTimeString(),
                $anchor->copy()->endOfDay()->toDateTimeString(),
            ],
        };
    }

    /**
     * 根据关联业务删除日程.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function delScheduleByLinkId(int $linkId, array|int $cid): void
    {
        $this->dao->select(['link_id' => $linkId, 'cid' => $cid], ['id', 'uid', 'cid', 'link_id'])->each(function ($item) use ($linkId) {
            $this->deleteFromLinkId((int) $item->uid, $linkId, (int) $item->cid);
        });
    }

    /**
     * 用户维度日程缓存标签.
     */
    protected function scheduleUserCacheTag(int $userId): string
    {
        return 'schedule_u_' . $userId;
    }

    /**
     * 日程缓存标签：全局 tag + 可选用户 tag，便于按用户失效.
     *
     * @return array<int, string>
     */
    protected function scheduleCacheTags(?int $userId = null): array
    {
        $tags = [CacheEnum::TAG_SCHEDULE];
        if ($userId) {
            $tags[] = $this->scheduleUserCacheTag($userId);
        }

        return $tags;
    }

    /**
     * 仅失效指定用户的日程缓存，避免整站冷启动.
     */
    protected function clearScheduleCache(array|int $userIds): void
    {
        $userIds = array_values(array_unique(array_filter(array_map(
            'intval',
            is_array($userIds) ? $userIds : [$userIds]
        ))));
        if ($userIds === []) {
            return;
        }
        foreach ($userIds as $uid) {
            Cache::tags([$this->scheduleUserCacheTag($uid)])->flush();
        }
    }

    /**
     * 全局失效日程缓存（类型颜色等跨用户变更时使用）.
     */
    protected function flushAllScheduleCache(): bool
    {
        return (bool) Cache::tags([CacheEnum::TAG_SCHEDULE])->flush();
    }

    /**
     * 批量写入日程参与人.
     *
     * @param array<int, int> $memberIds
     */
    protected function batchCreateScheduleUsers(int $scheduleId, array $memberIds): void
    {
        $rows = [];
        foreach ($memberIds as $memberId) {
            if ($memberId <= 0) {
                continue;
            }
            $rows[] = [
                'uid'         => $memberId,
                'schedule_id' => $scheduleId,
                'is_master'   => 0,
            ];
        }
        if ($rows) {
            ScheduleUser::query()->insert($rows);
        }
    }

    protected function deleteSystemSchedule(array $info)
    {
        if (! $this->remindDao->exists(['uniqued' => $info['remind_data']['uniqued']])) {
            switch ($info['cid']) {
                case ScheduleEnum::TYPE_CLIENT_TRACK:
                    $follow = app()->get(FollowUpService::class);
                    $follow->delScheduleAfter($info['remind_data']['uniqued']);
                    break;
                case ScheduleEnum::TYPE_CLIENT_RENEW:
                case ScheduleEnum::TYPE_CLIENT_RETURN:
                    $remind = app()->get(RemindService::class);
                    $remind->delScheduleAfter($info['remind_data']['uniqued']);
                    break;
                case ScheduleEnum::TYPE_PERSONAL:
                    Task::deliver(new StatusChangeTask(ScheduleEnum::SCHEDULE_FOLLOW_NOTICE, CommonEnum::STATUS_DELETE, $info['remind_data']['entid'], $info['id']));
                    break;
            }
        }
    }
}
