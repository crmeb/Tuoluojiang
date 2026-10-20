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
use App\Http\Service\Frame\FrameAssistService;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Mcp\Tools\Traits\FiltersPersonnelOutput;

/**
 * 直属下级工具
 * Class MySubordinatesTool.
 */
class MySubordinatesTool extends BaseTool
{
    use FiltersPersonnelOutput;

    public function getDescription(): string
    {
        return '获取当前用户的直属下级列表';
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
        $service = app(AdminService::class);
        $frameAssistService = app(FrameAssistService::class);
        $userDbId = $this->getUserDbId();

        // 使用标准权限逻辑获取直属下级
        $subordinateUids = $frameAssistService->getScopeUid($userDbId, 'sub', true);

        if (empty($subordinateUids)) {
            return ['list' => [], 'total' => 0];
        }

        // 获取下级用户信息
        $where = ['entid' => 1, 'status' => 1, 'ids' => $subordinateUids, 'types' => [1, 2, 3, 4]];
        return $this->filterPersonnelListResult($service->adminList($where, 1, 100));
    }
}
