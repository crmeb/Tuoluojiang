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
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Assess\AssessService;
use App\Mcp\Tools\Traits\InteractsWithService;
use App\Mcp\Tools\Traits\ResolvesTargetUserArguments;

class AssessListTool extends BaseTool
{
    use InteractsWithService;
    use ResolvesTargetUserArguments;

    public function getDescription(): string
    {
        return '获取绩效列表（绩效考核任务/考核结果，不是销售回款业绩）';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => array_merge([
                'period' => ['type' => 'string', 'description' => '考核周期'],
                'frame_id' => ['type' => 'string', 'description' => '部门ID'],
                'status' => ['type' => 'string', 'description' => '状态'],
                'test_uid' => ['type' => 'array', 'description' => '被考核人'],
                'check_uid' => ['type' => 'array', 'description' => '考核人'],
                'time' => ['type' => 'string', 'description' => '时间范围'],
                'start_date' => ['type' => 'string', 'description' => '开始日期，YYYY-MM-DD'],
                'end_date' => ['type' => 'string', 'description' => '结束日期，YYYY-MM-DD'],
                'types' => ['type' => 'integer', 'description' => '类型'],
                'handle' => ['type' => 'integer', 'description' => '待处理筛选：1=待处理'],
                'page' => ['type' => 'integer', 'description' => '页码，默认1'],
                'limit' => ['type' => 'integer', 'description' => '每页数量，默认20，最大100'],
            ], $this->targetUserSchemaProperties()),
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(AssessService::class);
        $userId = $this->getUserId();
        $this->applyPage($arguments);
        $types = (int) ($arguments['types'] ?? 0);
        $allowedUids = $this->isAdmin() ? [] : $this->getDataUids('assess', 1);

        $where = $this->onlyFilled($arguments, [
            'period',
            'frame_id',
            'status',
            'handle',
        ]);
        $where['handle'] = $where['handle'] ?? '';
        if ($time = $this->timeRange($arguments)) {
            $where['time'] = $time;
        }
        $where['types'] = $types;

        $authorized = $this->resolveAuthorizedUserIds($arguments, ModuleEnum::ASSESS, 'test_uid');
        if (! ($authorized['success'] ?? false)) {
            return $authorized['error'];
        }
        $testUid = ! empty($authorized['has_filter'])
            ? array_map('intval', $authorized['user_ids'])
            : array_map('intval', (array) ($arguments['test_uid'] ?? []));
        if ($testUid) {
            $where['test_uid'] = $allowedUids ? array_values(array_intersect($testUid, $allowedUids)) : $testUid;
        } elseif ($types !== 0) {
            // 与 AdminApi withScopeFrame(all) 对齐：管理员=全部在职，非管理员=数据权限（空则本人）
            $where['test_uid'] = $this->isAdmin()
                ? array_map('intval', app(AdminService::class)->column(['status' => 1], 'id'))
                : ($allowedUids ?: [$userId]);
        }
        if (! empty($arguments['check_uid'])) {
            $where['check_uid'] = array_map('intval', (array) $arguments['check_uid']);
        }

        return $service->getAssessList($userId, $where, $types);
    }
}
