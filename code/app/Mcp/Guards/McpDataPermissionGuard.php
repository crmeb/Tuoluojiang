<?php

declare(strict_types=1);

namespace App\Mcp\Guards;

use App\Http\Service\System\ModulePermissionService;

/**
 * MCP 目标人员数据权限守卫。
 */
class McpDataPermissionGuard
{
    public function filterAuthorizedTargetUsers(int $currentUserId, string $module, array $targetUserIds): array
    {
        $targetUserIds = array_values(array_unique(array_filter(array_map('intval', $targetUserIds))));
        if ($targetUserIds === []) {
            return [
                'authorized_ids' => [],
                'denied_ids' => [],
            ];
        }

        if ((bool) request()->input('mcp_is_admin', false)) {
            return [
                'authorized_ids' => $targetUserIds,
                'denied_ids' => [],
            ];
        }

        $allowedUserIds = app(ModulePermissionService::class)->getAccessibleUserIds($currentUserId, $module);
        $authorizedIds = array_values(array_intersect($targetUserIds, $allowedUserIds));

        return [
            'authorized_ids' => $authorizedIds,
            'denied_ids' => array_values(array_diff($targetUserIds, $authorizedIds)),
        ];
    }

    public function permissionDeniedResult(string $module, array $deniedIds): array
    {
        return [
            'error' => true,
            'type' => 'permission_denied',
            'module' => $module,
            'denied_user_ids' => array_values(array_map('intval', $deniedIds)),
            'message' => '你没有查看该员工在当前模块下数据的权限',
        ];
    }
}
