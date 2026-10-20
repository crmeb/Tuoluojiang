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

use App\Constants\Ai\AiModelEnum;
use App\Constants\KnowledgeEnum;
use App\Http\Dao\Ai\AiModelDao;
use App\Http\Dao\Knowledge\KnowledgeBaseDao;
use App\Http\Dao\Knowledge\KnowledgeChunkDao;
use App\Http\Model\Ai\AiModel;
use App\Http\Model\Knowledge\KnowledgeChunk;
use App\Http\Service\Ai\AiModelService;
use App\Jobs\Knowledge\ProcessKnowledgeEmbeddingJob;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\ai\BaseCurl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 知识库向量化服务.
 */
class KnowledgeEmbeddingService extends BaseService
{
    public function __construct(
        protected KnowledgeChunkDao $chunkDao,
        protected AiModelDao $modelDao,
    ) {}

    /**
     * 向量化待处理分块，失败分块保留为失败状态，后续重试即可.
     *
     * @param array<int, int> $chunkIds
     * @param int             $embeddingModelId 可选覆盖向量模型配置 ID（0 表示使用知识库绑定模型）
     *
     * @return array{total: int, done: int, failed: int, first_error: string|null}
     */
    public function embedPending(int $kbId, array $chunkIds = [], int $embeddingModelId = 0): array
    {
        $stats = ['total' => 0, 'done' => 0, 'failed' => 0, 'first_error' => null];

        $base = app(KnowledgeBaseDao::class)->get($kbId);
        if (! $base) {
            return $stats;
        }

        $chunks = $this->requeueableChunksQuery($kbId, $chunkIds)->get();
        if ($chunks->isEmpty()) {
            return $stats;
        }

        $stats['total'] = $chunks->count();
        $modelId        = $embeddingModelId > 0 ? $embeddingModelId : (int) $base->embedding_model_id;
        if ($modelId <= 0) {
            foreach ($chunks as $chunk) {
                $this->markKeywordReady($chunk);
                ++$stats['done'];
            }

            return $stats;
        }

        $model = $this->resolveEmbeddingModel($modelId);
        if (! $model) {
            $error = '向量模型不可用，请检查知识库绑定的向量模型是否启用且已配置模型ID';
            foreach ($chunks as $chunk) {
                $this->markModelUnavailable($chunk, $modelId, $error);
                ++$stats['failed'];
                $stats['first_error'] ??= $error;
            }
            $this->logEmbeddingFailure($kbId, $modelId, null, $error, $stats['failed']);

            return $stats;
        }

        $pending = $this->reuseEmbeddings($kbId, $model, $chunks);
        $stats['done'] += $chunks->count() - count($pending);

        foreach (array_chunk($pending, 64) as $batch) {
            try {
                $embeddings = $this->requestEmbeddings($model, array_map(
                    static fn (KnowledgeChunk $chunk): string => (string) $chunk->content,
                    $batch
                ));
                foreach ($batch as $index => $chunk) {
                    $embedding = $embeddings[$index] ?? [];
                    if (! $embedding) {
                        throw new \RuntimeException('模型未返回有效向量');
                    }
                    $this->chunkDao->update((int) $chunk->id, [
                        'embedding'           => $embedding,
                        'embedding_model_id'  => (int) $model->id,
                        'embedding_dimension' => count($embedding),
                        'status'              => KnowledgeEnum::CHUNK_DONE,
                        'error_msg'           => '',
                    ]);
                    app(KnowledgeVectorIndex::class)->upsert(
                        (int) $chunk->id,
                        (int) $chunk->kb_id,
                        (int) $chunk->doc_id,
                        (int) $model->id,
                        KnowledgeEnum::CHUNK_DONE,
                        $embedding
                    );
                    app(KnowledgeVectorIndex::class)->upsertChunkText($chunk);
                    ++$stats['done'];
                }
            } catch (\Throwable $e) {
                $error = $this->formatEmbeddingError($e, $model);
                foreach ($batch as $chunk) {
                    $metadata = (array) ($chunk->metadata ?: []);
                    unset($metadata['embedding_error'], $metadata['embedding_mode']);
                    $this->chunkDao->update((int) $chunk->id, [
                        'metadata'  => $metadata,
                        'status'    => KnowledgeEnum::CHUNK_FAILED,
                        'error_msg' => mb_substr($error, 0, 500),
                    ]);
                    ++$stats['failed'];
                    $stats['first_error'] ??= $error;
                }
                $this->logEmbeddingFailure(
                    $kbId,
                    (int) $model->id,
                    (string) $model->model_id,
                    $error,
                    count($batch),
                    array_map(static fn (KnowledgeChunk $chunk): int => (int) $chunk->id, $batch)
                );
            }
        }

        if ($stats['done'] > 0 || $stats['failed'] > 0) {
            KnowledgeSearchCache::flush();
        }

        return $stats;
    }

