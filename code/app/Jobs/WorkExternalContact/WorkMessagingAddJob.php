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

namespace App\Jobs\WorkExternalContact;

use App\Http\Service\WorkExternalContact\WorkMassMessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 创建群发任务
 */
class WorkMessagingAddJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    public function __construct(protected array|int $massId = []) {}

    public function handle(WorkMassMessagingService $messagingService)
    {
        if (! $this->massId) {
            $this->massId = $messagingService->column(['status' => 1, 'send_minute' => now()], 'id');
        }
        foreach (is_array($this->massId) ? $this->massId : [$this->massId] as $massId) {
            $messagingService->sendWorkMsg((int) $massId);
        }
    }
}
