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
use App\Http\Service\Attendance\AttendanceApplyRecordService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class AttendanceApplyListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取请假/补卡申请列表';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'types' => ['type' => 'integer', 'description' => '申请类型'],
                'user_id' => ['type' => 'integer', 'description' => '申请人用户ID'],
                'date' => ['type' => 'string', 'description' => '申请日期，YYYY-MM-DD'],
                'month' => ['type' => 'string', 'description' => '申请月份，YYYY-MM'],
                'page' => ['type' => 'integer', 'description' => '页码', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '每页数量', 'default' => 20],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        [$page, $limit] = $this->applyPage($arguments);
        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::ATTENDANCE);
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        if (! empty($authorized['has_filter'])) {
            $arguments['user_id'] = $authorized['user_ids'];
        }
        $where = $this->onlyFilled($arguments, [
            'types' => 'apply_type',
            'user_id' => 'uid',
            'date',
            'month',
        ]);

        $service = app(AttendanceApplyRecordService::class);
        $list    = $service->select($where, ['*'], [], $page, $limit)?->toArray() ?? [];

        return [
            'list'  => $list,
            'count' => $service->count($where),
        ];
    }
}
