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

namespace App\Mcp\Tools\Personnel;

use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\FiltersPersonnelOutput;

/**
 * 人员列表工具
 * Class PersonnelListTool.
 */
class PersonnelListTool extends BaseTool
{
    use FiltersPersonnelOutput;

    public function getDescription(): string
    {
        return '查询人员列表（基于数据权限过滤）';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'page'     => ['type' => 'integer', 'description' => '页码，默认1', 'default' => 1],
                'limit'    => ['type' => 'integer', 'description' => '每页数量，默认20', 'default' => 20],
                'keyword'  => ['type' => 'string', 'description' => '搜索关键词（姓名、手机号）'],
                'frame_id' => ['type' => 'integer', 'description' => '部门ID筛选'],
                'types'    => ['type' => 'string', 'description' => '人员类型筛选，用逗号分隔如:1,2,3'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $service      = app(AdminService::class);
        $frameService = app(FrameService::class);

        $types = $arguments['types'] ?? [];
        if (is_string($types)) {
            $types = array_map('intval', explode(',', $types));
        }

        $page  = max(1, (int) ($arguments['page'] ?? 1));
        $limit = min(100, max(1, (int) ($arguments['limit'] ?? 20)));

        // 获取数据权限范围内的用户ID
        $allowedUids = $this->getDataUids('personnel', 1);

        // 构建基础条件
        $where = ['entid' => 1, 'status' => 1, 'types' => $types ?: [1, 2, 3], 'time' => ''];

        // 如果非管理员，添加数据权限过滤
        if (! $this->isAdmin() && ! empty($allowedUids)) {
            $where['ids'] = $allowedUids;
        }

        if (! empty($arguments['keyword'])) {
            $where['search'] = trim((string) $arguments['keyword']);
        }

        if (! empty($arguments['frame_id'])) {
            $frameIds           = $frameService->column(['path' => $arguments['frame_id']], 'id') ?? [];
            $frameIds[]         = $arguments['frame_id'];
            $where['frame_ids'] = $frameIds;
        }

        return $this->filterPersonnelListResult($service->adminList($where, $page, $limit));
    }
}
