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

namespace App\Mcp\Tools\Approve;

use App\Http\Service\Approve\ApproveHolidayTypeService;
use App\Mcp\Tools\Abstract\BaseTool;

class ApproveHolidayTypeListTool extends BaseTool
{
    public function getDescription(): string
    {
        return '获取当前用户可选的假期类型（事假/年假等）。请假审批填写 form.holidayType 前必须调用。返回 value=假期类型ID，label=名称，duration_type：0=按天，1=按小时。';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'type'        => 'string',
                    'description' => '按名称筛选，如“事假”',
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $list = app(ApproveHolidayTypeService::class)->getSelectList($this->getUserDbId());
        $name = trim((string) ($arguments['name'] ?? ''));

        if ($name !== '') {
            $list = array_values(array_filter($list, static fn (array $item): bool => str_contains((string) ($item['label'] ?? ''), $name)));
        }

        $items = array_map(static function (array $item): array {
            $durationType = (int) ($item['duration_type'] ?? 0);

            return [
                'id'                 => (int) ($item['value'] ?? 0),
                'value'              => (int) ($item['value'] ?? 0),
                'label'              => (string) ($item['label'] ?? ''),
                'duration_type'      => $durationType,
                'duration_type_label'=> $durationType === 1 ? '按小时' : '按天',
            ];
        }, $list);

        return [
            'list'  => $items,
            'total' => count($items),
            'hint'  => '请假时长 leaveDuration 按天示例：{"timeType":"day","dateStart":"YYYY-MM-DD","dateEnd":"YYYY-MM-DD","timeStart":1,"timeEnd":0,"duration":2}；timeStart/timeEnd：1=上午，0=下午。duration 按自然日闭区间由起止自动计算（同日=1天，隔日=2天），勿按结束−开始手算。按小时则 timeType=hour，dateStart/dateEnd 为完整日期时间。',
        ];
    }
}
