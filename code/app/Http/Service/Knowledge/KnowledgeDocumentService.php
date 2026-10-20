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

use App\Constants\AttachEnum;
use App\Constants\KnowledgeEnum;
use App\Http\Dao\Knowledge\KnowledgeBaseDao;
use App\Http\Dao\Knowledge\KnowledgeDocumentDao;
use App\Http\Model\Knowledge\KnowledgeDocument;
use App\Http\Service\Attach\AttachService;
use App\Jobs\Knowledge\ProcessKnowledgeDocumentJob;
use App\Jobs\Knowledge\ProcessKnowledgeEmbeddingJob;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\Log;

/**
 * 知识库文档服务.
 * @mixin KnowledgeDocumentDao
 */
class KnowledgeDocumentService extends BaseService
{
    /**
     * 列表默认字段，避免返回大段解析正文.
     */
    private const LIST_FIELDS = [
        'id',
        'kb_id',
        'uid',
        'title',
        'file_name',
        'file_type',
        'file_size',
        'file_path',
        'attach_id',
        'status',
        'enabled',
        'error_msg',
        'chunk_count',
        'processed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * KnowledgeDocumentService constructor.
     */
    public function __construct(KnowledgeDocumentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取文档列表.
     * @param mixed $sort
     */
    public function getList(array $where, array $field = self::LIST_FIELDS, $sort = ['id' => 'desc'], array $with = ['user']): array
    {
        $baseService = app(KnowledgeBaseService::class);
        if (! empty($where['kb_id'])) {
            $baseService->checkVisible((int) $where['kb_id']);
        } else {
            $where['kb_id'] = $baseService->visibleIds();
        }

        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);

        return $this->listData($list, $count);
    }

    /**
     * 获取文档详情.
     */
    public function info(int $id): array
    {
        $document = $this->checkVisible($id);
        $data     = $document->load(['user', 'base'])->toArray();
        $data['content_preview'] = mb_substr((string) ($data['content_text'] ?? ''), 0, 500);

        return $data;
    }

    /**
     * 上传文档.
     * @return KnowledgeDocument[]
     */
    public function upload(int $kbId, array $attachIds): array
    {
        $attachIds = $this->normalizeAttachIds($attachIds);
        app(KnowledgeBaseService::class)->checkVisible($kbId);

        $currentCount = $this->dao->count(['kb_id' => $kbId]);
        if ($currentCount + count($attachIds) > KnowledgeEnum::MAX_DOCUMENTS) {
            throw $this->exception('知识库文档数量超出限制');
        }

        $validatedAttaches = [];
        foreach ($attachIds as $attachId) {
            $attach = app(AttachService::class)->get($attachId);
            if (! $attach) {
                throw $this->exception('附件不存在');
            }
            $this->assertAttachRelationAvailable($attach);
            $validatedAttaches[] = $this->validateAttachFile($attach);
        }

        return $this->transaction(function () use ($kbId, $validatedAttaches) {
            $documents = [];
            foreach ($validatedAttaches as $validatedAttach) {
                $documents[] = $this->createFromAttach($kbId, $validatedAttach);
            }

            return $documents;
        });
    }

    /**
     * 根据附件创建文档.
     * @param array{attach_id:int,att_dir:string,file_name:string,file_type:string,file_size:int} $validatedAttach
     */
    protected function createFromAttach(int $kbId, array $validatedAttach): KnowledgeDocument
    {
        $attachId = (int) $validatedAttach['attach_id'];
        $doc      = $this->dao->create([
            'kb_id'       => $kbId,
            'uid'         => (int) auth('admin')->id(),
            'title'       => pathinfo((string) $validatedAttach['file_name'], PATHINFO_FILENAME),
            'file_name'   => (string) $validatedAttach['file_name'],
            'file_type'   => (string) $validatedAttach['file_type'],
            'file_size'   => (int) $validatedAttach['file_size'],
            'file_path'   => (string) $validatedAttach['att_dir'],
            'attach_id'   => $attachId,
            'status'      => KnowledgeEnum::DOCUMENT_PENDING,
            'enabled'     => KnowledgeEnum::STATUS_ENABLED,
            'error_msg'   => '',
            'chunk_count' => 0,
        ]);

        app(AttachService::class)->update(['id' => $attachId], [
            'relation_id'   => (int) $doc->id,
            'relation_type' => AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_KNOWLEDGE],
        ]);

        ProcessKnowledgeDocumentJob::dispatch((int) $doc->id);

        return $doc->refresh();
    }

