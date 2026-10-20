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
use App\Http\Service\Knowledge\KnowledgeVectorIndex;
use Illuminate\Console\Command;

/**
 * 将 MySQL 中已有向量回填到 Redis Stack 索引.
 */
class KnowledgeVectorReindexCommand extends Command
{
    protected $signature = 'knowledge:vector-reindex
                            {--kb= : 仅重建指定知识库}
                            {--model= : 仅重建指定 embedding 模型}
                            {--drop : 重建前先删除对应 RediSearch 索引}
                            {--chunk=500 : 每批处理分块数}';

    protected $description = '将 knowledge_chunk 向量回填到 Redis Stack（REDIS_KNOWLEDGE_DB）';

    public function handle(KnowledgeChunkDao $dao, KnowledgeVectorIndex $index): int
    {
        if (! $index->enabled()) {
            $this->error('KNOWLEDGE_VECTOR_DRIVER 未启用 redis/auto');

            return self::FAILURE;
        }
        if (! $index->available()) {
            $this->error('Redis Stack / RediSearch 不可用，请确认 Redis 镜像为 redis-stack-server 且 REDIS_KNOWLEDGE_DB 可连');

            return self::FAILURE;
        }

        $kbId    = (int) $this->option('kb');
        $modelId = (int) $this->option('model');
        $chunk   = max(50, (int) $this->option('chunk'));
        $query   = $dao->search([])
            ->where('status', KnowledgeEnum::CHUNK_DONE)
            ->where('embedding_dimension', '>', 0)
            ->when($kbId > 0, fn ($q) => $q->where('kb_id', $kbId))
            ->when($modelId > 0, fn ($q) => $q->where('embedding_model_id', $modelId))
            ->orderBy('id');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('没有可回填的向量分块');

            return self::SUCCESS;
        }

        if ($this->option('drop')) {
            $modelIds = (clone $query)->distinct()->pluck('embedding_model_id')->map(fn ($id) => (int) $id)->filter()->unique();
            foreach ($modelIds as $id) {
                $index->dropIndex($id);
                $this->line("已删除索引 model={$id}");
            }
        }

        $this->info("开始回填 {$total} 条向量到 Redis Stack...");
        $bar     = $this->output->createProgressBar($total);
        $ok      = 0;
        $failed  = 0;
        $lastId  = 0;

        while (true) {
            $rows = (clone $query)->where('id', '>', $lastId)->limit($chunk)->get();
            if ($rows->isEmpty()) {
                break;
            }
            foreach ($rows as $row) {
                $lastId = (int) $row->id;
                if ($index->upsertChunk($row)) {
                    ++$ok;
                } else {
                    ++$failed;
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("完成：成功 {$ok}，失败 {$failed}");

        return $failed > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }
}
