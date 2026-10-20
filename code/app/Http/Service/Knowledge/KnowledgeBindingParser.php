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

namespace App\Http\Service\Knowledge;

use App\Constants\KnowledgeEnum;

/**
 * AI 应用 knowledge_ids 解析与 RAG 运行时绑定构建.
 *
 * 存储：chat_applications.knowledge_ids 为整数 ID 数组，如 [1, 5, 9]。
 * 检索时按系统默认 top_k、score_threshold 构建运行时绑定。
 */
class KnowledgeBindingParser
{
    /**
     * 规范化 knowledge_ids 数组.
     *
     * @param mixed $raw
     *
     * @return array<int, int>
     */
    public static function normalizeIds(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            return [];
        }

        return self::uniquePositiveIds($raw);
    }

    /**
     * 从知识库 ID 列表构建 RAG 运行时绑定（含系统默认 top_k、score_threshold）.
     *
     * @param array<int, int> $knowledgeIds
     *
     * @return array<int, array<string, mixed>>
     */
    public static function runtimeBindings(array $knowledgeIds): array
    {
        $topK      = KnowledgeEnum::DEFAULT_TOP_K;
        $threshold = KnowledgeEnum::defaultScoreThreshold();

        $bindings = [];
        foreach (self::normalizeIds($knowledgeIds) as $kbId) {
            $bindings[] = [
                'kb_id'           => $kbId,
                'top_k'           => $topK,
                'score_threshold' => $threshold,
                'status'          => KnowledgeEnum::STATUS_ENABLED,
            ];
        }

        return $bindings;
    }

    /** @param mixed $raw */
    public static function hasIds(mixed $raw): bool
    {
        return self::normalizeIds($raw) !== [];
    }

    /**
     * 从 ID 数组中移除指定知识库.
     *
     * @param mixed $raw
     *
     * @return array<int, int>
     */
    public static function removeId(mixed $raw, int $kbId): array
    {
        if ($kbId <= 0) {
            return self::normalizeIds($raw);
        }

        return array_values(array_filter(
            self::normalizeIds($raw),
            static fn (int $id): bool => $id !== $kbId
        ));
    }

    /**
     * @param array<int, mixed> $values
     *
     * @return array<int, int>
     */
    private static function uniquePositiveIds(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
