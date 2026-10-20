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

namespace App\Jobs\Knowledge;

use App\Http\Service\Knowledge\KnowledgeChunkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 分片单图 OCR 队列任务：一图一任务；超时不影响已落库文本分片与 image_urls.
 *
 * 同 chunk 串行回填（WithoutOverlapping）；全局 OCR 互斥避免多文档打满 CPU.
 */
class ProcessKnowledgeChunkOcrJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    /**
     * @param int    $chunkId  分片 ID
     * @param string $imageUrl 单张图片 URL；空字符串时兼容旧队列（整片 OCR）
     */
    public function __construct(
        private int $chunkId,
        private string $imageUrl = ''
    ) {
        $commandTimeout = max(5, (int) config('knowledge.pdf_ocr.command_timeout', 30));
        $configured     = (int) config('knowledge.pdf_ocr.job_timeout', 0);
        // 单图预算：job_timeout>0 用配置值；否则 command_timeout+5.
        $this->timeout = $configured > 0 ? max(5, $configured) : $commandTimeout + 5;

        $queue = trim((string) config('knowledge.chunk_ocr.queue', ''));
        if ($queue !== '') {
            $this->onQueue($queue);
        }
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        $release = max(10, $this->timeout);
        $expire  = $release + 30;

        return [
            // 同 chunk 多图串行合并，避免 content 覆盖写.
            (new WithoutOverlapping('knowledge-chunk-ocr-' . $this->chunkId))
                ->releaseAfter($release)
                ->expireAfter($expire),
            // 全局 OCR 互斥：等价于 OCR 并发约 1，防止多文档同时 tesseract.
            (new WithoutOverlapping('knowledge-ocr'))
                ->releaseAfter($release)
                ->expireAfter($expire),
        ];
    }

    public function handle(KnowledgeChunkService $service): void
    {
        if ($this->imageUrl !== '') {
            $service->processChunkImageOcr($this->chunkId, $this->imageUrl);

            return;
        }

        // 兼容升级前已入队的「整片 OCR」任务.
        $service->processChunkOcr($this->chunkId);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::warning('知识库分片图片 OCR 超时或失败，保留原文本分片', [
            'chunk_id'  => $this->chunkId,
            'image_url' => $this->imageUrl,
            'error'     => $exception?->getMessage(),
        ]);
    }
}
