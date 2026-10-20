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

namespace App\Jobs\Customer;

use App\Constants\AttachEnum;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\CommonService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ContractDocToLocalJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    public function __construct(protected string $url, protected int $cid, protected int $uid) {}

    public function handle(): void
    {
        try {
            if (! app(AttachService::class)->exists(['relation_id' => $this->cid, 'relation_type' => AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_SIGN_RESULT]])) {
                app(CommonService::class)->uploadFromUrl($this->url, ['entId' => 1, 'uuid' => uid_to_uuid($this->uid), 'link_id' => $this->cid, 'link_type' => AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_SIGN_RESULT]]);
            }
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
        }
    }
}
