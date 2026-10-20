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
use App\Http\Service\Schedule\ScheduleService;
use App\Mcp\Tools\Abstract\BaseTool;
use Throwable;

/**
 * 批量创建日程 MCP 写工具：确认后一次创建多条，部分失败不回滚已成功项；缺省分类为个人提醒.
 */
class ScheduleBatchCreateTool extends BaseTool
{
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
        return '批量创建日程。创建人固定为当前 MCP 用户。'
            . '必填 items[]，每项需 title、start_time、end_time；'
            . 'cid 为日程分类 ID，缺省为个人提醒；可选 link_id（考核指标 ID）、content、all_day。'
            . 'remind：仅当用户明确说明提醒时机时传对应档位；未说明则不提醒（-1）。'
            . '部分失败不回滚已成功项，返回 created 与 failed。';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['items'],
            'properties' => [
                'items' => [
                    'type'        => 'array',
                    'description' => '待创建日程列表',
                    'items'       => [
                        'type'       => 'object',
                        'required'   => ['title', 'start_time', 'end_time'],
                        'properties' => [
                            'title'      => ['type' => 'string', 'description' => '日程标题'],
                            'content'    => ['type' => 'string', 'description' => '日程内容/备注'],
                            'start_time' => ['type' => 'string', 'description' => '开始时间 Y-m-d H:i:s'],
                            'end_time'   => ['type' => 'string', 'description' => '结束时间 Y-m-d H:i:s'],
                            'cid'        => [
                                'type'        => 'integer',
                                'description' => '日程分类 ID（缺省为个人提醒，勿擅自改用汇报待办等业务类型）',
                                'default'     => ScheduleEnum::TYPE_PERSONAL,
                            ],
                            'link_id'    => ['type' => 'integer', 'description' => '关联业务 ID，可填考核指标 ID'],
                            'all_day'    => ['type' => 'integer', 'description' => '是否全天：0/1', 'default' => 0],
                            'remind'     => [
                                'type'        => 'integer',
                                'description' => '提前提醒档位：-1=不提醒（用户未说明提醒时默认），0=开始时，1=提前5分钟，2=提前15分钟，'
                                    . '3=提前30分钟，4=提前1小时，5=提前2小时，6=提前1天，7=提前2天，8=提前1周；'
                                    . '仅用户明确说明提醒时机时传对应档位，未说明则传 -1 或不传',
                                'default'     => -1,
                            ],
                            'member'     => [
                                'type'        => 'array',
                                'items'       => ['type' => 'integer'],
                                'description' => '参与人用户 ID，缺省含创建人',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 批量创建日程并返回成功/失败明细.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array
    {
        $items = $arguments['items'] ?? null;
        if (! is_array($items) || $items === []) {
            return [
                'error'   => true,
                'message' => 'items 不能为空',
                'field'   => 'items',
            ];
        }

        $userId  = $this->getUserDbId();
        $entId   = $this->getEntId();
        $service = app(ScheduleService::class);
        $created = [];
        $failed  = [];

        foreach (array_values($items) as $index => $item) {
            if (! is_array($item)) {
                $failed[] = [
                    'index'  => $index,
                    'title'  => '',
                    'reason' => '日程项格式无效',
                ];
                continue;
            }

            $title     = trim((string) ($item['title'] ?? ''));
            $startTime = trim((string) ($item['start_time'] ?? ''));
            $endTime   = trim((string) ($item['end_time'] ?? ''));
            if ($title === '') {
                $failed[] = ['index' => $index, 'title' => $title, 'reason' => '缺少日程标题'];
                continue;
            }
            if ($startTime === '' || $endTime === '') {
                $failed[] = ['index' => $index, 'title' => $title, 'reason' => '缺少开始或结束时间'];
                continue;
            }

            $members = array_values(array_filter(array_map('intval', (array) ($item['member'] ?? []))));
            if ($userId > 0 && ! in_array($userId, $members, true)) {
                $members[] = $userId;
            }

            $cid = ScheduleTypeListTool::resolveCreatableCid($userId, (int) ($item['cid'] ?? 0));

            $data = [
                'title'      => $title,
                'content'    => (string) ($item['content'] ?? ''),
                'cid'        => $cid,
                'member'     => $members,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'all_day'    => (int) ($item['all_day'] ?? 0),
                'remind'     => $this->resolveRemindLevel($item),
                'period'     => (int) ($item['period'] ?? 0),
                'rate'       => max(1, (int) ($item['rate'] ?? 1)),
                'days'       => (array) ($item['days'] ?? []),
                'fail_time'  => $item['fail_time'] ?? null,
                'link_id'    => (int) ($item['link_id'] ?? 0),
            ];

            try {
                $id = $service->saveSchedule($userId, $entId, $data);
                $created[] = [
                    'id'    => is_int($id) ? $id : 0,
                    'title' => $title,
                    'cid'   => $cid,
                    'link_id' => (int) $data['link_id'],
                ];
            } catch (Throwable $e) {
                $failed[] = [
                    'index'  => $index,
                    'title'  => $title,
                    'reason' => $e->getMessage() !== '' ? $e->getMessage() : '创建失败',
                ];
            }
        }

        $createdCount = count($created);
        $failedCount  = count($failed);
        // 任一创建成功即视为有效写入（部分失败不回滚）；全失败才走写契约失败分支.
        $firstId = (int) ($created[0]['id'] ?? 0);

        $payload = [
            'success' => $createdCount > 0,
            'partial' => $createdCount > 0 && $failedCount > 0,
            'created' => $created,
            'failed'  => $failed,
            'stats'   => [
                'created_count' => $createdCount,
                'failed_count'  => $failedCount,
            ],
            'message' => $createdCount === 0
                ? '全部日程创建失败'
                : ($failedCount > 0
                    ? sprintf('已创建 %d 条，失败 %d 条', $createdCount, $failedCount)
                    : sprintf('已创建 %d 条日程', $createdCount)),
        ];
        if ($firstId > 0) {
            $payload['id'] = $firstId;
        }

        return $payload;
    }

    /**
     * 解析提前提醒档位：未传或空值视为不提醒；用户明确说明时使用对应档位.
     *
     * @param array<string, mixed> $item 单条日程入参
     */
    private function resolveRemindLevel(array $item): int
    {
        if (! array_key_exists('remind', $item) || $item['remind'] === null || $item['remind'] === '') {
            return -1;
        }

        return (int) $item['remind'];
    }
}
