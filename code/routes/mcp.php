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

use App\Http\Controller\Mcp\McpServerController;
use App\Http\Middleware\McpAuthMiddleware;
use App\Http\Service\System\ModulePermissionService;
use App\Mcp\McpResourceRegistry;
use App\Mcp\ToolRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MCP 路由配置
|--------------------------------------------------------------------------
|
| MCP (Model Context Protocol) 服务路由
| 使用用户 McpKey 进行认证
|
*/

$mcpModulePattern = ToolRegistry::getModuleRoutePattern();

// Streamable HTTP（需在 /mcp/{module} 之前注册）
Route::match(['get', 'post'], '/mcp/stream', [McpServerController::class, 'handleStream'])
    ->middleware([McpAuthMiddleware::class])
    ->name('mcp.stream.handle');

Route::match(['get', 'post'], '/mcp/{module}/stream', [McpServerController::class, 'handleStream'])
    ->middleware([McpAuthMiddleware::class])
    ->where('module', $mcpModulePattern)
    ->name('mcp.module.stream.handle');

// MCP 服务端点 - 使用 JSON-RPC 2.0 协议
Route::match(['get', 'post'], '/mcp', [McpServerController::class, 'handle'])
    ->middleware([
        McpAuthMiddleware::class,
    ])
    ->name('mcp.handle');

// MCP 模块端点 - 暴露业务工具 + common_tools + context_tools
Route::match(['get', 'post'], '/mcp/{module}', [McpServerController::class, 'handle'])
    ->middleware([
        McpAuthMiddleware::class,
    ])
    ->where('module', $mcpModulePattern)
    ->name('mcp.module.handle');

// MCP 健康检查（不需要认证）
Route::get('/mcp/health', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => config('mcp.server.name', 'tuoluojiang-mcp'),
        'version' => config('mcp.server.version', '2.0.0'),
        'timestamp' => now()->toIso8601String(),
        'stream_enabled' => (bool) config('mcp.stream.enabled', true),
        'protocol' => [
            'supported_versions' => config('mcp.protocol.supported_versions', []),
            'session_mode' => config('mcp.protocol.session_mode', 'stateless'),
        ],
    ]);
})->name('mcp.health');

// MCP 服务信息（需要认证）
Route::get('/mcp/info', function () {
    $userDbId = (int) request()->input('mcp_user_db_id', 0);
    $isAdmin = (bool) request()->input('mcp_is_admin', false);

    $userPermissions = [];
    if ($userDbId > 0 && ! $isAdmin) {
        try {
            $modulePermService = app(ModulePermissionService::class);
            $userPermissions = $modulePermService->getUserAllPermissions($userDbId);
        } catch (\Throwable $e) {
        }
    }

    return response()->json([
        'server' => config('mcp.server', []),
        'auth' => [
            'enabled' => config('mcp.auth.enabled', true),
            'type' => 'mcpKey',
        ],
        'protocol' => [
            'supported_versions' => config('mcp.protocol.supported_versions', []),
            'unknown_version' => config('mcp.protocol.unknown_version', 'reject'),
            'session_mode' => config('mcp.protocol.session_mode', 'stateless'),
            'session_store' => config('mcp.protocol.session_store', 'none'),
            'list_page_size' => (int) config('mcp.protocol.list_page_size', 100),
            'list_compat_max' => (int) config('mcp.protocol.list_compat_max', 500),
            'get_sse' => false,
            'notes' => 'Clients must POST; GET returns 405 until SSE is implemented. 2026-07-28 draft is not supported.',
        ],
        'modules' => ToolRegistry::getConfiguredModules(),
        'business_capabilities' => config('mcp.business_capabilities', []),
        'resources' => McpResourceRegistry::listResources(),
        'user_permissions' => $userPermissions,
        'tools_count' => ToolRegistry::countTools(),
        'stream_url' => '/mcp/stream',
    ]);
})->middleware(McpAuthMiddleware::class)->name('mcp.info');

// MCP 模块服务信息（需要认证）
Route::get('/mcp/{module}/info', function (string $module) {
    $moduleConfig = ToolRegistry::getModuleConfig($module);
    if (! $moduleConfig) {
        return response()->json(['message' => 'MCP module not found'], 404);
    }

    return response()->json([
        'server' => config('mcp.server', []),
        'module' => [
            'key' => $module,
            'name' => $moduleConfig['name'] ?? $module,
            'description' => $moduleConfig['description'] ?? '',
        ],
        'auth' => [
            'enabled' => config('mcp.auth.enabled', true),
            'type' => 'mcpKey',
        ],
        'common_tools' => ToolRegistry::getCommonToolNames(),
        'context_tools' => ToolRegistry::getContextToolNames(),
        'resources' => McpResourceRegistry::listResources(),
        'tools_count' => count(ToolRegistry::getToolsMeta($module)),
        'stream_url' => '/mcp/' . $module . '/stream',
    ]);
})->middleware(McpAuthMiddleware::class)
    ->where('module', $mcpModulePattern)
    ->name('mcp.module.info');
