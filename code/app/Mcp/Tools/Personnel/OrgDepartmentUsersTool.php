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
 * 部门人员列表工具
 * Class OrgDepartmentUsersTool.
 */
class OrgDepartmentUsersTool extends BaseTool
{
    use FiltersPersonnelOutput;

    public function getDescription(): string
    {
        return '获取部门下的人员列表（基于数据权限过滤）';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'frame_id' => ['type' => 'integer', 'description' => '部门ID'],
                'page' => ['type' => 'integer', 'description' => '页码，默认1', 'default' => 1],
                'limit' => ['type' => 'integer', 'description' => '每页数量，默认20', 'default' => 20],
                'recursive' => ['type' => 'boolean', 'description' => '是否包含子部门人员', 'default' => false],
            ],
            'required' => ['frame_id'],
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(AdminService::class);
        $frameId = $arguments['frame_id'] ?? 0;
        $page = max(1, (int) ($arguments['page'] ?? 1));
        $limit = min(100, max(1, (int) ($arguments['limit'] ?? 20)));
        $recursive = $arguments['recursive'] ?? false;

        $where = ['entid' => 1, 'status' => 1, 'types' => [1, 2, 3, 4]];
        $allowedUids = $this->isAdmin() ? [] : $this->getDataUids('personnel', 1);
        if (! $this->isAdmin()) {
            $where['ids'] = $allowedUids ?: [-1];
        }
        if ($recursive) {
            $frameService = app(FrameService::class);
            $frameIds = $frameService->column(['path' => $frameId], 'id') ?? [];
            $frameIds[] = $frameId;
            $where['frame_ids'] = $frameIds;
        } else {
            $where['frame_id'] = $frameId;
        }

        return $this->filterPersonnelListResult($service->adminList($where, $page, $limit));
    }
}
