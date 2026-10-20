<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Jobs\Work;

use App\Http\Service\Customer\LabelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 同步企业微信标签到平台.
 */
class WorkWorkToPlatformLabelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    public function __construct(protected ?string $traceId = null) {}

    public function handle()
    {
        $traceId = $this->traceId ?: uniqid('work_label_sync_', true);
        Log::warning('企业微信标签同步到平台队列开始', [
            'trace_id' => $traceId,
        ]);
        app()->get(LabelService::class)->authWorkLabel([], [], $traceId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('企业微信标签同步到平台队列失败', [
            'trace_id' => $this->traceId ?? '',
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'message'   => $e->getMessage(),
        ]);
    }
}
