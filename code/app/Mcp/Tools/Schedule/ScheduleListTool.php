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

use App\Constants\ModuleEnum;
use App\Http\Service\Schedule\ScheduleService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;
use Illuminate\Support\Carbon;

class ScheduleListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取日程列表';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'user_id' => ['type' => 'integer', 'description' => '日程所属用户ID'],
                'cid' => ['type' => 'array', 'description' => '日程分类ID列表', 'items' => ['type' => 'integer']],
                'period' => ['type' => 'integer', 'description' => '是否包含周期日程：1=包含，0=不包含'],
                'date' => ['type' => 'string', 'description' => '日期筛选'],
                'start_date' => ['type' => 'string', 'description' => '开始日期'],
                'end_date' => ['type' => 'string', 'description' => '结束日期'],
                'page' => ['type' => 'integer', 'description' => '页码', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '每页数量', 'default' => 20],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::SCHEDULE);
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        $userId = ! empty($authorized['has_filter'])
            ? (int) ($authorized['user_ids'][0] ?? 0)
            : (int) ($arguments['user_id'] ?? $this->getUserDbId());

        $date  = $arguments['date'] ?? '';
        $start = $arguments['start_date'] ?? '';
        $end   = $arguments['end_date'] ?? '';

        if ($date) {
            $start = Carbon::parse($date)->startOfDay()->toDateTimeString();
            $end   = Carbon::parse($date)->endOfDay()->toDateTimeString();
        } else {
            $start = $start ? Carbon::parse($start)->startOfDay()->toDateTimeString() : now()->startOfMonth()->toDateTimeString();
            $end   = $end ? Carbon::parse($end)->endOfDay()->toDateTimeString() : now()->endOfMonth()->toDateTimeString();
        }

        return app(ScheduleService::class)->scheduleList(
            $userId ?: $this->getUserDbId(),
            1,
            $start,
            $end,
            $this->intArray($arguments['cid'] ?? []),
            (int) ($arguments['period'] ?? 1)
        );
    }
}
