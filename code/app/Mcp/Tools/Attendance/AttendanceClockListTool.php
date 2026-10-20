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
use App\Http\Service\Attendance\AttendanceClockService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class AttendanceClockListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取打卡记录列表';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'user_id' => ['type' => 'integer', 'description' => '用户ID，不传则查询当前用户'],
                'frame_id' => ['type' => 'integer', 'description' => '部门ID'],
                'group_id' => ['type' => 'integer', 'description' => '考勤组ID'],
                'scope' => ['type' => 'integer', 'description' => '人员范围：1=正式员工，其他=非正式员工'],
                'date' => ['type' => 'string', 'description' => '日期筛选，格式：YYYY-MM-DD'],
                'start_date' => ['type' => 'string', 'description' => '开始日期'],
                'end_date' => ['type' => 'string', 'description' => '结束日期'],
                'page' => ['type' => 'integer', 'description' => '页码', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '每页数量', 'default' => 20],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $this->applyPage($arguments);
        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::ATTENDANCE);
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        if (! empty($authorized['has_filter'])) {
            $arguments['user_id'] = $authorized['user_ids'];
        }

        $where = $this->onlyFilled($arguments, [
            'user_id' => 'uid',
            'frame_id',
            'group_id',
            'scope',
        ]);
        $where['time'] = $arguments['date'] ?? ($this->dateRange($arguments) ?: 'today');
        if (empty($where['uid'])) {
            $where['uid'] = $this->getUserDbId();
        }

        return app(AttendanceClockService::class)->getList($where);
    }
}
