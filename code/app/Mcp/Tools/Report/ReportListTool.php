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

use App\Constants\ModuleEnum;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Http\Service\Report\ReportService;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class ReportListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取汇报列表';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'types' => ['type' => 'integer', 'description' => '汇报类型'],
                'uid' => ['type' => 'array', 'description' => '用户ID列表别名'],
                'user_id' => ['type' => 'array', 'description' => '用户ID列表，将自动按汇报模块数据权限取交集'],
                'user_ids' => ['type' => 'array', 'description' => '用户ID列表别名'],
                'time' => ['type' => 'string', 'description' => '时间范围'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'finish_like' => ['type' => 'string', 'description' => '工作内容关键词'],
                'status' => ['type' => 'integer', 'description' => '状态'],
                'viewer' => ['type' => 'string', 'description' => '查看角色，如 user/hr'],
                'page' => ['type' => 'integer', 'description' => '页码，默认1'],
                'limit' => ['type' => 'integer', 'description' => '每页数量，默认20，最大100'],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(ReportService::class);
        $this->applyPage($arguments);

        $where = $this->onlyFilled($arguments, [
            'types',
            'finish_like',
            'status',
            'viewer',
        ]);
        if ($time = $this->timeRange($arguments)) {
            $where['time'] = $time;
        }
        $authorized = $this->resolveAuthorizedUserIds(
            ['target_user' => $arguments['target_user'] ?? null, 'user_id' => $arguments['user_ids'] ?? $arguments['user_id'] ?? $arguments['uid'] ?? []],
            ModuleEnum::REPORT
        );
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        $userIds = ! empty($authorized['has_filter'])
            ? $authorized['user_ids']
            : $this->intArray($arguments['user_ids'] ?? $arguments['user_id'] ?? $arguments['uid'] ?? []);
        $allowed = $this->isAdmin() ? [] : $this->getDataUids('report', 1);
        $userIds = $userIds
            ? ($allowed ? array_values(array_intersect(array_map('intval', $userIds), $allowed)) : array_map('intval', $userIds))
            : ($this->isAdmin() ? [] : ($allowed ?: [-1]));
        if ($userIds) {
            $where['user_ids'] = $userIds;
        }

        return $service->getList($where, ['*'], ['daily_id' => 'desc'], [
            'user' => fn ($q) => $q->select(['id', 'uid', 'name', 'avatar']),
            'frame',
        ]);
    }
}
