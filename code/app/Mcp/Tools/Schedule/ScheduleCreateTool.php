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

/**
 * 创建日程 MCP 写工具：创建人固定为当前 MCP 用户，缺省日程类型为个人提醒.
 */
class ScheduleCreateTool extends BaseTool
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
        return '创建日程（会议/提醒/待办）。创建人固定为当前 MCP 用户。'
            . '必填：title、start_time、end_time；cid 为日程类型 ID（可先调 schedule_type_list，缺省为个人提醒）。'
            . 'member 为参与人用户 ID 列表，缺省自动加入创建人。'
            . 'remind：仅当用户明确说明提醒时机时传对应档位；未说明则不提醒（-1）。';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['title', 'start_time', 'end_time'],
            'properties' => [
                'title'      => [
                    'type'          => 'string',
                    'description'   => '日程标题',
                    'x-ai-aliases'  => ['标题', '主题', '事项'],
                ],
                'content'    => [
                    'type'          => 'string',
                    'description'   => '日程内容/备注',
                    'x-ai-aliases'  => ['内容', '备注', '说明'],
                ],
                'cid'        => [
                    'type'        => 'integer',
                    'description' => '日程分类 ID（schedule_type_list 返回的 id；缺省为个人提醒，勿擅自改用汇报待办等业务类型）',
                    'default'     => ScheduleEnum::TYPE_PERSONAL,
                ],
                'member'     => [
                    'type'        => 'array',
                    'items'       => ['type' => 'integer'],
                    'description' => '参与人用户数据库 ID 列表；缺省自动包含创建人',
                ],
                'start_time' => [
                    'type'         => 'string',
                    'description'  => '开始时间（日期时间，如 2026-09-03 09:00:00）',
                    'x-ai-aliases' => ['开始时间', '起始时间'],
                    'x-ai-normalizer' => 'datetime',
                ],
                'end_time'   => [
                    'type'         => 'string',
                    'description'  => '结束时间（日期时间，如 2026-09-03 10:00:00）',
                    'x-ai-aliases' => ['结束时间', '截止时间'],
                    'x-ai-normalizer' => 'datetime',
                ],
                'all_day'    => ['type' => 'integer', 'description' => '是否全天：0=否，1=是', 'default' => 0],
                'remind'     => [
                    'type'        => 'integer',
                    'description' => '提前提醒档位：-1=不提醒（用户未说明提醒时默认），0=开始时，1=提前5分钟，2=提前15分钟，'
                        . '3=提前30分钟，4=提前1小时，5=提前2小时，6=提前1天，7=提前2天，8=提前1周；'
                        . '仅用户明确说明提醒时机时传对应档位，未说明则传 -1 或不传',
                    'default'     => -1,
                ],
                'period'     => ['type' => 'integer', 'description' => '重复方式：0=不重复', 'default' => 0],
                'rate'       => ['type' => 'integer', 'description' => '重复频率', 'default' => 1],
                'days'       => ['type' => 'array', 'description' => '按周/月重复时的天数配置'],
                'fail_time'  => ['type' => 'string', 'description' => '重复结束时间'],
                'link_id'    => ['type' => 'integer', 'description' => '关联业务 ID'],
            ],
        ];
    }

    /**
     * 创建日程并返回新建记录 ID.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array{success?: bool, id?: int, error?: bool, message?: string}
     */
    public function execute(array $arguments): array
    {
        $title     = trim((string) ($arguments['title'] ?? ''));
        $startTime = trim((string) ($arguments['start_time'] ?? ''));
        $endTime   = trim((string) ($arguments['end_time'] ?? ''));
        if ($title === '') {
            return ['error' => true, 'message' => '缺少日程标题', 'field' => 'title'];
        }
        if ($startTime === '' || $endTime === '') {
            return ['error' => true, 'message' => '缺少日程开始或结束时间', 'field' => $startTime === '' ? 'start_time' : 'end_time'];
        }

        $userId  = $this->getUserDbId();
        $members = array_values(array_filter(array_map('intval', (array) ($arguments['member'] ?? []))));
        if ($userId > 0 && ! in_array($userId, $members, true)) {
            $members[] = $userId;
        }

        $cid = ScheduleTypeListTool::resolveCreatableCid($userId, (int) ($arguments['cid'] ?? 0));

        $data = [
            'title'      => $title,
            'content'    => (string) ($arguments['content'] ?? ''),
            'cid'        => $cid,
            'member'     => $members,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'all_day'    => (int) ($arguments['all_day'] ?? 0),
            'remind'     => $this->resolveRemindLevel($arguments),
            'period'     => (int) ($arguments['period'] ?? 0),
            'rate'       => max(1, (int) ($arguments['rate'] ?? 1)),
            'days'       => (array) ($arguments['days'] ?? []),
            'fail_time'  => $arguments['fail_time'] ?? null,
            'link_id'    => (int) ($arguments['link_id'] ?? 0),
        ];

        $savedId = app(ScheduleService::class)->saveSchedule($userId, $this->getEntId(), $data);
        // saveSchedule 新建返回 int ID；更新路径可能返回 bool，故统一兜底.
        $id = is_numeric($savedId) ? (int) $savedId : 0;
        if ($id <= 0) {
            $id = (int) \App\Http\Model\Schedule\Schedule::query()
                ->where('uid', $userId)
                ->where('title', $title)
                ->where('start_time', $startTime)
                ->orderByDesc('id')
                ->value('id');
        }
        if ($id <= 0) {
            return ['error' => true, 'message' => '日程创建失败'];
        }

        $result = ['success' => true, 'id' => $id, 'cid' => $cid, 'title' => $title];
        if ((int) ($arguments['cid'] ?? 0) > 0 && (int) $arguments['cid'] !== $cid) {
            $result['note'] = '所选类型为业务联动日程，不能手动创建，已按个人提醒创建';
        }

        return $result;
    }

    /**
     * 解析提前提醒档位：未传或空值视为不提醒；用户明确说明时使用对应档位.
     *
     * @param array<string, mixed> $arguments 工具入参
     */
    private function resolveRemindLevel(array $arguments): int
    {
        if (! array_key_exists('remind', $arguments) || $arguments['remind'] === null || $arguments['remind'] === '') {
            return -1;
        }

        return (int) $arguments['remind'];
    }
}
