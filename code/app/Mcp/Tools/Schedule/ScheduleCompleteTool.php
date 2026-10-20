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

class ScheduleCompleteTool extends BaseTool
{
    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '将日程标记为已完成，操作人固定为当前 MCP 用户';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['id', 'start_time', 'end_time'],
            'properties' => [
                'id'         => ['type' => 'integer', 'description' => '日程ID'],
                'start_time' => ['type' => 'string', 'description' => '日程实例开始时间（周期日程必填）'],
                'end_time'   => ['type' => 'string', 'description' => '日程实例结束时间（周期日程必填）'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $id = (int) ($arguments['id'] ?? 0);
        if ($id <= 0) {
            return ['error' => true, 'message' => '缺少日程ID'];
        }

        $startTime = trim((string) ($arguments['start_time'] ?? ''));
        $endTime   = trim((string) ($arguments['end_time'] ?? ''));
        if ($startTime === '' || $endTime === '') {
            return ['error' => true, 'message' => '缺少日程开始或结束时间'];
        }

        app(ScheduleService::class)->updateStatus(
            $id,
            $this->getUserDbId(),
            $this->getEntId(),
            3,
            [$startTime, $endTime]
        );

        return ['success' => true, 'id' => $id, 'status' => 3];
    }
}
