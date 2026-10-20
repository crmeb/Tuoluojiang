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

namespace App\Http\Controller\Mcp;

use App\Mcp\McpProtocolSupport;
use App\Mcp\McpResourceRegistry;
use App\Mcp\ToolExecutor;
use App\Mcp\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * MCP 服务端控制器
 *
 * 处理 MCP 协议的 JSON-RPC 请求
 */
class McpServerController
{
    public function __construct(
        protected McpRequest $mcpRequest,
        protected ToolExecutor $toolExecutor
    ) {
    }

    /**
     * 处理 MCP 请求 - JSON-RPC 2.0（旧版 HTTP JSON）.
     */
    public function handle(Request $request): JsonResponse|Response
    {
        if ($request->isMethod('GET')) {
            return $this->methodNotAllowedForGet();
        }

        try {
            $state = $this->createRequestState($request);
            $body = $request->all();

            if (McpProtocolSupport::isNotification((string) ($body['method'] ?? ''))) {
                return response('', 202)->header('MCP-Protocol-Version', $state['protocolVersion']);
            }

            if (isset($body[0])) {
                return $this->handleBatch($body, $state);
            }

            $payload = $this->processSingle($body, $state);

            return $this->jsonRpcResponse($payload, $state);
        } catch (\Throwable $e) {
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->error('MCP request error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json($this->errorPayload(null, -32603, 'Internal error: ' . $e->getMessage()));
        }
    }

    /**
     * Streamable HTTP 端点（MCP 2025-03-26 兼容）.
     */
    public function handleStream(Request $request): JsonResponse|Response
    {
        if (! McpProtocolSupport::streamEnabled()) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32600,
                    'message' => 'Streamable HTTP endpoint is disabled',
                ],
            ], 404);
        }

        if ($request->isMethod('GET')) {
            return $this->methodNotAllowedForGet();
        }

        try {
            $state = $this->createRequestState($request);
            $body = $request->all();
            $method = (string) ($body['method'] ?? '');

            if (McpProtocolSupport::isNotification($method)) {
                return $this->handleNotification($method);
            }

            if (isset($body[0])) {
                $results = [];
                $hasRequest = false;
                foreach ($body as $item) {
                    if (is_array($item) && McpProtocolSupport::isNotification((string) ($item['method'] ?? ''))) {
                        continue;
                    }
                    $hasRequest = true;
                    $results[] = $this->processSingle(is_array($item) ? $item : [], $state);
                }

                if (! $hasRequest) {
                    return response('', 202)->header('MCP-Protocol-Version', $state['protocolVersion']);
                }

                return $this->streamJsonResponse($results, $body, $request, $state);
            }

            return $this->streamJsonResponse($this->processSingle($body, $state), $body, $request, $state);
        } catch (\Throwable $e) {
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->error('MCP stream request error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->streamJsonResponse(
                $this->errorPayload(null, -32603, 'Internal error: ' . $e->getMessage()),
                $request->all(),
                $request,
                $this->createRequestState($request)
            );
        }
    }

    /**
     * 未实现 SSE 前，协议端点 GET 一律 405，客户端应使用 POST.
     */
    protected function methodNotAllowedForGet(): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32600,
                'message' => 'GET SSE is not enabled; use POST',
            ],
        ], 405)->header('Allow', 'POST');
    }

    /**
     * @param array{module: ?string, protocolVersion: string, sessionId: ?string} $state
     */
    protected function handleBatch(array $requests, array &$state): JsonResponse|Response
    {
        $results = [];
        foreach ($requests as $req) {
            if (! is_array($req)) {
                $results[] = $this->errorPayload(null, -32600, 'Invalid batch item');
                continue;
            }
            if (McpProtocolSupport::isNotification((string) ($req['method'] ?? ''))) {
                continue;
            }
            $results[] = $this->processSingle($req, $state);
        }

        if ($results === []) {
            return response('', 202)->header('MCP-Protocol-Version', $state['protocolVersion']);
        }

        return $this->jsonRpcResponse($results, $state);
    }

    /**
     * @param array{module: ?string, protocolVersion: string, sessionId: ?string} $state
     */
    protected function processSingle(array $request, array &$state): array
    {
        $id = $request['id'] ?? null;
        $method = $request['method'] ?? '';
        $params = is_array($request['params'] ?? null) ? $request['params'] : [];

        if (($request['jsonrpc'] ?? '') !== '2.0') {
            return $this->errorPayload($id, -32600, 'Invalid JSON-RPC version');
        }

        if ($method === 'initialize') {
            $requestedVersion = trim((string) ($params['protocolVersion'] ?? ''));
            $rejectUnknownVersion = (string) config('mcp.protocol.unknown_version', 'reject') !== 'legacy';
            if ($requestedVersion !== ''
                && ! McpProtocolSupport::isSupportedProtocolVersion($requestedVersion)
                && $rejectUnknownVersion
            ) {
                return $this->errorPayload(
                    $id,
                    -32602,
                    'Unsupported protocol version: ' . $requestedVersion
                    . '; supported: ' . implode(', ', McpProtocolSupport::supportedProtocolVersions())
                );
            }
            $state['protocolVersion'] = McpProtocolSupport::resolveProtocolVersion($requestedVersion);
            if (McpProtocolSupport::sessionAttachmentEnabled()) {
                $state['sessionId'] = McpProtocolSupport::createSessionId();
            }
        }

        return match ($method) {
            'initialize' => $this->successPayload($id, $this->buildInitializeResult($state)),
            'tools/list' => $this->processToolsList($id, $params, $state['module']),
            'tools/call' => $this->processToolsCall($id, $params, $state['module']),
            'resources/list' => $this->processResourcesList($id, $params),
            'resources/read' => $this->processResourcesRead($id, $params),
            'resources/templates/list' => $this->successPayload($id, ['resourceTemplates' => McpResourceRegistry::listResourceTemplates()]),
            'prompts/list' => $this->successPayload($id, ['prompts' => []]),
            'ping' => $this->successPayload($id, []),
            default => $this->errorPayload($id, -32601, "Method not found: {$method}"),
        };
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function processToolsList(mixed $id, array $params, ?string $module): array
    {
        $page = McpProtocolSupport::paginateList(
            ToolRegistry::getToolsMeta($module),
            $params['cursor'] ?? null
        );
        if (($page['error'] ?? null) === 'invalid_cursor') {
            return $this->errorPayload($id, -32602, 'Invalid cursor');
        }

        $result = ['tools' => $page['items']];
        if ($page['nextCursor'] !== null) {
            $result['nextCursor'] = $page['nextCursor'];
        }

        return $this->successPayload($id, $result);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function processResourcesList(mixed $id, array $params): array
    {
        $page = McpProtocolSupport::paginateList(
            McpResourceRegistry::listResources(),
            $params['cursor'] ?? null
        );
        if (($page['error'] ?? null) === 'invalid_cursor') {
            return $this->errorPayload($id, -32602, 'Invalid cursor');
        }

        $result = ['resources' => $page['items']];
        if ($page['nextCursor'] !== null) {
            $result['nextCursor'] = $page['nextCursor'];
        }

        return $this->successPayload($id, $result);
    }

    protected function processToolsCall(mixed $id, array $params, ?string $module): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        if (! $toolName) {
            return $this->errorPayload($id, -32602, 'Tool name is required');
        }
        if (! is_array($arguments)) {
            return $this->errorPayload($id, -32602, 'Tool arguments must be an object');
        }

        $tool = ToolRegistry::getTool($toolName, $module);
        if (! $tool) {
            if ($module && ToolRegistry::getTool($toolName)) {
                return $this->errorPayload($id, -32602, "Tool not available in module {$module}: {$toolName}");
            }

            return $this->errorPayload($id, -32602, "Tool not found: {$toolName}");
        }

        try {
            $result = $this->toolExecutor->executeWithResult($toolName, $arguments);

            return $this->successPayload($id, $result);
        } catch (\Throwable $e) {
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->error('MCP tool execution error', [
                'tool' => $toolName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->successPayload($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode([
                            'error' => true,
                            'message' => $e->getMessage(),
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ],
                'isError' => true,
            ]);
        }
    }

    protected function processResourcesRead(mixed $id, array $params): array
    {
        $uri = (string) ($params['uri'] ?? '');
        $result = McpResourceRegistry::read($uri);
        if (! empty($result['error'])) {
            return $this->errorPayload($id, -32602, (string) ($result['message'] ?? 'Resource read failed'));
        }

        return $this->successPayload($id, $result);
    }

    /**
     * @param array{module: ?string, protocolVersion: string, sessionId: ?string} $state
     */
    protected function buildInitializeResult(array $state): array
    {
        $serverInfo = config('mcp.server', []);
        $moduleInfo = ToolRegistry::getModuleConfig($state['module']);
        $serverName = $serverInfo['name'] ?? 'tuoluojiang-mcp';
        if ($state['module'] && $moduleInfo) {
            $serverName .= '-' . $state['module'];
        }

        // 仅声明已实现能力；prompts/subscriptions/progress/logging 未实现不暗示支持.
        return [
            'protocolVersion' => $state['protocolVersion'],
            'capabilities' => [
                'tools' => ['listChanged' => false],
                'resources' => ['subscribe' => false, 'listChanged' => false],
            ],
            'serverInfo' => [
                'name' => $serverName,
                'version' => $serverInfo['version'] ?? '2.0.0',
            ],
            'instructions' => McpProtocolSupport::buildInstructions($state['module']),
        ];
    }

    protected function handleNotification(string $method): Response
    {
        return response('', 202);
    }

    /**
     * @param array{module: ?string, protocolVersion: string, sessionId: ?string} $state
     * @param mixed $payload
     */
    protected function streamJsonResponse(mixed $payload, array $requestBody, Request $request, array $state): JsonResponse
    {
        return $this->jsonRpcResponse($payload, $state, $request);
    }

    /**
     * @param array{module: ?string, protocolVersion: string, sessionId: ?string} $state
     * @param mixed $payload
     */
    protected function jsonRpcResponse(mixed $payload, array $state, ?Request $request = null): JsonResponse
    {
        $response = response()->json($payload);
        $response->headers->set('MCP-Protocol-Version', $state['protocolVersion']);

        // 默认 stateless：不下发无效会话头；仅 stateful+redis 时附加.
        if (McpProtocolSupport::sessionAttachmentEnabled()
            && ! empty($state['sessionId'])
        ) {
            $response->headers->set('Mcp-Session-Id', (string) $state['sessionId']);
        }

        if ($request !== null && str_contains((string) $request->header('Accept'), 'text/event-stream')) {
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }

    /**
     * @return array{module: ?string, protocolVersion: string, sessionId: ?string}
     */
    protected function createRequestState(Request $request): array
    {
        $headerVersion = trim((string) $request->header('MCP-Protocol-Version', ''));
        $protocolVersion = McpProtocolSupport::VERSION_LEGACY;
        if ($headerVersion !== '' && McpProtocolSupport::isSupportedProtocolVersion($headerVersion)) {
            $protocolVersion = McpProtocolSupport::resolveProtocolVersion($headerVersion);
        }

        return [
            'module' => $this->normalizeModule($request->route('module')),
            'protocolVersion' => $protocolVersion,
            'sessionId' => null,
        ];
    }

    protected function successPayload(mixed $id, mixed $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    protected function errorPayload(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    protected function normalizeModule(mixed $module): ?string
    {
        $module = trim((string) $module);
        if ($module === '') {
            return null;
        }

        return ToolRegistry::getModuleConfig($module) ? $module : null;
    }

    public function getMcpRequest(): McpRequest
    {
        return $this->mcpRequest;
    }
}
