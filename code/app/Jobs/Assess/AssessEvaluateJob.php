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

namespace App\Jobs\Assess;

use App\Constants\CacheEnum;
use App\Http\Service\Assess\UserAssessService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AssessEvaluateJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    public $uniqueFor = 300;

    public function __construct(private int $limit = 20) {}

    public function handle(UserAssessService $service)
    {
        try {
            $where   = ['status' => [0, 1, 2, 3], 'intact' => 1];
            $entList = $service->getUserAssessEntListCache($where);
            foreach ($entList as $item) {
                $where['entid'] = $item['entid'];
                $count          = Cache::tags([CacheEnum::TAG_FRAME])->remember(md5('AssessEvaluateJob'), (int) sys_config('system_cache_ttl', 3600), fn () => $service->count($where));
                $sumPage        = ceil($count / $this->limit);
                for ($i = 1; $i <= $sumPage; ++$i) {
                    $service->timer($i, $this->limit, $where);
                }
            }
        } catch (\Throwable $e) {
            Log::error(__CLASS__ . ':' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }
}
