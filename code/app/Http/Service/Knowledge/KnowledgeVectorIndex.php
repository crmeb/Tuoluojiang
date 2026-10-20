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
use App\Http\Model\Knowledge\KnowledgeChunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 知识库向量索引（Redis Stack RediSearch）：MySQL 仍为向量真源，本服务负责索引同步与 KNN 召回.
 */
class KnowledgeVectorIndex
{
    /** @var array<string, bool> */
    private array $indexReady = [];

    private ?bool $moduleReady = null;

    public function enabled(): bool
    {
        $driver = (string) config('knowledge.vector_driver', 'auto');

        return in_array($driver, ['auto', 'redis'], true);
    }

    public function fulltextEnabled(): bool
    {
        return (bool) config('knowledge.fulltext.enabled', true)
            && in_array((string) config('knowledge.fulltext.driver', 'redis'), ['auto', 'redis'], true);
    }

    public function fulltextAvailable(): bool
    {
        return $this->fulltextEnabled() && $this->moduleAvailable();
    }

    /**
     * 当前 Redis 是否具备 RediSearch 向量能力.
     */
    public function available(): bool
    {
        return $this->enabled() && $this->moduleAvailable();
    }

    /**
     * 写入/更新分块向量文档.
     *
     * @param array<int, float> $embedding
     */
    public function upsert(int $chunkId, int $kbId, int $docId, int $modelId, int $status, array $embedding): bool
    {
        if (! $this->available() || $chunkId <= 0 || $modelId <= 0 || $embedding === []) {
            return false;
        }

        $dim = count($embedding);
        if (! $this->ensureIndex($modelId, $dim)) {
            return false;
        }

        try {
            $this->raw([
                'HSET',
                $this->docKey($modelId, $chunkId),
                'chunk_id', (string) $chunkId,
                'kb_id', (string) $kbId,
                'doc_id', (string) $docId,
                'model_id', (string) $modelId,
                'status', (string) $status,
                'embedding', $this->packVector($embedding),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('知识库向量索引写入失败', [
                'chunk_id' => $chunkId,
                'model_id' => $modelId,
                'error'    => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function upsertChunk(KnowledgeChunk $chunk): bool
    {
        $embedding = array_map('floatval', (array) ($chunk->embedding ?: []));
        if ($embedding === [] || (int) $chunk->embedding_model_id <= 0) {
            return false;
        }

        return $this->upsert(
            (int) $chunk->id,
            (int) $chunk->kb_id,
            (int) $chunk->doc_id,
            (int) $chunk->embedding_model_id,
            (int) $chunk->status,
            $embedding
        );
    }

    public function upsertText(
        int $chunkId,
        int $kbId,
        int $docId,
        int $status,
        int $enabled,
        string $title,
        string $content
    ): bool {
        if (! $this->fulltextAvailable() || $chunkId <= 0 || $kbId <= 0 || trim($content) === '') {
            return false;
        }
        if (! $this->ensureTextIndex()) {
            return false;
        }

        try {
            $this->raw([
                'HSET',
                $this->textDocKey($chunkId),
                'chunk_id', (string) $chunkId,
                'kb_id', (string) $kbId,
                'doc_id', (string) $docId,
                'status', (string) $status,
                'enabled', (string) $enabled,
                'search_text', $this->tokenText($title . "\n" . $content),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('知识库全文索引写入失败', [
                'chunk_id' => $chunkId,
                'error'    => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function upsertChunkText(KnowledgeChunk $chunk, ?string $title = null): bool
    {
        return $this->upsertText(
            (int) $chunk->id,
            (int) $chunk->kb_id,
            (int) $chunk->doc_id,
            (int) $chunk->status,
            (int) $chunk->enabled,
            $title ?? (string) ($chunk->document->title ?? ''),
            (string) $chunk->content
        );
    }

    /**
     * @return array<int, float>
     */
    public function textSearch(array $kbIds, string $query, int $limit): array
    {
        if (! $this->fulltextAvailable() || $kbIds === [] || $limit <= 0 || ! $this->ensureTextIndex()) {
            return [];
        }

        // ponytail: cap at 32 terms to keep RediSearch queries bounded; add query expansion only with measured recall loss.
        $terms = array_slice($this->tokenize($query), 0, 32);
        if ($terms === []) {
            return [];
        }

        $kbFilter = implode('|', array_map(
            static fn (int $kbId): string => sprintf('@kb_id:[%d %d]', $kbId, $kbId),
            array_values(array_unique(array_map('intval', $kbIds)))
        ));
        $textQuery = implode('|', $terms);

        try {
            $result = $this->raw([
                'FT.SEARCH',
                $this->textIndexName(),
                sprintf('@enabled:[%d %d] (%s) @search_text:(%s)',
                    KnowledgeEnum::STATUS_ENABLED,
                    KnowledgeEnum::STATUS_ENABLED,
                    $kbFilter,
                    $textQuery
                ),
                'WITHSCORES',
                'RETURN', '1', 'chunk_id',
                'LIMIT', '0', (string) min(500, $limit),
                'DIALECT', '2',
            ]);
        } catch (\Throwable $e) {
            Log::warning('知识库全文检索失败', ['error' => $e->getMessage()]);

            return [];
        }

        return $this->parseTextResult($result);
    }

    /** @return array<int, string> */
    public function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        $tokens = [];
        if (preg_match_all('/[\p{Han}]+/u', $text, $matches)) {
            foreach ($matches[0] as $run) {
                $length = mb_strlen($run);
                if ($length > 1) {
                    for ($i = 0; $i < $length - 1; ++$i) {
                        $tokens[] = mb_substr($run, $i, 2);
                    }
                }
            }
        }
        if (preg_match_all('/[a-z0-9]+/u', $text, $matches)) {
            $tokens = array_merge($tokens, $matches[0]);
        }

        return array_values(array_unique(array_filter($tokens, static fn (string $token): bool => mb_strlen($token) > 1)));
    }

    public function delete(int $chunkId, int $modelId): bool
    {
        $deleted = $this->deleteText($chunkId);
        if (! $this->available() || $chunkId <= 0 || $modelId <= 0) {
            return $deleted;
        }
        try {
            $this->raw(['DEL', $this->docKey($modelId, $chunkId)]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('知识库向量索引删除失败', [
                'chunk_id' => $chunkId,
                'model_id' => $modelId,
                'error'    => $e->getMessage(),
            ]);

            return $deleted;
        }
    }

    /**
     * @param array<int, array{id:int,embedding_model_id:int}> $chunks
     */
    public function deleteMany(array $chunks): void
    {
        foreach ($chunks as $chunk) {
            $this->delete((int) ($chunk['id'] ?? 0), (int) ($chunk['embedding_model_id'] ?? 0));
        }
    }

    public function deleteText(int $chunkId): bool
    {
        if (! $this->fulltextAvailable() || $chunkId <= 0) {
            return false;
        }
        try {
            $this->raw(['DEL', $this->textDocKey($chunkId)]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * KNN 召回，返回 [chunk_id => similarity(0~1)].
     *
     * @param array<int, float> $vector
     * @param array<int, int>   $kbIds
     *
     * @return array<int, float>
     */
    public function knn(int $modelId, array $vector, array $kbIds, int $limit): array
    {
        if (! $this->available() || $modelId <= 0 || $vector === [] || $kbIds === [] || $limit <= 0) {
            return [];
        }

        $dim = count($vector);
        if (! $this->ensureIndex($modelId, $dim)) {
            return [];
        }

        $kbFilter = implode('|', array_map(
            static fn (int $kbId): string => sprintf('@kb_id:[%d %d]', $kbId, $kbId),
            array_values(array_unique(array_map('intval', $kbIds)))
        ));
        $k     = max(1, min(100, $limit));
        $query = sprintf(
            '(@status:[%d %d] (%s))=>[KNN %d @embedding $BLOB AS dist]',
            KnowledgeEnum::CHUNK_DONE,
            KnowledgeEnum::CHUNK_DONE,
            $kbFilter,
            $k
        );

        try {
            $result = $this->raw([
                'FT.SEARCH',
                $this->indexName($modelId),
                $query,
                'PARAMS', '2', 'BLOB', $this->packVector($vector),
                'RETURN', '2', 'chunk_id', 'dist',
                'SORTBY', 'dist',
                'DIALECT', '2',
                'LIMIT', '0', (string) $k,
            ]);
        } catch (\Throwable $e) {
            Log::warning('知识库向量 KNN 失败', [
                'model_id' => $modelId,
                'error'    => $e->getMessage(),
            ]);

            return [];
        }

        return $this->parseKnnResult($result);
    }

    /**
     * 删除指定模型索引（重建前可调用）.
     */
    public function dropIndex(int $modelId): bool
    {
        if (! $this->available() || $modelId <= 0) {
            return false;
        }

        try {
            $this->raw(['FT.DROPINDEX', $this->indexName($modelId), 'DD']);
            unset($this->indexReady[$this->indexName($modelId)]);

            return true;
        } catch (\Throwable) {
            unset($this->indexReady[$this->indexName($modelId)]);

            return false;
        }
    }

    public function ensureIndex(int $modelId, int $dimension): bool
    {
        if ($modelId <= 0 || $dimension <= 0 || ! $this->available()) {
            return false;
        }

        $index = $this->indexName($modelId);
        if (! empty($this->indexReady[$index])) {
            return true;
        }

        try {
            $info = $this->raw(['FT.INFO', $index]);
            if (is_array($info)) {
                $this->indexReady[$index] = true;

                return true;
            }
        } catch (\Throwable) {
            // 索引不存在则创建
        }

        try {
            $created = $this->raw([
                'FT.CREATE', $index,
                'ON', 'HASH',
                'PREFIX', '1', $this->docPrefix($modelId),
                'SCHEMA',
                'chunk_id', 'NUMERIC', 'SORTABLE',
                'kb_id', 'NUMERIC', 'SORTABLE',
                'doc_id', 'NUMERIC', 'SORTABLE',
                'model_id', 'NUMERIC', 'SORTABLE',
                'status', 'NUMERIC', 'SORTABLE',
                'embedding', 'VECTOR', 'HNSW', '6',
                'TYPE', 'FLOAT32',
                'DIM', (string) $dimension,
                'DISTANCE_METRIC', 'COSINE',
            ]);
            if (is_string($created) && $created !== 'OK' && ! str_contains(strtolower($created), 'ok')) {
                throw new \RuntimeException($created);
            }
            $this->indexReady[$index] = true;

            return true;
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'index already exists')) {
                $this->indexReady[$index] = true;

                return true;
            }
            Log::warning('知识库向量索引创建失败', [
                'index' => $index,
                'dim'   => $dimension,
                'error' => $e->getMessage(),
                'hint'  => 'RediSearch 仅支持 DB 0，请将 REDIS_KNOWLEDGE_DB 设为 0',
            ]);

            return false;
        }
    }

    public function ensureTextIndex(): bool
    {
        if (! $this->fulltextAvailable()) {
            return false;
        }

        $index = $this->textIndexName();
        if (! empty($this->indexReady[$index])) {
            return true;
        }
        try {
            if (is_array($this->raw(['FT.INFO', $index]))) {
                $this->indexReady[$index] = true;

                return true;
            }
        } catch (\Throwable) {
        }

        try {
            $this->raw([
                'FT.CREATE', $index,
                'ON', 'HASH',
                'PREFIX', '1', $this->textDocPrefix(),
                'SCHEMA',
                'chunk_id', 'NUMERIC', 'SORTABLE',
                'kb_id', 'NUMERIC', 'SORTABLE',
                'doc_id', 'NUMERIC', 'SORTABLE',
                'status', 'NUMERIC', 'SORTABLE',
                'enabled', 'NUMERIC', 'SORTABLE',
                'search_text', 'TEXT', 'NOSTEM',
            ]);
            $this->indexReady[$index] = true;

            return true;
        } catch (\Throwable $e) {
            if (str_contains(strtolower($e->getMessage()), 'index already exists')) {
                $this->indexReady[$index] = true;

                return true;
            }
            Log::warning('知识库全文索引创建失败', ['index' => $index, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param array<int, float> $vector
     */
    public function packVector(array $vector): string
    {
        $binary = '';
        foreach ($vector as $value) {
            $binary .= pack('g', (float) $value);
        }

        return $binary;
    }

    /**
     * @param mixed $result
     *
     * @return array<int, float>
     */
    public function parseKnnResult($result): array
    {
        if (! is_array($result) || $result === []) {
            return [];
        }

        $scores = [];
        // RESP2: [total, docKey, [field, value, ...], ...]
        $count = count($result);
        for ($i = 1; $i < $count; $i += 2) {
            $fields = $result[$i + 1] ?? null;
            if (! is_array($fields)) {
                continue;
            }
            $map = [];
            for ($j = 0; $j < count($fields); $j += 2) {
                $map[(string) ($fields[$j] ?? '')] = $fields[$j + 1] ?? null;
            }
            $chunkId = (int) ($map['chunk_id'] ?? 0);
            if ($chunkId <= 0) {
                continue;
            }
            $distance = (float) ($map['dist'] ?? $map['score'] ?? 1);
            // RediSearch COSINE distance ≈ 1 - cosine_similarity
            $scores[$chunkId] = max(0.0, min(1.0, 1.0 - $distance));
        }

        return $scores;
    }

    /** @return array<int, float> */
    private function parseTextResult($result): array
    {
        if (! is_array($result) || count($result) < 3) {
            return [];
        }
        $hits = [];
        $max = 0.0;
        for ($i = 1; $i < count($result); $i += 3) {
            $score = (float) ($result[$i + 1] ?? 0);
            $fields = $result[$i + 2] ?? [];
            $chunkId = 0;
            if (is_array($fields)) {
                for ($j = 0; $j < count($fields); $j += 2) {
                    if ((string) ($fields[$j] ?? '') === 'chunk_id') {
                        $chunkId = (int) ($fields[$j + 1] ?? 0);
                        break;
                    }
                }
            }
            if ($chunkId > 0 && $score > 0) {
                $hits[$chunkId] = $score;
                $max = max($max, $score);
            }
        }

        if ($max <= 0) {
            return [];
        }
        foreach ($hits as $chunkId => $score) {
            $hits[$chunkId] = min(1.0, $score / $max);
        }

        return $hits;
    }

    private function indexName(int $modelId): string
    {
        return rtrim((string) config('knowledge.index_prefix', 'kb_vec'), ':') . ':m' . $modelId;
    }

    private function textIndexName(): string
    {
        return rtrim((string) config('knowledge.fulltext.index_prefix', 'kb_text'), ':');
    }

    /**
     * HSET 使用的相对 key（Predis executeRaw 通常不加全局前缀）.
     */
    private function docPrefix(int $modelId): string
    {
        return rtrim((string) config('knowledge.doc_prefix', 'kb:chunk:'), ':') . ':m' . $modelId . ':';
    }

    private function docKey(int $modelId, int $chunkId): string
    {
        return $this->docPrefix($modelId) . $chunkId;
    }

    private function textDocPrefix(): string
    {
        return rtrim((string) config('knowledge.fulltext.doc_prefix', 'kb:text:'), ':') . ':';
    }

    private function textDocKey(int $chunkId): string
    {
        return $this->textDocPrefix() . $chunkId;
    }

    private function tokenText(string $text): string
    {
        return implode(' ', $this->tokenize($text));
    }

    private function moduleAvailable(): bool
    {
        if ($this->moduleReady !== null) {
            return $this->moduleReady;
        }
        try {
            $modules = $this->raw(['MODULE', 'LIST']);
            $text = strtolower(json_encode($modules, JSON_UNESCAPED_UNICODE) ?: '');
            $this->moduleReady = str_contains($text, 'search') || str_contains($text, 'redisearch');
        } catch (\Throwable $e) {
            Log::debug('知识库 Redis Stack 不可用', ['error' => $e->getMessage()]);
            $this->moduleReady = false;
        }

        return $this->moduleReady;
    }

    /**
     * @param array<int, mixed> $command
     *
     * @return mixed
     */
    private function raw(array $command)
    {
        $connection = (string) config('knowledge.redis_connection', 'knowledge');
        $client     = Redis::connection($connection)->client();

        // 二进制向量不可被二次编码；保持 string 原样传给 Redis.
        if (is_object($client) && method_exists($client, 'executeRaw')) {
            return $client->executeRaw($command);
        }

        if (is_object($client) && method_exists($client, 'rawCommand')) {
            $args = $command;
            $name = (string) array_shift($args);

            return $client->rawCommand($name, ...$args);
        }

        throw new \RuntimeException('当前 Redis 客户端不支持 executeRaw/rawCommand');
    }
}
