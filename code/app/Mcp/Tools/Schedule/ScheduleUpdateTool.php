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

namespace App\Mcp\Tools\Schedule;

use App\Constants\ScheduleEnum;
use App\Http\Model\Schedule\Schedule;
use App\Http\Model\Schedule\ScheduleRemind;
use App\Http\Service\Schedule\ScheduleService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesNaturalRecord;
use Illuminate\Support\Carbon;

/**
 * 修改个人或自定义日程的标题、内容、时间和提醒.
 *
 * 不开放付款提醒、回款、跟进、汇报待办、绩效等业务联动日程，也不改分类、关联业务和重复规则。
 * 仅日程创建人可改；写操作仍走 MCP write 确认。
 */
class ScheduleUpdateTool extends BaseTool
{
    use ResolvesNaturalRecord;
    /**
     * 业务联动日程，禁止经 MCP 修改.
     *
     * @var array<int, int>
     */
    private const LOCKED_TYPE_IDS = [
        ScheduleEnum::TYPE_CLIENT_TRACK,
        ScheduleEnum::TYPE_CLIENT_RENEW,
        ScheduleEnum::TYPE_CLIENT_RETURN,
        ScheduleEnum::TYPE_REPORT_RENEW,
        ScheduleEnum::TYPE_ASSESS,
        ScheduleEnum::TYPE_CLUE_TRACK,
        ScheduleEnum::TYPE_ODDS_TRACK,
    ];

    /**
     * {@inheritdoc}
     */
    public function getAction(): string
    {
        return 'write';
    }

