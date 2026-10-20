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

namespace App\Mcp\Tools\Assess;

use App\Constants\ModuleEnum;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Http\Service\Assess\AssessService;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class AssessStatisticsTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取绩效统计（绩效考核汇总，不是销售回款业绩）';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'period' => ['type' => 'string', 'description' => '考核周期'],
                'frame_id' => ['type' => 'string', 'description' => '部门ID'],
                'time' => ['type' => 'string', 'description' => '时间范围'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'types' => ['type' => 'integer', 'description' => '统计类型：0=自己，1=下级，2=人事范围'],
                'test_uid' => ['type' => 'array', 'description' => '被考核人ID列表，将自动按绩效模块数据权限取交集'],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(AssessService::class);
        $userId = $this->getUserId();
        $allowedUids = $this->isAdmin() ? [] : $this->getDataUids('assess', 1);
        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::ASSESS, 'test_uid');
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        $requestedUids = ! empty($authorized['has_filter'])
            ? array_map('intval', $authorized['user_ids'])
            : array_map('intval', (array) ($arguments['test_uid'] ?? []));

        $where = $this->onlyFilled($arguments, ['period', 'frame_id']);
        $where['types'] = (int) ($arguments['types'] ?? 0);
        $where['time'] = $this->timeRange($arguments);
        $where['test_uid'] = $requestedUids
            ? ($allowedUids ? array_values(array_intersect($requestedUids, $allowedUids)) : $requestedUids)
            : ($this->isAdmin() ? [] : ($allowedUids ?: [$userId]));

        return $service->getAssessStatistics($where, $userId);
    }
}
