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

use App\Constants\DataPermissionLevelEnum;
use App\Mcp\Tools\Abstract\BaseTool;
use App\Http\Service\System\ModulePermissionService;

/**
 * 数据权限范围工具
 * Class MyDataScopeTool.
 */
class MyDataScopeTool extends BaseTool
{
    public function getDescription(): string
    {
        return '获取当前用户的数据权限范围';
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
        $userDbId = $this->getUserDbId();

        // 获取用户在所有模块的权限
        $modulePermService = app(ModulePermissionService::class);
        $allPermissions = $modulePermService->getUserAllPermissions($userDbId);

        // 构建权限范围映射
        $scopeLabels = [
            DataPermissionLevelEnum::ALL => '全部数据',
            DataPermissionLevelEnum::DEPARTMENT => '本部门',
            DataPermissionLevelEnum::CUSTOM_DEPARTMENT => '自定义部门',
            DataPermissionLevelEnum::DIRECT_SUBORDINATE => '直属下级',
            DataPermissionLevelEnum::SELF => '仅本人',
            DataPermissionLevelEnum::NONE => '无权限',
        ];

        $result = [
            'user_id' => $userDbId,
            'modules' => [],
        ];

        foreach ($allPermissions as $module => $perm) {
            $dataLevel = $perm['data_level'];
            $result['modules'][$module] = [
                'scope_type' => $dataLevel,
                'scope_name' => $scopeLabels[$dataLevel] ?? '未知',
                'frame_ids' => $perm['frame_id'] ?? [],
                'directly' => $perm['directly'] ?? 0,
            ];
        }

        return $result;
    }
}
