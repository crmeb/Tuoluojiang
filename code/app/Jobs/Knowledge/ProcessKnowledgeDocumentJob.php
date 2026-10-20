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

class ProcessKnowledgeDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 840;

    public function __construct(private int $documentId) {}

    public function handle(KnowledgeDocumentService $service): void
    {
        $service->process($this->documentId);
    }
}
