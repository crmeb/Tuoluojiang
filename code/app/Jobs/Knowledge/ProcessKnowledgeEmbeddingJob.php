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
 */

namespace App\Jobs\Knowledge;

use App\Http\Service\Knowledge\KnowledgeEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessKnowledgeEmbeddingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 110;

    private const BATCH_SIZE = 64;

    /**
     * @param array<int, int> $chunkIds
     * @param int             $embeddingModelId 可选覆盖向量模型配置 ID（0 表示使用知识库绑定模型）
     */
    public function __construct(
        private int $kbId,
        private array $chunkIds = [],
        private int $embeddingModelId = 0,
        private int $afterId = 0
    ) {}

    public function handle(KnowledgeEmbeddingService $service): void
    {
        $chunkIds = $this->chunkIds !== []
            ? array_slice($this->chunkIds, 0, self::BATCH_SIZE)
            : $service->pendingChunkIds($this->kbId, $this->afterId, self::BATCH_SIZE);
        if ($chunkIds === []) {
            return;
        }

        $result = $service->embedPending($this->kbId, $chunkIds, $this->embeddingModelId);
        if (($result['failed'] ?? 0) > 0) {
            Log::warning('知识库异步向量化任务完成但存在失败分块', [
                'kb_id'               => $this->kbId,
                'chunk_ids'           => $chunkIds,
                'embedding_model_id'  => $this->embeddingModelId,
                'total'               => $result['total'] ?? 0,
                'done'                => $result['done'] ?? 0,
                'failed'              => $result['failed'] ?? 0,
                'first_error'         => $result['first_error'] ?? null,
            ]);
        }

        if ($this->chunkIds !== []) {
            $remaining = array_slice($this->chunkIds, self::BATCH_SIZE);
            if ($remaining !== []) {
                self::dispatch($this->kbId, $remaining, $this->embeddingModelId);
            }

            return;
        }

        if (count($chunkIds) === self::BATCH_SIZE) {
            self::dispatch($this->kbId, [], $this->embeddingModelId, max($chunkIds));
        }
    }
}
