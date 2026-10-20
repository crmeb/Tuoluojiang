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

namespace App\Mcp\Support;

/**
 * 外部 MCP 出站 URL 边界校验（P0）：协议白名单、私网/本机默认拒绝.
 */
final class McpOutboundUrlGuard
{
    /**
     * @return array{ok: bool, message?: string, host?: string, scheme?: string}
     */
    public static function validate(string $url, ?array $config = null): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['ok' => false, 'message' => 'Outbound MCP URL is empty'];
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return ['ok' => false, 'message' => 'Outbound MCP URL is invalid'];
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $config = $config ?? (array) config('mcp.outbound', []);
        $allowedSchemes = array_values(array_filter(array_map(
            static fn ($s): string => strtolower(trim((string) $s)),
            (array) ($config['allowed_schemes'] ?? ['https', 'http'])
        )));
        if ($allowedSchemes === []) {
            $allowedSchemes = ['https', 'http'];
        }
        if (! in_array($scheme, $allowedSchemes, true)) {
            return [
                'ok'      => false,
                'message' => 'Outbound MCP scheme not allowed: ' . $scheme,
                'host'    => $host,
                'scheme'  => $scheme,
            ];
        }

        $allowHosts = array_values(array_filter(array_map(
            static fn ($h): string => strtolower(trim((string) $h)),
            (array) ($config['allow_hosts'] ?? [])
        )));
        if ($allowHosts !== [] && in_array($host, $allowHosts, true)) {
            return ['ok' => true, 'host' => $host, 'scheme' => $scheme];
        }

        $allowPrivate = (bool) ($config['allow_private'] ?? false);
        if (! $allowPrivate && self::isBlockedHost($host)) {
            return [
                'ok'      => false,
                'message' => 'Outbound MCP host is private/local and not allowlisted: ' . $host,
                'host'    => $host,
                'scheme'  => $scheme,
            ];
        }

        return ['ok' => true, 'host' => $host, 'scheme' => $scheme];
    }

    public static function assertValid(string $url, ?array $config = null): void
    {
        $result = self::validate($url, $config);
        if (! ($result['ok'] ?? false)) {
            throw new \InvalidArgumentException((string) ($result['message'] ?? 'Invalid outbound MCP URL'));
        }
    }

    private static function isBlockedHost(string $host): bool
    {
        if ($host === 'localhost' || $host === '0.0.0.0' || str_ends_with($host, '.localhost')) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPrivateIp($host);
        }

        // 未解析域名不在此拦截；解析后的 IP 由调用方可再校验.
        return false;
    }

    private static function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) {
                return true;
            }
            $ranges = [
                ['0.0.0.0', '0.255.255.255'],
                ['10.0.0.0', '10.255.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.168.0.0', '192.168.255.255'],
            ];
            foreach ($ranges as [$start, $end]) {
                if ($long >= ip2long($start) && $long <= ip2long($end)) {
                    return true;
                }
            }

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $normalized = strtolower($ip);

            return $normalized === '::1'
                || str_starts_with($normalized, 'fc')
                || str_starts_with($normalized, 'fd')
                || str_starts_with($normalized, 'fe80:');
        }

        return true;
    }
}
