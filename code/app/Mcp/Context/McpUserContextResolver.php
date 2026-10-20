<?php

declare(strict_types=1);

namespace App\Mcp\Context;

use App\Http\Model\Admin\Admin;
use App\Http\Service\Frame\FrameAssistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 统一补齐 MCP 请求上下文。
 */
class McpUserContextResolver
{
    public function merge(Request $request, Admin $user): void
    {
        // 审批/跟进等业务 Service 依赖 auth('admin')->id()，MCP 必须同步登录守卫
        Auth::shouldUse('admin');
        Auth::guard('admin')->setUser($user);

        $request->merge(['mcp_user' => $user]);

        $request->macro('mcpUser', function () use ($user) {
            return $user;
        });

        $request->macro('mcpUserId', function () use ($user) {
            return $user->uid;
        });

        $request->macro('mcpUserInfo', function (?string $key = null) use ($user) {
            $info = $user->toArray();
            if ($key) {
                return $info[$key] ?? null;
            }
            return $info;
        });

        [$frameIds, $mainFrameId] = $this->getUserFrameContext((string) $user->uid);

        $request->merge([
            'mcp_user_id'       => $user->id,
            'mcp_user_db_id'    => $user->id,
            'mcp_is_admin'      => (bool) ($user->is_admin ?? false),
            'mcp_frame_ids'     => $frameIds,
            'mcp_main_frame_id' => $mainFrameId,
        ]);
    }

    private function getUserFrameContext(string $uuid): array
    {
        if ($uuid === '') {
            return [[], 0];
        }

        try {
            $frames = app()->get(FrameAssistService::class)->getUserFrames($uuid);
            $frames = $frames ? (is_array($frames) ? $frames : $frames->toArray()) : [];
        } catch (\Throwable) {
            return [[], 0];
        }

        $frameIds = [];
        $mainFrameId = 0;

        foreach ($frames as $item) {
            $frame = $item['frame'] ?? [];
            $frameId = (int) ($frame['id'] ?? $item['frame_id'] ?? $item['id'] ?? 0);
            if ($frameId > 0) {
                $frameIds[] = $frameId;
            }
            if (($item['is_mastart'] ?? false) || ($item['pivot']['is_mastart'] ?? false)) {
                $mainFrameId = $frameId;
            }
        }

        $frameIds = array_values(array_unique(array_filter($frameIds)));

        return [$frameIds, $mainFrameId ?: (int) ($frameIds[0] ?? 0)];
    }
}
