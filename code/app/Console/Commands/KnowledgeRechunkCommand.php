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

namespace App\Console\Commands;

use App\Constants\KnowledgeEnum;
use App\Http\Dao\Knowledge\KnowledgeBaseDao;
use App\Http\Dao\Knowledge\KnowledgeDocumentDao;
use App\Http\Service\Knowledge\KnowledgeChunkService;
use App\Http\Service\Knowledge\KnowledgeDocumentService;
use App\Http\Service\Knowledge\KnowledgeEmbeddingService;
use App\Http\Service\Knowledge\KnowledgeSearchCache;
use Illuminate\Console\Command;

/**
 * 按知识库灰度重建自动分块（保留手动分块）.
 *
 * 用法：php artisan knowledge:rechunk {kb_id}
 * 语义分片默认开启；对存量文档按库执行本命令可重建为结构感知分块.
 */
class KnowledgeRechunkCommand extends Command
{
    /** 按文档 ID 分批，避免一次性加载全部 content_text. */
    private const BATCH_SIZE = 50;

    protected $signature = 'knowledge:rechunk {kb_id : 知识库 ID}';

    protected $description = '按知识库重建文档自动分块（保留手动分块并刷新检索缓存）';

    public function handle(
        KnowledgeBaseDao $baseDao,
        KnowledgeDocumentDao $documentDao,
        KnowledgeChunkService $chunkService,
        KnowledgeDocumentService $documentService,
        KnowledgeEmbeddingService $embeddingService
    ): int {
        $kbId = (int) $this->argument('kb_id');
        if ($kbId <= 0) {
            $this->error('请提供有效的知识库 ID');

            return self::FAILURE;
        }

        $base = $baseDao->get($kbId);
        if (! $base) {
            $this->error("知识库不存在：{$kbId}");

            return self::FAILURE;
        }

        $total = $documentDao->count(['kb_id' => $kbId]);
        if ($total === 0) {
            $this->info('该知识库下没有文档');

            return self::SUCCESS;
        }

        $baseArr  = $base->toArray();
        $ok       = 0;
        $skipped  = 0;
        $failed   = 0;
        $chunkSum = 0;

        $this->info("开始重建知识库 {$kbId} 的自动分块，共 {$total} 篇文档...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $documentDao->search(['kb_id' => $kbId])
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(self::BATCH_SIZE, function ($rows) use (
                $kbId,
                $baseArr,
                $documentDao,
                $chunkService,
                $embeddingService,
                $bar,
                &$ok,
                &$skipped,
                &$failed,
                &$chunkSum
            ): void {
                foreach ($rows as $row) {
                    $document = $documentDao->get((int) $row->id, [
                        'id', 'kb_id', 'title', 'content_text', 'status', 'file_type', 'attach_id',
                    ]);
                    if (! $document) {
                        $bar->advance();
                        continue;
                    }

                    $text = trim((string) ($document->content_text ?? ''));
                    if ($text === '') {
                        ++$skipped;
                        $bar->advance();
                        continue;
                    }

                    try {
                        $chunkIds = $chunkService->rebuild($document, $text, $baseArr);
                        $chunkSum += count($chunkIds);
                        $errorMsg = '';
                        if ($chunkIds !== []) {
                            $embedResult = $embeddingService->embedPending($kbId, $chunkIds);
                            if (($embedResult['failed'] ?? 0) > 0) {
                                $errorMsg = '分块向量化失败';
                                if (! empty($embedResult['first_error'])) {
                                    $errorMsg .= '：' . (string) $embedResult['first_error'];
                                }
                                $errorMsg = mb_substr($errorMsg, 0, 500);
                            }
                        }
                        $documentDao->update((int) $document->id, [
                            'chunk_count' => $chunkService->count(['doc_id' => (int) $document->id]),
                            'status'      => KnowledgeEnum::DOCUMENT_DONE,
                            'error_msg'   => $errorMsg,
                        ]);
                        ++$ok;
                    } catch (\Throwable $e) {
                        ++$failed;
                        $documentDao->update((int) $document->id, [
                            'status'    => KnowledgeEnum::DOCUMENT_FAILED,
                            'error_msg' => mb_substr($e->getMessage(), 0, 500),
                        ]);
                        $this->newLine();
                        $this->warn("文档 {$document->id} 重建失败：" . $e->getMessage());
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        $documentService->refreshStats($kbId);
        KnowledgeSearchCache::flush();

        $this->info("完成：成功 {$ok}，跳过空正文 {$skipped}，失败 {$failed}，新建自动分块 {$chunkSum}");

        return $failed > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }
}