    /**
     * 个人提醒和用户自建分类可改，系统业务日程不可改.
     */
    public static function isEditableScheduleType(int $cid): bool
    {
        return $cid > 0 && ! in_array($cid, self::LOCKED_TYPE_IDS, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return '修改本人创建的个人或自定义日程（标题、内容、起止时间、全天、提醒、参与人）。'
            . '用户不会提供日程 ID，禁止向用户索要 ID。没有 id 时用 match_title（现有标题）和/或 match_date（现有开始日期）定位；唯一命中才修改，多条只返回候选、不写入。'
            . 'title 是修改后的新标题，不要把原标题写进 title。'
            . '不修改日程分类、关联业务和重复规则；付款提醒、回款、客户/线索/商机跟进、汇报待办、绩效等待办不可改。'
            . '仅创建人可操作，写操作需要用户确认。未传的字段保持原值。';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'         => [
                    'type'        => 'integer',
                    'description' => '仅当上一轮工具已唯一定位时由系统传入。用户不会提供，禁止向用户索要。',
                ],
                'match_title' => [
                    'type'         => 'string',
                    'description'  => '用来定位的现有标题关键词，不是新标题。例如把「联调测试会议」改成「下周评审」时，match_title 为联调测试会议，title 为下周评审。',
                    'x-ai-aliases' => ['原标题', '现有标题', '日程名称'],
                ],
                'match_date' => [
                    'type'         => 'string',
                    'description'  => '用来定位的现有开始日期，支持 YYYY-MM-DD、今天、昨天、明天。',
                    'x-ai-aliases' => ['原日期', '哪一天'],
                ],
                'title'      => [
                    'type'         => 'string',
                    'description'  => '修改后的新标题；不传则保持原标题。定位现有日程用 match_title，不要把原标题写在这里。',
                    'x-ai-aliases' => ['标题', '主题', '事项'],
                ],
                'content'    => [
                    'type'         => 'string',
                    'description'  => '日程内容/备注',
                    'x-ai-aliases' => ['内容', '备注', '说明'],
                ],
                'start_time' => [
                    'type'            => 'string',
                    'description'     => '开始时间（日期时间，如 2026-09-03 09:00:00）',
                    'x-ai-aliases'    => ['开始时间', '起始时间'],
                    'x-ai-normalizer' => 'datetime',
                ],
                'end_time'   => [
                    'type'            => 'string',
                    'description'     => '结束时间（日期时间，如 2026-09-03 10:00:00）',
                    'x-ai-aliases'    => ['结束时间', '截止时间'],
                    'x-ai-normalizer' => 'datetime',
                ],
                'all_day'    => ['type' => 'integer', 'description' => '是否全天：0=否，1=是'],
                'remind'     => [
                    'type'        => 'integer',
                    'description' => '提前提醒档位：-1=不提醒，0=开始时，1=提前5分钟，2=提前15分钟，'
                        . '3=提前30分钟，4=提前1小时，5=提前2小时，6=提前1天，7=提前2天，8=提前1周；'
                        . '仅用户明确说明提醒时机时传对应档位',
                ],
                'member'     => [
                    'type'        => 'array',
                    'items'       => ['type' => 'integer'],
                    'description' => '参与人用户数据库 ID 列表；会保留创建人',
                ],
            ],
        ];
    }

    /**
     * 修改日程并返回原记录 ID.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array{success?: bool, id?: int, error?: bool, message?: string, field?: string}
     */
    public function execute(array $arguments): array
    {
        if (! $this->hasMutableField($arguments)) {
            return ['error' => true, 'message' => '请提供要修改的内容', 'field' => 'title'];
        }

        $resolved = $this->resolveScheduleId($arguments);
        if (isset($resolved['error'])) {
            return $resolved;
        }
        $id = $resolved['id'];

        $schedule = Schedule::query()->with(['remind', 'scheduleUser'])->find($id);
        if (! $schedule) {
            return ['error' => true, 'message' => '日程不存在'];
        }
        if (! self::isEditableScheduleType((int) $schedule->cid)) {
            return ['error' => true, 'message' => '无权修改该业务待办日程'];
        }

        $actorId = $this->getUserDbId();
        $ownerId = (int) $schedule->uid;
        if ($actorId <= 0 || $actorId !== $ownerId) {
            return ['error' => true, 'message' => '无权修改他人日程'];
        }

        $title = array_key_exists('title', $arguments) ? trim((string) $arguments['title']) : trim((string) $schedule->title);
        if ($title === '') {
            return ['error' => true, 'message' => '日程标题不能为空', 'field' => 'title'];
        }

        $startTime = array_key_exists('start_time', $arguments)
            ? trim((string) $arguments['start_time'])
            : $this->formatDateTime($schedule->start_time);
        $endTime = array_key_exists('end_time', $arguments)
            ? trim((string) $arguments['end_time'])
            : $this->formatDateTime($schedule->end_time);
        if ($startTime === '' || $endTime === '') {
            return ['error' => true, 'message' => '缺少日程开始或结束时间', 'field' => $startTime === '' ? 'start_time' : 'end_time'];
        }
        if (strtotime($endTime) !== false && strtotime($startTime) !== false && strtotime($endTime) < strtotime($startTime)) {
            return ['error' => true, 'message' => '结束时间不能早于开始时间', 'field' => 'end_time'];
        }

        $members = array_key_exists('member', $arguments)
            ? array_values(array_unique(array_filter(array_map('intval', (array) $arguments['member']))))
            : $schedule->scheduleUser->pluck('uid')->map(static fn ($uid): int => (int) $uid)->all();
        if (! in_array($ownerId, $members, true)) {
            $members[] = $ownerId;
        }

        $remind = array_key_exists('remind', $arguments)
            && $arguments['remind'] !== null
            && $arguments['remind'] !== ''
            ? (int) $arguments['remind']
            : $this->resolveRemindLevel(
                (int) $schedule->getRawOriginal('remind'),
                $schedule->getRelation('remind'),
                $this->formatDateTime($schedule->start_time)
            );

        $data = [
            'title'      => $title,
            'content'    => array_key_exists('content', $arguments) ? (string) $arguments['content'] : (string) $schedule->content,
            'cid'        => (int) $schedule->cid,
            'member'     => $members,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'all_day'    => array_key_exists('all_day', $arguments) ? (int) $arguments['all_day'] : (int) $schedule->all_day,
            'remind'     => $remind,
            'period'     => (int) $schedule->period,
            'rate'       => max(1, (int) $schedule->rate),
            'days'       => (array) ($schedule->days ?? []),
            'fail_time'  => $this->formatDateTime($schedule->fail_time) ?: null,
            'link_id'    => (int) $schedule->link_id,
            'type'       => ScheduleEnum::CHANGE_ALL,
            'start'      => $startTime,
            'end'        => $endTime,
        ];

        $updated = app(ScheduleService::class)->updateSchedule($this->getEntId(), $id, $data, $ownerId);
        if (! $updated) {
            return ['error' => true, 'message' => '日程修改失败'];
        }

        return ['success' => true, 'id' => $id, 'title' => $title];
    }

    /**
     * 用现有标题或日期定位本人可修改的日程；多条不猜测.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array{id: int}|array{error: true, message: string, field?: string, candidates?: array<int, array<string, mixed>>}
     */
    private function resolveScheduleId(array $arguments): array
    {
        $id = (int) ($arguments['id'] ?? 0);
        if ($id > 0) {
            return ['id' => $id];
        }

        $matchTitle = $this->firstFilledString($arguments, ['match_title']);
        $matchDate  = $this->firstFilledString($arguments, ['match_date']);
        if ($matchTitle === '' && $matchDate === '') {
            return ['error' => true, 'message' => '请用日程标题或日期说明要改哪一条，不要向用户索要 ID', 'field' => 'match_title'];
        }

        $actorId = $this->getUserDbId();
        if ($actorId <= 0) {
            return ['error' => true, 'message' => '无法识别当前用户，请重新登录后再试'];
        }

        $query = Schedule::query()
            ->where('uid', $actorId)
            ->whereNotIn('cid', self::LOCKED_TYPE_IDS);
        if ($matchTitle !== '') {
            $query->where('title', 'like', '%' . $this->escapeLike($matchTitle) . '%');
        }
        if ($matchDate !== '') {
            $day = $this->parseNaturalDay($matchDate);
            if ($day === null) {
                return ['error' => true, 'message' => '无法识别日期，请使用 YYYY-MM-DD 或今天、昨天、明天', 'field' => 'match_date'];
            }
            $query->whereDate('start_time', $day);
        }

        $rows = $query->orderByDesc('start_time')->limit(6)->get(['id', 'title', 'start_time']);
        if ($rows->isEmpty()) {
            return ['error' => true, 'message' => '没有找到可修改的日程，请换一个标题或日期'];
        }
        if ($rows->count() > 1) {
            return [
                'error'      => true,
                'message'    => '匹配到多条日程，请让用户按标题和时间选择，不要索要 ID',
                'candidates' => $rows->map(fn ($row): array => [
                    'id'         => (int) $row->id,
                    'title'      => (string) $row->title,
                    'start_time' => $this->formatDateTime($row->start_time),
                ])->all(),
            ];
        }

        return ['id' => (int) $rows->first()->id];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function hasMutableField(array $arguments): bool
    {
        foreach (['title', 'content', 'start_time', 'end_time', 'all_day', 'remind', 'member'] as $field) {
            if (array_key_exists($field, $arguments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 按原开始时间反推提醒档位，避免只改标题时把提醒重置掉.
     *
     * remind 列与 remind 关联同名，必须用原始列值和已加载关联，不能读 $schedule->remind。
     */
    private function resolveRemindLevel(int $remindFlag, mixed $remind, string $startTime): int
    {
        if ($remindFlag <= 0 || $startTime === '') {
            return -1;
        }
        if (! $remind instanceof ScheduleRemind) {
            return 0;
        }

        $day   = $this->formatDateTime($remind->remind_day, 'Y-m-d');
        $clock = trim((string) $remind->remind_time);
        if ($day === '' || $clock === '') {
            return 0;
        }

        $tz       = (string) config('app.timezone');
        $remindAt = Carbon::parse($day . ' ' . $clock, $tz)->timestamp;
        $start    = Carbon::parse($startTime, $tz);
        $levels   = [
            1 => $start->copy()->subMinutes(5)->timestamp,
            2 => $start->copy()->subMinutes(15)->timestamp,
            3 => $start->copy()->subMinutes(30)->timestamp,
            4 => $start->copy()->subHour()->timestamp,
            5 => $start->copy()->subHours(2)->timestamp,
            6 => $start->copy()->subDay()->timestamp,
            7 => $start->copy()->subDays(2)->timestamp,
            8 => $start->copy()->subWeek()->timestamp,
        ];
        foreach ($levels as $level => $timestamp) {
            if ($remindAt === $timestamp) {
                return $level;
            }
        }

        return 0;
    }

    private function formatDateTime(mixed $value, string $format = 'Y-m-d H:i:s'): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        $value = trim((string) $value);

        return $value;
    }
}
