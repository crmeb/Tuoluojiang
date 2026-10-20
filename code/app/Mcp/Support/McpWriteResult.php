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
 * MCP 写工具结果契约：须提供明确落库证据，通用 _meta.success 不算.
 *
 * 有效证据（满足其一即可）：
 * - success === true
 * - id > 0
 * - stats.created_count > 0（批量写，含部分成功）
 */
final class McpWriteResult
{
    /**
     * 判断写工具返回是否构成有效写入.
     *
     * @param array<string, mixed> $result 工具原始或已规范化结果
     */
    public static function succeeded(array $result): bool
    {
        if (! empty($result['error'])) {
            return false;
        }
        if (($result['_meta']['success'] ?? true) === false) {
            return false;
        }
        if (isset($result['id']) && (int) $result['id'] <= 0) {
            return false;
        }

        $createdCount = self::createdCount($result);
        if ($createdCount === 0) {
            return false;
        }

        $hasId      = (int) ($result['id'] ?? 0) > 0;
        $hasCreated = $createdCount !== null && $createdCount > 0;
        $explicitOk = ($result['success'] ?? null) === true;

        // 显式失败且无任何落库证据
        if (($result['success'] ?? null) === false && ! $hasId && ! $hasCreated) {
            return false;
        }

        return $explicitOk || $hasId || $hasCreated;
    }

    /**
     * @param array<string, mixed> $result
     */
    private static function createdCount(array $result): ?int
    {
        if (! isset($result['stats']) || ! is_array($result['stats'])) {
            return null;
        }
        if (! array_key_exists('created_count', $result['stats'])) {
            return null;
        }

        return (int) $result['stats']['created_count'];
    }
}
