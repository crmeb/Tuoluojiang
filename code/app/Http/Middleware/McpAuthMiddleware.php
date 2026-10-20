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

namespace App\Http\Middleware;

use App\Http\Model\Admin\Admin;
use App\Mcp\Context\McpUserContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * MCP 认证中间件
 * Class McpAuthMiddleware.
 *
 * 功能：
 * 1. 从请求头获取 mcpKey，迁移期开关允许请求参数兼容
 * 2. 通过 mcpKey 查询员工表 admin 获取当前用户
 * 3. 获取员工信息（用户ID、角色、企业ID等）
 * 4. 将用户信息注入到请求上下文中，供后续 Tool 使用
 * 5. 如果认证失败，返回 401 Unauthorized
 */
class McpAuthMiddleware
{
    /**
     * MCP Key Header 名称.
     */
    private const HEADER_MCP_KEY = 'X-Mcp-Key';

    /**
     * MCP Key Header 兼容名称.
     */
    private const HEADER_MCP_KEY_ALIAS = 'Mcp-Key';

    /**
     * 执行中间件.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        $authMethod = 'mcp_key';
        $mcpKey     = $this->resolveMcpKey($request);

        if ($this->isStreamRequest($request) && ! $this->isOriginAllowed($request)) {
            $this->logger()->warning('MCP Origin rejected', $this->requestLogContext($request));

            return $this->forbiddenResponse('Invalid Origin');
        }

        $authRateKeys = $this->authRateLimitKeys($request, $mcpKey);
        foreach ($authRateKeys as $authRateKey) {
            if (RateLimiter::tooManyAttempts($authRateKey, $this->authFailureLimit())) {
                return $this->rateLimitResponse(RateLimiter::availableIn($authRateKey));
            }
        }

        try {
            if ((int) sys_config('mcp_enabled', 1) !== 1) {
                throw new \RuntimeException('MCP 服务已关闭');
            }
            $user = $this->authenticateWithMcpKey($request, $mcpKey);
        } catch (\Throwable $e) {
            foreach ($authRateKeys as $authRateKey) {
                RateLimiter::hit($authRateKey, 60);
            }
            $this->logger()->warning('MCP mcpKey auth failed', array_merge([
                'error' => $e->getMessage(),
            ], $this->requestLogContext($request)));

            return $this->unauthorizedResponse($e->getMessage());
        }

        RateLimiter::clear($authRateKeys[1]);
        $requestRateKey = $this->requestRateLimitKey($request, $user);
        if (RateLimiter::tooManyAttempts($requestRateKey, $this->requestLimit())) {
            return $this->rateLimitResponse(RateLimiter::availableIn($requestRateKey));
        }
        RateLimiter::hit($requestRateKey, 60);

        try {
            $this->setUserContext($request, $user);
        } catch (\Exception $e) {
            $this->logger()->error('MCP setUserContext failed', [
                'auth_method' => $authMethod,
                'error' => $e->getMessage(),
            ]);

            return $this->unauthorizedResponse('Failed to get user context: ' . $e->getMessage());
        }

        if ($this->shouldLogAuthSuccess()) {
            $this->logger()->info('MCP Auth success', array_merge([
                'auth_method' => $authMethod,
                'user_id' => $user->id,
                'mcp_user_id' => $request->input('mcp_user_id'),
                'user_name' => $user->name ?? '',
            ], $this->requestLogContext($request)));
        }

        return $next($request);
    }

    /**
     * 使用 mcpKey 查询员工并完成认证.
     */
    private function authenticateWithMcpKey(Request $request, ?string $resolvedKey = null): Admin
    {
        $mcpKey = $resolvedKey ?? $this->resolveMcpKey($request);
        if ($mcpKey === '') {
            throw new \RuntimeException('No mcpKey provided');
        }

        $user = Admin::where('mcp_key', $mcpKey)->where('status', 1)->first();
        if (! $user) {
            throw new \RuntimeException('Invalid mcpKey or user not found');
        }

        return $user;
    }

