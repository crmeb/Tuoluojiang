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
use App\Jobs\Knowledge\ProcessKnowledgeDocumentJob;
use Illuminate\Console\Command;

/**
 * 按知识库批量重新处理文档（完整解析 → 图文分离 rebuild → OCR → 向量化）.
 *
 * 用于纠正「文档级图片按序硬挂文本分片」的存量数据；勿用 knowledge:rechunk（不传图片 URL）.
 * 控制台侧绕过 HTTP 可见性校验，直接按库 ID 入队 ProcessKnowledgeDocumentJob.
 *
 * 用法：php artisan knowledge:reprocess-kb {kb_id} [--dry-run]
 */
class KnowledgeReprocessKbCommand extends Command
{
    private const BATCH_SIZE = 50;

    protected $signature = 'knowledge:reprocess-kb
        {kb_id : 知识库 ID}
        {--dry-run : 仅列出将处理的文档，不入队}';

    protected $description = '按知识库批量 reprocess 文档（重抽图并重建图文分离分片）';

    public function handle(KnowledgeBaseDao $baseDao, KnowledgeDocumentDao $documentDao): int
    {
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

        $dryRun = (bool) $this->option('dry-run');
        $ok     = 0;
        $failed = 0;
        $queued = 0;

        $this->info(($dryRun ? '[dry-run] ' : '') . "开始处理知识库 {$kbId}，共 {$total} 篇文档...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $documentDao->search(['kb_id' => $kbId])
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(self::BATCH_SIZE, function ($rows) use (
                $documentDao,
                $dryRun,
                $bar,
                &$ok,
                &$failed,
                &$queued
            ): void {
                foreach ($rows as $row) {
                    $docId = (int) $row->id;
                    if ($dryRun) {
                        ++$ok;
                        $bar->advance();
                        continue;
                    }

                    try {
                        $documentDao->update($docId, [
                            'status'       => KnowledgeEnum::DOCUMENT_PENDING,
                            'error_msg'    => '',
                            'processed_at' => null,
                        ]);
                        ProcessKnowledgeDocumentJob::dispatch($docId);
                        ++$queued;
                        ++$ok;
                    } catch (\Throwable $e) {
                        ++$failed;
                        $this->newLine();
                        $this->warn("文档 {$docId} 入队失败：" . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info("dry-run 完成：将处理 {$ok} 篇文档（未入队）");
        } else {
            $this->info("完成：成功入队 {$queued}，失败 {$failed}");
            $this->comment('任务已写入队列，请确保 queue worker / LaravelS 正在消费 ProcessKnowledgeDocumentJob。');
        }

        return $failed > 0 && $queued === 0 ? self::FAILURE : self::SUCCESS;
    }
}
