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

namespace App\Http\Service\Ai;

/**
 * MCP 配置解析与运行时转换.
 *
 * chat_mcp_services.config_json 为单条 server 扁平 JSON（含 module 为内置）；
 * 应用侧 mcp_json 合并后为 { mcpServers: { key: server } } 格式.
 */
class McpConfigParser
{
    /** @var array<int, string> */
    public const BUILTIN_MODULES = ['customer', 'attendance', 'assess', 'report', 'schedule', 'approve'];

    /**
     * 从 MCP JSON 提取 server 映射（兼容扁平配置与 mcpServers 包装）.
     *
     * @param array<string, mixed> $config
     *
     * @return array<string, array<string, mixed>>
     */
    public static function servers(array $config): array
    {
        if (isset($config['mcpServers'])) {
            $servers = $config['mcpServers'];
            if (! is_array($servers)) {
                return [];
            }

            $result = [];
            foreach ($servers as $key => $server) {
                if (! is_string($key) || $key === '' || ! is_array($server)) {
                    continue;
                }
                $result[$key] = $server;
            }

            return $result;
        }

        if (! self::isFlatServerConfig($config)) {
            return [];
        }

        return [self::serverKeyFromFlat($config) => $config];
    }

    /**
     * 判断是否为单条 server 扁平配置（表记录 config_json 格式）.
     *
     * @param array<string, mixed> $config
     */
    public static function isFlatServerConfig(array $config): bool
    {
        if (isset($config['mcpServers'])) {
            return false;
        }

        return isset($config['module'])
            || isset($config['url'])
            || isset($config['transport'])
            || isset($config['type']);
    }

    /**
     * 解析传输协议：优先 transport，其次 Cursor/MemCP 的 type 别名，再按 url 路径推断.
     *
     * @param array<string, mixed> $server
     *
     * @return string sse|http|空串（无法判定）|其它原样（由调用方校验）
     */
    public static function resolveTransport(array $server): string
    {
        $explicit = strtolower(trim((string) ($server['transport'] ?? '')));
        if ($explicit !== '') {
            return self::normalizeTransportAlias($explicit);
        }

        $type = strtolower(trim((string) ($server['type'] ?? '')));
        if ($type !== '') {
            return self::normalizeTransportAlias($type);
        }

        $url = trim((string) ($server['url'] ?? ''));
        if ($url === '') {
            return '';
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        if ($path !== '' && preg_match('#(^|/)sse(/|$)#i', $path)) {
            return 'sse';
        }

        // 标准 MCP HTTP 端点（如 /mcp）按 streamable http.
        return 'http';
    }

    /**
     * @return string 归一化后的 transport 名
     */
    private static function normalizeTransportAlias(string $value): string
    {
        return match ($value) {
            'streamable-http', 'streamable_http', 'http' => 'http',
            'sse' => 'sse',
            default => $value,
        };
    }

    /**
     * 构建系统内置 MCP 单条配置.
     *
     * @return array{transport: string, module: string, headers: array<int, mixed>, timeout: int}
     */
    public static function buildBuiltinServiceConfig(string $module): array
    {
        return [
            'transport' => 'sse',
            'module'    => $module,
            'headers'   => [],
            'timeout'   => 30,
        ];
    }

    /**
     * 规范化 chat_mcp_services 表 config_json（单条 server 扁平格式）.
     *
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     */
    public static function normalizeServiceRecordConfig(array $config): array
    {
        if (isset($config['mcpServers'])) {
            $servers = self::servers($config);
            if (count($servers) !== 1) {
                throw new \InvalidArgumentException('MCP服务配置仅支持单条 server');
            }
            $serverKey = (string) array_key_first($servers);
            $server    = $servers[$serverKey];
            if (self::looksLikeStdioConfig($server)) {
                throw new \InvalidArgumentException(
                    'MCP「' . $serverKey . '」不支持 stdio/command/npx 配置，请使用可访问的 http(s) url 与 transport=sse'
                );
            }

            return self::normalizeFlatServer($server);
        }

        if (self::looksLikeStdioConfig($config)) {
            throw new \InvalidArgumentException('MCP配置不支持 stdio/command/npx，请使用可访问的 http(s) url 与 transport=sse');
        }

        if (! self::isFlatServerConfig($config)) {
            throw new \InvalidArgumentException('MCP配置格式无效');
        }

        return self::normalizeFlatServer($config);
    }

    /**
     * 合并多个 MCP JSON 的 server 映射（后者覆盖同名 key）.
     *
     * @param array<int, array<string, mixed>> $configs
     *
     * @return array<string, mixed>
     */
    public static function merge(array $configs): array
    {
        $servers = [];
        foreach ($configs as $config) {
            foreach (self::servers($config) as $key => $server) {
                $servers[$key] = $server;
            }
        }

        return ['mcpServers' => $servers];
    }

    /**
     * 构建应用侧标准 MCP JSON.
     *
     * @param array<string, mixed> $mergedServers mcpServers 内容
     * @param array<int, string> $tools 可选工具白名单
     *
     * @return array<string, mixed>
     */
    public static function buildApplicationJson(array $mergedServers, array $tools = []): array
    {
        $payload = ['mcpServers' => $mergedServers];
        if ($tools !== []) {
            $payload['tools'] = array_values(array_unique(array_filter(array_map('strval', $tools))));
        }

        return $payload;
    }

    /**
     * 判断单个 server 配置是否为内置 MCP（含 module 字段）.
     *
     * @param array<string, mixed> $server
     */
    public static function isBuiltinServer(array $server): bool
    {
        return trim((string) ($server['module'] ?? '')) !== '';
    }

    /**
     * 将 server 配置转为运行时连接配置.
     *
     * @return array{
     *     url: string,
     *     headers: array<string, string>,
     *     timeout: int,
     *     module: null|string,
     *     local: bool,
     *     internal: bool,
     *     server_key: string
     * }
     */
    public static function resolveServer(string $serverKey, array $server): array
    {
        $url     = trim((string) ($server['url'] ?? ''));
        $module  = trim((string) ($server['module'] ?? ''));
        $headers = self::normalizeHeaders($server['headers'] ?? []);
        $timeout = max(1, (int) ($server['timeout'] ?? 30));

        if ($module === '' && preg_match('~(?:^|/)mcp/([^/?#]+)~', $url, $matches)) {
            $module = (string) $matches[1];
        }

        $builtin = self::isBuiltinServer($server) || ($url !== '' && ! preg_match('/^https?:\/\//i', $url));
        if ($builtin) {
            if ($module !== '' && ($url === '' || str_starts_with($url, '/'))) {
                $url = '/mcp/' . $module;
            }

            return [
                'url'        => $url,
                'headers'    => $headers,
                'timeout'    => $timeout,
                'module'     => $module !== '' ? $module : null,
                'local'      => true,
                'internal'   => true,
                'server_key' => $serverKey,
            ];
        }

        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = '';
        }

        return [
            'url'        => $url,
            'headers'    => $headers,
            'timeout'    => $timeout,
            'module'     => null,
            'local'      => false,
            'internal'   => false,
            'server_key' => $serverKey,
        ];
    }

