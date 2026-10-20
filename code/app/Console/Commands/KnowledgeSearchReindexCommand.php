<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 */

namespace App\Console\Commands;

use App\Constants\KnowledgeEnum;
use App\Http\Model\Knowledge\KnowledgeChunk;
use App\Http\Service\Knowledge\KnowledgeVectorIndex;
use Illuminate\Console\Command;

/** 重建知识库 Redis Stack 全文索引. */
class KnowledgeSearchReindexCommand extends Command
{
    protected $signature = 'knowledge:reindex-search {kb_id? : 知识库 ID，不传则重建全部}';

    protected $description = '重建知识库 Redis Stack 全文索引';

    public function handle(KnowledgeVectorIndex $index): int
    {
        if (! $index->ensureTextIndex()) {
            $this->error('Redis Stack RediSearch 不可用');

            return self::FAILURE;
        }

        $kbId = (int) ($this->argument('kb_id') ?: 0);
        $count = 0;
        KnowledgeChunk::query()
            ->with(['document:id,title,enabled'])
            ->where('enabled', KnowledgeEnum::STATUS_ENABLED)
            ->when($kbId > 0, fn ($query) => $query->where('kb_id', $kbId))
            ->orderBy('id')
            ->chunkById(200, function ($chunks) use ($index, &$count): void {
                foreach ($chunks as $chunk) {
                    if (! $chunk->document || (int) $chunk->document->enabled !== KnowledgeEnum::STATUS_ENABLED) {
                        continue;
                    }
                    if ($index->upsertChunkText($chunk)) {
                        ++$count;
                    }
                }
            });

        $this->info("全文索引重建完成：{$count} 个分块");

        return self::SUCCESS;
    }
}
