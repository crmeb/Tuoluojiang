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

namespace App\Listeners;

use App\Http\Context\DataPermissionContext;
use App\Http\Contract\Runtime\RequestScopedResettable;
use App\Http\Service\Ai\AiStreamService;
use Hhxsv5\LaravelS\Illuminate\Cleaners\BaseCleaner;
use Hhxsv5\LaravelS\Illuminate\Cleaners\CleanerInterface;

/**
 * 统一清理 Swoole 请求态，避免长生命周期下上下文与服务实例残留.
 *
 * 挂载于 config/laravels.php 的 cleaners；与 Auth/JWT/Session 等框架 Cleaner 互补。
 */
class RequestStateCleaner extends BaseCleaner implements CleanerInterface
{
    /**
     * 已解析且实现 RequestScopedResettable 的服务类名列表.
     *
     * @var array<int, class-string>
     */
    private array $resettableClasses = [
        AiStreamService::class,
    ];

    /**
     * 清理数据权限上下文与可重置服务.
     */
    public function clean(): void
    {
        DataPermissionContext::clear();
        if ($this->currentApp->bound('context.data_permission')) {
            $this->currentApp->forgetInstance('context.data_permission');
        }

        foreach ($this->resettableClasses as $class) {
            $this->resetResolved($class);
        }
    }

    /**
     * 若容器已解析该类且实现契约，则调用 resetRequestState.
     *
     * @param class-string $class 服务类名
     */
    private function resetResolved(string $class): void
    {
        if (! $this->currentApp->resolved($class)) {
            return;
        }

        try {
            $instance = $this->currentApp->make($class);
        } catch (\Throwable) {
            return;
        }

        if ($instance instanceof RequestScopedResettable) {
            $instance->resetRequestState();
        }
    }
}