    /**
     * 重新处理文档.
     */
    public function reprocess(int $id): array
    {
        $document = $this->checkVisible($id);
        app(KnowledgeBaseService::class)->checkVisible((int) $document->kb_id);

        $this->dao->update($id, [
            'status'       => KnowledgeEnum::DOCUMENT_PENDING,
            'error_msg'    => '',
            'processed_at' => null,
        ]);
        ProcessKnowledgeDocumentJob::dispatch($id);

        return $this->info($id);
    }

    /**
     * 手动向量化文档分块（不重新分片）.
     * 仅处理待向量化 / 失败 / 已完成但无向量的已开启分块；可选覆盖向量模型配置 ID.
     *
     * @param int $id               文档 ID
     * @param int $embeddingModelId 可选 AI 模型配置 ID，0 表示使用知识库绑定模型
     *
     * @throws \Exception
     */
    public function embedDocument(int $id, int $embeddingModelId = 0): void
    {
        $document    = $this->checkVisible($id);
        $baseService = app(KnowledgeBaseService::class);
        $baseService->checkVisible((int) $document->kb_id);

        if ($embeddingModelId > 0) {
            $baseService->assertEmbeddingModel($embeddingModelId);
        }

        $queued = app(KnowledgeEmbeddingService::class)->requeueDocument(
            (int) $document->kb_id,
            (int) $document->id,
            $embeddingModelId
        );
        if ($queued <= 0) {
            throw $this->exception('没有需要向量化的分块');
        }

        if ((string) ($document->error_msg ?? '') !== '') {
            $this->dao->update($id, ['error_msg' => '']);
        }
    }

    /**
     * 删除文档.
     */
    public function deleteInfo(int $id): bool
    {
        $document = $this->checkVisible($id);
        app(KnowledgeBaseService::class)->checkVisible((int) $document->kb_id);

        return $this->transaction(function () use ($document) {
            app(KnowledgeChunkService::class)->deleteByDocument((int) $document->id);
            $this->unbindAttach((int) ($document->attach_id ?? 0));
            $res = (bool) $this->dao->delete((int) $document->id);
            $this->refreshStats((int) $document->kb_id);
            KnowledgeSearchCache::flush();

            return $res;
        });
    }

