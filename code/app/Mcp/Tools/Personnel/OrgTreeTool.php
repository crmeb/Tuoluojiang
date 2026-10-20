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

use App\Mcp\Tools\Abstract\BaseTool;
use App\Http\Service\Frame\FrameService;

/**
 * 组织架构树工具
 * Class OrgTreeTool.
 */
class OrgTreeTool extends BaseTool
{
    public function getDescription(): string
    {
        return '获取组织架构树（基于数据权限，只返回有权限的部门）';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
        ];
    }

    public function execute(array $arguments): array
    {
        $service = app(FrameService::class);
        $tree = $service->tree();
        if ($this->isAdmin()) {
            return $tree;
        }

        [$frameIds] = $this->getMcpRequest()->getDataFrames(1);
        if (! $frameIds) {
            return [];
        }

        return $this->filterTree((array) $tree, array_map('intval', $frameIds));
    }

    protected function filterTree(array $nodes, array $frameIds): array
    {
        $result = [];
        foreach ($nodes as $node) {
            $children = $this->filterTree((array) ($node['children'] ?? []), $frameIds);
            if (in_array((int) ($node['id'] ?? 0), $frameIds, true) || $children) {
                $node['children'] = $children;
                $result[] = $node;
            }
        }
        return $result;
    }
}
