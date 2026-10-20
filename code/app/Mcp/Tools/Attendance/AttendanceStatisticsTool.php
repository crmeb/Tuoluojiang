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

namespace App\Mcp\Tools\Attendance;

use App\Constants\ModuleEnum;
use App\Http\Service\Attendance\AttendancePersonnelStatisticsService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class AttendanceStatisticsTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取考勤统计汇总';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'user_id' => ['type' => 'integer', 'description' => '用户ID'],
                'month' => ['type' => 'string', 'description' => '月份，YYYY-MM（如 2026-09）；也支持 09'],
                'start_date' => ['type' => 'string', 'description' => '开始日期 YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期 YYYY-MM-DD'],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::ATTENDANCE);
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        $userId = ! empty($authorized['has_filter'])
            ? (int) ($authorized['user_ids'][0] ?? 0)
            : (int) ($arguments['user_id'] ?? $this->getUserDbId());
        $time = $arguments['month'] ?? '';
        if (! $time && ! empty($arguments['start_date']) && ! empty($arguments['end_date'])) {
            $time = str_replace('-', '/', (string) $arguments['start_date'])
                . '-' . str_replace('-', '/', (string) $arguments['end_date']);
        }

        return app(AttendancePersonnelStatisticsService::class)->getAttendanceStatistics(
            $this->getUserDbId(),
            $userId ?: $this->getUserDbId(),
            $time !== '' ? (string) $time : 'month'
        );
    }
}
