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

namespace App\Http\Service\Todo;

use App\Constants\ApproveEnum;
use App\Constants\AssessEnum;
use App\Constants\ProgramEnum\TaskStatusEnum;
use App\Constants\TodoEnum;
use App\Http\Dao\Approve\ApproveApplyDao;
use App\Http\Dao\Company\CompanyUserAssessDao;
use App\Http\Dao\Customer\ContractDao;
use App\Http\Dao\Customer\CustomerDao;
use App\Http\Dao\Customer\InvoiceDao;
use App\Http\Dao\News\NewsDao;
use App\Http\Dao\Program\ProgramTaskDao;
use App\Http\Dao\Schedule\ScheduleDao;
use App\Http\Dao\Schedule\ScheduleUserDao;
use App\Http\Dao\Todo\TodoItemDao;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Todo\TodoItem;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveUserService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\News\NewsVisitService;
use App\Http\Service\Program\ProgramTaskMemberService;
use App\Http\Service\Schedule\ScheduleService;
use App\Http\Service\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TodoItemService extends BaseService
{
    public function __construct(
        protected TodoItemDao $todoItemDao,
        protected CompanyUserAssessDao $assessDao,
        protected CustomerDao $customerDao,
        protected ContractDao $contractDao,
        protected InvoiceDao $invoiceDao,
        protected ProgramTaskDao $programTaskDao,
        protected NewsDao $newsDao,
        protected ScheduleDao $scheduleDao,
        protected ScheduleUserDao $scheduleUserDao,
        protected ApproveApplyDao $applyDao,
    ) {}

    /**
     * 待办列表.
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->todoItemDao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->todoItemDao->countList($where);

        foreach ($list as &$item) {
            $item['type_label'] = TodoEnum::TYPE_LABELS[$item['type']] ?? '';
            if (($item['type'] ?? '') === TodoEnum::TYPE_SCHEDULE && ! empty($item['extra']['schedule_id'])) {
                $item['todo_source_id'] = $item['source_id'];
                $item['source_id']      = (int) $item['extra']['schedule_id'];
            }
        }
        unset($item);

        $this->attachScheduleMembers($list);

        return ['list' => $list, 'count' => $count];
    }

    /**
     * 为日程类待办补充参与人信息（写入 extra.members）.
     *
     * 参与人仅存在于日程详情，列表读取时按 schedule_id 批量补充，避免冗余落库导致的数据不一致。
     *
     * @param array<int, mixed> $list 待办列表（引用传入，就地补充）
     */
    protected function attachScheduleMembers(array &$list): void
    {
        $scheduleIds = [];
        foreach ($list as $item) {
            if (($item['type'] ?? '') === TodoEnum::TYPE_SCHEDULE && ! empty($item['extra']['schedule_id'])) {
                $scheduleIds[] = (int) $item['extra']['schedule_id'];
            }
        }
        if (! $scheduleIds) {
            return;
        }

        $relations = $this->scheduleUserDao->getModel(false)
            ->whereIn('schedule_id', array_values(array_unique($scheduleIds)))
            ->where('is_master', 0)
            ->orderBy('id')
            ->get(['schedule_id', 'uid'])
            ->toArray();
        if (! $relations) {
            return;
        }

        $nameMap = Admin::query()
            ->whereIn('id', array_values(array_unique(array_column($relations, 'uid'))))
            ->pluck('name', 'id')
            ->all();

        $memberMap = [];
        foreach ($relations as $relation) {
            $uid = (int) $relation['uid'];
            if (! isset($nameMap[$uid])) {
                continue;
            }
            $memberMap[(int) $relation['schedule_id']][] = [
                'id'   => $uid,
                'name' => (string) $nameMap[$uid],
            ];
        }

        foreach ($list as &$item) {
            if (($item['type'] ?? '') !== TodoEnum::TYPE_SCHEDULE || empty($item['extra']['schedule_id'])) {
                continue;
            }
            $extra            = $item['extra'];
            $extra['members'] = $memberMap[(int) $item['extra']['schedule_id']] ?? [];
            $item['extra']    = $extra;
        }
        unset($item);
    }

    /**
     * 获取待办概览（各类型数量统计）.
     * @return array<string, array{count: int, label: string}>
     */
    public function getOverview(int $userId): array
    {
        $result = [];
        foreach (TodoEnum::ALL_TYPES as $type) {
            $result[$type] = [
                'count' => $this->todoItemDao->count(['user_id' => $userId, 'type' => $type, 'status' => TodoItem::STATUS_PENDING]),
                'label' => TodoEnum::TYPE_LABELS[$type] ?? '',
            ];
        }
        return $result;
    }

    /**
     * 获取指定日期的未完成待办数量.
     */
    public function countPendingByDate(int $userId): int
    {
        return $this->todoItemDao->count([
            'user_id' => $userId,
            'status'  => TodoItem::STATUS_PENDING,
        ]);
    }

    /**
     * 同步某用户全部类型的待办.
     */
    public function syncAllForUser(int $userId): void
    {
        foreach (TodoEnum::ALL_TYPES as $type) {
            try {
                $this->syncForUser($userId, $type);
            } catch (\Throwable $e) {
                Log::error("TodoItem sync failed: userId={$userId}, type={$type}", [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);
            }
        }
    }

    /**
     * 同步指定类型集合的全部用户待办.
     *
     * @param array<int, string> $types
     */
    public function syncByTypesForAllUsers(array $types): void
    {
        Admin::where('status', 1)
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($admins) use ($types) {
                foreach ($admins as $admin) {
                    try {
                        $this->syncByTypesForUser((int) $admin->id, $types);
                    } catch (\Throwable $e) {
                        Log::error('TodoItem syncByTypesForAllUsers failed', [
                            'userId'  => (int) $admin->id,
                            'types'   => $types,
                            'message' => $e->getMessage(),
                            'file'    => $e->getFile(),
                            'line'    => $e->getLine(),
                        ]);
                    }
                }
            });
    }

    /**
     * 同步某用户指定类型集合的待办.
     *
     * @param array<int, string> $types
     */
    public function syncByTypesForUser(int $userId, array $types): void
    {
        foreach (array_values(array_unique($types)) as $type) {
            try {
                $this->syncForUser($userId, $type);
            } catch (\Throwable $e) {
                Log::error("TodoItem sync failed: userId={$userId}, type={$type}", [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);
            }
        }
    }

    /**
     * 同步某用户某类型的待办到 todo_items.
     */
    public function syncForUser(int $userId, string $type): void
    {
        if ($type === TodoEnum::TYPE_SCHEDULE) {
            $maxRetries = 3;
            $retryCount = 0;

            while (true) {
                try {
                    $this->syncScheduleForUser($userId);
                    return;
                } catch (\Throwable $e) {
                    if ($this->isDeadlockException($e) && $retryCount < $maxRetries) {
                        ++$retryCount;
                        $delay = (int) (100 * pow(2, $retryCount - 1));
                        Log::warning("TodoItem schedule sync deadlock detected, retrying ({$retryCount}/{$maxRetries}) after {$delay}ms: userId={$userId}");
                        usleep($delay * 1000);
                        continue;
                    }
                    throw $e;
                }
            }
        }

        $maxRetries = 3;
        $retryCount = 0;

        while (true) {
            try {
                $this->doSyncForUser($userId, $type);
                return;
            } catch (\Throwable $e) {
                if ($this->isDeadlockException($e) && $retryCount < $maxRetries) {
                    ++$retryCount;
                    $delay = (int) (100 * pow(2, $retryCount - 1)); // 指数退避: 100, 200, 400ms
                    Log::warning("TodoItem sync deadlock detected, retrying ({$retryCount}/{$maxRetries}) after {$delay}ms: userId={$userId}, type={$type}");
                    usleep($delay * 1000);
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * 标记某条待办完成.
     */
    public function markDone(int $userId, string $type, int $sourceId): void
    {
        $this->todoItemDao->markDone($userId, $type, $sourceId);
    }

    /**
     * 根据业务数据变更同步待办状态.
     * 由模型观察者触发，仅需传入类型和资源ID，内部查询应同步的用户范围.
     *
     * @param string $type 待办类型
     * @param int $sourceId 业务记录ID
     * @param string $action 动作类型 (create/update/delete)
     * @param array<int, int> $preloadedUserIds 预加载的用户ID列表（删除时模型已不存在）
     */
    public function syncBySourceId(string $type, int $sourceId, string $action = 'update', array $preloadedUserIds = []): void
    {
        match ($action) {
            'create' => $this->createBySourceId($type, $sourceId, $preloadedUserIds),
            'delete' => $this->deleteBySourceId($type, $sourceId, $preloadedUserIds),
            default  => $this->updateBySourceId($type, $sourceId, $preloadedUserIds),
        };
    }

    /**
     * 业务数据新增时同步待办.
     * 企业动态未传指定用户时，按企业正式员工分块创建未读待办.
     *
     * @param array<int, int> $preloadedUserIds
     */
    public function createBySourceId(string $type, int $sourceId, array $preloadedUserIds = []): void
    {
        if ($type === TodoEnum::TYPE_NOTICE && ! $preloadedUserIds) {
            $this->syncNoticeSource($sourceId);
            return;
        }

        $this->syncSingleSourceForUsers($type, $sourceId, $preloadedUserIds);
    }

    /**
     * 业务数据变更时刷新待办.
     * 用于负责人、协作者、审批节点、阅读状态等变化后的精准同步.
     *
     * @param array<int, int> $preloadedUserIds
     */
    public function updateBySourceId(string $type, int $sourceId, array $preloadedUserIds = []): void
    {
        if ($type === TodoEnum::TYPE_NOTICE && ! $preloadedUserIds) {
            $this->syncNoticeSource($sourceId);
            return;
        }

        $this->syncSingleSourceForUsers($type, $sourceId, $preloadedUserIds);
    }

    /**
     * 业务数据删除时硬删除关联待办.
     * 删除源数据后待办不再有业务入口，不能只标记完成.
     *
     * @param array<int, int> $preloadedUserIds
     */
    public function deleteBySourceId(string $type, int $sourceId, array $preloadedUserIds = []): void
    {
        if ($type === TodoEnum::TYPE_SCHEDULE) {
            $this->todoItemDao->deleteScheduleBySource($sourceId);
            return;
        }

        $this->todoItemDao->deleteBySource($type, $sourceId);
    }

    /**
     * 业务数据完成时标记关联待办完成.
     *
     * @param array<int, int> $preloadedUserIds
     */
    public function completeBySourceId(string $type, int $sourceId, array $preloadedUserIds = []): void
    {
        try {
            if ($type !== TodoEnum::TYPE_SCHEDULE) {
                $this->todoItemDao->markDoneBySource($type, $sourceId);
            }
        } catch (\Throwable $e) {
            Log::error('TodoItem completeBySourceId failed', [
                'type'     => $type,
                'sourceId' => $sourceId,
                'message'  => $e->getMessage(),
            ]);
        }

        $userIds = $this->resolveAffectedUserIds($type, $sourceId, $preloadedUserIds);
        foreach ($userIds as $userId) {
            try {
                $this->markSourceDoneForUser((int) $userId, $type, $sourceId);
            } catch (\Throwable $e) {
                Log::error('TodoItem completeBySourceId failed', [
                    'userId'   => $userId,
                    'type'     => $type,
                    'sourceId' => $sourceId,
                    'message'  => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 只同步当前业务源记录，不再重扫该用户该类型的全部待办.
     *
     * @param array<int, int> $preloadedUserIds
     */
    protected function syncSingleSourceForUsers(string $type, int $sourceId, array $preloadedUserIds = []): void
    {
        $userIds = $this->resolveAffectedUserIds($type, $sourceId, $preloadedUserIds);
        foreach ($userIds as $userId) {
            try {
                $this->syncSingleSourceForUser((int) $userId, $type, $sourceId);
            } catch (\Throwable $e) {
                Log::error('TodoItem syncSingleSourceForUser failed', [
                    'userId'   => $userId,
                    'type'     => $type,
                    'sourceId' => $sourceId,
                    'message'  => $e->getMessage(),
                    'file'     => $e->getFile(),
                    'line'     => $e->getLine(),
                ]);
            }
        }
    }

    /**
     * 合并当前业务源可推导的用户和事件预加载用户.
     *
     * @param array<int, int> $preloadedUserIds
     * @return array<int, int>
     */
    protected function resolveAffectedUserIds(string $type, int $sourceId, array $preloadedUserIds = []): array
    {
        return array_values(array_unique(array_filter(array_map('intval', array_merge(
            $this->getUserIdsBySource($type, $sourceId),
            $preloadedUserIds
        )))));
    }

    /**
     * 只同步指定用户的单条业务源待办.
     *
     * 日程：按实例真实完成态同步待办状态——未完成 upsert 为待处理，已完成才 markDone；
     * 禁止先全量 markScheduleDone，否则编辑未完成日程也会把待办标成已完成.
     */
    protected function syncSingleSourceForUser(int $userId, string $type, int $sourceId): void
    {
        if ($type === TodoEnum::TYPE_SCHEDULE) {
            $this->syncScheduleSourceStatusForUser($userId, $sourceId);
            return;
        }

        $items = $this->getSingleSourceTodoItems($userId, $type, $sourceId);
        if (! $items) {
            $this->markSourceDoneForUser($userId, $type, $sourceId);
            return;
        }

        foreach ($items as $item) {
            $this->todoItemDao->upsertItem(
                $userId,
                $type,
                (int) $item['id'],
                (string) $item['title'],
                $item['extra'] ?? null,
                $item['created_at'] ?? null
            );
        }
    }

    /**
     * 按日程展开结果同步指定用户下该日程的待办.
     * 统一用 upsert 写入 title/extra/source_created_at/status 等字段；
     * 仅 finish 为 2/3 的实例 markDone；窗口外无展开结果时用日程主记录构造同步数据.
     */
    protected function syncScheduleSourceStatusForUser(int $userId, int $scheduleId): void
    {
        [$pendingItems, $completedSourceIds] = $this->fetchScheduleSourceSyncState($userId, $scheduleId);

        // 展开窗口外时无实例：若已有待处理待办，用日程主记录构造字段后走同一套 upsert.
        if ($pendingItems === [] && $completedSourceIds === []
            && $this->todoItemDao->hasPendingSchedule($userId, $scheduleId)) {
            $pendingItems = $this->buildScheduleTodoItemsFromRecord($scheduleId);
        }

        $keepSourceIds = $completedSourceIds;
        foreach ($pendingItems as $item) {
            $this->todoItemDao->upsertItem(
                $userId,
                TodoEnum::TYPE_SCHEDULE,
                (int) $item['id'],
                (string) $item['title'],
                $item['extra'] ?? null,
                $item['created_at'] ?? null
            );
            $keepSourceIds[] = (int) $item['id'];
        }

        foreach ($completedSourceIds as $completedSourceId) {
            $this->todoItemDao->markDone($userId, TodoEnum::TYPE_SCHEDULE, (int) $completedSourceId);
        }

        if ($pendingItems || $completedSourceIds) {
            $this->todoItemDao->deletePendingScheduleExceptSourceIds(
                $userId,
                $scheduleId,
                array_values(array_unique($keepSourceIds))
            );
        }
    }

    /**
     * 用日程主记录构造待办同步数据（不依赖日历展开窗口）.
     *
     * @return array<int, array{id: int, title: string, extra: array<string, mixed>, created_at: string}>
     */
    protected function buildScheduleTodoItemsFromRecord(int $scheduleId): array
    {
        $schedule = app(ScheduleService::class)->dao->get($scheduleId, ['id', 'title', 'start_time', 'end_time', 'all_day']);
        if (! $schedule) {
            return [];
        }

        $startTime = Carbon::parse($schedule->start_time, config('app.timezone'))->toDateTimeString();
        $endTime   = Carbon::parse($schedule->end_time, config('app.timezone'))->toDateTimeString();
        $sourceId  = $this->buildScheduleTodoSourceId($scheduleId, $startTime, $endTime);

        return [[
            'id'         => $sourceId,
            'title'      => (string) $schedule->title,
            'extra'      => [
                'schedule_id' => $scheduleId,
                'start_time'  => $startTime,
                'end_time'    => $endTime,
                'all_day'     => (int) $schedule->all_day,
            ],
            'created_at' => $startTime,
        ]];
    }

    /**
     * 精准同步某个日程实例的待办状态，避免状态更新时重算未来 30 天日程.
     */
    public function syncScheduleInstanceForUser(
        int $userId,
        int $scheduleId,
        string $title,
        string $startTime,
        string $endTime,
        int $scheduleStatus,
        int $allDay = 0
    ): void {
        $startTime = Carbon::parse($startTime, config('app.timezone'))->toDateTimeString();
        $endTime   = Carbon::parse($endTime, config('app.timezone'))->toDateTimeString();
        $sourceId = $this->buildScheduleTodoSourceId($scheduleId, $startTime, $endTime);

        if (in_array($scheduleStatus, [2, 3], true) || ! $this->isInScheduleTodoWindow($startTime, $endTime)) {
            $this->todoItemDao->markDone($userId, TodoEnum::TYPE_SCHEDULE, $sourceId);
            return;
        }

        $this->todoItemDao->upsertItem(
            $userId,
            TodoEnum::TYPE_SCHEDULE,
            $sourceId,
            $title,
            [
                'schedule_id' => $scheduleId,
                'start_time'  => $startTime,
                'end_time'    => $endTime,
                'all_day'     => $allDay,
            ],
            $startTime
        );
    }

    /**
     * 获取指定业务源在当前用户下应存在的待办数据.
     *
     * @return array<int, array{id: int, title: string, extra: null|array, created_at: null|string}>
     */
    protected function getSingleSourceTodoItems(int $userId, string $type, int $sourceId): array
    {
        if ($type === TodoEnum::TYPE_SCHEDULE) {
            return $this->fetchScheduleSourceItems($userId, $sourceId);
        }

        return array_values(array_filter(
            $this->fetchSourceItems($userId, $type),
            fn (array $item) => (int) $item['id'] === $sourceId
        ));
    }

    /**
     * 指定用户不再需要处理该业务源时，按类型完成或删除待办.
     */
    protected function markSourceDoneForUser(int $userId, string $type, int $sourceId): void
    {
        if ($type === TodoEnum::TYPE_SCHEDULE) {
            $this->todoItemDao->markScheduleDone($userId, $sourceId);
            return;
        }

        if ($type === TodoEnum::TYPE_TASK && ! $this->isTaskClosed($sourceId)) {
            $this->todoItemDao->deleteByUserSource($userId, $type, $sourceId);
            return;
        }

        $this->todoItemDao->markDone($userId, $type, $sourceId);
    }

    /**
     * 获取某个日程在未来待办窗口内展开出的待办实例.
     *
     * @return array<int, array{id: int, title: string, extra: array<string, mixed>, created_at: null|string}>
     */
    protected function fetchScheduleSourceItems(int $userId, int $scheduleId): array
    {
        [$pendingItems] = $this->fetchScheduleSourceSyncState($userId, $scheduleId);

        return $pendingItems;
    }

    /**
     * 展开单条日程，拆出未完成待办与已完成实例 source_id，供状态同步使用.
     *
     * @return array{0: array<int, array{id: int, title: string, extra: array<string, mixed>, created_at: null|string}>, 1: array<int, int>}
     */
    protected function fetchScheduleSourceSyncState(int $userId, int $scheduleId): array
    {
        $startDate       = now()->startOfDay();
        $endDate         = now()->addDays(30)->endOfDay();
        $scheduleService = app(ScheduleService::class);

        $dayItems = $scheduleService->scheduleDateListByScheduleId(
            $userId,
            $scheduleId,
            $startDate->toDateTimeString(),
            $endDate->toDateTimeString()
        );

        $pendingItems       = [];
        $completedSourceIds = [];

        foreach ($dayItems as $dayItem) {
            foreach (($dayItem['list'] ?? []) as $item) {
                if ((int) ($item['id'] ?? 0) !== $scheduleId) {
                    continue;
                }

                $startTime = (string) ($item['start_time'] ?? '');
                $endTime   = (string) ($item['end_time'] ?? '');
                if ($startTime === '' || $endTime === '') {
                    continue;
                }

                $todoSourceId = $this->buildScheduleTodoSourceId($scheduleId, $startTime, $endTime);
                if (in_array((int) ($item['finish'] ?? -1), [2, 3], true)) {
                    $completedSourceIds[] = $todoSourceId;
                    continue;
                }

                $pendingItems[$todoSourceId] = [
                    'id'         => $todoSourceId,
                    'title'      => (string) ($item['title'] ?? ''),
                    'extra'      => [
                        'schedule_id' => $scheduleId,
                        'start_time'  => $startTime,
                        'end_time'    => $endTime,
                        'all_day'     => $item['all_day'] ?? 0,
                    ],
                    'created_at' => $startTime,
                ];
            }
        }

        usort($pendingItems, fn ($a, $b) => strcmp((string) $a['extra']['start_time'], (string) $b['extra']['start_time']));

        return [array_values($pendingItems), array_values(array_unique($completedSourceIds))];
    }

    /**
     * 获取用户未读企业动态待办.
     *
     * @return array<int, array{id: int, title: string, extra: array<string, mixed>, created_at: null|string}>
     */
    public function fetchNoticeItems(int $userId): array
    {
        return $this->newsDao->select([
            'status'    => 1,
            'is_push'   => 1,
            'not_visit' => $userId,
        ], cursor: true)->map(fn ($item) => [
            'id'         => $item->id,
            'title'      => $item->title,
            'extra'      => ['push_time' => $item->push_time, 'is_read' => 0],
            'created_at' => $item->created_at?->toDateTimeString(),
        ])?->toArray();
    }

    /**
     * 按企业动态源数据分块创建未读待办.
     * 仅已发布且到达发布时间的动态会创建待办.
     */
    public function syncNoticeSource(int $sourceId): void
    {
        $news = $this->newsDao->get($sourceId, ['id', 'title', 'status', 'push_time', 'created_at']);
        if (! $news || (int) $news->status !== 1 || datetime_timestamp((string) $news->push_time) > time()) {
            return;
        }

        $readUserIds = app()->get(NewsVisitService::class)->dao->column(['notice_id' => $sourceId], 'user_id');

        Admin::where('status', 1)
            ->when(! empty($readUserIds), fn ($query) => $query->whereNotIn('id', $readUserIds))
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($admins) use ($news) {
                foreach ($admins as $admin) {
                    $this->todoItemDao->upsertItem(
                        (int) $admin->id,
                        TodoEnum::TYPE_NOTICE,
                        (int) $news->id,
                        (string) $news->title,
                        ['push_time' => $news->push_time, 'is_read' => 0],
                        $news->created_at?->toDateTimeString()
                    );
                }
            });
    }

    /**
     * 根据业务数据获取应同步的用户ID列表.
     *
     * @return array<int, int>
     */
    protected function getUserIdsBySource(string $type, int $sourceId): array
    {
        return match ($type) {
            TodoEnum::TYPE_SCHEDULE => $this->getScheduleUserIds($sourceId),
            TodoEnum::TYPE_ASSESS_SELF, TodoEnum::TYPE_ASSESS_CHECK, TodoEnum::TYPE_ASSESS_APPEAL => $this->getAssessUserIds($sourceId),
            TodoEnum::TYPE_CUSTOMER => $this->getCustomerUserIds($sourceId),
            TodoEnum::TYPE_CONTRACT => $this->getContractUserIds($sourceId),
            TodoEnum::TYPE_INVOICE  => $this->getInvoiceUserIds($sourceId),
            TodoEnum::TYPE_TASK     => $this->getTaskUserIds($sourceId),
            TodoEnum::TYPE_NOTICE          => $this->getNoticeUserIds($sourceId),
            TodoEnum::TYPE_APPROVE_SUBMIT  => $this->getApproveSubmitUserIds($sourceId),
            TodoEnum::TYPE_APPROVE_PENDING => $this->getApprovePendingUserIds($sourceId),
            default => [],
        };
    }

    /**
     * 获取日程参与人.
     *
     * @return array<int, int>
     */
    protected function getScheduleUserIds(int $sourceId): array
    {
        $schedule = app(ScheduleService::class)->dao->get($sourceId, ['id']);
        if (! $schedule) {
            return [];
        }
        // 日程同步给所有参与者
        return $schedule->schedule_user()->pluck('uid')->toArray();
    }

    /**
     * 获取绩效待办相关用户.
     *
     * @return array<int, int>
     */
    protected function getAssessUserIds(int $sourceId): array
    {
        $assess = $this->assessDao->get($sourceId, ['id', 'test_uid', 'check_uid', 'status']);
        if (! $assess) {
            return [];
        }
        $userIds = array_filter([
            $assess->test_uid,
            $assess->check_uid,
        ]);
        // 绩效申诉还需要上级
        if ($assess->status === AssessEnum::ASSESS_APPEAL) {
            $verifyUid = app()->get(FrameService::class)->getLevelSuper($assess->test_uid, 2);
            if ($verifyUid) {
                $userIds[] = $verifyUid;
            }
        }
        return array_values($userIds);
    }

    /**
     * 获取客户负责人.
     *
     * @return array<int, int>
     */
    protected function getCustomerUserIds(int $sourceId): array
    {
        $uid = $this->customerDao->value(['id' => $sourceId], 'uid');
        if (! $uid) {
            return [];
        }
        return array_filter([(int) $uid]);
    }

    /**
     * 获取合同负责人.
     *
     * @return array<int, int>
     */
    protected function getContractUserIds(int $sourceId): array
    {
        $uid = $this->contractDao->value(['id' => $sourceId], 'uid');
        if (! $uid) {
            return [];
        }
        return array_filter([(int) $uid]);
    }

    /**
     * 获取发票负责人.
     *
     * @return array<int, int>
     */
    protected function getInvoiceUserIds(int $sourceId): array
    {
        $uid = $this->invoiceDao->value(['id' => $sourceId], 'uid');
        if (! $uid) {
            return [];
        }
        return array_filter([(int) $uid]);
    }

    /**
     * 获取项目任务负责人和协作者.
     *
     * @return array<int, int>
     */
    protected function getTaskUserIds(int $sourceId): array
    {
        $task = $this->programTaskDao->get($sourceId, ['id', 'uid']);
        if (! $task) {
            return [];
        }
        return array_values(array_unique(array_filter(array_merge(
            [(int) $task->uid],
            $this->getTaskMemberUserIds($sourceId)
        ))));
    }

    /**
     * 获取项目任务协作者.
     *
     * @return array<int, int>
     */
    protected function getTaskMemberUserIds(int $sourceId): array
    {
        return app()->get(ProgramTaskMemberService::class)->column(['task_id' => $sourceId], 'uid') ?: [];
    }

    /**
     * 获取未读企业动态用户.
     *
     * @return array<int, int>
     */
    protected function getNoticeUserIds(int $sourceId): array
    {
        $news = $this->newsDao->get($sourceId, ['id', 'status', 'push_time']);
        if (! $news) {
            return [];
        }

        if ((int) $news->status !== 1 || datetime_timestamp((string) $news->push_time) > time()) {
            return [];
        }

        return $this->getUnreadNoticeUserIds($sourceId);
    }

    /**
     * 企业动态是面向企业有效用户的未读待办，创建时不能从阅读记录反推目标用户.
     *
     * @return array<int, int>
     */
    public function getUnreadNoticeUserIds(int $sourceId): array
    {
        $visitService = app()->get(NewsVisitService::class);
        $readUserIds  = $visitService->dao->column(['notice_id' => $sourceId], 'user_id');

        return Admin::where('status', 1)
            ->when(! empty($readUserIds), fn ($query) => $query->whereNotIn('id', $readUserIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * 获取审批提交人.
     *
     * @return array<int, int>
     */
    protected function getApproveSubmitUserIds(int $sourceId): array
    {
        $apply = app(ApproveApplyService::class)->dao->get($sourceId, ['id', 'user_id', 'status']);
        if (! $apply) {
            return [];
        }
        return array_values(array_unique(array_filter([(int) $apply->user_id])));
    }

    /**
     * 获取当前节点审批人.
     *
     * @return array<int, int>
     */
    protected function getApprovePendingUserIds(int $sourceId): array
    {
        $apply = app(ApproveApplyService::class)->dao->get($sourceId, ['id', 'approve_id', 'node_id', 'status']);
        if (! $apply || (int) $apply->status !== ApproveEnum::APPROVE_PENDING) {
            return [];
        }

        $approveUsers = app()->get(ApproveUserService::class)->dao->setDefaultSort(['sort' => 'asc'])->select([
            'apply_id'   => $sourceId,
            'approve_id' => (int) $apply->approve_id,
            'node_id'    => $apply->node_id,
            'types'      => 1,
            'status'     => 0,
        ], ['user_id', 'sort', 'process_info'])?->toArray() ?: [];
        if (! $approveUsers) {
            return [];
        }

        $processInfo = $approveUsers[0]['process_info'] ?? [];
        $settype     = (int) ($processInfo['settype'] ?? 0);
        $sendAll     = in_array($settype, [1, 4], true)
            && ($settype !== 4 || (int) ($processInfo['select_mode'] ?? 0) === 2)
            && in_array((int) ($processInfo['examine_mode'] ?? 0), [1, 2], true);

        if (! $sendAll) {
            $approveUsers = [(int) ($processInfo['director_order'] ?? 0) ? end($approveUsers) : reset($approveUsers)];
        }

        return array_values(array_unique(array_filter(array_map('intval', array_column($approveUsers, 'user_id')))));
    }

    /**
     * 判断是否为 MySQL 死锁异常.
     */
    protected function isDeadlockException(\Throwable $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'Deadlock found')
               || str_contains($message, '1213 Deadlock');
    }

    /**
     * 执行同步逻辑（内部方法，由 syncForUser 调用）.
     */
    protected function doSyncForUser(int $userId, string $type): void
    {
        $items = $this->fetchSourceItems($userId, $type);

        $activeSourceIds = [];
        foreach ($items as $item) {
            $this->todoItemDao->upsertItem(
                $userId,
                $type,
                $item['id'],
                $item['title'],
                $item['extra'] ?? null,
                $item['created_at'] ?? null
            );
            $activeSourceIds[] = $item['id'];
        }

        $this->todoItemDao->markDoneExceptSourceIds($userId, $type, $activeSourceIds);
    }

    /**
     * 日程待办按区间一次展开后按天分桶同步，避免 30 天内反复全量读缓存.
     */
    protected function syncScheduleForUser(int $userId): void
    {
        $startDate       = now()->startOfDay();
        $endDate         = now()->addDays(30)->startOfDay();
        $scheduleService = app(ScheduleService::class);
        $typeIds         = $scheduleService->typeList($userId, 'id');

        $rangeStart = $startDate->toDateTimeString();
        $rangeEnd   = $endDate->copy()->endOfDay()->toDateTimeString();
        $allDayItems = $scheduleService->scheduleDateList($userId, 1, $rangeStart, $rangeEnd, $typeIds);

        $byDate = [];
        foreach ($allDayItems as $dayItem) {
            $rawDate = trim((string) ($dayItem['date'] ?? ''));
            if ($rawDate === '') {
                continue;
            }
            $date = Carbon::parse($rawDate)->toDateString();
            $byDate[$date] = $dayItem;
        }

        $window = clone $startDate;
        while ($window->lessThanOrEqualTo($endDate)) {
            $date     = $window->toDateString();
            $dayStart = $window->copy()->startOfDay()->toDateTimeString();
            $dayEnd   = $window->copy()->endOfDay()->toDateTimeString();
            $dayItems = isset($byDate[$date])
                ? [$byDate[$date]]
                : [['date' => $date, 'list' => []]];

            $items           = $this->buildScheduleItemsForDay($dayItems);
            $activeSourceIds = [];
            foreach ($items as $item) {
                $this->todoItemDao->upsertItem(
                    $userId,
                    TodoEnum::TYPE_SCHEDULE,
                    $item['id'],
                    $item['title'],
                    $item['extra'] ?? null,
                    $item['created_at'] ?? null
                );
                $activeSourceIds[] = $item['id'];
            }

            $this->todoItemDao->markDoneExceptSourceIdsBySourceDate(
                $userId,
                TodoEnum::TYPE_SCHEDULE,
                $activeSourceIds,
                $dayStart,
                $dayEnd
            );

            unset($items, $activeSourceIds, $dayItems);
            $window->addDay();
        }

        $this->todoItemDao->markDoneOutsideSourceDateRange(
            $userId,
            TodoEnum::TYPE_SCHEDULE,
            $startDate->startOfDay()->toDateTimeString(),
            $endDate->endOfDay()->toDateTimeString()
        );
    }

    /**
     * 将某一天展开出来的日程记录规范化为待办写入结构.
     * @param array<int, array<string, mixed>> $dayItems
     * @return array<int, array{id: int, title: string, extra: array<string, mixed>, created_at: ?string}>
     */
    protected function buildScheduleItemsForDay(array $dayItems): array
    {
        $items = [];

        foreach ($dayItems as $dayItem) {
            foreach (($dayItem['list'] ?? []) as $item) {
                if (in_array((int) ($item['finish'] ?? -1), [2, 3], true)) {
                    continue;
                }

                $scheduleId = (int) ($item['id'] ?? 0);
                $startTime  = (string) ($item['start_time'] ?? '');
                $endTime    = (string) ($item['end_time'] ?? '');
                if (! $scheduleId || $startTime === '' || $endTime === '') {
                    continue;
                }

                $sourceId = $this->buildScheduleTodoSourceId($scheduleId, $startTime, $endTime);
                $items[$sourceId] = [
                    'id'         => $sourceId,
                    'title'      => (string) ($item['title'] ?? ''),
                    'extra'      => [
                        'schedule_id' => $scheduleId,
                        'start_time'  => $startTime,
                        'end_time'    => $endTime,
                        'all_day'     => $item['all_day'] ?? 0,
                    ],
                    'created_at' => $startTime,
                ];
            }
        }

        usort($items, fn ($a, $b) => strcmp((string) $a['extra']['start_time'], (string) $b['extra']['start_time']));
        return array_values($items);
    }

    protected function buildScheduleTodoSourceId(int $scheduleId, string $startTime, string $endTime): int
    {
        return (int) sprintf('%u', crc32($scheduleId . '|' . $startTime . '|' . $endTime));
    }

    protected function isInScheduleTodoWindow(string $startTime, string $endTime): bool
    {
        $start = Carbon::parse($startTime);
        $end   = Carbon::parse($endTime);

        return $end->greaterThanOrEqualTo(now()->startOfDay())
               && $start->lessThanOrEqualTo(now()->addDays(30)->endOfDay());
    }

    /**
     * 从源表获取待办数据并统一格式.
     * @return array<int, array{id: int, title: string, extra: null|array, created_at: null|string}>
     */
    protected function fetchSourceItems(int $userId, string $type): array
    {
        return match ($type) {
            TodoEnum::TYPE_SCHEDULE        => $this->fetchScheduleItems($userId),
            TodoEnum::TYPE_ASSESS_SELF     => $this->fetchAssessSelfItems($userId),
            TodoEnum::TYPE_ASSESS_CHECK    => $this->fetchAssessCheckItems($userId),
            TodoEnum::TYPE_ASSESS_APPEAL   => $this->fetchAssessAppealItems($userId),
            TodoEnum::TYPE_CUSTOMER        => $this->fetchCustomerItems($userId),
            TodoEnum::TYPE_CONTRACT        => $this->fetchContractItems($userId),
            TodoEnum::TYPE_INVOICE         => $this->fetchInvoiceItems($userId),
            TodoEnum::TYPE_TASK            => $this->fetchTaskItems($userId),
            TodoEnum::TYPE_NOTICE          => $this->fetchNoticeItems($userId),
            TodoEnum::TYPE_APPROVE_SUBMIT  => $this->fetchApproveSubmitItems($userId),
            TodoEnum::TYPE_APPROVE_PENDING => $this->fetchApprovePendingItems($userId),
            default                        => [],
        };
    }

    protected function fetchScheduleItems(int $userId): array
    {
        // 复用日程展开逻辑，按当前用户的 schedule_task 状态过滤已完成/已拒绝实例。
        $start           = now()->startOfDay()->toDateTimeString();
        $end             = now()->addDays(30)->endOfDay()->toDateTimeString();
        $scheduleService = app(ScheduleService::class);
        $typeIds         = $scheduleService->typeList($userId, 'id');
        $dayItems        = $scheduleService->scheduleDateList($userId, 1, $start, $end, $typeIds);

        $items = [];
        foreach ($dayItems as $dayItem) {
            foreach (($dayItem['list'] ?? []) as $item) {
                if (in_array((int) ($item['finish'] ?? -1), [2, 3], true)) {
                    continue;
                }

                $sourceId = (int) $item['id'];
                if (isset($items[$sourceId])
                    && datetime_timestamp((string) $items[$sourceId]['extra']['start_time']) <= datetime_timestamp((string) $item['start_time'])) {
                    continue;
                }

                $items[$sourceId] = [
                    'id'    => $sourceId,
                    'title' => (string) ($item['title'] ?? ''),
                    'extra' => [
                        'start_time' => $item['start_time'] ?? '',
                        'end_time'   => $item['end_time'] ?? '',
                        'all_day'    => $item['all_day'] ?? 0,
                    ],
                    'created_at' => null,
                ];
            }
        }

        if ($items) {
            $createdAtMap = $this->scheduleDao->getModel(false)
                ->whereIn('id', array_keys($items))
                ->pluck('created_at', 'id')
                ->all();
            foreach ($items as $sourceId => &$item) {
                $item['created_at'] = isset($createdAtMap[$sourceId])
                    ? (string) $createdAtMap[$sourceId]
                    : ($item['extra']['start_time'] ?: null);
            }
            unset($item);
        }

        usort($items, fn ($a, $b) => strcmp((string) $a['extra']['start_time'], (string) $b['extra']['start_time']));
        return array_slice(array_values($items), 0, 500);
    }

    protected function fetchAssessSelfItems(int $userId): array
    {
        return $this->assessDao->select([
            'test_uid' => $userId,
            'status'   => AssessEnum::ASSESS_SELF_APPRAISAL,
            'is_show'  => 1,
        ], ['id', 'name', 'period', 'end_time', 'created_at'], cursor: true)
            ->map(fn ($item) => [
                'id'         => $item->id,
                'title'      => $item->name,
                'extra'      => ['period' => $item->period, 'end_time' => $item->end_time],
                'created_at' => $item->created_at?->toDateTimeString(),
            ])->all();
    }

    protected function fetchAssessCheckItems(int $userId): array
    {
        return $this->assessDao->select([
            'check_uid' => $userId,
            'status'    => AssessEnum::ASSESS_EVALUATION,
            'is_show'   => 1,
        ], ['id', 'name', 'period', 'end_time', 'created_at'], cursor: true)
            ->map(fn ($item) => [
                'id'         => $item->id,
                'title'      => $item->name,
                'extra'      => ['period' => $item->period, 'end_time' => $item->end_time],
                'created_at' => $item->created_at?->toDateTimeString(),
            ])->all();
    }

    protected function fetchAssessAppealItems(int $userId): array
    {
        $frameService = app()->get(FrameService::class);
        $list = $this->assessDao->search([
            'status'  => AssessEnum::ASSESS_APPEAL,
            'is_show' => 1,
        ])
            ->select(['id', 'name', 'period', 'test_uid', 'end_time', 'created_at'])
            ->limit(500)
            ->get();

        return $list->filter(fn ($item) => (int) $frameService->getLevelSuper((int) $item->test_uid, 2) === $userId)
            ->values()
            ->map(fn ($item) => [
                'id'         => $item->id,
                'title'      => $item->name,
                'extra'      => ['period' => $item->period, 'end_time' => $item->end_time],
                'created_at' => $item->created_at?->toDateTimeString(),
            ])->all();
    }

    protected function fetchCustomerItems(int $userId): array
    {
        $urgentCustomerIds = $this->customerDao->getUrgentFollowUpIds(['uid' => $userId]);
        return $this->customerDao->select(['id' => $urgentCustomerIds], ['id', 'customer_name', 'uid', 'created_at'], cursor: true)
            ->map(fn ($item) => [
                'id'         => $item->id,
                'title'      => $item->customer_name,
                'extra'      => null,
                'created_at' => $item->created_at?->toDateTimeString(),
            ])->all();
    }

    protected function fetchContractItems(int $userId): array
    {
        return $this->contractDao->select(['uid' => $userId, 'status' => 2], ['id', 'doc_no', 'doc_name', 'created_at'], cursor: true)
            ->map(fn ($item) => [
                'id'         => $item->id,
                'title'      => $item->doc_name,
                'extra'      => ['doc_no' => $item->doc_no],
                'created_at' => $item->created_at?->toDateTimeString(),
            ])->all();
    }

    protected function fetchInvoiceItems(int $userId): array
    {
        return $this->invoiceDao->select(['uid' => $userId, 'status' => 0], ['id', 'title', 'amount', 'num', 'created_at'], cursor: true)
            ->map(fn ($item) => [
                'id'         => $item->id,
                'title'      => $item->title,
                'extra'      => ['amount' => $item->amount, 'num' => $item->num],
                'created_at' => $item->created_at?->toDateTimeString(),
            ])->all();
    }

    protected function fetchTaskItems(int $userId): array
    {
        return $this->programTaskDao->select([
            'status'    => [TaskStatusEnum::UNTREATED, TaskStatusEnum::IN_PROGRESS],
            'types'     => 2,
            'admin_uid' => $userId,
        ], ['id', 'name', 'program_id', 'status', 'priority', 'plan_end', 'created_at'], cursor: true)
            ->map(fn ($item) => [
                'id'         => $item->id,
                'title'      => $item->name,
                'extra'      => ['program_id' => $item->program_id, 'status' => $item->status, 'priority' => $item->priority, 'plan_end' => $item->plan_end],
                'created_at' => $item->created_at?->toDateTimeString(),
            ])->all();
    }

    /**
     * 判断项目任务是否已进入关闭类状态.
     */
    protected function isTaskClosed(int $sourceId): bool
    {
        return in_array((int) $this->programTaskDao->value(['id' => $sourceId], 'status'), [
            TaskStatusEnum::RESOLVED,
            TaskStatusEnum::ACCEPTED,
            TaskStatusEnum::REJECTED,
            TaskStatusEnum::CLOSED,
        ], true);
    }

    protected function fetchApproveSubmitItems(int $userId): array
    {
        return $this->applyDao->select(
            ['user_id' => $userId, 'status' => ApproveEnum::APPROVE_PENDING],
            ['id', 'approve_id', 'crud_id', 'user_id', 'status', 'created_at'],
            ['approve' => fn ($q) => $q->select(['id', 'name']), 'crudApprove' => fn ($q) => $q->select(['id', 'name']), 'card'],
            cursor: true
        )->map(function ($item) {
            $userName = $item->card?->name ?? '';
            $approveName = $item->approve_config?->name ?? '';
            $title = $approveName ? "{$userName}的{$approveName}审批" : $userName;
            return [
                'id'         => $item->id,
                'title'      => $title,
                'extra'      => ['approve_id' => $item->approve_id, 'crud_id' => $item->crud_id],
                'created_at' => $item->created_at?->toDateTimeString(),
            ];
        })->all();
    }

    protected function fetchApprovePendingItems(int $userId): array
    {
        $where = app(ApproveApplyService::class)->getWhere(['types' => 1, 'name' => '', 'verify_status' => 1], $userId);
        return $this->applyDao->select(
            $where,
            ['id', 'approve_id', 'crud_id', 'user_id', 'status', 'created_at'],
            ['approve' => fn ($q) => $q->select(['id', 'name']), 'crudApprove' => fn ($q) => $q->select(['id', 'name']), 'card'],
            cursor: true
        )->map(function ($item) {
            $userName = $item->card?->name ?? '';
            $approveName = $item->approve_config?->name ?? '';
            $title = $approveName ? "{$userName}的{$approveName}审批" : $userName;
            return [
                'id'         => $item->id,
                'title'      => $title,
                'extra'      => ['approve_id' => $item->approve_id, 'crud_id' => $item->crud_id],
                'created_at' => $item->created_at?->toDateTimeString(),
            ];
        })->all();
    }
}
