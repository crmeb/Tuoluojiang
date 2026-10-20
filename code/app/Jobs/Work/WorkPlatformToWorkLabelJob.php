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
 * 同步平台标签到企业微信客户.
 */
class WorkPlatformToWorkLabelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $timeout = 80;

    public $failOnTimeout = true;

    public function __construct(protected int $pid, protected string $name, protected ?string $traceId = null) {}

    public function handle()
    {
        $traceId = $this->traceId ?: uniqid('work_label_sync_', true);
        Log::warning('客户标签同步到企业微信队列开始', [
            'trace_id'   => $traceId,
            'pid'        => $this->pid,
            'group_name' => $this->name,
            'attempt'    => method_exists($this, 'attempts') ? $this->attempts() : 1,
        ]);
        $syncResult = [];
        $success = app()->get(LabelService::class)->addCorpClientLabel($this->pid, $this->name, $traceId, $syncResult);
        if (! $success && ($syncResult['retryable'] ?? false) && $this->attempts() < $this->tries) {
            $delay = 8 * $this->attempts();
            Log::warning('客户标签同步到企业微信队列释放重试', [
                'trace_id'    => $traceId,
                'pid'         => $this->pid,
                'group_name'  => $this->name,
                'attempt'     => $this->attempts(),
                'delay'       => $delay,
                'errcode'     => $syncResult['errcode'] ?? null,
                'errmsg'      => $syncResult['errmsg'] ?? null,
            ]);
            $this->release($delay);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('客户标签同步到企业微信队列失败', [
            'trace_id'   => $this->traceId ?? '',
            'pid'        => $this->pid,
            'group_name' => $this->name,
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
            'message'    => $e->getMessage(),
        ]);
    }
}
