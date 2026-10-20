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

namespace App\Mcp\Tools\Report;

use App\Http\Model\Attach\SystemAttach;
use App\Http\Model\Company\DailyReportMember;
use App\Http\Model\User\UserDaily;
use App\Http\Service\Report\ReportService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\ResolvesNaturalRecord;

/**
 * 修改本人工作汇报的完成项、计划和备注.
 *
 * 不改汇报类型、状态和提交人。写操作仍走 MCP write 确认。
 */
class ReportUpdateTool extends BaseTool
{
    use ResolvesNaturalRecord;
    /**
     * {@inheritdoc}
     */
    public function getAction(): string
    {
        return 'write';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return '修改本人已提交的工作汇报（完成项、计划、备注、汇报对象、附件）。'
            . '用户不会提供汇报 ID，禁止向用户索要 ID。没有 id 时用 match_date、match_types、keyword 定位；说「我的日报/最近一条」时传 match_types 和 latest=true。'
            . '唯一命中才修改，多条只返回候选、不写入。不修改汇报类型和提交人，也不能改他人汇报。写操作需要用户确认。未传的字段保持原值。';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'          => [
                    'type'        => 'integer',
                    'description' => '仅当上一轮工具已唯一定位时由系统传入。用户不会提供，禁止向用户索要。',
                ],
                'match_date'  => [
                    'type'         => 'string',
                    'description'  => '用来定位的提交日期，支持 YYYY-MM-DD、今天、昨天、明天。',
                    'x-ai-aliases' => ['哪一天', '日期'],
                ],
                'match_types' => [
                    'type'         => 'string',
                    'description'  => '用来定位的汇报类型，不是要修改的类型。0或日报、1或周报、2或月报、3或汇报。',
                    'x-ai-aliases' => ['日报', '周报', '月报'],
                ],
                'keyword'     => [
                    'type'         => 'string',
                    'description'  => '完成项、计划或备注里的原文关键词，用来区分是哪一条。',
                    'x-ai-aliases' => ['原文', '关键词'],
                ],
                'latest'      => [
                    'type'        => 'boolean',
                    'description' => '用户说「最近一条」「我的日报」且没有指定日期时传 true，只改该类型最新的一条。',
                ],
                'finish'     => [
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'description' => '已完成工作（每项一条）；传入后整体替换，不传则保持原值',
                ],
                'plan'       => [
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'description' => '工作计划（每项一条）；传入后整体替换，不传则保持原值',
                ],
                'mark'       => [
                    'type'         => 'string',
                    'description'  => '备注',
                    'x-ai-aliases' => ['备注', 'remark'],
                ],
                'members'    => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '汇报对象用户 ID；传入后整体替换'],
                'attach_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '附件 ID 列表；传入后整体替换'],
            ],
        ];
    }

    /**
     * 修改汇报并返回原记录 ID.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array{success?: bool, id?: int, daily_id?: int, error?: bool, message?: string, field?: string}
     */
    public function execute(array $arguments): array
    {
        if (! $this->hasMutableField($arguments)) {
            return ['error' => true, 'message' => '请提供要修改的内容', 'field' => 'finish'];
        }

        $resolved = $this->resolveReportId($arguments);
        if (isset($resolved['error'])) {
            return $resolved;
        }
        $id = $resolved['id'];

        $uid = (string) ($this->getUserInfo()['uid'] ?? '');
        if ($uid === '') {
            return ['error' => true, 'message' => '无法识别当前用户，请重新登录后再试'];
        }

        $daily = UserDaily::query()->find($id);
        if (! $daily) {
            return ['error' => true, 'message' => '汇报不存在'];
        }
        if ((int) $daily->user_id !== $this->getUserDbId()) {
            return ['error' => true, 'message' => '无权修改他人汇报'];
        }

        $finish = array_key_exists('finish', $arguments) ? array_values((array) $arguments['finish']) : (array) $daily->finish;
        $plan   = array_key_exists('plan', $arguments) ? array_values((array) $arguments['plan']) : (array) $daily->plan;
        if ((int) $daily->types !== 3 && $plan === []) {
            return ['error' => true, 'message' => '请填写工作计划', 'field' => 'plan'];
        }

        $members = array_key_exists('members', $arguments)
            ? array_values(array_map('intval', (array) $arguments['members']))
            : DailyReportMember::query()->where('daily_id', $id)->pluck('member')->map(static fn ($member): int => (int) $member)->all();
        $attachIds = array_key_exists('attach_ids', $arguments)
            ? array_values(array_map('intval', (array) $arguments['attach_ids']))
            : SystemAttach::query()->where('relation_id', $id)->where('relation_type', 1)->pluck('id')->map(static fn ($attachId): int => (int) $attachId)->all();

        $mark = $this->optionalString($arguments, ['mark', 'remark']);
        $updated = app(ReportService::class)->resourceUpdate($id, [
            'finish'     => $finish,
            'plan'       => $plan,
            'mark'       => $mark ?? (string) $daily->mark,
            'types'      => (int) $daily->types,
            'uid'        => $uid,
            'entid'      => (int) $daily->entid,
            'attach_ids' => $attachIds,
            'members'    => $members,
        ]);
        if (! $updated) {
            return ['error' => true, 'message' => '工作汇报修改失败'];
        }

        return [
            'success'  => true,
            'id'       => $id,
            'daily_id' => $id,
        ];
    }

    /**
     * 用日期、类型或原文定位本人汇报。未指明日期且 latest 为真时取该类型最新一条；多条不猜测.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array{id: int}|array{error: true, message: string, field?: string, candidates?: array<int, array<string, mixed>>}
     */
    private function resolveReportId(array $arguments): array
    {
        $id = (int) ($arguments['id'] ?? 0);
        if ($id > 0) {
            return ['id' => $id];
        }

        $matchDate = $this->firstFilledString($arguments, ['match_date']);
        $keyword   = $this->firstFilledString($arguments, ['keyword']);
        $types     = $this->parseReportTypes($arguments['match_types'] ?? null);
        if ($types === false) {
            return ['error' => true, 'message' => '无法识别汇报类型，请使用日报、周报、月报或汇报', 'field' => 'match_types'];
        }
        if ($matchDate === '' && $keyword === '' && $types === null && ! $this->wantsLatestRecord($arguments)) {
            return ['error' => true, 'message' => '请说明是哪一天、哪种汇报，或原文里的关键词，不要向用户索要 ID', 'field' => 'match_date'];
        }

        $actorId = $this->getUserDbId();
        if ($actorId <= 0) {
            return ['error' => true, 'message' => '无法识别当前用户，请重新登录后再试'];
        }

        $query = UserDaily::query()->where('user_id', $actorId);
        if ($types !== null) {
            $query->where('types', $types);
        }
        if ($matchDate !== '') {
            $day = $this->parseNaturalDay($matchDate);
            if ($day === null) {
                return ['error' => true, 'message' => '无法识别日期，请使用 YYYY-MM-DD 或今天、昨天、明天', 'field' => 'match_date'];
            }
            $query->whereDate('created_at', $day);
        }
        if ($keyword !== '') {
            $like = '%' . $this->escapeLike($keyword) . '%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('mark', 'like', $like)
                    ->orWhere('finish', 'like', $like)
                    ->orWhere('plan', 'like', $like);
            });
        }

        $takeLatest = $this->wantsLatestRecord($arguments) && $keyword === '';
        $rows       = $query->orderByDesc('daily_id')->limit($takeLatest ? 1 : 6)->get(['daily_id', 'types', 'mark', 'created_at']);
        if ($rows->isEmpty()) {
            return ['error' => true, 'message' => '没有找到可修改的汇报，请换一个日期或类型'];
        }
        if (! $takeLatest && $rows->count() > 1) {
            return [
                'error'      => true,
                'message'    => '匹配到多条汇报，请让用户按日期和摘要选择，不要索要 ID',
                'candidates' => $rows->map(fn (UserDaily $daily): array => [
                    'id'         => (int) $daily->daily_id,
                    'types'      => $this->reportTypeLabel((int) $daily->types),
                    'created_at' => $this->formatReportTime($daily->created_at),
                    'excerpt'    => $this->reportExcerpt($daily),
                ])->all(),
            ];
        }

        return ['id' => (int) $rows->first()->daily_id];
    }

    /**
     * 解析定位用的汇报类型。未传返回 null，无法识别返回 false.
     */
    private function parseReportTypes(mixed $value): int|false|null
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = trim((string) $value);

        return match ($text) {
            '0', '日报' => 0,
            '1', '周报' => 1,
            '2', '月报' => 2,
            '3', '汇报' => 3,
            default     => false,
        };
    }

    private function reportTypeLabel(int $types): string
    {
        return match ($types) {
            0       => '日报',
            1       => '周报',
            2       => '月报',
            3       => '汇报',
            default => '汇报',
        };
    }

    private function formatReportTime(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string) $value);
    }

    private function reportExcerpt(UserDaily $daily): string
    {
        $mark = trim((string) $daily->mark);
        if ($mark !== '') {
            return mb_substr($mark, 0, 40);
        }
        $finish = $daily->finish;
        if (is_array($finish) && isset($finish[0])) {
            return mb_substr(trim((string) $finish[0]), 0, 40);
        }

        return '';
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function hasMutableField(array $arguments): bool
    {
        foreach (['finish', 'plan', 'mark', 'remark', 'members', 'attach_ids'] as $field) {
            if (array_key_exists($field, $arguments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取第一个已传入的字符串字段；都未传时返回 null.
     *
     * @param array<string, mixed> $arguments
     * @param array<int, string> $keys
     */
    private function optionalString(array $arguments, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $arguments)) {
                return (string) $arguments[$key];
            }
        }

        return null;
    }
}
