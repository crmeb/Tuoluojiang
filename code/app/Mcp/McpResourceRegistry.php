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

namespace App\Mcp;

use App\Http\Service\System\ModulePermissionService;
use Illuminate\Support\Str;

/**
 * MCP Resources 注册与读取.
 */
class McpResourceRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listResources(): array
    {
        $resources = config('mcp.resources', []);

        return is_array($resources) ? array_values($resources) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listResourceTemplates(): array
    {
        $templates = config('mcp.resource_templates', []);

        return is_array($templates) ? array_values($templates) : [];
    }

    /**
     * @return array{contents: array<int, array<string, mixed>>}|array{error: true, message: string}
     */
    public static function read(string $uri): array
    {
        $uri = trim($uri);
        if ($uri === '') {
            return ['error' => true, 'message' => 'Resource uri is required'];
        }

        if (! self::isAllowedUri($uri)) {
            return ['error' => true, 'message' => "Resource not found: {$uri}"];
        }

        $payload = self::resolvePayload($uri);
        if (isset($payload['error'])) {
            return $payload;
        }

        $meta = self::findResourceMeta($uri);

        return [
            'contents' => [[
                'uri'      => $uri,
                'mimeType' => (string) ($meta['mimeType'] ?? 'application/json'),
                'text'     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolvePayload(string $uri): array
    {
        $executor = app(ToolExecutor::class);

        return match ($uri) {
            'oa://user/me' => $executor->execute('who_am_i', []),
            'oa://user/data-scope' => $executor->execute('my_data_scope', []),
            'oa://org/tree' => $executor->execute('org_tree', []),
            'oa://modules/permissions' => self::modulePermissionsPayload(),
            default => ['error' => true, 'message' => "Unsupported resource: {$uri}"],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function modulePermissionsPayload(): array
    {
        $userDbId = (int) request()->input('mcp_user_db_id', 0);
        $isAdmin = (bool) request()->input('mcp_is_admin', false);
        if ($userDbId <= 0) {
            return ['error' => true, 'message' => 'User context missing'];
        }
        if ($isAdmin) {
            return ['is_admin' => true, 'permissions' => []];
        }

        try {
            return [
                'is_admin'    => false,
                'permissions' => app(ModulePermissionService::class)->getUserAllPermissions($userDbId),
            ];
        } catch (\Throwable $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    private static function isAllowedUri(string $uri): bool
    {
        foreach (self::listResources() as $resource) {
            if (($resource['uri'] ?? '') === $uri) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private static function findResourceMeta(string $uri): array
    {
        foreach (self::listResources() as $resource) {
            if (($resource['uri'] ?? '') === $uri) {
                return $resource;
            }
        }

        return ['mimeType' => 'application/json', 'name' => Str::after($uri, 'oa://')];
    }
}