    /**
     * 获取请求中的 mcpKey，兼容参数、Header 和 Bearer 传递方式.
     */
    private function resolveMcpKey(Request $request): string
    {
        $candidates = [
            $request->header(self::HEADER_MCP_KEY),
            $request->header(self::HEADER_MCP_KEY_ALIAS),
        ];
        if ((bool) config('mcp.auth.allow_query_key', false)) {
            array_unshift(
                $candidates,
                $request->input('mcpKey'),
                $request->input('mcp_key'),
                $request->input('params.mcpKey'),
                $request->input('params.mcp_key')
            );
        }

        foreach ($candidates as $candidate) {
            $mcpKey = trim((string) $candidate);
            if ($mcpKey !== '') {
                return $mcpKey;
            }
        }

        $bearerToken = trim((string) $request->bearerToken());
        if ($bearerToken !== '' && ! str_contains($bearerToken, '.')) {
            return $bearerToken;
        }

        return '';
    }

    /**
     * 设置用户上下文.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function setUserContext(Request $request, Admin $user): void
    {
        if ($this->shouldLogUserContext()) {
            $this->logger()->debug('MCP setUserContext', array_merge([
                'has_user' => true,
                'user_id' => $user->id,
                'user_db_id' => $user->id,
                'user_name' => $user->name ?? '',
            ], $this->requestLogContext($request)));
        }

        app(McpUserContextResolver::class)->merge($request, $user);
    }

    /**
     * 返回 401 未授权响应.
     */
    private function unauthorizedResponse(string $message = 'Unauthorized'): mixed
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32600,
                'message' => $message,
            ],
        ], 401);
    }

    private function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error'   => [
                'code'    => -32003,
                'message' => $message,
            ],
        ], 403);
    }

    private function rateLimitResponse(int $retryAfter): JsonResponse
    {
        $retryAfter = max(1, $retryAfter);
        $response   = response()->json([
            'jsonrpc' => '2.0',
            'error'   => [
                'code'    => -32029,
                'message' => 'Too many MCP requests',
            ],
        ], 429);
        $response->headers->set('Retry-After', (string) $retryAfter);

        return $response;
    }

    private function isStreamRequest(Request $request): bool
    {
        return $request->routeIs('mcp.stream.handle', 'mcp.module.stream.handle');
    }

    private function isOriginAllowed(Request $request): bool
    {
        $origin = trim((string) $request->header('Origin', ''));
        if ($origin === '') {
            return (bool) config('mcp.stream.allow_missing_origin', true);
        }

        $allowedOrigins = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) config('mcp.stream.allowed_origins', [])
        )));

        return in_array($origin, $allowedOrigins, true);
    }

    /**
     * 认证失败同时按 IP 和 IP+Key 指纹限流，避免攻击者通过不断更换伪造 Key 绕过限制。
     *
     * @return array{0: string, 1: string}
     */
    private function authRateLimitKeys(Request $request, string $mcpKey): array
    {
        $fingerprint = $mcpKey !== '' ? hash('sha256', $mcpKey) : 'missing';
        $ip = (string) $request->ip();

        return [
            'mcp:auth-failure:ip:' . $ip,
            'mcp:auth-failure:key:' . $ip . ':' . $fingerprint,
        ];
    }

    private function requestRateLimitKey(Request $request, Admin $user): string
    {
        return 'mcp:request:' . (int) $user->id . ':' . (string) $request->ip();
    }

    private function authFailureLimit(): int
    {
        return max(1, (int) config('mcp.auth.rate_limit.auth_failures_per_minute', 5));
    }

    private function requestLimit(): int
    {
        return max(1, (int) config('mcp.auth.rate_limit.per_minute', 120));
    }

    private function shouldLogAuthSuccess(): bool
    {
        return (bool) config('mcp.logging.log_success', false);
    }

    private function shouldLogUserContext(): bool
    {
        return (bool) config('mcp.logging.log_user_context', false);
    }

    private function requestLogContext(Request $request): array
    {
        $mcpKey = $this->resolveMcpKey($request);
        $fingerprint = $mcpKey !== '' ? substr(hash('sha256', $mcpKey), 0, 12) : null;

        return [
            'method' => $request->method(),
            'path' => '/' . ltrim($request->path(), '/'),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'mcp_key_fingerprint' => $fingerprint,
        ];
    }

    private function logger(): LoggerInterface
    {
        return Log::channel((string) config('mcp.logging.channel', 'mcp'));
    }
}
