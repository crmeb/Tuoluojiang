<?php

declare(strict_types=1);

namespace App\Jobs\Knowledge;

use App\Http\Service\Knowledge\KnowledgeDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 非 PDF 文档单图片 OCR 任务。
 */
class ProcessKnowledgeImageOcrJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 110;

    public function __construct(
        private int $documentId,
        private string $manifest,
        private int $index
    ) {}

    public function handle(KnowledgeDocumentService $service): void
    {
        $service->processImageOcr($this->documentId, $this->manifest, $this->index);
    }
}
