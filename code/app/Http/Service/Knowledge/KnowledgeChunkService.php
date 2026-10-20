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
use App\Http\Dao\Knowledge\KnowledgeChunkDao;
use App\Http\Dao\Knowledge\KnowledgeDocumentDao;
use App\Http\Model\Knowledge\KnowledgeChunk;
use App\Http\Model\Knowledge\KnowledgeDocument;
use App\Http\Service\CommonService;
use App\Jobs\Knowledge\ProcessKnowledgeChunkOcrJob;
use App\Jobs\Knowledge\ProcessKnowledgeEmbeddingJob;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 知识库分块服务.
 *
 * 负责自动/手动分块维护、启停、删除、重建与向量化入队；
 * 手动/编辑分块先落库文本与 image_urls 并向量化；含图分片再入队 OCR（一分片一任务）.
 *
 * @mixin KnowledgeChunkDao
 */
class KnowledgeChunkService extends BaseService
{
    /**
     * KnowledgeChunkService constructor.
     */
    public function __construct(KnowledgeChunkDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取文档分块列表.
     *
     * @return array<string, mixed>
     */
    public function listByDocument(int $docId): array
    {
        $document       = app(KnowledgeDocumentService::class)->checkVisible($docId);
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList(
            ['doc_id' => $document->id],
            ['id', 'kb_id', 'doc_id', 'chunk_index', 'source_type', 'content', 'content_html', 'status', 'error_msg', 'enabled', 'embedding_model_id', 'embedding_dimension', 'metadata', 'image_urls', 'created_at', 'updated_at'],
            $page,
            $limit,
            ['chunk_index'=>'asc']
        );
        $list = array_map(static function (array $item): array {
            $metadata = (array) ($item['metadata'] ?? []);
            // 优先正式字段；兼容历史仅写在 metadata.embedding_error 的数据.
            $item['error_msg']       = (string) (($item['error_msg'] ?? '') ?: ($metadata['embedding_error'] ?? ''));
            $item['embedding_error'] = $item['error_msg'];
            $item['image_urls']      = array_values(array_filter(array_map('strval', (array) ($item['image_urls'] ?? []))));

            return $item;
        }, $list);
        $count = $this->dao->count(['doc_id' => $document->id]);

        return $this->listData($list, $count);
    }

    /**
     * 新增手动分块（挂在已有文档下），并入队向量化.
     *
     * 仅接收 content_html（Markdown）；content 由 Markdown 解析 + 图片 OCR 生成后入库.
     * 图片由前端上传后以链接写入 Markdown，后端只抽取保存 image_urls，不再二次上传附件.
     *
     * @param array{doc_id:int,content_html:string,image_urls?:array<int,mixed>} $data
     *
     * @throws \Exception
     */
    public function createManualChunk(array $data): int
    {
        $document   = app(KnowledgeDocumentService::class)->checkVisible((int) $data['doc_id']);
        $prepared   = $this->prepareChunkContent($data);
        $content    = $prepared['content'];
        $html       = $prepared['content_html'];
        $metaExtra  = $prepared['metadata'];
        $imageUrls  = $this->resolveManualImageUrls($html, (array) ($data['image_urls'] ?? []));

        $total = $this->dao->count(['doc_id' => $document->id]);
        if ($total >= KnowledgeEnum::MAX_CHUNKS_PER_DOCUMENT) {
            throw $this->exception('文档分块数量超出限制');
        }

        $base     = app(KnowledgeBaseService::class)->checkVisible((int) $document->kb_id);
        $maxIndex = (int) $this->dao->search(['doc_id' => $document->id])->max('chunk_index');

        $metadata = array_merge([
            'source'      => (string) $document->title,
            'chunk_index' => $maxIndex + 1,
            'manual'      => true,
        ], $metaExtra);

        $created = $this->dao->create([
            'kb_id'              => (int) $document->kb_id,
            'doc_id'             => (int) $document->id,
            'chunk_index'        => $maxIndex + 1,
            'source_type'        => KnowledgeEnum::CHUNK_MANUAL,
            'content'            => $content,
            'content_html'       => $html,
            'content_hash'       => hash('sha256', $content),
            'embedding'          => [],
            'embedding_model_id' => (int) ($base->embedding_model_id ?? 0),
            'metadata'           => $metadata,
            'image_urls'         => $imageUrls,
            'status'             => KnowledgeEnum::CHUNK_PENDING,
            'error_msg'          => '',
            'enabled'            => KnowledgeEnum::STATUS_ENABLED,
        ]);

        $chunkId = (int) $created->id;
        app(KnowledgeVectorIndex::class)->upsertText(
            $chunkId,
            (int) $document->kb_id,
            (int) $document->id,
            KnowledgeEnum::CHUNK_PENDING,
            KnowledgeEnum::STATUS_ENABLED,
            (string) $document->title,
            $content
        );
        $this->refreshDocumentChunkCount((int) $document->id);
        app(KnowledgeDocumentService::class)->refreshStats((int) $document->kb_id);
        KnowledgeSearchCache::flush();
        ProcessKnowledgeEmbeddingJob::dispatch((int) $document->kb_id, [$chunkId]);
        $this->dispatchChunkOcrJobs([$chunkId]);

        return $chunkId;
    }

    /**
     * 更新分块内容并重新向量化；不改变 source_type.
     *
     * 仅接收 content_html（Markdown）；content 由服务端解析后覆盖写入.
     *
     * @param array{content_html:string,image_urls?:array<int,mixed>} $data
     *
     * @throws \Exception
     */
    public function updateChunk(int $id, array $data): int
    {
        $chunk    = $this->getVisibleChunk($id);
        $prepared = $this->prepareChunkContent($data);
        $content  = $prepared['content'];
        $html     = $prepared['content_html'];
        $imageUrls = array_key_exists('image_urls', $data)
            ? $this->normalizeImageUrls((array) $data['image_urls'])
            : $this->resolveManualImageUrls($html, []);

        $metadata = array_merge((array) ($chunk->metadata ?: []), $prepared['metadata']);

        $update = [
            'content'      => $content,
            'content_html' => $html,
            'content_hash' => hash('sha256', $content),
            'status'       => KnowledgeEnum::CHUNK_PENDING,
            'error_msg'    => '',
            'metadata'     => $metadata,
            'image_urls'   => $imageUrls,
        ];

        $result = (int) $this->dao->update($id, $update);
        app(KnowledgeVectorIndex::class)->delete((int) $chunk->id, (int) $chunk->embedding_model_id);
        app(KnowledgeVectorIndex::class)->upsertText(
            (int) $chunk->id,
            (int) $chunk->kb_id,
            (int) $chunk->doc_id,
            KnowledgeEnum::CHUNK_PENDING,
            (int) $chunk->enabled,
            (string) $chunk->document->title,
            $content
        );
        KnowledgeSearchCache::flush();
        if ((int) $chunk->enabled === KnowledgeEnum::STATUS_ENABLED) {
            ProcessKnowledgeEmbeddingJob::dispatch((int) $chunk->kb_id, [$id]);
        }
        $this->dispatchChunkOcrJobs([$id]);

        return $result;
    }

    /**
     * 按文档同步分块启停：关闭时剔除向量索引；开启时入队重新向量化.
     *
     * @param array<int, int> $docIds
     */
    public function syncEnabledByDocuments(array $docIds, int $enabled): void
    {
        $docIds = array_values(array_unique(array_filter(array_map('intval', $docIds))));
        if ($docIds === []) {
            return;
        }

        $enabled = $enabled === KnowledgeEnum::STATUS_ENABLED
            ? KnowledgeEnum::STATUS_ENABLED
            : KnowledgeEnum::STATUS_DISABLED;

        if ($enabled === KnowledgeEnum::STATUS_DISABLED) {
            $chunks = $this->dao->search(['doc_id' => $docIds])
                ->get(['id', 'embedding_model_id'])
                ->map(static fn ($row): array => [
                    'id'                 => (int) $row->id,
                    'embedding_model_id' => (int) $row->embedding_model_id,
                ])
                ->all();
            $this->dao->search(['doc_id' => $docIds])->update([
                'enabled' => KnowledgeEnum::STATUS_DISABLED,
            ]);
            if ($chunks !== []) {
                app(KnowledgeVectorIndex::class)->deleteMany($chunks);
                KnowledgeSearchCache::flush();
            }

            return;
        }

        $pendingIds = $this->dao->search(['doc_id' => $docIds])
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        if ($pendingIds === []) {
            return;
        }

        $this->dao->search(['doc_id' => $docIds])->update([
            'enabled'   => KnowledgeEnum::STATUS_ENABLED,
            'status'    => KnowledgeEnum::CHUNK_PENDING,
            'error_msg' => '',
        ]);
        KnowledgeSearchCache::flush();

        $rows = $this->dao->search(['id' => $pendingIds])->with(['document'])->get();
        foreach ($rows as $row) {
            app(KnowledgeVectorIndex::class)->upsertChunkText($row);
        }
        $byKb = $rows->groupBy('kb_id');
        foreach ($byKb as $kbId => $rows) {
            $chunkIds = $rows->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            if ($chunkIds !== []) {
                ProcessKnowledgeEmbeddingJob::dispatch((int) $kbId, $chunkIds);
            }
        }
    }

    /**
     * 开启或关闭分块.
     *
     * 关闭：删除向量索引并排除检索；开启：置为待向量化并入队写回索引.
     *
     * @throws \Exception
     */
    public function setEnabled(int $id, int $enabled): void
    {
        $chunk   = $this->getVisibleChunk($id);
        $enabled = $enabled === KnowledgeEnum::STATUS_ENABLED
            ? KnowledgeEnum::STATUS_ENABLED
            : KnowledgeEnum::STATUS_DISABLED;

        if ((int) $chunk->enabled === $enabled) {
            return;
        }

        if ($enabled === KnowledgeEnum::STATUS_DISABLED) {
            $this->dao->update($id, ['enabled' => KnowledgeEnum::STATUS_DISABLED]);
            app(KnowledgeVectorIndex::class)->delete((int) $chunk->id, (int) $chunk->embedding_model_id);
            KnowledgeSearchCache::flush();

            return;
        }

        $this->dao->update($id, [
            'enabled'   => KnowledgeEnum::STATUS_ENABLED,
            'status'    => KnowledgeEnum::CHUNK_PENDING,
            'error_msg' => '',
        ]);
        app(KnowledgeVectorIndex::class)->upsertText(
            (int) $chunk->id,
            (int) $chunk->kb_id,
            (int) $chunk->doc_id,
            KnowledgeEnum::CHUNK_PENDING,
            KnowledgeEnum::STATUS_ENABLED,
            (string) $chunk->document->title,
            (string) $chunk->content
        );
        KnowledgeSearchCache::flush();
        ProcessKnowledgeEmbeddingJob::dispatch((int) $chunk->kb_id, [$id]);
    }

    /**
     * 手动触发向量化（失败或待处理；关闭态须先开启）.
     *
     * @throws \Exception
     */
    public function embedChunk(int $id): void
    {
        $chunk = $this->getVisibleChunk($id);
        if ((int) $chunk->enabled !== KnowledgeEnum::STATUS_ENABLED) {
            throw $this->exception('分块已关闭，请先开启后再向量化');
        }

        $status = (int) $chunk->status;
        if ($status === KnowledgeEnum::CHUNK_DONE && (int) $chunk->embedding_dimension > 0) {
            return;
        }

        $metadata = (array) ($chunk->metadata ?: []);
        unset($metadata['embedding_error']);
        $this->dao->update($id, [
            'status'    => KnowledgeEnum::CHUNK_PENDING,
            'error_msg' => '',
            'metadata'  => $metadata,
        ]);
        KnowledgeSearchCache::flush();
        ProcessKnowledgeEmbeddingJob::dispatch((int) $chunk->kb_id, [$id]);
    }

    /**
     * 删除单个分块并清理索引、刷新计数.
     *
     * @throws \Exception
     */
    public function deleteChunk(int $id): void
    {
        $chunk = $this->getVisibleChunk($id);
        $kbId  = (int) $chunk->kb_id;
        $docId = (int) $chunk->doc_id;

        app(KnowledgeVectorIndex::class)->delete((int) $chunk->id, (int) $chunk->embedding_model_id);
        $this->dao->delete($id);
        $this->refreshDocumentChunkCount($docId);
        app(KnowledgeDocumentService::class)->refreshStats($kbId);
        KnowledgeSearchCache::flush();
    }

    /**
     * 重建文档自动分块；保留手动分块，并顺延其 chunk_index 避免冲突.
     *
     * 图文分离：正文分片只保留 Markdown/HTML 内嵌图；文档级页图/附图全部建为
     * image_only 独立分片（一分片一图一 OCR），禁止按 chunk 下标硬挂到无关文本.
     *
     * @param list<string> $documentImageUrls 文档级图片地址（扫描页、Office 附图等）
     *
     * @return array<int, int> 新建自动分块 ID 列表（用于入队向量化）
     *
     * @throws \Exception
     */
    public function rebuild(KnowledgeDocument $document, string $content, array $base, array $documentImageUrls = []): array
    {
        $oldAutoChunks = $this->dao->search([
            'doc_id'      => $document->id,
            'source_type' => KnowledgeEnum::CHUNK_AUTO,
        ])
            ->get(['id', 'embedding_model_id'])
            ->map(static fn ($row): array => [
                'id'                 => (int) $row->id,
                'embedding_model_id' => (int) $row->embedding_model_id,
            ])
            ->all();
        $this->dao->delete([
            'doc_id'      => $document->id,
            'source_type' => KnowledgeEnum::CHUNK_AUTO,
        ]);
        app(KnowledgeVectorIndex::class)->deleteMany($oldAutoChunks);
        KnowledgeSearchCache::flush();

        $size        = (int) $base['chunk_size'];
        $overlap     = (int) $base['chunk_overlap'];
        $content     = $this->localizeContentImages(KnowledgeTextNormalizer::normalize($content));
        $documentImageUrls = $this->normalizeImageUrls($documentImageUrls);
        $manualCount = $this->dao->count([
            'doc_id'      => $document->id,
            'source_type' => KnowledgeEnum::CHUNK_MANUAL,
        ]);

        // 仅有文档图、无有效正文时不建空文本分片，全部走 image_only.
        $parts  = [];
        $chunks = [];
        if (! $this->isImagePlaceholderContent($content) || $documentImageUrls === []) {
            $parts  = $this->splitParts($content, $size, $overlap);
            $chunks = array_column($parts, 'content');
        }

        $inlineUrlSet = [];
        foreach ($chunks as $chunkText) {
            foreach ($this->extractImageUrlsFromText((string) $chunkText) as $inlineUrl) {
                $inlineUrlSet[$inlineUrl] = true;
            }
        }
        // 已出现在正文内嵌的文档级 URL 不再重复建 image_only，避免双份检索噪声.
        $standaloneImages = array_values(array_filter(
            $documentImageUrls,
            static fn (string $url): bool => ! isset($inlineUrlSet[$url])
        ));

        $textChunkCount = count($chunks);
        if ($textChunkCount + count($standaloneImages) + $manualCount > KnowledgeEnum::MAX_CHUNKS_PER_DOCUMENT) {
            throw $this->exception('文档分块数量超出限制');
        }

        $useSemantic = $this->semanticChunkEnabled();
        $chunkIds    = [];
        /** @var list<array{url:string,caption:string}> $detachedInlineImages */
        $detachedInlineImages = [];
        $carrierMaxChars      = max(1, (int) config('ai.reply_images.image_chunk_min_chars', 50));
        foreach ($chunks as $index => $chunk) {
            $chunk = KnowledgeTextNormalizer::normalize($chunk);
            $metadata = [
                'source'      => $document->title,
                'chunk_index' => $index + 1,
            ];
            if ($useSemantic) {
                $metadata                = array_merge($metadata, (array) ($parts[$index]['metadata'] ?? []));
                $metadata['chunk_index'] = $index + 1;
                $metadata['source']      = $document->title;
            }

            $chunkImageUrls = $this->normalizeImageUrls($this->extractImageUrlsFromText($chunk));
            if ($chunkImageUrls !== []) {
                if (mb_strlen($chunk) < $carrierMaxChars) {
                    // 短正文+内嵌图：结构上视为图载体（跳过短文质量过滤）.
                    $metadata['image_source']  = 'inline';
                    $metadata['reply_display'] = true;
                } else {
                    // 长文夹图：图拆到独立短分片，避免弱相关长文检索误挂图，且便于按配图说明召回.
                    $caption = $this->inlineImageCaption($chunk, $carrierMaxChars);
                    foreach ($chunkImageUrls as $imageUrl) {
                        $detachedInlineImages[] = [
                            'url'     => $imageUrl,
                            'caption' => $caption,
                        ];
                    }
                    $chunk          = $this->stripInlineImageMarkup($chunk);
                    $chunkImageUrls = [];
                }
            }

            $created = $this->dao->create([
                'kb_id'              => $document->kb_id,
                'doc_id'             => $document->id,
                'chunk_index'        => $index + 1,
                'source_type'        => KnowledgeEnum::CHUNK_AUTO,
                'content'            => $chunk,
                'content_html'       => null,
                'content_hash'       => hash('sha256', $chunk),
                'embedding'          => [],
                'embedding_model_id' => (int) ($base['embedding_model_id'] ?? 0),
                'metadata'           => $metadata,
                'image_urls'         => $chunkImageUrls,
                'status'             => KnowledgeEnum::CHUNK_PENDING,
                'error_msg'          => '',
                'enabled'            => KnowledgeEnum::STATUS_ENABLED,
            ]);
            app(KnowledgeVectorIndex::class)->upsertText(
                (int) $created->id,
                (int) $document->kb_id,
                (int) $document->id,
                KnowledgeEnum::CHUNK_PENDING,
                KnowledgeEnum::STATUS_ENABLED,
                (string) $document->title,
                $chunk
            );
            $chunkIds[] = (int) $created->id;
        }

        // 长文拆出的内嵌图：短 caption + 图，立即可向量化（非 image_only OCR 通道）.
        foreach ($detachedInlineImages as $detached) {
            $index    = count($chunkIds);
            $caption  = KnowledgeTextNormalizer::normalize((string) ($detached['caption'] ?? ''));
            if ($caption === '') {
                $caption = '（文档配图）';
            }
            if (mb_strlen($caption) >= $carrierMaxChars) {
                $caption = mb_substr($caption, 0, max(1, $carrierMaxChars - 1));
            }
            $metadata = [
                'source'        => $document->title,
                'chunk_index'   => $index + 1,
                'image_source'  => 'inline_detached',
                'reply_display' => true,
            ];
            $created = $this->dao->create([
                'kb_id'              => $document->kb_id,
                'doc_id'             => $document->id,
                'chunk_index'        => $index + 1,
                'source_type'        => KnowledgeEnum::CHUNK_AUTO,
                'content'            => $caption,
                'content_html'       => null,
                'content_hash'       => hash('sha256', $caption),
                'embedding'          => [],
                'embedding_model_id' => (int) ($base['embedding_model_id'] ?? 0),
                'metadata'           => $metadata,
                'image_urls'         => [$detached['url']],
                'status'             => KnowledgeEnum::CHUNK_PENDING,
                'error_msg'          => '',
                'enabled'            => KnowledgeEnum::STATUS_ENABLED,
            ]);
            app(KnowledgeVectorIndex::class)->upsertText(
                (int) $created->id,
                (int) $document->kb_id,
                (int) $document->id,
                KnowledgeEnum::CHUNK_PENDING,
                KnowledgeEnum::STATUS_ENABLED,
                (string) $document->title,
                $caption
            );
            $chunkIds[] = (int) $created->id;
        }

        $docTitle = (string) $document->title;
        foreach ($standaloneImages as $offset => $imageUrl) {
            $index   = count($chunkIds);
            $imgNo   = $offset + 1;
            $content = $docTitle !== ''
                ? sprintf('文档《%s》图片%d', $docTitle, $imgNo)
                : sprintf('文档图片%d', $imgNo);
            $metadata = [
                'source'        => $document->title,
                'chunk_index'   => $index + 1,
                'image_only'    => true,
                'reply_display' => true,
                'image_source'  => 'document',
            ];
            $created = $this->dao->create([
                'kb_id'              => $document->kb_id,
                'doc_id'             => $document->id,
                'chunk_index'        => $index + 1,
                'source_type'        => KnowledgeEnum::CHUNK_AUTO,
                'content'            => $content,
                'content_html'       => null,
                'content_hash'       => hash('sha256', $content),
                'embedding'          => [],
                'embedding_model_id' => (int) ($base['embedding_model_id'] ?? 0),
                'metadata'           => $metadata,
                'image_urls'         => [$imageUrl],
                'status'             => KnowledgeEnum::CHUNK_PENDING,
                'error_msg'          => '',
                'enabled'            => KnowledgeEnum::STATUS_ENABLED,
            ]);
            // image_only 等 OCR 回填后再入向量索引，避免占位文污染检索.
            $chunkIds[] = (int) $created->id;
        }

        $autoCount = count($chunkIds);
        $this->renumberManualChunks((int) $document->id, $autoCount);
        $this->refreshDocumentChunkCount((int) $document->id);

        return $chunkIds;
    }

    /**
     * 从长文内嵌图附近抽取短 caption（取文末有效行，截断到载体字数上限）.
     */
    private function inlineImageCaption(string $chunk, int $maxChars): string
    {
        $lines  = preg_split('/\R/u', $chunk) ?: [];
        $picked = [];
        for ($i = count($lines) - 1; $i >= 0; --$i) {
            $line = trim((string) $lines[$i]);
            if ($line === '' || $line === '---') {
                continue;
            }
            // 跳过图片标记 / 纯图片 URL / 常见本地文件名残片.
            if (preg_match('/!\[[^\]]*\]\([^)]+\)/u', $line) === 1) {
                continue;
            }
            if (preg_match('#https?://\S+\.(?:jpg|jpeg|png|gif|webp|bmp)(?:\?\S*)?$#iu', $line) === 1) {
                continue;
            }
            if (preg_match('/\.(?:jpg|jpeg|png|gif|webp|bmp)$/iu', $line) === 1) {
                continue;
            }
            array_unshift($picked, $line);
            if (mb_strlen(implode("\n", $picked)) >= $maxChars) {
                break;
            }
        }
        $caption = KnowledgeTextNormalizer::normalize(implode("\n", $picked));
        if ($caption === '') {
            return '（文档配图）';
        }
        if (mb_strlen($caption) >= $maxChars) {
            return mb_substr($caption, 0, max(1, $maxChars - 1));
        }

        return $caption;
    }

    /**
     * 从正文中去掉 Markdown/HTML 图片标记，保留说明文字.
     */
    private function stripInlineImageMarkup(string $chunk): string
    {
        $chunk = preg_replace('/!\[[^\]]*\]\([^)]+\)/u', '', $chunk) ?? $chunk;
        $chunk = preg_replace('/<img\b[^>]*>/iu', '', $chunk) ?? $chunk;

        return KnowledgeTextNormalizer::normalize($chunk);
    }

    /**
     * 正文是否仅为「无有效文本」占位（空或历史占位文案）.
     */
    private function isImagePlaceholderContent(string $content): bool
    {
        $normalized = KnowledgeTextNormalizer::normalize($content);

        return $normalized === '' || $normalized === '（文档含图片）';
    }

    /**
     * 筛选可立即向量化的分片 ID（排除待 OCR 的 image_only）.
     *
     * @param array<int, int> $chunkIds
     *
     * @return array<int, int>
     */
    public function filterImmediateEmbedChunkIds(array $chunkIds): array
    {
        $chunkIds = array_values(array_filter(array_map('intval', $chunkIds)));
        if ($chunkIds === []) {
            return [];
        }

        $rows = $this->dao->search(['id' => $chunkIds])
            ->get(['id', 'metadata']);
        $ready = [];
        foreach ($rows as $row) {
            $meta = (array) ($row->metadata ?? []);
            if (! empty($meta['image_only'])) {
                continue;
            }
            $ready[] = (int) $row->id;
        }

        return $ready;
    }

    /**
     * 删除知识库分块.
     */
    public function deleteByKnowledge(int $kbId): int
    {
        $chunks = $this->dao->search(['kb_id' => $kbId])
            ->get(['id', 'embedding_model_id'])
            ->map(static fn ($row): array => [
                'id'                 => (int) $row->id,
                'embedding_model_id' => (int) $row->embedding_model_id,
            ])
            ->all();
        $deleted = (int) $this->dao->delete(['kb_id' => $kbId]);
        if ($deleted > 0) {
            app(KnowledgeVectorIndex::class)->deleteMany($chunks);
            KnowledgeSearchCache::flush();
        }

        return $deleted;
    }

    /**
     * 删除文档分块.
     */
    public function deleteByDocument(int $docId): int
    {
        $chunks = $this->dao->search(['doc_id' => $docId])
            ->get(['id', 'embedding_model_id'])
            ->map(static fn ($row): array => [
                'id'                 => (int) $row->id,
                'embedding_model_id' => (int) $row->embedding_model_id,
            ])
            ->all();
        $deleted = (int) $this->dao->delete(['doc_id' => $docId]);
        if ($deleted > 0) {
            app(KnowledgeVectorIndex::class)->deleteMany($chunks);
            KnowledgeSearchCache::flush();
        }

        return $deleted;
    }

    /**
     * 刷新文档分块计数（含关闭与手动分块）.
     */
    public function refreshDocumentChunkCount(int $docId): void
    {
        $count = $this->dao->count(['doc_id' => $docId]);
        app(KnowledgeDocumentDao::class)->update($docId, ['chunk_count' => $count]);
    }

    /**
     * 切分文本（对外仍返回纯字符串列表，兼容现有调用方）.
     *
     * features.semantic_chunk=true 时走结构感知切分；异常回退硬切分.
     *
     * @return array<int, string>
     */
    public function split(string $content, int $size = KnowledgeEnum::DEFAULT_CHUNK_SIZE, int $overlap = KnowledgeEnum::DEFAULT_CHUNK_OVERLAP): array
    {
        return array_values(array_column($this->splitParts($content, $size, $overlap), 'content'));
    }

    /**
     * 切分文本并返回带 metadata 的分块列表.
     *
     * @return list<array{content: string, metadata: array<string, mixed>}>
     */
    private function splitParts(string $content, int $size, int $overlap): array
    {
        $splitter = app(KnowledgeChunkSplitter::class);
        if (! $this->semanticChunkEnabled()) {
            return $splitter->splitLegacy($content, $size, $overlap);
        }

        try {
            return $splitter->split($content, $size, $overlap);
        } catch (\Throwable $e) {
            Log::warning('知识库结构感知分块失败，回退硬切分', [
                'error' => $e->getMessage(),
            ]);

            return $splitter->splitLegacy($content, $size, $overlap);
        }
    }

    /**
     * 是否启用结构感知切分（默认开启；异常时回退硬切分）.
     */
    private function semanticChunkEnabled(): bool
    {
        try {
            return app()->bound('config') && (bool) config('knowledge.features.semantic_chunk');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 对含 image_urls 的分片按图投递 OCR 队列任务（一图一任务）.
     *
     * @param array<int, int> $chunkIds
     */
    public function dispatchChunkOcrJobs(array $chunkIds): void
    {
        $chunkIds = array_values(array_unique(array_filter(array_map('intval', $chunkIds))));
        if ($chunkIds === []) {
            return;
        }

        $maxPerChunk = max(1, (int) config('knowledge.chunk_ocr.max_images', 10));
        $rows        = $this->dao->search(['id' => $chunkIds])
            ->get(['id', 'image_urls'])
            ->all();
        foreach ($rows as $row) {
            $urls = $this->normalizeImageUrls((array) ($row->image_urls ?? []));
            if ($urls === []) {
                continue;
            }
            foreach (array_slice($urls, 0, $maxPerChunk) as $url) {
                ProcessKnowledgeChunkOcrJob::dispatch((int) $row->id, (string) $url);
            }
        }
    }

    /**
     * 兼容旧「整片 OCR」队列：按图依次处理（仍受单图超时保护的应优先用 processChunkImageOcr）.
     */
    public function processChunkOcr(int $chunkId): void
    {
        /** @var KnowledgeChunk|null $chunk */
        $chunk = $this->dao->get($chunkId);
        if (! $chunk) {
            return;
        }
        $urls = $this->normalizeImageUrls((array) ($chunk->image_urls ?? []));
        foreach ($urls as $url) {
            $this->processChunkImageOcr($chunkId, $url);
        }
    }

    /**
     * 分片单图 OCR：识别一张图并合并进 content，成功后重新入队向量化.
     *
     * 同 chunk 多任务并发时用锁串行读改写；超时/失败由 Job failed() 记日志，不改失败态.
     */
    public function processChunkImageOcr(int $chunkId, string $imageUrl): void
    {
        $imageUrl = trim($imageUrl);
        if ($chunkId <= 0 || $imageUrl === '') {
            return;
        }

        $lock = Cache::lock('knowledge:chunk-ocr:' . $chunkId, 90);
        try {
            $lock->block(60, function () use ($chunkId, $imageUrl): void {
                $this->applyChunkImageOcr($chunkId, $imageUrl);
            });
        } catch (LockTimeoutException $e) {
            Log::warning('知识库分片 OCR 获取锁超时，跳过本图', [
                'chunk_id'  => $chunkId,
                'image_url' => $imageUrl,
                'error'     => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('知识库分片单图 OCR 软失败，保留原文本', [
                'chunk_id'  => $chunkId,
                'image_url' => $imageUrl,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * 在持锁状态下执行单图 OCR 回填.
     */
    private function applyChunkImageOcr(int $chunkId, string $imageUrl): void
    {
        /** @var KnowledgeChunk|null $chunk */
        $chunk = $this->dao->get($chunkId, ['*'], ['document']);
        if (! $chunk) {
            return;
        }

        $meta          = (array) ($chunk->metadata ?: []);
        $appliedUrls   = array_values(array_filter(array_map('strval', (array) ($meta['ocr_applied_urls'] ?? []))));
        $failedUrls    = array_values(array_filter(array_map('strval', (array) ($meta['ocr_failed_urls'] ?? []))));
        if (in_array($imageUrl, $appliedUrls, true)) {
            return;
        }

        $contentService = app(KnowledgeChunkContentService::class);
        $paths          = $contentService->resolveImagePaths([$imageUrl]);
        if ($paths === []) {
            $failedUrls[] = $imageUrl;
            $meta['ocr_failed_urls'] = array_values(array_unique($failedUrls));
            $meta['ocr_async']       = true;
            $this->dao->update($chunkId, ['metadata' => $meta]);
            Log::info('知识库分片单图 OCR 跳过：图片路径不可读', [
                'chunk_id'  => $chunkId,
                'image_url' => $imageUrl,
            ]);
            $this->embedImageOnlyFallback($chunk);

            return;
        }

        $ocrService = app(KnowledgeImageOcrService::class);
        $ocrText    = $ocrService->recognize($paths[0]);
        $before     = (string) $chunk->content;

        // OCR 空结果：记失败并让 image_only 用 caption 兜底入向量.
        if ($ocrText === '') {
            $failedUrls[] = $imageUrl;
            $meta['ocr_failed_urls'] = array_values(array_unique($failedUrls));
            $meta['ocr_image_count'] = count($this->normalizeImageUrls((array) ($chunk->image_urls ?? [])));
            $meta['ocr_applied']     = $appliedUrls !== [];
            $meta['ocr_async']       = true;
            $this->dao->update($chunkId, ['metadata' => $meta]);
            $this->embedImageOnlyFallback($chunk);

            return;
        }

        $decision = app(KnowledgeOcrEmbedPolicy::class)->resolve($before, $ocrText, $meta);
        $after    = (string) $decision['content'];
        $meta     = array_merge($meta, (array) $decision['metadata']);

        $appliedUrls[]            = $imageUrl;
        $meta['ocr_applied_urls'] = array_values(array_unique($appliedUrls));
        $meta['ocr_failed_urls']  = array_values(array_diff($failedUrls, [$imageUrl]));
        $meta['ocr_image_count']  = count($this->normalizeImageUrls((array) ($chunk->image_urls ?? [])));
        $meta['ocr_applied']      = true;
        $meta['ocr_async']        = true;
        unset($meta['ocr_pending']);

        // caption_only 时 content 可能不变，但仍需落 metadata 并让 image_only 入向量.
        $payload = [
            'metadata'  => $meta,
            'status'    => KnowledgeEnum::CHUNK_PENDING,
            'error_msg' => '',
        ];
        if ($after !== $before) {
            $payload['content']      = $after;
            $payload['content_hash'] = hash('sha256', $after);
        }
        $this->dao->update($chunkId, $payload);

        $title = (string) ($chunk->document->title ?? '');
        app(KnowledgeVectorIndex::class)->upsertText(
            $chunkId,
            (int) $chunk->kb_id,
            (int) $chunk->doc_id,
            KnowledgeEnum::CHUNK_PENDING,
            (int) $chunk->enabled,
            $title,
            $after
        );
        KnowledgeSearchCache::flush();
        if ((int) $chunk->enabled === KnowledgeEnum::STATUS_ENABLED) {
            ProcessKnowledgeEmbeddingJob::dispatch((int) $chunk->kb_id, [$chunkId]);
        }
    }
    /**
     * image_only 分片 OCR 失败时，用标题占位文兜底入向量，至少可按文档名召回.
     */
    private function embedImageOnlyFallback(KnowledgeChunk $chunk): void
    {
        $meta = (array) ($chunk->metadata ?? []);
        if (empty($meta['image_only'])) {
            return;
        }
        if ((int) $chunk->enabled !== KnowledgeEnum::STATUS_ENABLED) {
            return;
        }

        ProcessKnowledgeEmbeddingJob::dispatch((int) $chunk->kb_id, [(int) $chunk->id]);
    }

    /**
     * 校验分块存在且所属知识库可见.
     *
     * @throws \Exception
     */
    private function getVisibleChunk(int $id): KnowledgeChunk
    {
        /** @var KnowledgeChunk|null $chunk */
        $chunk = $this->dao->get($id, ['*'], ['document']);
        if (! $chunk || ! $chunk->document) {
            throw $this->exception('分块不存在');
        }

        app(KnowledgeBaseService::class)->checkVisible((int) $chunk->kb_id);

        return $chunk;
    }

    /**
     * 解析 Markdown 分块生成 content / content_html / metadata（同步不做 OCR）.
     *
     * 入参仅使用 content_html；content 一律由服务端从 Markdown 解析生成；
     * 含图分片落库后由 {@see dispatchChunkOcrJobs()} 异步 OCR.
     *
     * @param array{content_html?:string} $data
     *
     * @return array{content:string,content_html:string,metadata:array<string,mixed>}
     *
     * @throws \Exception
     */
    private function prepareChunkContent(array $data): array
    {
        $htmlInput = trim((string) ($data['content_html'] ?? ''));
        if ($htmlInput === '') {
            throw $this->exception('请填写分块 Markdown 内容');
        }

        $prepared = app(KnowledgeChunkContentService::class)->prepare($htmlInput, false);
        $imageUrls = app(KnowledgeChunkContentService::class)->extractImageUrls($htmlInput);
        if ($prepared['content'] === '' && $imageUrls === []) {
            throw $this->exception('分块内容为空，请补充文字或图片');
        }
        if ($prepared['content'] === '') {
            $prepared['content'] = '（含图片）';
        }

        return $prepared;
    }

    /**
     * 手动分块图片链接：优先请求传入，否则从 Markdown 抽取（前端已上传，不再本地化）.
     *
     * @param array<int, mixed> $requested
     *
     * @return list<string>
     */
    private function resolveManualImageUrls(string $contentHtml, array $requested): array
    {
        if ($requested !== []) {
            return $this->normalizeImageUrls($requested);
        }

        return $this->normalizeImageUrls(
            app(KnowledgeChunkContentService::class)->extractImageUrls($contentHtml)
        );
    }

    /**
     * 归一化图片链接数组.
     *
     * @param array<int, mixed> $urls
     *
     * @return list<string>
     */
    private function normalizeImageUrls(array $urls): array
    {
        $max = max(1, (int) config('ai.reply_images.max_per_chunk', 10));
        $validator = app(KnowledgeChunkContentService::class);
        $normalized = [];
        foreach ($urls as $url) {
            $url = trim((string) $url);
            if (! $validator->isValidImageUrl($url)) {
                continue;
            }
            $normalized[$url] = true;
            if (count($normalized) >= $max) {
                break;
            }
        }

        return array_keys($normalized);
    }

    /**
     * 自动分片前：将正文中的远程图片按系统上传配置本地化，并回写链接.
     */
    private function localizeContentImages(string $content): string
    {
        $urls = $this->extractImageUrlsFromText($content);
        if ($urls === []) {
            return $content;
        }

        $map = [];
        foreach ($urls as $url) {
            if ($this->isLocalImageUrl($url)) {
                $map[$url] = $url;
                continue;
            }
            try {
                $result = app(CommonService::class)->uploadFromUrl($url, [
                    'upload_type' => sys_config('upload_type', 1),
                    'entId'       => 1,
                    'way'         => 1,
                ]);
                $newUrl = (string) ($result['new_url'] ?? '');
                if ($newUrl !== '') {
                    $map[$url] = $newUrl;
                }
            } catch (\Throwable $e) {
                Log::warning('知识库自动分片图片本地化失败', [
                    'url'   => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($map === []) {
            return $content;
        }

        return strtr($content, $map);
    }

    /**
     * 从文本/Markdown 抽取图片链接.
     *
     * @return list<string>
     */
    private function extractImageUrlsFromText(string $text): array
    {
        return $this->normalizeImageUrls(
            app(KnowledgeChunkContentService::class)->extractImageUrls($text)
        );
    }

    /**
     * 是否已是本站/本地上传路径，无需再次转存.
     */
    private function isLocalImageUrl(string $url): bool
    {
        $lower = strtolower($url);
        if (str_starts_with($lower, '/uploads/') || str_starts_with($lower, 'uploads/')) {
            return true;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        if ($path !== '' && (str_contains($path, '/uploads/') || str_starts_with($path, '/attach/'))) {
            return true;
        }

        return false;
    }

    /**
     * 将手动分块序号顺延到自动分块之后，避免 chunk_index 冲突.
     */
    private function renumberManualChunks(int $docId, int $autoCount): void
    {
        $manuals = $this->dao->search([
            'doc_id'      => $docId,
            'source_type' => KnowledgeEnum::CHUNK_MANUAL,
        ])
            ->orderBy('chunk_index')
            ->orderBy('id')
            ->get(['id', 'metadata']);

        $index = $autoCount;
        foreach ($manuals as $manual) {
            ++$index;
            $metadata                 = (array) ($manual->metadata ?: []);
            $metadata['chunk_index']  = $index;
            $this->dao->update((int) $manual->id, [
                'chunk_index' => $index,
                'metadata'    => $metadata,
            ]);
        }
    }
}