    /**
     * 将旧表结构转为扁平 server 配置.
     *
     * @param array<string, mixed> $legacy
     *
     * @return array<string, mixed>
     */
    public static function fromLegacyServiceRecord(array $legacy): array
    {
        $json   = self::arrayValue($legacy['config_json'] ?? []);
        if (self::isFlatServerConfig($json)) {
            return self::normalizeFlatServer($json);
        }

        $url     = trim((string) (($legacy['service_url'] ?? '') ?: ($json['url'] ?? '')));
        $module  = trim((string) ($json['module'] ?? ''));
        $name    = trim((string) ($legacy['name'] ?? ''));
        $key     = $module !== '' ? $module : self::serverKeyFromName($name, (int) ($legacy['id'] ?? 0));

        $server = [
            'transport' => (string) ($json['transport'] ?? 'sse'),
            'timeout'   => max(1, (int) ($json['timeout'] ?? 30)),
            'headers'   => [],
        ];
        if ($url !== '') {
            $server['url'] = $url;
        }
        if ($module !== '') {
            $server['module'] = $module;
        }

        $headers = self::normalizeHeaders($json['headers'] ?? ($legacy['headers'] ?? []));
        if ($headers !== [] && $module === '') {
            $server['headers'] = array_values(array_map(
                static fn (string $key, string $value): array => ['key' => $key, 'value' => $value],
                array_keys($headers),
                array_values($headers)
            ));
        }

        return self::normalizeFlatServer($server);
    }

    /**
     * 校验 MCP JSON（表记录扁平格式或应用侧 mcpServers 格式）.
     *
     * - 含 module：内置 MCP，跳过外部 url/transport 校验.
     * - 无 module：须 http(s) url + transport=sse|http，禁止 stdio/command/npx.
     *
     * @throws \InvalidArgumentException
     */
    public static function assertBindable(array $config, bool $isDefault = false): void
    {
        $servers = self::servers($config);
        if ($servers === []) {
            throw new \InvalidArgumentException('MCP配置无效');
        }

        foreach ($servers as $serverKey => $server) {
            if ($isDefault && self::isBuiltinServer($server)) {
                self::assertBuiltinServer($serverKey, $server);
                continue;
            }
            if (self::isBuiltinServer($server)) {
                self::assertBuiltinServer($serverKey, $server);
                continue;
            }
            self::assertExternalServer($serverKey, $server);
        }
    }

