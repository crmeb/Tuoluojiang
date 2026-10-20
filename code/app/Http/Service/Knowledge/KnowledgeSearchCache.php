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
use Illuminate\Support\Facades\Cache;

/**
 * 知识库检索 Redis 缓存：查询向量与检索结果短期复用，文件缓存驱动自动降级.
 */
final class KnowledgeSearchCache
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rememberSearch(string $key, callable $callback): array
    {
        return self::remember($key, KnowledgeEnum::SEARCH_CACHE_TTL, $callback);
    }

    /**
     * @return array<int, float>|null
     */
    public static function rememberEmbedding(string $key, callable $callback): ?array
    {
        $value = self::remember($key, KnowledgeEnum::EMBEDDING_CACHE_TTL, static function () use ($callback): array {
            return ['vector' => $callback()];
        });

        return $value['vector'] ?? null;
    }

    public static function flush(): void
    {
        try {
            Cache::tags([KnowledgeEnum::CACHE_TAG_SEARCH])->flush();
        } catch (\Throwable) {
            // file/array 驱动不支持 tag，忽略即可.
        }
    }

    /**
     * @return mixed
     */
    private static function remember(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::tags([KnowledgeEnum::CACHE_TAG_SEARCH])->remember($key, $ttl, $callback);
        } catch (\Throwable) {
            return $callback();
        }
    }
}
