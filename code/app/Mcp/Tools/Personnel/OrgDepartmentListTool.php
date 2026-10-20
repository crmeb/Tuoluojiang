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
 * 部门列表工具
 * Class OrgDepartmentListTool.
 */
class OrgDepartmentListTool extends BaseTool
{
    public function getDescription(): string
    {
        return '获取部门列表（基于数据权限过滤）';
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
        $where = ['is_show' => 1, 'entid' => 1];
        if (! $this->isAdmin()) {
            [$frameIds] = $this->getMcpRequest()->getDataFrames(1);
            if (! $frameIds) {
                return [];
            }
            $where['ids'] = array_map('intval', $frameIds);
        }

        return $service->getList($where);
    }
}
