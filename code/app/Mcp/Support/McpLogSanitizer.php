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
 * MCP 日志脱敏工具.
 */
class McpLogSanitizer
{
    private const SENSITIVE_KEY_PATTERN = '/(?:phone|mobile|password|passwd|id_card|identity|mcp_key|token|secret|email)/i';

    /**
     * 递归脱敏数组中的敏感字段.
     */
    public static function sanitize(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            $stringKey = (string) $key;
            if (self::isSensitiveKey($stringKey)) {
                $result[$key] = self::mask($value);
                continue;
            }

            $result[$key] = is_array($value) ? self::sanitize($value) : $value;
        }

        return $result;
    }

    /**
     * 生成适合写入日志的摘要字符串.
     */
    public static function summarizeForLog(mixed $data, int $maxLength = 500): string
    {
        if ($data === null || $data === []) {
            return '{}';
        }

        $json = json_encode(self::sanitize($data), JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return '{"error":"json_encode_failed"}';
        }

        if (mb_strlen($json) <= $maxLength) {
            return $json;
        }

        return mb_substr($json, 0, $maxLength) . '…';
    }

    private static function isSensitiveKey(string $key): bool
    {
        return (bool) preg_match(self::SENSITIVE_KEY_PATTERN, $key);
    }

    private static function mask(mixed $value): string
    {
        $string = trim((string) $value);
        if ($string === '') {
            return '';
        }

        if (mb_strlen($string) <= 4) {
            return '****';
        }

        return mb_substr($string, 0, 2) . '****' . mb_substr($string, -2);
    }
}
