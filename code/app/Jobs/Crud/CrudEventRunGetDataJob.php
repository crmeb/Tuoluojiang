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

namespace App\Jobs\Crud;

use App\Constants\Crud\CrudTriggerEnum;
use App\Http\Service\Crud\CrudModuleService;
use App\Http\Service\Crud\SystemCrudEventService;
use App\Http\Service\Crud\SystemCrudService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrudEventRunGetDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 80;

    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $crudId, protected string $action, protected array $event, protected int $page) {}

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $crudService = app()->get(SystemCrudService::class);
            $crudInfo    = $crudService->get($this->crudId)?->toArray();
            if (! $crudInfo) {
                return;
            }
            $modelService = app()->get(CrudModuleService::class);

            $join  = $crudService->getJoinCrudData($this->crudId);
            $model = $modelService->model(crudId: $this->crudId)->setJoin($join)->withTrashed($this->action === CrudTriggerEnum::TRIGGER_DELETED);
            app()->get(SystemCrudEventService::class)->runGetData($modelService, $model, $crudInfo, $join, $this->crudId, $this->action, $this->event, [], 0, ['page' => $this->page]);
        } catch (\Throwable $e) {
            Log::error('实体触发器执行定获取数据报错：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }
}
