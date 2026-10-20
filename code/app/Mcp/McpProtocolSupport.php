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

/**
 * MCP 协议辅助（版本协商、会话策略、list 分页、instructions）.
 */
class McpProtocolSupport
{
    public const VERSION_LEGACY = '2024-11-05';

    public const VERSION_STREAMABLE = '2025-03-26';

    public static function streamEnabled(): bool
    {
        return (bool) config('mcp.stream.enabled', true);
    }

    public static function resolveProtocolVersion(mixed $clientVersion): string
    {
        $clientVersion = trim((string) $clientVersion);
        if ($clientVersion === self::VERSION_STREAMABLE
            && self::isSupportedProtocolVersion($clientVersion)
        ) {
            return self::VERSION_STREAMABLE;
        }

        return self::VERSION_LEGACY;
    }

    /**
     * 获取当前服务端声明支持的协议版本。
     *
     * @return array<int, string>
     */
    public static function supportedProtocolVersions(): array
    {
        $versions = config('mcp.protocol.supported_versions', [
            self::VERSION_LEGACY,
            self::VERSION_STREAMABLE,
        ]);

        return array_values(array_unique(array_filter(array_map(
            static fn ($version): string => trim((string) $version),
            is_array($versions) ? $versions : []
        ))));
    }

    public static function isSupportedProtocolVersion(mixed $version): bool
    {
        $version = trim((string) $version);

        return $version !== '' && in_array($version, self::supportedProtocolVersions(), true);
    }

    /**
     * 会话模式：stateless（默认）或 stateful.
     */
    public static function sessionMode(): string
    {
        $mode = strtolower(trim((string) config('mcp.protocol.session_mode', 'stateless')));

        return $mode === 'stateful' ? 'stateful' : 'stateless';
    }

    /**
     * 是否允许下发/校验 Mcp-Session-Id.
     *
     * Redis 会话存储与请求头校验尚未实现；在实现前恒为 false，
     * 避免 stateful+redis 配置下发无效 Mcp-Session-Id.
     */
    public static function sessionAttachmentEnabled(): bool
    {
        return false;
    }

    public static function createSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * tools/list、resources/list 游标分页.
     *
     * - 无 cursor：兼容旧客户端，一次返回至多 compatMax 条；超出部分给 nextCursor.
     * - 有 cursor：按 pageSize 续页，总可见上限仍为 compatMax.
     * - 非法 cursor（含非 string）返回 error=invalid_cursor.
     *
     * @param array<int, mixed> $items
     *
     * @return array{items: array<int, mixed>, nextCursor: ?string, error?: string}
     */
    public static function paginateList(
        array $items,
        mixed $cursor = null,
        ?int $pageSize = null,
        ?int $compatMax = null
    ): array {
        $items = array_values($items);
        $pageSize = max(1, $pageSize ?? (int) config('mcp.protocol.list_page_size', 100));
        $compatMax = max($pageSize, $compatMax ?? (int) config('mcp.protocol.list_compat_max', 500));
        $visibleTotal = min(count($items), $compatMax);

        if ($cursor === null || $cursor === '') {
            // 旧客户端：一次返回至多 compatMax；超出部分不暴露（也不给无效 nextCursor）.
            return [
                'items'      => array_slice($items, 0, $visibleTotal),
                'nextCursor' => null,
            ];
        }

        if (! is_string($cursor)) {
            return [
                'items'      => [],
                'nextCursor' => null,
                'error'      => 'invalid_cursor',
            ];
        }

        $decoded = self::decodeListCursor(trim($cursor));
        if ($decoded === null) {
            return [
                'items'      => [],
                'nextCursor' => null,
                'error'      => 'invalid_cursor',
            ];
        }

        $offset = $decoded;
        if ($offset >= $visibleTotal) {
            return [
                'items'      => [],
                'nextCursor' => null,
            ];
        }

        $limit = min($pageSize, $visibleTotal - $offset);
        $slice = array_slice($items, $offset, $limit);
        $nextOffset = $offset + count($slice);

        return [
            'items'      => $slice,
            'nextCursor' => $nextOffset < $visibleTotal ? self::encodeListCursor($nextOffset) : null,
        ];
    }

    /**
     * 编码 list 游标（opaque，客户端勿解析）.
     */
    public static function encodeListCursor(int $offset): string
    {
        $offset = max(0, $offset);

        return 'v1.' . rtrim(strtr(base64_encode((string) $offset), '+/', '-_'), '=');
    }

    /**
     * 解码 list 游标；非法返回 null.
     */
    public static function decodeListCursor(string $cursor): ?int
    {
        $cursor = trim($cursor);
        if (! str_starts_with($cursor, 'v1.')) {
            return null;
        }
        $payload = substr($cursor, 3);
        if ($payload === '') {
            return null;
        }
        $pad = strlen($payload) % 4;
        if ($pad > 0) {
            $payload .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($raw === false || $raw === '' || ! ctype_digit($raw)) {
            return null;
        }

        return (int) $raw;
    }

    public static function buildInstructions(?string $module): string
    {
        $serverInfo = config('mcp.server', []);
        $moduleInfo = ToolRegistry::getModuleConfig($module);
        $lines = [];

        $description = (string) ($moduleInfo['description'] ?? ($serverInfo['description'] ?? ''));
        if ($description !== '') {
            $lines[] = $description;
        }

        $contextGuide = trim((string) config('mcp.context_instructions', ''));
        if ($contextGuide !== '') {
            $lines[] = $contextGuide;
        }

        $resourceGuide = '可通过 resources/read 读取 oa://user/me、oa://org/tree、oa://modules/permissions 减少重复 tool call。';
        $lines[] = $resourceGuide;

        return implode("\n", array_filter($lines));
    }

    public static function isNotification(string $method): bool
    {
        return str_starts_with($method, 'notifications/');
    }
}
