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

use App\Mcp\Support\McpOutboundUrlGuard;
use Tuoluojiang\Open\services\HttpService;
use Illuminate\Support\Facades\Log;

/**
 * 外部 MCP 客户端.
 *
 * 通过 JSON-RPC 2.0 over HTTP（Streamable HTTP / SSE）连接外部 MCP 服务器。
 * 兼容 Apifox 等要求 Accept 同时含 application/json 与 text/event-stream、
 * 并以 Mcp-Session-Id 维持会话的远端 MCP.
 */
class ExternalMcpClient
{
    private string $serviceUrl;

    private array $headers;

    private int $timeout;

    private int $requestId = 1;

    private bool $initialized = false;

    private ?array $serverInfo = null;

    /** Streamable HTTP 会话 ID（来自响应头 mcp-session-id）. */
    private ?string $sessionId = null;

    public function __construct(string $serviceUrl, array $headers = [], int $timeout = 30)
    {
        McpOutboundUrlGuard::assertValid($serviceUrl);
        $this->serviceUrl = rtrim($serviceUrl, '/');
        $this->headers    = $headers;
        $this->timeout    = $timeout;
    }

    /**
     * 构造请求头：强制 Streamable HTTP Accept，并回传会话 ID.
     *
     * @return array<int, string>
     */
    private function buildRequestHeaders(): array
    {
        $headerList = [
            'Content-Type: application/json',
            'Accept: application/json, text/event-stream',
        ];
        foreach ($this->headers as $key => $value) {
            $name = strtolower((string) $key);
            // 避免调用方重复传入覆盖协议必需头.
            if (in_array($name, ['content-type', 'accept', 'mcp-session-id'], true)) {
                continue;
            }
            $headerList[] = "{$key}: {$value}";
        }
        if ($this->sessionId !== null && $this->sessionId !== '') {
            $headerList[] = 'Mcp-Session-Id: ' . $this->sessionId;
        }

        return $headerList;
    }

    /**
     * 从原始 HTTP 响应头提取 mcp-session-id.
     */
    private function extractMcpSessionId(string $rawHeader): ?string
    {
        if ($rawHeader === '') {
            return null;
        }
        if (preg_match('/^mcp-session-id:\s*(.+)$/im', $rawHeader, $matches)) {
            $value = trim($matches[1]);

            return $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * 解析 JSON 或 SSE 帧中的 JSON-RPC 响应体.
     *
     * @return null|array<string, mixed>
     */
    private function decodeResponseBody(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // SSE: 取最后一个 data: JSON 行（与 Streamable HTTP 约定一致）.
        $payload = null;
        foreach (preg_split("/\r\n|\n|\r/", $content) ?: [] as $line) {
            $line = trim($line);
            if (stripos($line, 'data:') === 0) {
                $payload = trim(substr($line, 5));
            }
        }
        if ($payload === null || $payload === '' || $payload === '[DONE]') {
            return null;
        }

        $decoded = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * 发送 JSON-RPC 2.0 请求（可带或不带 id）.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function sendRequest(string $method, array $params = [], bool $notification = false): array
    {
        $body = [
            'jsonrpc' => '2.0',
            'method'  => $method,
            'params'  => $params === [] ? new \stdClass() : $params,
        ];
        if (! $notification) {
            $body['id'] = $this->requestId++;
        }

        $headerList = $this->buildRequestHeaders();

        try {
            [$httpCode, $content, $rawHeader] = (new HttpService())
                ->setHeader($headerList)
                ->requests($this->serviceUrl, 'POST', json_encode($body), true, $this->timeout);

            $rawHeader = is_string($rawHeader ?? null) ? $rawHeader : '';
            $session   = $this->extractMcpSessionId($rawHeader);
            if ($session !== null) {
                $this->sessionId = $session;
            }

            // 通知类请求常见 202/204 且无 body.
            if ($notification && $httpCode >= 200 && $httpCode < 300) {
                return [];
            }

            if (! $httpCode || $httpCode < 200 || $httpCode >= 300) {
                Log::channel((string) config('mcp.logging.channel', 'mcp'))->error('External MCP request failed', [
                    'url'      => $this->serviceUrl,
                    'method'   => $method,
                    'httpCode' => $httpCode,
                    'content'  => $content,
                ]);

                return ['error' => true, 'message' => "HTTP {$httpCode}: {$content}"];
            }

            if ($notification) {
                return [];
            }

            $response = $this->decodeResponseBody((string) $content);
            if ($response === null) {
                return ['error' => true, 'message' => 'JSON parse error: ' . json_last_error_msg()];
            }

            if (isset($response['error'])) {
                return [
                    'error'   => true,
                    'message' => $response['error']['message'] ?? 'Unknown error',
                    'code'    => $response['error']['code'] ?? -1,
                ];
            }

            return $response['result'] ?? [];
        } catch (\Throwable $e) {
            Log::channel((string) config('mcp.logging.channel', 'mcp'))->error('External MCP connection error', [
                'url'   => $this->serviceUrl,
                'error' => $e->getMessage(),
            ]);

            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    /**
     * 发送 initialize 请求，获取服务能力信息并建立会话.
     */
    public function initialize(): array
    {
        $result = $this->sendRequest('initialize', [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => new \stdClass(),
            'clientInfo'      => [
                'name'    => 'tuoluojiang-chat',
                'version' => '2.0.0',
            ],
        ]);

        if (empty($result['error'])) {
            $this->initialized = true;
            $this->serverInfo  = $result;
            // Streamable HTTP：initialize 后通知服务端会话就绪（失败不阻断后续调用）.
            $this->sendRequest('notifications/initialized', [], true);
        }

        return $result;
    }

    /**
     * 获取服务端的工具列表.
     */
    public function listTools(): array
    {
        if (! $this->initialized) {
            $initResult = $this->initialize();
            if (! empty($initResult['error'])) {
                return $initResult;
            }
        }

        $result = $this->sendRequest('tools/list', []);
        if (! empty($result['error'])) {
            return $result;
        }

        return $result['tools'] ?? [];
    }

    /**
     * 调用服务端的工具.
     */
    public function callTool(string $toolName, array $arguments): array
    {
        if (! $this->initialized) {
            $initResult = $this->initialize();
            if (! empty($initResult['error'])) {
                return [
                    'content' => [['type' => 'text', 'text' => $initResult['message'] ?? 'MCP initialize failed']],
                    'isError' => true,
                ];
            }
        }

        $result = $this->sendRequest('tools/call', [
            'name'      => $toolName,
            'arguments' => $arguments === [] ? new \stdClass() : $arguments,
        ]);

        if (! empty($result['error'])) {
            return [
                'content' => [['type' => 'text', 'text' => $result['message'] ?? 'Tool call failed']],
                'isError' => true,
            ];
        }

        return $result;
    }

    /**
     * 测试连接：initialize + tools/list.
     */
    public function testConnection(): array
    {
        $initResult = $this->initialize();
        if (! empty($initResult['error'])) {
            return ['success' => false, 'message' => $initResult['message'], 'tool_count' => 0];
        }

        $tools = $this->listTools();
        if (! empty($tools['error'])) {
            return ['success' => false, 'message' => $tools['message'], 'tool_count' => 0];
        }

        return [
            'success'    => true,
            'message'    => '连接成功',
            'server'     => $this->serverInfo['serverInfo'] ?? [],
            'tool_count' => count($tools),
        ];
    }
}
