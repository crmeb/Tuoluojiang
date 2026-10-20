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

use App\Http\Service\Schedule\ScheduleService;
use App\Mcp\Tools\Abstract\BaseTool;

/**
 * 日程工作汇总 MCP 读工具：按日/周/月返回完成与待办；大列表附带标准 pageable 信封供会话表格翻页.
 */
class ScheduleWorkSummaryTool extends BaseTool
{
    private const COLUMNS = [
        'title'            => '标题',
        'start'            => '开始时间',
        'end'              => '结束时间',
        'status_text'      => '状态',
        'repeat_text'      => '重复说明',
        'occurrence_count' => '周期内次数',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return '按日/周/月汇总我的日程完成与待办。'
            . '循环日程按主记录计 1 条，并用 repeat_text/occurrence_count 标明本周期展开次数，禁止把每次重复实例当成多条日程。'
            . '用于绩效自评、日报周报月报素材，以及列举未完成日程。'
            . 'period 必填：day|week|month；date 锚点日期（默认今天）。'
            . '列举待办用 focus=pending；默认只返回一页（limit=10），结果含 items/total/pageable。';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['period'],
            'properties' => [
                'period' => [
                    'type'        => 'string',
                    'description' => '汇总周期：day=日，week=周，month=月',
                    'enum'        => ['day', 'week', 'month'],
                ],
                'date' => [
                    'type'        => 'string',
                    'description' => '锚点日期 Y-m-d，默认今天',
                ],
                'focus' => [
                    'type'        => 'string',
                    'description' => '返回范围：all=完成+待办；pending=仅待办；completed=仅已完成；stats=仅统计',
                    'enum'        => ['all', 'pending', 'completed', 'stats'],
                    'default'     => 'all',
                ],
                'page' => [
                    'type'        => 'integer',
                    'description' => '页码，从 1 起',
                    'default'     => 1,
                    'minimum'     => 1,
                ],
                'limit' => [
                    'type'        => 'integer',
                    'description' => '每页条数，默认 10，最大 100',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ],
            ],
        ];
    }

    /**
     * 汇总当前 MCP 用户在指定周期内的日程完成与待办，并附带 pageable 信封.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array
    {
        $period = strtolower(trim((string) ($arguments['period'] ?? '')));
        if (! in_array($period, ['day', 'week', 'month'], true)) {
            return [
                'error'   => true,
                'message' => 'period 必须为 day、week 或 month',
                'field'   => 'period',
            ];
        }

        $focus = strtolower(trim((string) ($arguments['focus'] ?? 'all')));
        if ($focus === '') {
            $focus = 'all';
        }
        if (! in_array($focus, ['all', 'pending', 'completed', 'stats'], true)) {
            return [
                'error'   => true,
                'message' => 'focus 必须为 all、pending、completed 或 stats',
                'field'   => 'focus',
            ];
        }

        $date = trim((string) ($arguments['date'] ?? ''));
        if ($date === '') {
            $date = null;
        }

        $page  = max(1, (int) ($arguments['page'] ?? 1));
        $limit = (int) ($arguments['limit'] ?? 10);
        if ($limit < 1) {
            $limit = 10;
        }

        $summary = app(ScheduleService::class)->workSummary(
            $this->getUserDbId(),
            $period,
            $date,
            $focus,
            $page,
            $limit
        );

        return $this->withPageableEnvelope($summary, $period, $date, $focus, $page, $limit);
    }

    /**
     * 将汇总结果投影为 items/total/pageable，供模型首屏列举与会话表格翻页.
     *
     * @param array<string, mixed> $summary
     *
     * @return array<string, mixed>
     */
    private function withPageableEnvelope(
        array $summary,
        string $period,
        ?string $date,
        string $focus,
        int $page,
        int $limit
    ): array {
        $listFocus = $focus === 'completed' ? 'completed' : 'pending';
        if ($focus === 'stats') {
            $summary['items'] = [];
            $summary['total'] = 0;

            return $summary;
        }

        $bucket = $listFocus === 'completed'
            ? (array) ($summary['completed'] ?? [])
            : (array) ($summary['pending'] ?? []);
        $total = $listFocus === 'completed'
            ? (int) ($summary['stats']['completed_count'] ?? 0)
            : (int) ($summary['stats']['pending_count'] ?? 0);

        $items = [];
        foreach ($bucket as $row) {
            if (! is_array($row)) {
                continue;
            }
            $status = (int) ($row['status'] ?? -1);
            $items[] = [
                'title'            => (string) ($row['title'] ?? ''),
                'start'            => (string) ($row['start'] ?? ''),
                'end'              => (string) ($row['end'] ?? ''),
                'status_text'      => $status === 3 ? '已完成' : '待处理',
                'repeat_text'      => (string) ($row['repeat_text'] ?? ''),
                'occurrence_count' => (int) ($row['occurrence_count'] ?? 1),
            ];
        }

        $summary['items'] = $items;
        $summary['total'] = $total;

        if ($total > count($items)) {
            $anchor = $date;
            if ($anchor === null || $anchor === '') {
                $start = (string) ($summary['start'] ?? '');
                $anchor = $start !== '' ? substr($start, 0, 10) : null;
            }
            $summary['pageable'] = [
                'tool'      => $this->getName(),
                'arguments' => array_filter([
                    'period' => $period,
                    'date'   => $anchor,
                    'focus'  => $listFocus,
                ], static fn ($v) => $v !== null && $v !== ''),
                'columns'   => self::COLUMNS,
            ];
        }

        return $summary;
    }
}
