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

use App\Http\Service\Knowledge\KnowledgeDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 文档级 OCR 队列任务：整篇文档一次 OCR（默认 30s），超时则用纯文本分片并保留 image_urls.
 */
class ProcessKnowledgeDocumentOcrJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** OCR 失败/超时不重试，避免反复打满 CPU；由 failed() 走纯文本分片. */
    public int $tries = 1;

    /** 单个 OCR 任务墙钟超时（秒），与 config knowledge.pdf_ocr.job_timeout 对齐. */
    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(
        private int $documentId,
        private string $manifest
    ) {
        $this->timeout = max(5, (int) config('knowledge.pdf_ocr.job_timeout', 30));
    }

    public function handle(KnowledgeDocumentService $service): void
    {
        $service->processDocumentOcr($this->documentId, $this->manifest);
    }

    /**
     * 任务超时或异常：仍用已解析正文完成分片，不把文档标为 OCR 失败.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::warning('知识库文档 OCR 队列任务失败/超时，降级纯文本分片', [
            'document_id' => $this->documentId,
            'error'       => $exception?->getMessage(),
        ]);

        try {
            app(KnowledgeDocumentService::class)->finishDocumentOcrFallback(
                $this->documentId,
                $this->manifest,
                'ocr_job_failed'
            );
        } catch (\Throwable $e) {
            Log::error('知识库文档 OCR 降级分片失败', [
                'document_id' => $this->documentId,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
