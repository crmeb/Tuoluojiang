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

use App\Constants\ModuleEnum;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Report\ReportService;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class ReportSubmitStatisticsTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '统计一段时间内汇报提交人数。返回 submitted_count（已提交人数）、missing_count（未提交人数）、employee_count（应统计在职人数）。'
            . '人数只看这三个字段；不要把日历上的是否缺交标记当成未提交人数。submitted_count 为 0 表示该时段没有任何提交，禁止说全员已提交。';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'uuid' => ['type' => 'string', 'description' => '用户UUID'],
                'type' => ['type' => 'integer', 'description' => '类型'],
                'types' => ['type' => 'integer', 'description' => '汇报类型：0=日报，1=周报，2=月报，3=汇报'],
                'time' => ['type' => 'string', 'description' => '时间范围'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'uid' => ['type' => 'array', 'description' => '用户ID列表别名'],
                'user_ids' => ['type' => 'array', 'description' => '用户ID列表'],
                'user_id' => ['type' => 'array', 'description' => '用户ID列表别名'],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(ReportService::class);
        $authorized = $this->resolveAuthorizedUserIds(
            ['target_user' => $arguments['target_user'] ?? null, 'user_id' => $arguments['user_ids'] ?? $arguments['user_id'] ?? $arguments['uid'] ?? []],
            ModuleEnum::REPORT
        );
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        $requested = ! empty($authorized['has_filter'])
            ? $authorized['user_ids']
            : $this->intArray($arguments['user_ids'] ?? $arguments['user_id'] ?? $arguments['uid'] ?? []);
        $allowed   = $this->isAdmin() ? [] : $this->getDataUids('report', 1);
        $userIds   = $requested
            ? ($allowed ? array_values(array_intersect(array_map('intval', $requested), $allowed)) : array_map('intval', $requested))
            : ($this->isAdmin() ? app(AdminService::class)->column(['status' => 1], 'id') : ($allowed ?: [-1]));

        $uuid = $arguments['uuid'] ?? ($this->getUserInfo()['uid'] ?? '');
        $time = $this->timeRange($arguments);
        $type = (int) ($arguments['type'] ?? 0);
        $types = (int) ($arguments['types'] ?? 0);
        $reportTypes = $type === 1 ? 0 : (in_array($types, [0, 1, 2, 3], true) ? $types : 0);

        $calendar = $service->submitStatistics((string) $uuid, $type, $types, $time, $userIds);
        $summary  = $service->statistics([
            'types'    => $reportTypes,
            'time'     => $time,
            'user_ids' => $userIds,
        ]);

        return $this->presentCounts(is_array($summary) ? $summary : [], is_array($calendar) ? $calendar : []);
    }

    /**
     * 把日历缺交标记和真实人数拆开，避免模型把 no_submit=0 读成「未提交 0 人、全员已交」。
     *
     * @param array<string, mixed> $summary statistics() 的人数
     * @param array<int, array<string, mixed>> $calendar submitStatistics() 的按日标记
     *
     * @return array<string, mixed>
     */
    private function presentCounts(array $summary, array $calendar): array
    {
        $submitted = (int) ($summary['submit'] ?? 0);
        $missing   = (int) ($summary['no_submit'] ?? 0);
        $total     = (int) ($summary['total'] ?? 0);

        $hint = match (true) {
            $total === 0 => '统计范围内没有在职员工，不能据此说全员已提交。',
            $submitted === 0 => "已提交 0 人，未提交 {$missing} 人。该时段没有任何汇报提交，禁止回答全员已按时提交，也禁止说未提交人数为 0。",
            $missing === 0 => "应统计 {$total} 人，均已提交。",
            default => "应统计 {$total} 人，已提交 {$submitted} 人，未提交 {$missing} 人。",
        };

        $byDay = [];
        foreach ($calendar as $row) {
            if (! is_array($row)) {
                continue;
            }
            $byDay[] = [
                'date'        => (string) ($row['time'] ?? ''),
                'has_missing' => (int) ($row['no_submit'] ?? 0) === 1,
            ];
        }

        return [
            'submitted_count'     => $submitted,
            'missing_count'       => $missing,
            'employee_count'      => $total,
            'all_submitted'       => $total > 0 && $missing === 0 && $submitted === $total,
            'answer_hint'         => $hint,
            'by_day_missing_mark' => $byDay,
            'by_day_note'         => 'has_missing 只表示当天是否有人未交；休息日为 false，不代表已经提交。回答人数时只使用 missing_count 和 submitted_count。',
        ];
    }
}
