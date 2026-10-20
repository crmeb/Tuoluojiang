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

namespace App\Http\Service\Report;

use App\Constants\CommonEnum;
use App\Constants\DailyEnum;
use App\Constants\ModuleEnum;
use App\Constants\NoticeEnum;
use App\Constants\RuleEnum;
use App\Events\SystemMessageEvent;
use App\Http\Contract\Schedule\ScheduleInterface;
use App\Http\Dao\Report\MemberDao;
use App\Http\Dao\Report\UserDailyDao;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Attendance\AttendanceArrangeService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\System\RolesService;
use App\Http\Service\User\UserRemindLogService;
use App\Task\message\DailyReportRemind;
use App\Task\message\StatusChangeTask;
use App\Task\report\DailyCreateTask;
use Carbon\CarbonPeriod;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Tuoluojiang\Open\utils\MessageType;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 汇报.
 */
class ReportService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    protected FormService $build;

    /**
     * @var Application|mixed|Repository
     */
    private mixed $timeZone;

    private MemberDao $memberDao;

    /**
     * ReportService constructor.
     */
    public function __construct(UserDailyDao $dao, FormService $build, MemberDao $memberDao)
    {
        $this->dao       = $dao;
        $this->build     = $build;
        $this->memberDao = $memberDao;
        $this->timeZone  = config('app.timezone');
    }

    /**
     * 查询.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'created_at', array $with = []): array
    {
        if (isset($where['viewer']) && $where['viewer'] == 'hr') {
            unset($where['user_id']);
        }
        if (isset($where['finish_like'])) {
            if (! $where['user_ids'] = app()->get(AdminService::class)->column(['name_like' => $where['finish_like']], 'id')) {
                return $this->listData([]);
            }
        }
        return $this->defaultList($where, $field, $sort, $with);
    }

    /**
     * 按天分组查询.
     *
     * @param array|string[] $field
     */
    public function getGroupList(array $where, array $field = ['*'], int $uid = 0): array
    {
        if ($where['viewer'] != 'hr') {
            if ($where['dep_report']) {
                if ($where['user_id']) {
                    if ((int) $where['user_id'] === $uid) {
                        $where['user_ids'] = [-1];
                        unset($where['user_id']);
                    }
                } else {
                    $where['user_ids'] = array_values(array_diff(
                        app()->get(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::REPORT),
                        [$uid]
                    )) ?: [-1];
                }
            } else {
                $where['user_id'] = $uid;
            }
        }
        unset($where['viewer'], $where['dep_report']);
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getGroupList($where, $field, $page, $limit, 'daily_id', [
            'user' => fn ($q) => $q->select(['id', 'uid', 'name', 'avatar']),
        ]);
        $count = $this->dao->getGroupCount($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取创建汇报表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    /**
     * 编辑汇报获取表单.
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $info = $this->dao->get($id, ['*'], [
            'replys' => function ($query) {
                $query->with([
                    'card' => fn ($query) => $query->select(['name', 'id', 'uid', 'avatar']),
                ]);
            },
            'attachs' => function ($query) {
                $query->select(['id', 'att_dir as src', 'relation_id', 'name', 'att_size as size']);
            },
            'card',
            'members',
        ])?->toArray();
        if (! $info) {
            throw $this->exception('汇报信息不存在');
        }
        $create = Carbon::make($info['created_at']);
        switch ($info['types']) {
            case 2:
                $info['start_time'] = $create->startOfMonth()->toDateTimeString();
                $info['end_time']   = $create->endOfMonth()->toDateTimeString();
                break;
            case 1:
                $info['start_time'] = $create->startOfWeek()->toDateTimeString();
                $info['end_time']   = $create->endOfWeek()->toDateTimeString();
                break;
            default:
                $info['start_time'] = $create->startOfDay()->toDateTimeString();
                $info['end_time']   = $create->endOfDay()->toDateTimeString();
        }
        $info['replys'] = $info['replys'] ?? [];
        $pid            = array_filter(array_unique(array_column($info['replys'], 'pid')));
        $replyList      = app()->get(ReportReplyService::class)->getList(['ids' => $pid], ['pid', 'uid', 'id'], 'id', ['card']);
        $dataList       = [];
        foreach ($replyList['list'] as $item) {
            $dataList[$item['id']] = $item;
        }
        foreach ($info['replys'] as &$reply) {
            $reply['paent_user'] = $dataList[$reply['pid']] ?? null;
        }
        foreach ($info['attachs'] as &$attach) {
            $attach['src'] = link_file($attach['src']);
        }

        // report member
        $members = [];
        foreach ($info['members'] as $member) {
            $members[] = ['card' => $member];
        }
        $info['members'] = $members;
        return $info;
    }

    /**
     * 获取某一月的计划列表.
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getMonthDailyList(string $uid, int $entid, string $time)
    {
        $data = $this->dao->search(['uid' => $uid, 'entid' => $entid])
            ->where('created_at', 'like', $time . '%')
            ->groupBy('day')->select(['finish', 'plan', DB::raw('day(created_at) as day')])->get()->toArray();

        return array_combine(array_column($data, 'day'), $data);
    }

    /**
     * 获取今日计划.
     *
     * @return null|BaseModel|Model|object
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getDayDaily(string $uid)
    {
        return $this->dao->search(['time' => 'today', 'uid' => $uid])->select(['finish', 'plan'])->first();
    }

    /**
     * 创建汇报.
     *
     * @return BaseModel|mixed|Model
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if ($data['types'] != 3 && ! $data['plan']) {
            throw $this->exception('请填写工作计划');
        }
        if (! $data['entid']) {
            throw $this->exception('请先加入企业再尝试填写汇报');
        }
        return $this->transaction(function () use ($data) {
            $attachIds = $data['attach_ids'];
            $members   = array_values(array_filter(array_map('intval', (array) ($data['members'] ?? []))));
            unset($data['attach_ids'], $data['members']);
            $data['user_id']  = uuid_to_uid($data['uid']);
            // AI / MCP 等入口常不传 members；与 PC/移动端「默认汇报人」一致，回落直属上级
            if ($members === []) {
                $members = $this->defaultMemberIds((int) $data['user_id']);
            }
            $data['plan']     = is_array($data['plan']) ? array_filter($data['plan']) : explode("\n", str_replace(["\r\n", "\r"], "\n", $data['plan']));
            $data['finish']   = is_array($data['finish']) ? array_filter($data['finish']) : explode("\n", str_replace(["\r\n", "\r"], "\n", $data['finish']));
            $res              = $this->dao->create($data);
            $data['daily_id'] = $res->daily_id;

            $this->handleMember($members, $res->daily_id);
            app()->get(AttachService::class)->saveRelation($attachIds, $data['uid'], $res->daily_id, AttachService::RELATION_TYPE_DAILY);
            // 事务提交后再投递，避免侧效失败回滚主数据，且非 Swoole 环境可本地兜底
            $this->scheduleReportTask(new DailyReportRemind($data['entid'], $data['uid'], $res->daily_id, $data, true));
            $this->scheduleReportTask(new DailyCreateTask($data['entid'], $data['uid'], $res->daily_id, $data));

            return $res;
        });
    }

    /**
     * 修改汇报.
     *
     * @param int $id
     *
     * @return bool|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        if ($data['types'] != 3 && ! $data['plan']) {
            throw $this->exception('请填写工作计划');
        }

        $daily = $this->dao->get($id);
        if (! $daily) {
            throw $this->exception('汇报不存在');
        }

        return $this->transaction(function () use ($id, $daily, $data) {
            $timezone  = config('app.timezone');
            $createdAt = datetime_timestamp((string) $daily->created_at);
//            switch ($daily->types) {
//                case 0:
//                    if ($createdAt < now()->startOfDay()->timezone($timezone)->timestamp || $createdAt > now()->endOfDay()->timezone($timezone)->timestamp) {
//                        throw $this->exception('您只能修改当天的汇报');
//                    }
//                    break;
//                case 1:
//                    if ($createdAt > now()->endOfWeek()->timezone($timezone)->timestamp || $createdAt < now()->startOfWeek()->timezone($timezone)->timestamp) {
//                        throw $this->exception('您只能修改本周的汇报');
//                    }
//                    break;
//                case 2:
//                    if ($createdAt > now()->endOfMonth()->timezone($timezone)->timestamp || $createdAt < now()->startOfMonth()->timezone($timezone)->timestamp) {
//                        throw $this->exception('您只能修改本月的汇报');
//                    }
//                    break;
//            }

            $daily->finish    = array_filter($data['finish']);
            $daily->plan      = array_filter($data['plan']);
            $daily->mark      = $data['mark'];
            $res              = $daily->save();
            $data['daily_id'] = $daily->daily_id;
            $this->handleMember($data['members'], $daily->daily_id);
            app()->get(AttachService::class)->saveRelation($data['attach_ids'], $daily->uid, $daily->daily_id, AttachService::RELATION_TYPE_DAILY);
            $this->scheduleReportTask(new DailyReportRemind($data['entid'], $data['uid'], $id, $data));
            $this->scheduleReportTask(new DailyCreateTask($data['entid'], $data['uid'], $id, $data));
            return $res;
        });
    }

    /**
     * @param mixed $id
     * @return bool
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $info = toArray($this->dao->get($id));
        if (! $info) {
            throw $this->exception('未找到删除记录');
        }
        $tz   = config('app.timezone');
        $time = datetime_timestamp($info['created_at']);
        if ($time < now()->startOfDay()->timezone($tz)->timestamp || $time > now()->endOfDay()->timezone($tz)->timestamp) {
            throw $this->exception('您只能删除当天提交的汇报');
        }

        if ($this->dao->delete($id, $key)) {
            $this->scheduleReportTask(new StatusChangeTask(DailyEnum::LINK_NOTICE, DailyEnum::DAILY_DELETE, $info['entid'], $id));
            return true;
        }
        return false;
    }

    /**
     * 获取下级日报人员.
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getDailyUser(int $uid, string $viewer = 'user')
    {
        $ids = [];
        if ($viewer != 'user') {
            if (! in_array($uid, app()->get(RolesService::class)->getRuleUserIds(1, RuleEnum::PERSONNEL_TYPE))) {
                $ids = [];
            }
        } else {
            $ids = app()->get(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::REPORT);
        }
        return app()->get(AdminService::class)->select(['id' => $ids])?->toArray();
    }

    /**
     * 日报定时任务
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function timer(int $page, int $limit, int $entId = 1, int $type = 0)
    {
        $user = app()->get(AdminInfoService::class)->getEntUserIdsCache($entId, $page, $limit);
        if (! $user) {
            return;
        }
        $where['time'] = match ($type) {
            1       => 'week',
            2       => 'month',
            default => 'today',
        };
        $where     = ['entid' => $entId, 'user_ids' => array_column($user, 'id'), 'types' => $type];
        $unUserIds = $this->dao->column($where, 'user_id');
        // 比较出没有提交的用户
        $diffUserId = array_diff(array_column($user, 'id'), $unUserIds);
        if (! $diffUserId) {
            return;
        }
        $remindService = app(UserRemindLogService::class);
        $diffUserId    = collect($remindService->column([
            'entid'       => $entId,
            'year'        => now()->year,
            'month'       => now()->month,
            'day'         => now()->day,
            'remind_type' => MessageType::DAILY_REMIND_TYPE,
            'user_id'     => $diffUserId,
        ], 'user_id') ?? [])->unique()->diff($diffUserId)->all();
        event(new SystemMessageEvent(type: NoticeEnum::DAILY_REMIND_TYPE, receiverIds: $diffUserId));
        $remind = [];
        foreach ($diffUserId as $uid) {
            $remind[] = [
                'remind_type' => MessageType::DAILY_REMIND_TYPE,
                'user_id'     => $uid,
                'entid'       => $entId,
                'year'        => now()->year,
                'day'         => now()->day,
                'month'       => now()->month,
                'week'        => now()->week,
                'quarter'     => now()->quarter,
                'created_at'  => date('Y-m-d H:i:s'),
            ];
        }
        if ($remind) {
            $remindService->insert($remind);
        }
    }

    /**
     * 获取用户汇报统计
     * @param string $types
     * @param mixed $uuid
     * @param mixed $entId
     * @return array
     * @throws BindingResolutionException
     */
    public function getUserDailyCount($uuid, $entId, $types = 'day')
    {
        $frameService = app()->get(FrameService::class);
        $uid          = $frameService->uuidToUid($uuid, $entId);
        switch ($types) {
            case 'day':
                $data['count']   = now()->daysInMonth;
                $data['finish']  = count($this->dao->getOnceList(['user_id' => $uid, 'time' => 'month', 'types' => 0]));
                $data['surplus'] = bcsub((string) $data['count'], (string) $data['finish'], 0);
                break;
            case 'week':
                $data['count']   = 4;
                $data['finish']  = count($this->dao->getOnceList(['user_id' => $uid, 'time' => 'month', 'types' => 1]));
                $data['surplus'] = bcsub((string) $data['count'], (string) $data['finish'], 0);
                break;
            case 'month':
                $data['count']   = 1;
                $data['finish']  = count($this->dao->getOnceList(['user_id' => $uid, 'time' => 'month', 'types' => 2]));
                $data['surplus'] = bcsub((string) $data['count'], (string) $data['finish'], 0);
                break;
            default:
                $data = [];
        }
        return $data;
    }

    /**
     * 汇报提交统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function submitStatistics(string $uuid, int $type = 0, int $types = 0, string $time = '', array $userIds = []): array
    {
        $types          = $type == 1 ? 0 : (! in_array($types, [0, 1, 2, 3]) ? 0 : $types);
        $arrangeService = app()->get(AttendanceArrangeService::class);

        $list        = [];
        $format      = 'Y/m/d H:i:s';
        $uid         = uuid_to_uid($uuid);
        $tz          = config('app.timezone');
        $now         = Carbon::now($tz);
        $originTime  = $this->parseTime($time, $types);
        $origin      = explode('-', $originTime);

        $userNum = 1;
        if ($type == 1) {
            $where = ['entid' => 1, 'types' => $types, 'user_id' => $uid];
        } else {
            $userIds = $this->validReportUserIds($userIds);
            $userNum = count($userIds);
            $where   = ['entid' => 1, 'types' => $types, 'user_ids' => $userIds ?: [-1]];
        }
        switch ($types) {
            case 2:// 月报
                $now = now($tz)->toDateTimeString();
                for ($i = 1; $i <= 12; ++$i) {
                    $start = Carbon::parse($origin[0], $tz)->month($i);
                    if ($start->gt($now)) {
                        break;
                    }
                    $where['time'] = $start->format($format) . '-' . Carbon::parse($origin[0], $tz)->month($i)->endOfMonth()->format($format);
                    $num           = $this->dao->search($where)->groupBy('user_id')->get(DB::raw('distinct(user_id)'))->count();
                    $list[]        = ['time' => $start->format('Y-m'), 'no_submit' => $num == $userNum ? 0 : 1];
                }
                break;
            case 1:// 周报
                $weekData    = [];
                $endOfWeek   = Carbon::parse($origin[0], $tz)->endOfWeek();
                $startOfWeek = Carbon::parse($origin[0], $tz)->startOfWeek();

                $weekData[] = [$startOfWeek, $endOfWeek];
                for ($i = 0; $i < 4; ++$i) {
                    $endOfWeek   = Carbon::parse($endOfWeek, $tz)->endOfWeek()->addWeek();
                    $startOfWeek = Carbon::parse($endOfWeek, $tz)->startOfWeek();
                    $weekData[]  = [$startOfWeek, $endOfWeek];
                }
                foreach ($weekData as $week) {
                    $timeZone  = CarbonPeriod::create(Carbon::parse($week[0], $tz)->toDateString(), Carbon::parse($week[1], $tz)->toDateString())->toArray();
                    $notSubmit = false;

                    foreach ($timeZone as $day) {
                        if ($day->startOfDay()->gt($now->toDateTimeString())) {
                            break;
                        }
                        $where['time'] = $day->startOfDay()->format($format) . '-' . $day->endOfDay()->format($format);
                        $result        = $this->dao->search($where)->groupBy('user_id')->get(DB::raw('distinct(user_id)'))->count() == $userNum;
                        if (! $result) {
                            $notSubmit = true;
                            break;
                        }
                    }
                    $list[] = ['time' => $week[1]->toDateString(), 'no_submit' => $notSubmit ? 1 : 0];
                }
                break;
            default:// 日报
                $startObj       = Carbon::parse($origin[0], $tz)->startOfDay();
                $endObj         = Carbon::parse($origin[1], $tz)->endOfDay();
                $timeZone       = CarbonPeriod::create($startObj->toDateString(), $endObj->toDateString())->toArray();
                $submitCountMap = $this->dao->getDailySubmitUserCount($where + ['time' => $originTime]);
                $restMap        = $arrangeService->allUsersRestByDate($type == 1 ? [$uid] : $where['user_ids'], $startObj, $endObj);
                foreach ($timeZone as $day) {
                    $dayObj = Carbon::parse($day, $tz);
                    if ($dayObj->startOfDay()->gt($now->toDateTimeString())) {
                        continue;
                    }

                    $date   = $dayObj->toDateString();
                    $isRest = $restMap[$date] ?? false;

                    if ($isRest) {
                        $list[] = ['time' => $date, 'no_submit' => 0];
                        continue;
                    }

                    $result = ($submitCountMap[$date] ?? 0) == $userNum;
                    $list[] = ['time' => $date, 'no_submit' => $result ? 0 : 1];
                }
        }
        return $list;
    }

    /**
     * 提交统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function statistics(array $where): array
    {
        $data              = $submit = [];
        $where['user_ids'] = $this->validReportUserIds($where['user_ids']);
        if (! $where['user_ids']) {
            return ['submit' => 0, 'no_submit' => 0, 'total' => 0];
        }
        $where['entid'] = 1;
        $where['time']  = $this->parseTime($where['time'], $where['types']);
        $data['submit'] = $this->dao->search($where)->select(['user_id'])->groupBy('user_id')->get()->each(function ($item) use (&$submit) {
            $submit[] = $item['user_id'];
        })->count();
        $data['no_submit'] = count(array_diff($where['user_ids'], $submit));
        $data['total']     = $data['submit'] + $data['no_submit'];
        return $data;
    }

    /**
     * 提交统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function statisticsByFrameId(string $uuid, int $entId, int $frameId = 0, int $types = 0, string $time = ''): array
    {
        $userIds = $this->getScopeUserIds($uuid, $entId, $frameId);
        return $this->statistics(['types' => $types, 'time' => $time, 'user_ids' => $userIds]);
    }

    /**
     * 解析时间.
     */
    public function parseTime(string $originTime, int $types = 0, string $format = 'Y/m/d H:i:s'): string
    {
        $start  = $end = '';
        $tz     = config('app.timezone');
        $now    = Carbon::now($tz);
        $origin = explode('-', $originTime);
        if (! $originTime) {
            switch ($types) {
                case 0:
                    $yesterday = $now->subDay();
                    $start     = $yesterday->startOfDay()->format($format);
                    $end       = $yesterday->endOfDay()->format($format);
                    break;
                case 1:
                    $start = $now->startOfWeek()->format($format);
                    $end   = $now->endOfWeek()->endOfDay()->format($format);
                    break;
                case 2:
                    $start = $now->month(1)->startOfMonth()->startOfDay()->format($format);
                    $end   = $now->month(12)->endOfMonth()->endOfDay()->format($format);
                    break;
            }
        } else {
            if ($types == 2) {
                if (strlen($originTime) == 4) {
                    $origin = [$originTime . '/01/01', $originTime . '/12/31'];
                }
            }
            $startCarbon = Carbon::parse($origin[0], $tz);
            $endCarbon   = Carbon::parse($origin[1], $tz);
            if ($endCarbon->gt($now->toDateTimeString())) {
                $endCarbon = match ($types) {
                    1       => $endCarbon,
                    default => $now,
                };
            }

            if ($types == 2) {
                $start = $startCarbon->startOfMonth()->startOfDay()->format($format);
                $end   = $endCarbon->endOfMonth()->endOfDay()->format($format);
            } else {
                $start = $startCarbon->startOfDay()->format($format);
                $end   = $endCarbon->endOfDay()->format($format);
            }
        }
        return $start . '-' . $end;
    }

    /**
     * 已提交统计列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSubmitList(array $where): array
    {
        $where['user_ids'] = $this->validReportUserIds($where['user_ids']);
        if (! $where['user_ids']) {
            return $this->listData([]);
        }
        $where['entid'] = 1;
        $where['time']  = $this->parseTime($where['time'], $where['types']);
        $field          = ['daily_id', 'uid', 'user_id', 'types', 'created_at'];
        [$page, $limit] = $this->getPageValue();
        $list           = toArray($this->dao->getList($where, $field, $page, $limit, 'created_at', [
            'user' => fn ($q) => $q->select(['id', 'uid', 'name', 'avatar']),
            'frame',
        ]));

        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $item = $this->submitUserListFormat($item);
        }

        return $this->listData($list, $count);
    }

    /**
     * 获取统计范围提交汇报用户列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSubmitListByFrameId(string $uuid, int $frameId, int $types, string $time): array
    {
        $userIds = $this->getScopeUserIds($uuid, 1, $frameId);
        return $this->getSubmitList(['types' => $types, 'time' => $time, 'user_ids' => $userIds]);
    }

    /**
     * 获取统计范围未提交汇报用户列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getNoSubmitUserListByFrameId(string $uuid, int $frameId, int $types, string $time): array
    {
        $userIds = $this->getScopeUserIds($uuid, 1, $frameId);
        return $this->getNoSubmitUserList(['types' => $types, 'time' => $time, 'user_ids' => $userIds]);
    }

    /**
     * 未提交汇报用户列表.
     * @throws BindingResolutionException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getNoSubmitUserList(array $where): array
    {
        $where['user_ids'] = $this->validReportUserIds($where['user_ids']);
        if (! $where['user_ids']) {
            return $this->listData([]);
        }
        $submit         = [];
        $where['entid'] = 1;
        $where['time']  = $this->parseTime($where['time'], $where['types']);
        $this->dao->search($where)->select(['user_id'])->groupBy('user_id')->get()->each(function ($item) use (&$submit) {
            $submit[] = $item['user_id'];
        });

        $noSubmitUserIds = array_values(array_diff($where['user_ids'], $submit));
        if (! $noSubmitUserIds) {
            return $this->listData([]);
        }

        $field     = ['id', 'name', 'avatar'];
        $userWhere = ['id' => $noSubmitUserIds];
        $users     = app()->get(AdminService::class)->getList($userWhere, $field, 'created_at', ['frame']);
        foreach ($users['list'] as &$item) {
            $item['types']      = $where['types'];
            $item['frame_name'] = $item['frame']['name'] ?? '';
            unset($item['frame']);
        }
        return $users;
    }

    /**
     * 统计范围.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getScopeUserIds(string $uuid, int $entId, int $frameId): array
    {
        return match ($frameId) {
            -1      => app()->get(FrameService::class)->getLevelSub(uuid_to_uid($uuid, $entId)),
            0       => app()->get(ModulePermissionService::class)->getAccessibleUserIds(uuid_to_uid($uuid, $entId), ModuleEnum::REPORT),
            default => app()->get(FrameAssistService::class)->column([
                'frame_id'   => app()->get(FrameService::class)->scopeFrames((int) $frameId),
                'is_mastart' => 1,
            ], 'user_id'),
        };
    }

    /**
     * 获取日报表单回显数据.
     * @param mixed $uuid
     * @param mixed $type
     * @return array[]
     * @throws BindingResolutionException
     */
    public function dailyScheduleRecord($uuid, $type, string $origin = CommonEnum::ORIGIN_WEB): array
    {
        $where = [
            'uid'   => $uuid,
            'types' => $type,
            'time'  => match ((int) $type) {
                1       => 'week',
                2       => 'month',
                default => 'today'
            },
        ];

        $scheduleService = app()->get(ScheduleInterface::class);
        return [
            'plan'   => $origin === CommonEnum::ORIGIN_WEB ? $scheduleService->getNextWorkDayPlan($uuid) : [],
            'finish' => ! $this->dao->exists($where) ? $scheduleService->dailyCompleteRecord($uuid, $type) : [],
        ];
    }

    /**
     * 默认汇报人用户库 ID 列表（直属上级），与 PC/移动端「默认汇报人」接口一致.
     *
     * @return array<int, int>
     * @throws BindingResolutionException
     */
    public function defaultMemberIds(int $uid): array
    {
        if ($uid <= 0) {
            return [];
        }
        $info = app()->get(FrameService::class)->getLevelSuperUser($uid);
        $id   = (int) ($info['id'] ?? 0);

        return $id > 0 ? [$id] : [];
    }

    /**
     * 事务提交后再投递汇报侧效任务（无活跃事务时立即执行）.
     */
    private function scheduleReportTask(Task $task): void
    {
        DB::afterCommit(function () use ($task) {
            $this->dispatchReportTask($task);
        });
    }

    /**
     * LaravelS 下异步投递；非 Swoole（artisan/tinker）同步 handle，失败只记日志不回滚主流程.
     */
    private function dispatchReportTask(Task $task): void
    {
        if ($this->canUseSwooleTask()) {
            Task::deliver($task);

            return;
        }

        try {
            $task->handle();
        } catch (\Throwable $e) {
            Log::warning('汇报侧效任务在非 Swoole 环境执行失败，已跳过', [
                'task'    => $task::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 是否具备真实 Swoole Worker 投递能力.
     */
    private function canUseSwooleTask(): bool
    {
        try {
            if (! app()->bound('swoole')) {
                return false;
            }
            $swoole = app('swoole');

            return is_object($swoole)
                && method_exists($swoole, 'task')
                && ($swoole instanceof \Swoole\Server || property_exists($swoole, 'worker_id'));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 直属上级
     * 兼容前端组件 card.uid.
     * @throws BindingResolutionException
     */
    public function getReportMember(int $uid): array
    {
        $data = [];
        $info = app()->get(FrameService::class)->getLevelSuperUser($uid);
        if ($info) {
            $data[] = [['card' => $info]];
        }
        return $data;
    }

    /**
     * 直属上级
     * 兼容前端组件 card.uid.
     * @throws BindingResolutionException
     */
    public function getReportMemberPC(int $uid): array
    {
        $data = [];
        $info = app()->get(FrameService::class)->getLevelSuperUser($uid);
        if ($info) {
            $data[] = ['card' => $info];
        }
        return $data;
    }

    /**
     * 默认列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function defaultList(array $where, array $field = ['*'], null|array|string $sort = null, array $with = []): array
    {
        unset($where['dep_report'], $where['viewer'], $where['finish_like']);
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, [
            'user' => fn ($q) => $q->select(['id', 'uid', 'name', 'avatar']), 'frame',
        ]);

        foreach ($list as &$item) {
            $create = Carbon::make($item['created_at']);
            switch ($item['types']) {
                case 2:
                    $item['start_time'] = $create->startOfMonth()->toDateTimeString();
                    $item['end_time']   = $create->endOfMonth()->toDateTimeString();
                    break;
                case 1:
                    $item['start_time'] = $create->startOfWeek()->toDateTimeString();
                    $item['end_time']   = $create->endOfWeek()->toDateTimeString();
                    break;
                default:
                    $item['start_time'] = $create->startOfDay()->toDateTimeString();
                    $item['end_time']   = $create->endOfDay()->toDateTimeString();
            }
            $item = $this->submitUserListFormat($item);
        }
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 汇报人查看汇报列表.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getReportDailyList(array $where, array $field = ['*'], $sort = 'created_at', array $with = []): array
    {
        // get report member
        $where['daily_id'] = $this->memberDao->column(['member' => uuid_to_uid($this->uuId(false))], 'daily_id');

        if (empty($where['daily_id'])) {
            return $this->listData([]);
        }

        return $this->defaultList($where, $field, $sort, $with);
    }

    /**
     * 数据处理.
     * @return mixed
     */
    public function submitUserListFormat(array $item): array
    {
        $item['name']       = $item['user']['name'] ?? '';
        $item['avatar']     = $item['user']['avatar'] ?? '';
        $item['frame_name'] = $item['frame']['name'] ?? '';
        unset($item['user'], $item['frame']);
        return $item;
    }

    private function validReportUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (! $userIds) {
            return [];
        }

        $validIds = app()->get(AdminService::class)->joinSearch([
            'id'    => $userIds,
            'types' => [1, 2, 3],
        ])->pluck('admin.id')->map(fn ($id) => (int) $id)->all();

        return array_values(array_intersect($userIds, $validIds));
    }

    /**
     * 按周期获取已提交次数及未提交次数.
     * @return int[]
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getSubmitNumberByCycle(int $types, int $uid, array $cycle, string $tz): array
    {
        $submitTotal = $unSubmitTotal = 0;
        foreach ($cycle as $item) {
            $timeZone            = CarbonPeriod::create(Carbon::parse($item[0], $tz)->toDateString(), Carbon::parse($item[1], $tz)->toDateString())->toArray();
            [$submit, $unSubmit] = $this->getSubmitNumberByDay($types, $uid, $timeZone);
            $submitTotal += $submit;
            $unSubmit && $unSubmitTotal++;
        }
        return [$submitTotal, $unSubmitTotal];
    }

    /**
     * 按天为单位获取已提交次数及未提交次数.
     * @return int[]
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSubmitNumberByDay(int $types, int $uid, array $cycle): array
    {
        $unSubmit       = $submit = 0;
        $arrangeService = app()->get(AttendanceArrangeService::class);
        foreach ($cycle as $day) {
            $isRest = $arrangeService->dayIsRest($uid, $day->startOfDay()->toDateString());
            if ($isRest) {
                continue;
            }

            $num = $this->dao->search(['types' => $types, 'user_id' => $uid, 'day' => $day->format('Y-m-d')])->count();
            if ($num > 0) {
                $submit += $num;
            } else {
                ++$unSubmit;
            }
        }
        return [$submit, $unSubmit];
    }

    /**
     * 汇报统计导出.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function export(array $where): array
    {
        $adminWhere = ['status' => 1];
        if ($where['viewer'] == 'hr') {
            unset($where['user_id']);
        } else {
            $adminWhere['id'] = $where['user_id'];
        }
        $adminWhere['name_like'] = $where['name_like'];
        $types                   = is_array($where['types']) ? (int) $where['types'][0] : $where['types'];
        $tz                      = config('app.timezone');
        [$startTime, $endTime]   = explode('-', $where['time']);
        if (! $startTime || ! $endTime) {
            throw $this->exception('时间格式错误');
        }
        switch ($types) {
            case 1:
                $field  = 'WEEK(created_at) as days,user_id';
                $sumNum = Carbon::parse($startTime, $tz)->diffInWeeks(Carbon::parse($endTime, $tz));
                break;
            case 2:
                $field  = 'MONTH(created_at) as days,user_id';
                $sumNum = Carbon::parse($startTime, $tz)->diffInMonths(Carbon::parse($endTime, $tz));
                break;
            default:
                $field  = "DATE_FORMAT(created_at,'%Y-%m-%d') as days,user_id";
                $sumNum = Carbon::parse($startTime, $tz)->diffInDays(Carbon::parse($endTime, $tz)->addDays());
                break;
        }
        $typeName = DailyEnum::getDailyTypeName((int)$types);
        $list     = app()->get(AdminService::class)->select($adminWhere, ['id', 'name'], ['frame' => fn ($query) => $query->select(['frame.id', 'frame.name'])])?->toArray();
        foreach ($list as &$item) {
            $item['frame_name'] = $item['frame']['name'] ?? '';
            $item['type_name']  = $typeName;
            $submitNum          = 0;
            $data               = $this->dao->getGroupSearch(['types' => $types, 'user_id' => $item['id'], 'time' => $where['time']], $field)->get()->toArray();
            array_walk_recursive($data, function ($value, $key) use (&$submitNum, $item) {
                if ($value === $item['id'] && $key === 'user_id') {
                    ++$submitNum;
                }
            });
            $item['submit']    = $submitNum;
            $item['no_submit'] = $sumNum - $submitNum;
            unset($item['frame']);
        }
        return $list;
    }

    /**
     * 处理汇报人.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function handleMember(array $members, int $dailyId): void
    {
        $data = [];
        foreach ($this->memberDao->column(['daily_id' => $dailyId], 'member', 'id') as $key => $item) {
            $data[$item] = $key;
        }

        foreach ($members as $member) {
            if ($dailyId && isset($data[$member])) {
                unset($data[$member]);
                continue;
            }
            $this->memberDao->create(['daily_id' => $dailyId, 'member' => $member]);
        }

        if ($data) {
            $this->memberDao->delete(['daily_id' => $dailyId, 'id' => array_values($data)]);
        }
    }
}
