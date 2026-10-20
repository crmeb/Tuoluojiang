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

use App\Http\Service\Report\ReportService;
use App\Mcp\Tools\Abstract\BaseTool;

class ReportCreateTool extends BaseTool
{
    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '新增工作汇报（支持日报/周报/月报/汇报）。通过 types 参数区分：0=日报（默认），1=周报，2=月报，3=汇报。'
            . '提交人固定为当前 MCP 用户；未传 members 时默认直属上级为汇报人，勿向用户索要汇报人 ID。';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['finish', 'plan'],
            'properties' => [
                'finish'     => [
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'description' => '今日/本周/本月已完成工作（每项一条；优先从用户已口述内容整理，勿重复追问）',
                ],
                'plan'       => [
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'description' => '明日/下周/下月工作计划（每项一条；仅当用户未说明计划时再追问）',
                ],
                'mark'       => ['type' => 'string', 'description' => '备注'],
                'types'      => [
                    'type'        => 'integer',
                    'description' => '汇报类型：0=日报（默认），1=周报，2=月报，3=汇报',
                    'enum'        => [0, 1, 2, 3],
                    'default'     => 0,
                ],
                'members'    => [
                    'type'        => 'array',
                    'items'       => ['type' => 'integer'],
                    'description' => '汇报对象用户库 ID；未传或为空时默认直属上级，勿向用户索要',
                ],
                'attach_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '附件 ID 列表'],
            ],
        ];
    }

    /**
     * 创建工作汇报，返回写工具约定结果（success + id）.
     *
     * @param array<string, mixed> $arguments 工具入参
     *
     * @return array{success?: bool, id?: int, daily_id?: int, types?: int, error?: bool, message?: string}
     */
    public function execute(array $arguments): array
    {
        $uid = (string) ($this->getUserInfo()['uid'] ?? '');
        if ($uid === '') {
            return ['error' => true, 'message' => '无法识别当前用户，请重新登录后再试'];
        }

        $data = [
            'plan'       => (array) ($arguments['plan'] ?? []),
            'finish'     => (array) ($arguments['finish'] ?? []),
            'mark'       => (string) ($arguments['mark'] ?? ''),
            'status'     => 0,
            'types'      => (int) ($arguments['types'] ?? 0),
            'uid'        => $uid,
            'entid'      => $this->getEntId(),
            'attach_ids' => (array) ($arguments['attach_ids'] ?? []),
            'members'    => (array) ($arguments['members'] ?? []),
        ];

        $res = app(ReportService::class)->resourceSave($data);
        $id  = is_object($res) ? (int) ($res->daily_id ?? $res->id ?? 0) : (int) $res;
        if ($id <= 0) {
            return ['error' => true, 'message' => '工作汇报创建失败'];
        }

        return [
            'success'  => true,
            'id'       => $id,
            'daily_id' => $id,
            'types'    => (int) ($data['types'] ?? 0),
        ];
    }
}