    /**
     * 校验外部 HTTP SSE MCP server 配置.
     *
     * @param array<string, mixed> $server
     *
     * @throws \InvalidArgumentException
     */
    public static function assertExternalServer(string $serverKey, array $server): void
    {
        if (self::looksLikeStdioConfig($server)) {
            throw new \InvalidArgumentException(
                'MCP「' . $serverKey . '」不支持 stdio/command/npx 配置，请使用可访问的 http(s) url 与 transport=sse'
            );
        }

        $transport = self::resolveTransport($server);
        if ($transport === '') {
            throw new \InvalidArgumentException('MCP「' . $serverKey . '」须配置 transport 或可推断的 type/url（支持 sse|http）');
        }
        if (! in_array($transport, ['sse', 'http'], true)) {
            throw new \InvalidArgumentException('MCP「' . $serverKey . '」仅支持 HTTP SSE（transport=sse|http），不支持 stdio/command');
        }

        $url = trim((string) ($server['url'] ?? ''));
        if ($url === '' || ! preg_match('/^https?:\/\//i', $url)) {
            throw new \InvalidArgumentException('MCP「' . $serverKey . '」须配置可访问的 http(s) url');
        }
    }

    /**
     * @param array<string, mixed> $server
     *
     * @throws \InvalidArgumentException
     */
    private static function assertBuiltinServer(string $serverKey, array $server): void
    {
        $module = trim((string) ($server['module'] ?? ''));
        if ($module === '') {
            throw new \InvalidArgumentException('内置 MCP「' . $serverKey . '」须配置 module 字段');
        }
    }

    /**
     * 识别 stdio/command/npx 配置.
     *
     * @param array<string, mixed> $config
     */
    public static function looksLikeStdioConfig(array $config): bool
    {
        if (isset($config['command'])) {
            return true;
        }
        foreach ($config as $value) {
            if (is_array($value) && isset($value['command'])) {
                return true;
            }
        }

        return false;
    }

    /** @param mixed $headers */
    public static function normalizeHeaders(mixed $headers): array
    {
        if (is_string($headers)) {
            $headers = json_decode($headers, true) ?: [];
        }
        if (! is_array($headers)) {
            return [];
        }
        if (isset($headers[0]) && is_array($headers[0])) {
            $result = [];
            foreach ($headers as $header) {
                $key = (string) ($header['key'] ?? '');
                if ($key !== '') {
                    $result[$key] = (string) ($header['value'] ?? '');
                }
            }

            return $result;
        }

        return array_filter(array_map('strval', $headers), static fn (string $value): bool => $value !== '');
    }

    /**
     * @param array<string, mixed> $server
     *
     * @return array<string, mixed>
     */
    private static function normalizeFlatServer(array $server): array
    {
        $module    = trim((string) ($server['module'] ?? ''));
        $transport = self::resolveTransport($server);
        // 内置 module 或无法从 url/type 推断时，缺省按 HTTP SSE.
        if ($transport === '') {
            $transport = 'sse';
        }
        if ($module === '' && ! in_array($transport, ['sse', 'http'], true)) {
            throw new \InvalidArgumentException('MCP配置仅支持 HTTP SSE（transport=sse|http），不支持其他传输方式');
        }

        $result = [
            'transport' => $transport,
            'headers'   => self::normalizeStoredHeaders($server['headers'] ?? []),
            'timeout'   => max(1, (int) ($server['timeout'] ?? 30)),
        ];

        $module = trim((string) ($server['module'] ?? ''));
        if ($module !== '') {
            $result['module'] = $module;
        } else {
            $url = trim((string) ($server['url'] ?? ''));
            if ($url !== '') {
                $result['url'] = $url;
            }
        }

        return $result;
    }

    /** @param mixed $headers */
    private static function normalizeStoredHeaders(mixed $headers): array
    {
        if (is_string($headers)) {
            $headers = json_decode($headers, true) ?: [];
        }
        if (! is_array($headers) || $headers === []) {
            return [];
        }
        if (isset($headers[0]) && is_array($headers[0])) {
            return array_values(array_filter(array_map(static function ($header): ?array {
                if (! is_array($header)) {
                    return null;
                }
                $key = (string) ($header['key'] ?? '');
                if ($key === '') {
                    return null;
                }

                return ['key' => $key, 'value' => (string) ($header['value'] ?? '')];
            }, $headers)));
        }

        return array_values(array_map(
            static fn (string $key, string $value): array => ['key' => $key, 'value' => $value],
            array_keys($headers),
            array_values(array_map('strval', $headers))
        ));
    }

    /** @param array<string, mixed> $config */
    private static function serverKeyFromFlat(array $config): string
    {
        $module = trim((string) ($config['module'] ?? ''));
        if ($module !== '') {
            return $module;
        }

        $url = trim((string) ($config['url'] ?? ''));
        if (preg_match('~(?:^|/)mcp/([^/?#]+)~', $url, $matches)) {
            return (string) $matches[1];
        }

        return 'mcp-' . substr(md5(json_encode($config, JSON_UNESCAPED_UNICODE) ?: ''), 0, 8);
    }

    /** @return array<string, mixed> */
    private static function arrayValue(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    private static function serverKeyFromName(string $name, int $id): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name) ?? '');
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : ('mcp-' . max(1, $id));
    }
}