    /**
     * 获取下一批可向量化分块，供短队列任务续跑。
     *
     * @return array<int, int>
     */
    public function pendingChunkIds(int $kbId, int $afterId, int $limit): array
    {
        return $this->requeueableChunksQuery($kbId)
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->limit(max(1, $limit))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * 重新排队知识库下「待向量化 / 向量化失败 / 已完成但无向量」的分块.
     * 已成功写入向量的分块不会被修改.
     */
    public function requeueKnowledgeBase(int $kbId, int $embeddingModelId = 0): int
    {
        $affected = $this->requeueableChunksQuery($kbId)->update(['status' => KnowledgeEnum::CHUNK_PENDING]);
        if ($affected > 0) {
            ProcessKnowledgeEmbeddingJob::dispatch($kbId, [], $embeddingModelId);
        }

        return (int) $affected;
    }

    /**
     * 重置文档下可重试分块为待向量化并异步入队；不重新分片.
     * 可选覆盖 embedding 模型（仅本次生效，不修改知识库绑定）.
     *
     * @param int $kbId             知识库 ID
     * @param int $docId            文档 ID
     * @param int $embeddingModelId 可选向量模型配置 ID，0 表示使用知识库绑定模型
     *
     * @return int 入队分块数量
     */
    public function requeueDocument(int $kbId, int $docId, int $embeddingModelId = 0): int
    {
        $base = app(KnowledgeBaseDao::class)->get($kbId);
        if (! $base || $docId <= 0) {
            return 0;
        }

        $effectiveModelId = $embeddingModelId > 0 ? $embeddingModelId : (int) $base->embedding_model_id;
        $chunks           = $this->requeueableChunksQuery($kbId, [], $docId)
            ->get(['id', 'embedding_model_id']);
        if ($chunks->isEmpty()) {
            return 0;
        }

        $chunkIds = [];
        $index    = app(KnowledgeVectorIndex::class);
        foreach ($chunks as $chunk) {
            $chunkId    = (int) $chunk->id;
            $oldModelId = (int) $chunk->embedding_model_id;
            $chunkIds[] = $chunkId;
            if ($oldModelId > 0 && $oldModelId !== $effectiveModelId) {
                $index->delete($chunkId, $oldModelId);
            }
        }

        $update = [
            'status'    => KnowledgeEnum::CHUNK_PENDING,
            'error_msg' => '',
        ];
        if ($embeddingModelId > 0) {
            $update['embedding_dimension'] = 0;
            $update['embedding_model_id']  = $embeddingModelId;
        }

        $this->requeueableChunksQuery($kbId, $chunkIds, $docId)->update($update);
        KnowledgeSearchCache::flush();
        ProcessKnowledgeEmbeddingJob::dispatch($kbId, $chunkIds, $embeddingModelId);

        return count($chunkIds);
    }

    /**
     * 生成查询向量，模型不可用时交给关键词检索兜底.
     *
     * @return array<int, float>|null
     */
    public function embedQuery(string $text, int $modelId): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $cacheKey = 'kb_embed:' . $modelId . ':' . md5($text);

        return KnowledgeSearchCache::rememberEmbedding($cacheKey, function () use ($text, $modelId): ?array {
            return $this->requestQueryEmbedding($text, $modelId);
        });
    }