    /**
     * 批量删除文档（删除后不可检索）.
     *
     * @param array<int, int|string> $ids
     *
     * @return array{success:int,failed:int}
     */
    public function batchDelete(array $ids): array
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            throw $this->exception('请选择要删除的文档');
        }

        $success = 0;
        $failed  = 0;
        foreach ($ids as $id) {
            try {
                $this->deleteInfo($id);
                ++$success;
            } catch (\Throwable) {
                ++$failed;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * 批量启停文档：关闭时同步关闭分块并剔除向量索引，开启时恢复分块并重新向量化.
     *
     * @param array<int, int|string> $ids
     *
     * @return array{success:int,failed:int}
     */
    public function batchSetEnabled(array $ids, int $enabled): array
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            throw $this->exception('请选择要操作的文档');
        }

        $enabled = $enabled === KnowledgeEnum::STATUS_ENABLED
            ? KnowledgeEnum::STATUS_ENABLED
            : KnowledgeEnum::STATUS_DISABLED;

        $success = 0;
        $failed  = 0;
        $touched = [];

        foreach ($ids as $id) {
            try {
                $document = $this->checkVisible($id);
                app(KnowledgeBaseService::class)->checkVisible((int) $document->kb_id);
                if ((int) ($document->enabled ?? KnowledgeEnum::STATUS_ENABLED) === $enabled) {
                    ++$success;
                    continue;
                }
                $this->dao->update($id, ['enabled' => $enabled]);
                $touched[] = $id;
                ++$success;
            } catch (\Throwable) {
                ++$failed;
            }
        }

        if ($touched !== []) {
            app(KnowledgeChunkService::class)->syncEnabledByDocuments($touched, $enabled);
            KnowledgeSearchCache::flush();
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * @param array<int, int|string> $ids
     *
     * @return array<int, int>
     */
    protected function normalizeIds(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            if (is_array($id)) {
                $id = (int) ($id['id'] ?? 0);
            }
            $id = (int) $id;
            if ($id > 0) {
                $normalized[] = $id;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * 删除知识库文档.
     */
    public function deleteByKnowledge(int $kbId): int
    {
        $documents = $this->dao->select(['kb_id' => $kbId], ['id', 'attach_id']);
        foreach ($documents as $document) {
            $this->unbindAttach((int) ($document->attach_id ?? 0));
        }

        return (int) $this->dao->delete(['kb_id' => $kbId]);
    }

    /**
     * 校验文档可见.
     */
    public function checkVisible(int $id): KnowledgeDocument
    {
        $document = $this->dao->get($id);
        if (! $document) {
            throw $this->exception('文档不存在');
        }

        app(KnowledgeBaseService::class)->checkVisible((int) $document->kb_id);

        return $document;
    }

    /**
     * 处理文档.
     */
    public function process(int $id): void
    {
        $document = $this->dao->get($id);
        if (! $document) {
            throw $this->exception('文档不存在');
        }

        $this->dao->update($id, ['status' => KnowledgeEnum::DOCUMENT_PROCESSING, 'error_msg' => '']);

        try {
            Log::info('knowledge_pipeline_version=image_ocr_embed_policy_v1', [
                'document_id' => $id,
            ]);

            $base = app(KnowledgeBaseDao::class)->get((int) $document->kb_id)?->toArray();
            if (! $base) {
                throw $this->exception('知识库不存在');
            }
            $path       = $this->resolveDocumentPath($document);
            $parsed     = app(KnowledgeParserService::class)->parseWithImages($path, (string) $document->file_type, false);
            $text       = (string) $parsed['text'];
            $localPaths = array_values(array_filter(array_map('strval', (array) ($parsed['image_paths'] ?? []))));

            // 扫描件 PDF（无正文）先导出页图落 image_urls，OCR 交给各分片队列任务.
            $tempPageDirs = [];
            if (strtolower((string) $document->file_type) === 'pdf'
                && KnowledgeTextNormalizer::normalize($text) === '') {
                $pagePaths = app(KnowledgePdfOcrService::class)->exportPageImages($path);
                foreach ($pagePaths as $pagePath) {
                    $dir = dirname((string) $pagePath);
                    if ($dir !== '' && is_dir($dir)) {
                        $tempPageDirs[$dir] = true;
                    }
                }
                $localPaths = array_values(array_unique(array_merge($localPaths, $pagePaths)));
            }

            $imageUrls = $this->collectDocumentImageUrls((int) $document->id, $text, $localPaths);
            foreach ($localPaths as $localPath) {
                $dir = dirname((string) $localPath);
                if ($dir !== '' && (str_contains($dir, '/pdf_images/') || str_contains($dir, '/document_images/'))) {
                    $tempPageDirs[$dir] = true;
                }
            }
            foreach (array_keys($tempPageDirs) as $dir) {
                $this->cleanupTempImageDir((string) $dir);
            }

            // 先文本分片 + 写入 image_urls + 入队向量化；OCR 按分片另投队列任务.
            $this->finishProcess($document, $text, $base, $imageUrls);
        } catch (\Throwable $e) {
            $this->dao->update($id, [
                'status'    => KnowledgeEnum::DOCUMENT_FAILED,
                'error_msg' => mb_substr($e->getMessage(), 0, 500),
            ]);
        }

        $this->refreshStats((int) $document->kb_id);
    }

    /**
     * 收集文档图片可访问地址：正文 Markdown 链接 + Office/提取图持久化后的 URL.
     *
     * @param list<string> $localImagePaths
     *
     * @return list<string>
     */
    private function collectDocumentImageUrls(int $docId, string $text, array $localImagePaths): array
    {
        $fromText = [];
        try {
            $fromText = app(KnowledgeChunkContentService::class)->extractImageUrls($text);
        } catch (\Throwable) {
        }

        $persisted = $this->persistDocumentImages($docId, $localImagePaths);

        return array_values(array_unique(array_filter(array_merge($fromText, $persisted))));
    }

    /**
     * 将解析出的本地临时图片落到 uploads，供 image_urls 持久引用.
     *
     * @param list<string> $localImagePaths
     *
     * @return list<string>
     */
    private function persistDocumentImages(int $docId, array $localImagePaths): array
    {
        if ($localImagePaths === []) {
            return [];
        }

        $max = 50;
        try {
            $pageLimit = (int) config('knowledge.pdf_ocr.max_pages', 0);
            $chunkMax  = max(1, (int) config('knowledge.chunk_ocr.max_images', 10));
            // 扫描件可能一页一图：max_pages>0 时以其为准，否则至少覆盖常见页数.
            $max = $pageLimit > 0 ? $pageLimit : max(50, $chunkMax * 5);
        } catch (\Throwable) {
        }

        $relativeDir = 'uploads/knowledge/doc_' . $docId;
        $absoluteDir = public_path($relativeDir);
        if (! is_dir($absoluteDir) && ! @mkdir($absoluteDir, 0777, true) && ! is_dir($absoluteDir)) {
            Log::warning('知识库文档图片持久化目录创建失败', ['doc_id' => $docId]);

            return [];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff'];
        $urls    = [];
        foreach (array_slice(array_values($localImagePaths), 0, $max) as $path) {
            $path = (string) $path;
            if ($path === '' || ! is_readable($path)) {
                continue;
            }
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowed, true)) {
                $ext = 'png';
            }
            $name = 'img_' . uniqid('', true) . '.' . $ext;
            $dest = $absoluteDir . '/' . $name;
            if (! @copy($path, $dest) || ! is_file($dest)) {
                continue;
            }
            $urls[] = '/' . $relativeDir . '/' . $name;
        }

        return $urls;
    }

    /**
     * 清理 PDF 页图临时目录（persist 完成后调用）.
     */
    private function cleanupTempImageDir(string $dir): void
    {
        if ($dir === '' || ! is_dir($dir) || ! str_contains($dir, '/temp/knowledge/')) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    /**
     * 兼容旧文档级/逐图 OCR 队列：文档若仍在处理中，直接用正文完成分片.
     */
    public function processDocumentOcr(int $id, string $manifest): void
    {
        $this->finishLegacyDocumentWithoutBlockingOcr($id, $manifest, 'legacy_document_ocr_job');
    }

    public function finishDocumentOcrFallback(int $id, string $manifest, string $reason = 'ocr_timeout'): void
    {
        $this->finishLegacyDocumentWithoutBlockingOcr($id, $manifest, $reason);
    }

    public function processImageOcr(int $id, string $manifest, int $index): void
    {
        $this->finishLegacyDocumentWithoutBlockingOcr($id, $manifest, 'legacy_image_ocr_job');
    }

    public function processPdfOcrPage(int $id, int $page, int $pageCount): void
    {
        $this->finishLegacyDocumentWithoutBlockingOcr($id, '', 'legacy_pdf_ocr_job');
    }

    private function finishLegacyDocumentWithoutBlockingOcr(int $id, string $manifest, string $from): void
    {
        if ($manifest !== '') {
            @unlink($manifest);
        }
        $document = $this->dao->get($id);
        if (! $document || (int) $document->status !== KnowledgeEnum::DOCUMENT_PROCESSING) {
            return;
        }

        try {
            $base = app(KnowledgeBaseDao::class)->get((int) $document->kb_id)?->toArray();
            if (! $base) {
                throw $this->exception('知识库不存在');
            }
            $text      = (string) $document->content_text;
            $imageUrls = $this->collectDocumentImageUrls((int) $document->id, $text, []);
            $this->finishProcess($document, $text, $base, $imageUrls);
        } catch (\Throwable $e) {
            $this->dao->update($id, [
                'status'    => KnowledgeEnum::DOCUMENT_FAILED,
                'error_msg' => mb_substr($e->getMessage(), 0, 500),
            ]);
        }

        $this->refreshStats((int) $document->kb_id);
        Log::info('knowledge_pipeline_version=image_ocr_queue_v1_legacy_drain', [
            'document_id' => $id,
            'from'        => $from,
        ]);
    }

    /**
     * 文本准备完成后统一重建自动分块并异步向量化。
     *
     * @param array<string, mixed> $base
     * @param list<string>         $imageUrls 文档级图片地址；由 rebuild 建成独立 image_only 分片，OCR 成败都保留
     */
    private function finishProcess(KnowledgeDocument $document, string $text, array $base, array $imageUrls = []): void
    {
        $text = KnowledgeTextNormalizer::normalize($text);
        if ($text === '' && $imageUrls === []) {
            throw $this->exception('未能从文档中提取到有效文本');
        }

        // 仅有图片时保留空正文交给 rebuild（只建 image_only）；落库 content_text 用占位便于列表展示.
        $contentText = $text !== '' ? $text : '（文档含图片）';

        $chunkService = app(KnowledgeChunkService::class);
        $chunkIds     = $chunkService->rebuild($document, $text, $base, $imageUrls);
        $this->dao->update((int) $document->id, [
            'content_text' => $contentText,
            'status'       => KnowledgeEnum::DOCUMENT_DONE,
            'chunk_count'  => $chunkService->count(['doc_id' => $document->id]),
            'processed_at' => now(),
            'error_msg'    => '',
        ]);
        if ($chunkIds !== []) {
            // image_only 等 OCR 成功后再向量化；正文分片立刻嵌入.
            $embedIds = $chunkService->filterImmediateEmbedChunkIds($chunkIds);
            if ($embedIds !== []) {
                ProcessKnowledgeEmbeddingJob::dispatch((int) $document->kb_id, $embedIds);
            }
            app(KnowledgeChunkService::class)->dispatchChunkOcrJobs($chunkIds);
        }
    }

    /**
     * 刷新知识库统计.
     */
    public function refreshStats(int $kbId): void
    {
        app(KnowledgeBaseService::class)->updateStats(
            $kbId,
            $this->dao->count(['kb_id' => $kbId]),
            app(KnowledgeChunkService::class)->count(['kb_id' => $kbId])
        );
    }

    /**
     * 归一化上传附件 ID.
     */
    protected function normalizeAttachIds(array $attachIds): array
    {
        if (count($attachIds) > KnowledgeEnum::MAX_UPLOAD_ATTACH_IDS) {
            throw $this->exception('单次最多上传20个附件');
        }

        $ids = [];
        foreach ($attachIds as $attachId) {
            if (is_array($attachId)) {
                $attachId = (int) ($attachId['id'] ?? $attachId['attach_id'] ?? 0);
            } elseif (is_string($attachId) && str_contains($attachId, ',')) {
                foreach (explode(',', $attachId) as $item) {
                    $item = (int) trim($item);
                    if ($item > 0) {
                        $ids[] = $item;
                    }
                }
                continue;
            } else {
                $attachId = (int) $attachId;
            }

            if ($attachId <= 0) {
                throw $this->exception('附件ID格式错误');
            }
            $ids[] = $attachId;
        }

        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            throw $this->exception('请选择附件');
        }
        if (count($ids) > KnowledgeEnum::MAX_UPLOAD_ATTACH_IDS) {
            throw $this->exception('单次最多上传20个附件');
        }
        if (app(AttachService::class)->count(['id' => $ids]) !== count($ids)) {
            throw $this->exception('附件不存在');
        }

        return $ids;
    }

    /**
     * 校验附件关联状态.
     */
    protected function assertAttachRelationAvailable(object $attach): void
    {
        $attachId = (int) ($attach->id ?? 0);
        if ($attachId <= 0) {
            throw $this->exception('附件不存在');
        }
        if ($this->dao->count(['attach_id' => $attachId]) > 0) {
            throw $this->exception('附件已被其他知识库文档使用');
        }
        if ((int) ($attach->relation_type ?? 0) === AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_KNOWLEDGE]
            && (int) ($attach->relation_id ?? 0) > 0) {
            throw $this->exception('附件已关联其他知识库文档');
        }
    }

    /**
     * 校验附件类型与大小，并返回文档元数据.
     * @return array{attach_id:int,att_dir:string,file_name:string,file_type:string,file_size:int}
     */
    protected function validateAttachFile(object $attach): array
    {
        $attDir   = (string) ($attach->getAttributes()['att_dir'] ?? '');
        $fileName = (string) ($attach->real_name ?: $attach->name ?: basename($attDir));
        $fileExt  = strtolower((string) ($attach->file_ext ?: pathinfo($fileName, PATHINFO_EXTENSION)));

        if ($fileExt === '' || ! in_array($fileExt, KnowledgeEnum::PARSEABLE_TYPES, true)) {
            throw $this->exception(sprintf('「%s」仅支持 %s 文档', $fileName, implode('、', KnowledgeEnum::PARSEABLE_TYPES)));
        }

        $localPath = $this->resolveAttachPath($attach);
        $this->assertAttachContentReadable($localPath, $fileExt, $fileName);
        $fileSize  = $this->resolveAttachFileSize($attach, $localPath);
        if ($fileSize <= 0) {
            throw $this->exception(sprintf('「%s」无法读取文件大小', $fileName));
        }
        if ($fileSize > KnowledgeEnum::MAX_FILE_SIZE) {
            throw $this->exception(sprintf('「%s」文件大小不能超过20MB', $fileName));
        }

        $fileType = app(KnowledgeParserService::class)->resolveType($localPath, $fileExt);

        return [
            'attach_id' => (int) ($attach->id ?? 0),
            'att_dir'   => $attDir,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => $fileSize,
        ];
    }

    /**
     * 解析附件文件大小.
     */
    protected function resolveAttachFileSize(object $attach, string $localPath): int
    {
        $fileSize = (int) ($attach->getAttributes()['att_size'] ?? 0);
        if ($fileSize <= 0) {
            $fileSize = (int) preg_replace('/\D/', '', (string) ($attach->att_size ?? 0));
        }
        if ($fileSize <= 0 && is_file($localPath)) {
            $fileSize = (int) filesize($localPath);
        }

        return $fileSize;
    }

    /**
     * 解析文档本地路径.
     */
    protected function resolveDocumentPath(KnowledgeDocument $document): string
    {
        if ((int) ($document->attach_id ?? 0) > 0) {
            $attach = app(AttachService::class)->get((int) $document->attach_id);
            if ($attach) {
                return $this->resolveAttachPath($attach);
            }
        }

        return $this->resolveStoredFilePath(
            (string) $document->file_path,
            null,
            (string) ($document->file_name ?: $document->file_type)
        );
    }

    /**
     * 解析附件本地路径.
     */
    protected function resolveAttachPath(object $attach): string
    {
        $attDir = $this->normalizeAttachDir((string) ($attach->getAttributes()['att_dir'] ?? ''));
        if ($attDir === '') {
            throw $this->exception('附件路径不存在');
        }

        return $this->resolveStoredFilePath(
            $attDir,
            $attach,
            (string) ($attach->real_name ?: $attach->name ?: basename($attDir))
        );
    }

    /**
     * 解析存储路径为可读取的本地文件路径.
     */
    protected function resolveStoredFilePath(string $filePath, ?object $attach = null, string $hintName = ''): string
    {
        $filePath = trim(str_replace('\\', '/', $filePath));
        if ($filePath === '') {
            throw $this->exception('文档文件路径不存在');
        }

        if ($this->isRemotePath($filePath)) {
            return $this->downloadRemoteToTemp($filePath, $attach, $hintName);
        }

        foreach ($this->buildAttachLocalCandidates($filePath) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $this->downloadRemoteToTemp(link_file($filePath), $attach, $hintName);
    }

    /**
     * 判断是否为远程地址.
     */
    protected function isRemotePath(string $path): bool
    {
        $path = trim($path);

        return str_contains($path, '://') || str_starts_with($path, '//');
    }

    /**
     * 规范化附件路径.
     */
    protected function normalizeAttachDir(string $attDir): string
    {
        $attDir = trim(str_replace('\\', '/', $attDir));
        if ($attDir === '') {
            return '';
        }

        if ($this->isRemotePath($attDir)) {
            return $attDir;
        }

        $siteUrl = rtrim((string) sys_config('site_url', config('app.url')), '/');
        if ($siteUrl !== '' && str_starts_with($attDir, $siteUrl)) {
            $attDir = substr($attDir, strlen($siteUrl));
        }

        return '/' . ltrim($attDir, '/');
    }

    /**
     * 构建附件本地候选路径.
     * @return string[]
     */
    protected function buildAttachLocalCandidates(string $attDir): array
    {
        if ($this->isRemotePath($attDir)) {
            return [];
        }

        $normalized = ltrim($attDir, '/');
        $candidates = [
            public_path($normalized),
            base_path('public/' . $normalized),
            base_path('public' . $attDir),
        ];

        if (str_starts_with($normalized, 'uploads/')) {
            $candidates[] = public_path(substr($normalized, strlen('uploads/')));
        }

        $candidates[] = storage_path('app/' . $normalized);

        return array_values(array_unique($candidates));
    }

    /**
     * 下载远程附件到临时目录.
     */
    protected function downloadRemoteToTemp(string $source, ?object $attach = null, string $hintName = ''): string
    {
        $url = $this->isRemotePath($source) ? $source : link_file($source);
        $content = @file_get_contents($url);
        if ($content === false || $content === '') {
            throw $this->exception('附件文件读取失败，请确认附件已上传成功');
        }
        if ($this->looksLikeErrorResponse($content)) {
            throw $this->exception('附件文件读取失败，请确认附件地址可访问');
        }

        $fileExt = strtolower((string) (
            $attach?->file_ext
            ?: pathinfo($hintName, PATHINFO_EXTENSION)
            ?: pathinfo((string) ($attach?->real_name ?? ''), PATHINFO_EXTENSION)
            ?: pathinfo($source, PATHINFO_EXTENSION)
            ?: 'bin'
        ));
        $tempDir = storage_path('app/temp/knowledge');
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            throw $this->exception('创建临时目录失败');
        }

        $cacheKey = md5($url);
        $tempPath = $tempDir . '/attach_' . (int) ($attach?->id ?? 0) . '_' . $cacheKey . ($fileExt !== '' ? '.' . $fileExt : '');
        if (is_file($tempPath)) {
            return $tempPath;
        }

        if (@file_put_contents($tempPath, $content) === false) {
            throw $this->exception('附件文件下载失败');
        }

        return $tempPath;
    }

    /**
     * 判断远程响应是否为错误页面.
     */
    protected function looksLikeErrorResponse(string $content): bool
    {
        $sample = ltrim(substr($content, 0, 512));
        if ($sample === '') {
            return true;
        }

        return str_starts_with($sample, '<!DOCTYPE')
            || str_starts_with($sample, '<html')
            || str_starts_with($sample, '<HTML')
            || (str_starts_with($sample, '{') && str_contains($sample, '"message"'));
    }

    /**
     * 校验附件内容可读，避免路径错误被误判为格式不一致.
     */
    protected function assertAttachContentReadable(string $localPath, string $fileExt, string $fileName): void
    {
        if (! is_file($localPath)) {
            throw $this->exception(sprintf('「%s」附件文件不存在', $fileName));
        }

        $handle = @fopen($localPath, 'rb');
        if ($handle === false) {
            throw $this->exception(sprintf('「%s」附件文件读取失败', $fileName));
        }

        $header = (string) fread($handle, 8192);
        fclose($handle);

        if ($header === '' || $this->looksLikeErrorResponse($header)) {
            throw $this->exception(sprintf('「%s」附件文件读取失败，请重新上传附件', $fileName));
        }

        $detected = app(KnowledgeParserService::class)->detectType($localPath);
        if ($detected === 'txt' && in_array($fileExt, ['pdf', 'docx', 'xlsx', 'pptx'], true)) {
            throw $this->exception(sprintf('「%s」附件文件读取失败，请确认附件已上传成功', $fileName));
        }
    }

    /**
     * 解除附件与知识库文档的关联.
     */
    protected function unbindAttach(int $attachId): void
    {
        if ($attachId <= 0) {
            return;
        }

        app(AttachService::class)->update(['id' => $attachId], [
            'relation_id'   => 0,
            'relation_type' => AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_DEFAULT],
        ]);
    }
}
