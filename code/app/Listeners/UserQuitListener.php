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

use App\Events\UserQuitEvent;
use App\Http\Service\User\UserTokenService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

/**
 * 员工离职事件监听器
 * 处理员工离职后的token销毁等操作.
 */
class UserQuitListener
{
    /**
     * Handle the event.
     *
     * @param UserQuitEvent $event
     * @throws BindingResolutionException
     */
    public function handle(UserQuitEvent $event): void
    {
        $this->invalidateTokens($event->userId);
    }

    /**
     * 销毁用户所有有效token.
     *
     * @param int $userId
     * @throws BindingResolutionException
     */
    protected function invalidateTokens(int $userId): void
    {
        $tokenService = app()->get(UserTokenService::class);
        $tokens = $tokenService->select(['uid' => $userId]);

        foreach ($tokens as $tokenInfo) {
            foreach (['remember_token', 'last_token'] as $tokenField) {
                if ($tokenInfo->{$tokenField}) {
                    try {
                        auth('admin')->setToken($tokenInfo->{$tokenField})->invalidate(true);
                    } catch (\Exception $e) {
                        Log::error('Token失效失败：' . $e->getMessage());
                    }
                }
            }
            $now = date('Y-m-d H:i:s');
            $tokenInfo->fail_time = $now;
            $tokenInfo->refresh_revoked_at = $now;
            $tokenInfo->save();
        }
    }
}
