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

/**
 * 数据权限检查工具
 * Class CheckDataPermissionTool.
 */
class CheckDataPermissionTool extends BaseTool
{
    public function getDescription(): string
    {
        return '检查当前用户对指定人员的数据权限';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'target_user_id' => ['type' => 'integer', 'description' => '目标用户ID'],
            ],
            'required' => ['target_user_id'],
        ];
    }

    public function execute(array $arguments): array
    {
        $targetUserId = $arguments['target_user_id'] ?? 0;
        if ($this->getUserDbId() === $targetUserId) {
            return ['has_permission' => true, 'reason' => '本人'];
        }

        $hasPermission = $this->hasPermissionToUser((int) $targetUserId, 'personnel');

        return [
            'has_permission' => $hasPermission,
            'reason' => $hasPermission ? '在数据权限范围内' : '不在数据权限范围内',
        ];
    }
}