    /**
     * 实际请求 embedding 接口，不走缓存.
     *
     * @return array<int, float>|null
     */
    private function requestQueryEmbedding(string $text, int $modelId): ?array
    {
        $model = $this->resolveEmbeddingModel($modelId);
        if (! $model) {
            return null;
        }

        try {
            return $this->requestEmbeddings($model, [$text])[0] ?? null;
        } catch (\Throwable $e) {
            Log::warning('知识库查询向量化失败', [
                'model_id'        => $modelId,
                'embedding_model' => (string) $model->model_id,
                'error'           => $this->formatEmbeddingError($e, $model),
            ]);

            return null;
        }
    }

    /**
     * 复用同一知识库中相同内容的既有向量，避免重复请求模型.
     *
     * @return array<int, KnowledgeChunk>
     */
    private function reuseEmbeddings(int $kbId, AiModel $model, Collection $chunks): array
    {
        $hashes = $chunks->pluck('content_hash')->filter()->unique()->values()->all();
        $existing = $hashes === [] ? collect() : $this->chunkDao->search(['kb_id' => $kbId])
            ->whereIn('content_hash', $hashes)
            ->where('embedding_model_id', $model->id)
            ->where('status', KnowledgeEnum::CHUNK_DONE)
            ->where('embedding_dimension', '>', 0)
            ->get();
        $map = $existing->filter(fn (KnowledgeChunk $chunk) => ! empty($chunk->embedding))
            ->keyBy('content_hash');
        $pending = [];

        foreach ($chunks as $chunk) {
            $source = $map->get($chunk->content_hash);
            if ($source) {
                $this->chunkDao->update((int) $chunk->id, [
                    'embedding'           => $source->embedding,
                    'embedding_model_id'  => (int) $model->id,
                    'embedding_dimension' => (int) ($source->embedding_dimension ?: count((array) $source->embedding)),
                    'status'              => KnowledgeEnum::CHUNK_DONE,
                    'error_msg'           => '',
                ]);
                app(KnowledgeVectorIndex::class)->upsert(
                    (int) $chunk->id,
                    (int) $chunk->kb_id,
                    (int) $chunk->doc_id,
                    (int) $model->id,
                    KnowledgeEnum::CHUNK_DONE,
                    array_map('floatval', (array) $source->embedding)
                );
                app(KnowledgeVectorIndex::class)->upsertChunkText($chunk);
                continue;
            }
            $pending[] = $chunk;
        }

        return $pending;
    }

    /**
     * 调用 OpenAI 兼容 embedding 接口.
     *
     * @param array<int, string> $texts
     * @return array<int, array<int, float>>
     */
    private function requestEmbeddings(AiModel $model, array $texts): array
    {
        $option = app(AiModelService::class)->makeEmbeddingOption($model);
        $result = (new BaseCurl((string) $model->api_key)
        )->setBody($option)->send(
            url: $option->url,
            body: ['model' => $option->model, 'input' => $texts],
            timeout: 60
        );

        if (! empty($result['error'])) {
            $error   = $result['error'];
            $message = is_array($error)
                ? (string) ($error['message'] ?? json_encode($error, JSON_UNESCAPED_UNICODE))
                : (string) $error;
            throw new \RuntimeException($message !== '' ? $message : 'Embedding 接口返回错误');
        }

        $data = array_values((array) ($result['data'] ?? []));
        if ($data === []) {
            throw new \RuntimeException('Embedding 接口未返回向量数据');
        }

        usort($data, static fn (array $left, array $right): int => ((int) ($left['index'] ?? 0)) <=> ((int) ($right['index'] ?? 0)));

        return array_values(array_map(
            static fn (array $item): array => array_map('floatval', (array) ($item['embedding'] ?? [])),
            $data
        ));
    }

