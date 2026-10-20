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
use App\Http\Dao\Knowledge\KnowledgeBaseDao;
use App\Http\Dao\Knowledge\KnowledgeChunkDao;
use App\Http\Service\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 知识库检索服务：向量召回 + 全文/关键词召回，RRF 融合排序，Redis 缓存检索结果.
 */
class KnowledgeSearchService extends BaseService
{
    private const VECTOR_WEIGHT = 0.65;

    private const KEYWORD_WEIGHT = 0.35;

    /**
     * KnowledgeSearchService constructor.
     */
    public function __construct(KnowledgeChunkDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 检索知识库分块.
     *
     * @param string $question 检索问句（调用方传入改写后的问句）
     * @param array<int, int> $kbIds 知识库 ID
     * @param int $topK 精排/最终条数，未传 recallLimit 时作为融合截断
     * @param int $spaceType 空间类型
     * @param bool $skipVisibilityCheck 是否跳过可见性校验
     * @param null|int $recallLimit 粗排召回条数；null 时始终用 topK（Rag 管线显式传 coarse_top_n）
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(
        string $question,
        array $kbIds = [],
        int $topK = 5,
        int $spaceType = KnowledgeEnum::SPACE_INTERNAL,
        bool $skipVisibilityCheck = false,
        ?int $recallLimit = null
    ): array {
        $question = trim($question);
        if ($question === '') {
            return [];
        }

        $kbIds = $this->resolveKbIds($question, $kbIds, $spaceType, $skipVisibilityCheck);
        if ($kbIds === []) {
            return [];
        }

        $effectiveLimit = $this->resolveRecallLimit($topK, $recallLimit);

        $cacheKey = 'kb_search:' . md5(json_encode([
            'rewrite_q'       => $question,
            'kb_ids'          => $kbIds,
            'top_k'           => $topK,
            'space_type'      => $spaceType,
            'skip_visibility' => $skipVisibilityCheck,
            'recall_limit'    => $effectiveLimit,
            'flags'           => $this->featureFlags(),
        ], JSON_UNESCAPED_UNICODE));

        return KnowledgeSearchCache::rememberSearch($cacheKey, fn (): array => $this->hybridSearch($question, $kbIds, $effectiveLimit));
    }

    /**
     * 融合向量与关键词召回结果，供单测复用.
     *
     * @param array<int, array<string, mixed>> $keywordHits
     * @param array<int, array<string, mixed>> $vectorHits
     *
     * @return array<int, array<string, mixed>>
     */
    public function fuseHits(array $keywordHits, array $vectorHits, int $topK): array
    {
        $bucket = [];
        foreach ($keywordHits as $hit) {
            $id                           = (int) $hit['id'];
            $bucket[$id]                  = $hit;
            $bucket[$id]['keyword_score'] = (float) ($hit['score'] ?? 0);
        }
        foreach ($vectorHits as $hit) {
            $id = (int) $hit['id'];
            if (! isset($bucket[$id])) {
                $bucket[$id] = $hit;
            } else {
                $bucket[$id] = array_merge($bucket[$id], $hit);
            }
            $bucket[$id]['vector_score'] = (float) ($hit['score'] ?? 0);
        }

        return collect($bucket)
            ->map(function (array $item): array {
                $vector  = (float) ($item['vector_score'] ?? 0);
                $keyword = (float) ($item['keyword_score'] ?? 0);
                if ($vector > 0 && $keyword > 0) {
                    $item['score'] = min(1, $vector * self::VECTOR_WEIGHT + $keyword * self::KEYWORD_WEIGHT);
                } elseif ($vector > 0) {
                    $item['score'] = $vector;
                } else {
                    $item['score'] = $keyword;
                }
                unset($item['vector_score'], $item['keyword_score']);

                return $item;
            })
            ->filter(static fn (array $item): bool => (float) ($item['score'] ?? 0) > 0)
            ->sortByDesc('score')
            ->take($topK)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hybridSearch(string $question, array $kbIds, int $topK): array
    {
        $terms        = $this->terms($question);
        $recallLimit  = max(20, min(500, $topK * 40));
        $queryVectors = $this->queryVectors($kbIds, $question);
        $keywordHits  = $this->keywordRecall($kbIds, $question, $terms, $recallLimit);
        $vectorHits   = $queryVectors === [] ? [] : $this->vectorRecall($kbIds, $queryVectors, $recallLimit);

        return $this->fuseHits($keywordHits, $vectorHits, $topK);
    }

    /**
     * 解析粗排召回条数：显式 recallLimit 优先，否则与 topK 一致.
     */
    private function resolveRecallLimit(int $topK, ?int $recallLimit): int
    {
        if ($recallLimit !== null && $recallLimit > 0) {
            return $recallLimit;
        }

        return max(1, $topK);
    }

    /**
     * 参与缓存隔离的功能开关快照.
     *
     * @return array<string, bool>
     */
    private function featureFlags(): array
    {
        return [
            'query_rewrite'   => (bool) config('knowledge.features.query_rewrite'),
            'rerank'          => (bool) config('knowledge.features.rerank'),
            'fulltext'        => (bool) config('knowledge.fulltext.enabled', true),
            'fulltext_driver' => (string) config('knowledge.fulltext.driver', 'redis'),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function resolveKbIds(string $question, array $kbIds, int $spaceType, bool $skipVisibilityCheck): array
    {
        $baseService = app(KnowledgeBaseService::class);
        $kbIds       = array_values(array_filter(array_map('intval', $kbIds)));
        if ($kbIds) {
            if (! $skipVisibilityCheck) {
                foreach ($kbIds as $kbId) {
                    $base = $baseService->checkVisible($kbId);
                    if ((int) $base->space_type !== $spaceType) {
                        throw $this->exception('知识库空间类型错误');
                    }
                }
            }

            return $kbIds;
        }

        return $baseService->visibleIds($spaceType);
    }

    /**
     * @return array<int, array<int, float>>
     */
    private function queryVectors(array $kbIds, string $question): array
    {
        $modelIds = app(KnowledgeBaseDao::class)->search(['id' => $kbIds])
            ->pluck('embedding_model_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $queryVectors = [];
        foreach ($modelIds as $modelId) {
            if ($vector = app(KnowledgeEmbeddingService::class)->embedQuery($question, $modelId)) {
                $queryVectors[$modelId] = $vector;
            }
        }

        return $queryVectors;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function keywordRecall(array $kbIds, string $question, array $terms, int $limit): array
    {
        $redisHits = $this->redisTextRecall($kbIds, $question, $limit);
        $likeHits = $this->mapKeywordHits(
            $this->likeRecall($kbIds, $question, $terms, $limit),
            $terms
        );

        // Redis 全文索引可能尚未完成回填或只包含部分分块，不能以非空结果
        // 判断“已完成检索”。合并两路召回，避免索引缺口直接造成内容漏召回。
        if ($redisHits === []) {
            return $likeHits;
        }
        if ($likeHits === []) {
            return $redisHits;
        }

        $merged = [];
        foreach (array_merge($redisHits, $likeHits) as $hit) {
            $id = (int) ($hit['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if (! isset($merged[$id]) || (float) ($hit['score'] ?? 0) > (float) ($merged[$id]['score'] ?? 0)) {
                $merged[$id] = $hit;
            }
        }

        return collect($merged)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    private function redisTextRecall(array $kbIds, string $question, int $limit): array
    {
        $scores = app(KnowledgeVectorIndex::class)->textSearch($kbIds, $question, $limit);
        if ($scores === []) {
            return [];
        }

        $rows = $this->dao->search([
            'kb_id'   => $kbIds,
            'status'  => KnowledgeEnum::CHUNK_DONE,
            'enabled' => KnowledgeEnum::STATUS_ENABLED,
        ])
            ->with(['document'])
            ->whereHas('document', static function ($query): void {
                $query->where('enabled', KnowledgeEnum::STATUS_ENABLED);
            })
            ->whereIn('id', array_keys($scores))
            ->get()
            ->keyBy('id');

        return collect($scores)
            ->map(function (float $score, int $chunkId) use ($rows): ?array {
                $chunk = $rows->get($chunkId);
                return $chunk ? $this->hitFromChunk($chunk, $score) : null;
            })
            ->filter()
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    private function likeRecall(array $kbIds, string $question, array $terms, int $limit): Collection
    {
        return $this->dao->search([
            'kb_id'   => $kbIds,
            'status'  => KnowledgeEnum::CHUNK_DONE,
            'enabled' => KnowledgeEnum::STATUS_ENABLED,
        ])
            ->with(['document'])
            ->whereHas('document', static function ($query): void {
                $query->where('enabled', KnowledgeEnum::STATUS_ENABLED);
            })
            ->where(function ($query) use ($question, $terms): void {
                $query->where('content', 'like', '%' . $question . '%')
                    ->orWhereHas('document', static function ($query) use ($question): void {
                        $query->where('title', 'like', '%' . $question . '%');
                    });
                if ($terms) {
                    $query->orWhere(function ($query) use ($terms): void {
                        foreach ($terms as $term) {
                            $query->orWhere('content', 'like', '%' . $term . '%')
                                ->orWhereHas('document', static function ($query) use ($term): void {
                                    $query->where('title', 'like', '%' . $term . '%');
                                });
                        }
                    });
                }
            })
            ->limit($limit)
            ->get();
    }

    /**
     * @param array<int, array<int, float>> $queryVectors
     *
     * @return array<int, array<string, mixed>>
     */
    private function vectorRecall(array $kbIds, array $queryVectors, int $limit): array
    {
        $redisHits = $this->redisVectorRecall($kbIds, $queryVectors, $limit);
        if ($redisHits !== null) {
            return $redisHits;
        }

        return $this->mysqlVectorRecall($kbIds, $queryVectors, $limit);
    }

    /**
     * Redis Stack KNN；不可用时返回 null 以触发 MySQL 回退.
     *
     * @param array<int, array<int, float>> $queryVectors
     *
     * @return null|array<int, array<string, mixed>>
     */
    private function redisVectorRecall(array $kbIds, array $queryVectors, int $limit): ?array
    {
        if ((string) config('knowledge.vector_driver', 'auto') === 'mysql') {
            return null;
        }

        $index = app(KnowledgeVectorIndex::class);
        if (! $index->available()) {
            return null;
        }

        $scores = [];
        foreach ($queryVectors as $modelId => $vector) {
            foreach ($index->knn((int) $modelId, $vector, $kbIds, $limit) as $chunkId => $score) {
                $scores[(int) $chunkId] = max($scores[(int) $chunkId] ?? 0.0, (float) $score);
            }
        }
        if ($scores === []) {
            // auto 模式下索引可能尚未回填，回退 MySQL 线性扫描.
            return (string) config('knowledge.vector_driver', 'auto') === 'redis' ? [] : null;
        }

        arsort($scores);
        $chunkIds = array_slice(array_keys($scores), 0, $limit);
        $rows     = $this->dao->search([
            'id'      => $chunkIds,
            'status'  => KnowledgeEnum::CHUNK_DONE,
            'enabled' => KnowledgeEnum::STATUS_ENABLED,
        ])
            ->with(['document'])
            ->whereHas('document', static function ($query): void {
                $query->where('enabled', KnowledgeEnum::STATUS_ENABLED);
            })
            ->get()
            ->keyBy('id');

        $hits = [];
        foreach ($chunkIds as $chunkId) {
            $chunk = $rows->get($chunkId);
            if (! $chunk) {
                continue;
            }
            $hits[] = $this->hitFromChunk($chunk, (float) $scores[$chunkId]);
        }

        return $hits;
    }

    /**
     * @param array<int, array<int, float>> $queryVectors
     *
     * @return array<int, array<string, mixed>>
     */
    private function mysqlVectorRecall(array $kbIds, array $queryVectors, int $limit): array
    {
        if ($queryVectors === []) {
            return [];
        }

        $query = $this->dao->search([
            'kb_id'   => $kbIds,
            'status'  => KnowledgeEnum::CHUNK_DONE,
            'enabled' => KnowledgeEnum::STATUS_ENABLED,
        ])
            ->where('embedding_dimension', '>', 0)
            ->whereIn('embedding_model_id', array_map('intval', array_keys($queryVectors)))
            ->with(['document'])
            ->whereHas('document', static function ($query): void {
                $query->where('enabled', KnowledgeEnum::STATUS_ENABLED);
            });

        $maxCandidates = max(0, (int) config('knowledge.mysql_vector_max_candidates', 5000));
        $candidateCount = (clone $query)->count();
        if ($maxCandidates > 0 && $candidateCount > $maxCandidates) {
            Log::warning('Knowledge MySQL vector fallback skipped: candidate set is too large', [
                'kb_ids'          => $kbIds,
                'candidate_count' => $candidateCount,
                'max_candidates'  => $maxCandidates,
            ]);

            return [];
        }

        $rows = $query->get();

        return $rows->map(function ($chunk) use ($queryVectors): ?array {
            $vector = $queryVectors[(int) $chunk->embedding_model_id] ?? null;
            if (! $vector || ! $chunk->embedding) {
                return null;
            }
            $score = $this->cosine($vector, (array) $chunk->embedding);
            if ($score <= 0) {
                return null;
            }

            return $this->hitFromChunk($chunk, $score);
        })->filter()->sortByDesc('score')->take($limit)->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapKeywordHits(Collection $rows, array $terms): array
    {
        return $rows->map(function ($chunk) use ($terms): array {
            $content      = (string) $chunk->content;
            $title        = (string) ($chunk->document->title ?? '');
            $relevance    = isset($chunk->relevance) ? (float) $chunk->relevance : null;
            $score        = $this->keywordScore($title . "\n" . $content, $terms, $relevance);
            $hit          = $this->hitFromChunk($chunk, $score);
            $hit['score'] = $score;

            return $hit;
        })->filter(static fn (array $item): bool => (float) ($item['score'] ?? 0) > 0)
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    private function hitFromChunk($chunk, float $score): array
    {
        return [
            'id'         => (int) $chunk->id,
            'kb_id'      => (int) $chunk->kb_id,
            'doc_id'     => (int) $chunk->doc_id,
            'title'      => (string) ($chunk->document->title ?? ''),
            'content'    => (string) $chunk->content,
            'score'      => min(1, $score),
            'metadata'   => (array) ($chunk->metadata ?: []),
            'image_urls' => array_values(array_filter(array_map('strval', (array) ($chunk->image_urls ?? [])))),
        ];
    }

    private function keywordScore(string $content, array $terms, ?float $relevance = null): float
    {
        $versionBoost = $this->versionKeywordBoost($content, $terms);

        if ($relevance !== null && $relevance > 0) {
            return min(1, max($relevance / 8, $versionBoost));
        }

        $haystack = mb_strtolower($content);
        $matched  = 0;
        $count    = 0;
        foreach (array_values(array_unique($terms)) as $term) {
            $term = mb_strtolower((string) $term);
            if ($term === '') {
                continue;
            }
            $occurrences = substr_count($haystack, $term);
            if ($occurrences > 0) {
                ++$matched;
                $count += $occurrences;
            }
        }
        if ($matched === 0) {
            return $versionBoost;
        }

        // 与向量/Redis 分数统一到 0~1：单个有效短语命中也应能通过默认
        // 0.55 门槛，多词覆盖率越高分数越高，重复出现只提供小幅加分。
        $coverage = $matched / max(1, count(array_unique($terms)));
        $base     = min(1.0, 0.55 + $coverage * 0.4 + min(0.05, $count * 0.01));

        return min(1.0, max($base, $versionBoost));
    }

    /**
     * 问句含版本号且正文命中时抬高关键词分，避免被高向量噪声挤出粗排 TopK.
     *
     * @param array<int, string> $terms
     */
    private function versionKeywordBoost(string $content, array $terms): float
    {
        $versions = [];
        foreach ($terms as $term) {
            if (preg_match('/^v?\d+(?:\.\d+){1,3}$/iu', $term)) {
                $versions[] = ltrim(mb_strtolower($term), 'v');
            }
        }
        $versions = array_values(array_unique($versions));
        if ($versions === []) {
            return 0.0;
        }

        $hit = 0;
        foreach ($versions as $version) {
            $quoted = preg_quote($version, '/');
            if (preg_match(
                '/(?<![0-9.])v' . $quoted . '(?![0-9.])|(?<![0-9.])' . $quoted . '(?:版本|版)/iu',
                $content
            )) {
                ++$hit;
            }
        }
        if ($hit <= 0) {
            return 0.0;
        }

        return min(1.0, 0.82 + 0.08 * ($hit / count($versions)));
    }

    /**
     * 提取关键词.
     */
    private function terms(string $question): array
    {
        $terms = [];
        $question = mb_strtolower(trim($question));

        // 中文问句通常没有空格，按连续汉字切成二字词；整句 LIKE 会把同义改写
        // 或补充追问全部挡在召回层之外。保留英文/数字 token 及版本号用于精确命中。
        if (preg_match_all('/[\p{Han}]+/u', $question, $runs)) {
            foreach ($runs[0] as $run) {
                $length = mb_strlen($run);
                if ($length === 1) {
                    $terms[] = $run;
                    continue;
                }
                for ($i = 0; $i < $length - 1; ++$i) {
                    $terms[] = mb_substr($run, $i, 2);
                }
            }
        }
        $terms = array_merge(
            $terms,
            preg_split('/[\s,，。！？；;、:：()（）\[\]{}]+/u', preg_replace('/[\p{Han}]+/u', ' ', $question) ?? $question, -1, PREG_SPLIT_NO_EMPTY) ?: []
        );

        // 额外抽出版本号（含 v 前缀变体），便于命中「2.5/V2.5」文档。
        if (preg_match_all('/v?\d+(?:\.\d+){1,3}/iu', $question, $versionMatches)) {
            foreach ($versionMatches[0] as $version) {
                $version = mb_strtolower((string) $version);
                $terms[] = $version;
                $bare    = ltrim($version, 'v');
                $terms[] = $bare;
                $terms[] = 'v' . $bare;
            }
        }

        $stopWords = array_fill_keys([
            '什么', '哪些', '如何', '怎么', '是否', '可以', '请问', '请', '帮我', '一下',
            '关于', '介绍', '查询', '查找', '告诉', '能否', '有无', '吗', '呢', '的',
            '我', '你', '他', '她', '它', '这', '那', '是', '有', '无', '了', '和', '与', '在', '要',
        ], true);

        return array_slice(array_values(array_unique(array_filter(
            array_map(static fn ($term): string => trim((string) $term), $terms),
            static fn (string $term): bool => (mb_strlen($term) > 1 || preg_match('/^[\p{Han}]$/u', $term) === 1)
                && ! isset($stopWords[$term])
        ))), 0, 48);
    }

    /**
     * 计算余弦相似度.
     *
     * @param array<int, float> $left
     * @param array<int, float> $right
     */
    private function cosine(array $left, array $right): float
    {
        if (count($left) !== count($right) || $left === []) {
            return 0;
        }

        $dot = $leftNorm = $rightNorm = 0.0;
        foreach ($left as $index => $value) {
            $leftValue  = (float) $value;
            $rightValue = (float) ($right[$index] ?? 0);
            $dot += $leftValue * $rightValue;
            $leftNorm += $leftValue ** 2;
            $rightNorm += $rightValue ** 2;
        }

        return $leftNorm && $rightNorm ? max(0, min(1, $dot / sqrt($leftNorm * $rightNorm))) : 0;
    }
}
