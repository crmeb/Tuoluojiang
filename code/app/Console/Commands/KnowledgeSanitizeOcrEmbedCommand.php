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
use App\Http\Dao\Knowledge\KnowledgeChunkDao;
use App\Http\Service\Knowledge\KnowledgeOcrEmbedPolicy;
use App\Http\Service\Knowledge\KnowledgeSearchCache;
use App\Http\Service\Knowledge\KnowledgeVectorIndex;
use App\Jobs\Knowledge\ProcessKnowledgeEmbeddingJob;
use Illuminate\Console\Command;

/**
 * 清洗已污染的 image_only 分片：把合并进 content 的演示 OCR 拆回 metadata，并重向量化.
 *
 * 用法：
 *   php artisan knowledge:sanitize-ocr-embed
 *   php artisan knowledge:sanitize-ocr-embed --kb=12 --dry-run
 */
class KnowledgeSanitizeOcrEmbedCommand extends Command
{
    protected $signature = 'knowledge:sanitize-ocr-embed
                            {--kb= : 仅处理指定知识库}
                            {--dry-run : 只统计不写库}
                            {--chunk=200 : 每批分片数}
                            {--all-kinds : caption_only 风格：凡能拆出 OCR 尾部一律剥离（含扫描正文）}';

    protected $description = '剥离 image_only 分片中已合并的演示/截图 OCR，改存 metadata.ocr_text 并重向量化';

    public function handle(KnowledgeChunkDao $dao, KnowledgeOcrEmbedPolicy $policy): int
    {
        $kbId     = (int) $this->option('kb');
        $dryRun   = (bool) $this->option('dry-run');
        $batch    = max(50, (int) $this->option('chunk'));
        $allKinds = (bool) $this->option('all-kinds');

        $query = $dao->search([])
            ->when($kbId > 0, fn ($q) => $q->where('kb_id', $kbId))
            ->where(function ($q): void {
                $q->where('content', 'like', '文档《%》图片%')
                    ->orWhere('content', 'like', '文档图片%');
            })
            ->orderBy('id');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('没有可扫描的 image caption 分片');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "扫描 {$total} 条候选分片...");
        $bar      = $this->output->createProgressBar($total);
        $bar->start();

        $changed  = 0;
        $skipped  = 0;
        $lastId   = 0;
        $embedMap = [];

        while (true) {
            $rows = (clone $query)
                ->where('id', '>', $lastId)
                ->limit($batch)
                ->get(['id', 'kb_id', 'doc_id', 'content', 'metadata', 'enabled', 'status']);
            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = (int) $row->id;
                $bar->advance();

                $content  = (string) $row->content;
                $stripped = $policy->stripMergedOcrFromImageOnly($content);
                if ($stripped === null) {
                    ++$skipped;
                    continue;
                }

                $kind = $policy->classify($stripped['ocr_text']);
                if (! $allKinds && $kind === KnowledgeOcrEmbedPolicy::KIND_DOCUMENT) {
                    ++$skipped;
                    continue;
                }

                ++$changed;
                if ($dryRun) {
                    continue;
                }

                $meta = (array) ($row->metadata ?? []);
                $meta['image_only']      = true;
                $meta['ocr_text']        = $stripped['ocr_text'];
                $meta['image_kind']      = $kind;
                $meta['ocr_embed_mode']  = KnowledgeOcrEmbedPolicy::MODE_CAPTION_ONLY;
                $meta['ocr_merged']      = false;
                $meta['ocr_sanitized']   = true;

                $caption = $stripped['caption'];
                $dao->update((int) $row->id, [
                    'content'      => $caption,
                    'content_hash' => hash('sha256', $caption),
                    'metadata'     => $meta,
                    'status'       => KnowledgeEnum::CHUNK_PENDING,
                    'error_msg'    => '',
                ]);

                app(KnowledgeVectorIndex::class)->upsertText(
                    (int) $row->id,
                    (int) $row->kb_id,
                    (int) $row->doc_id,
                    KnowledgeEnum::CHUNK_PENDING,
                    (int) $row->enabled,
                    (string) ($meta['source'] ?? ''),
                    $caption
                );

                if ((int) $row->enabled === KnowledgeEnum::STATUS_ENABLED) {
                    $kb = (int) $row->kb_id;
                    $embedMap[$kb][] = (int) $row->id;
                }
            }
        }

        $bar->finish();
        $this->newLine();

        if (! $dryRun && $embedMap !== []) {
            KnowledgeSearchCache::flush();
            foreach ($embedMap as $kb => $chunkIds) {
                foreach (array_chunk(array_values(array_unique($chunkIds)), 50) as $batchIds) {
                    ProcessKnowledgeEmbeddingJob::dispatch($kb, $batchIds);
                }
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "完成：变更 {$changed}，跳过 {$skipped}");

        return self::SUCCESS;
    }
}