    /**
     * 筛选需要（重新）向量化的分块：
     * - status = 待处理(0) 或 失败(2)
     * - status = 完成(1) 且 embedding_dimension = 0（未真正写入向量）
     *
     * 不包含：status = 完成(1) 且 embedding_dimension > 0（已成功向量化）.
     *
     * @param array<int, int> $chunkIds
     */
    private function requeueableChunksQuery(int $kbId, array $chunkIds = [], int $docId = 0)
    {
        $where = [
            'kb_id'   => $kbId,
            'enabled' => KnowledgeEnum::STATUS_ENABLED,
        ];
        if ($docId > 0) {
            $where['doc_id'] = $docId;
        }

        return $this->chunkDao->search($where)
            ->when($chunkIds !== [], fn ($query) => $query->whereIn('id', $chunkIds))
            ->where(function ($query) {
                $query->whereIn('status', [KnowledgeEnum::CHUNK_PENDING, KnowledgeEnum::CHUNK_FAILED])
                    ->orWhere(function ($query) {
                        $query->where('status', KnowledgeEnum::CHUNK_DONE)
                            ->where('embedding_dimension', 0);
                    });
            })
            ->orderBy('id');
    }

    private function resolveEmbeddingModel(int $modelId): ?AiModel
    {
        $model = $this->modelDao->get([
            'id'         => $modelId,
            'status'     => AiModelEnum::STATUS_ENABLED,
            'model_type' => AiModelEnum::MODEL_TYPE_EMBEDDING,
        ]);
        if (! $model || trim((string) $model->model_id) === '') {
            return null;
        }

        return $model;
    }

    private function markKeywordReady(KnowledgeChunk $chunk): void
    {
        $metadata = (array) ($chunk->metadata ?: []);
        unset($metadata['embedding_error']);
        $metadata['embedding_mode'] = 'keyword';

        $this->chunkDao->update((int) $chunk->id, [
            'embedding'           => [],
            'embedding_model_id'  => 0,
            'embedding_dimension' => 0,
            'status'              => KnowledgeEnum::CHUNK_DONE,
            'error_msg'           => '',
            'metadata'            => $metadata,
        ]);
    }

    private function markModelUnavailable(KnowledgeChunk $chunk, int $modelId, ?string $error = null): void
    {
        $message  = $error ?: '向量模型不可用，请检查知识库绑定的向量模型是否启用且已配置模型ID';
        $metadata = (array) ($chunk->metadata ?: []);
        unset($metadata['embedding_error']);

        $this->chunkDao->update((int) $chunk->id, [
            'embedding'           => [],
            'embedding_model_id'  => $modelId,
            'embedding_dimension' => 0,
            'status'              => KnowledgeEnum::CHUNK_FAILED,
            'error_msg'           => mb_substr($message, 0, 500),
            'metadata'            => $metadata,
        ]);
    }

    private function formatEmbeddingError(\Throwable $e, AiModel $model): string
    {
        $configured = trim((string) $model->model_id);
        $message    = mb_substr($e->getMessage(), 0, 500);
        if ($configured !== '') {
            $message = sprintf('[model_id=%s] %s', $configured, $message);
        }
        if ($model->provider !== AiModelEnum::DRIVER_VOLCENGINE) {
            return mb_substr($message, 0, 500);
        }

        if (str_contains($message, 'does not support this api') || str_contains($message, '不支持')) {
            $message .= '。当前向量模型接入点绑定的是聊天模型（如 doubao-seed），请在火山方舟单独创建 Embedding 模型接入点';
        } elseif (str_contains($message, 'does not exist') || str_contains($message, '不存在')) {
            $message .= '。请确认 AI 模型配置中的模型ID已保存为 Embedding 接入点 ep-xxx（不是模型展示名称）';
        }

        return mb_substr($message, 0, 500);
    }

    /**
     * @param array<int, int>|null $chunkIds
     */
    private function logEmbeddingFailure(
        int $kbId,
        int $modelId,
        ?string $embeddingModel,
        string $error,
        int $chunkCount,
        ?array $chunkIds = null
    ): void {
        Log::warning('知识库分块向量化失败', array_filter([
            'kb_id'           => $kbId,
            'model_id'        => $modelId,
            'embedding_model' => $embeddingModel,
            'chunk_count'     => $chunkCount,
            'chunk_ids'       => $chunkIds,
            'error'           => $error,
        ], static fn ($value) => $value !== null && $value !== []));
    }
}
