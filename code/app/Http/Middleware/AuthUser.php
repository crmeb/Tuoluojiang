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

namespace App\Http\Middleware;

use Tuoluojiang\Open\exceptions\AuthException;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

/**
 * 用户后台登录
 * Class AuthEnterprise.
 */
class AuthUser extends BaseMiddleware implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    /**
     * Token 刷新锁前缀.
     */
    private const REFRESH_LOCK_PREFIX = 'user_token_refresh_lock:';

    /**
     * @return mixed|void
     */
    public function before(Request $request)
    {
        app('tymon.jwt.provider.auth')->setProvider(Auth::createUserProvider('user'));
        try {
            $userInfo = $this->auth->setToken($request->bearerToken())->authenticate();
            $timeRemaining = (int) $this->auth->getPayload()->get('exp') - time();
            if ($timeRemaining > 0 && $timeRemaining <= $this->getRefreshThreshold()) {
                $newToken = $this->refreshTokenWithLock($request);
                if ($newToken) {
                    $this->other['token'] = $newToken;
                    $userInfo             = $this->auth->setToken($newToken)->authenticate();
                }
            }
        } catch (TokenExpiredException $e) {
            try {
                $newToken = $this->refreshTokenWithLock($request);
                if ($newToken) {
                    $this->other['token'] = $newToken;
                    $userInfo             = $this->auth->setToken($newToken)->authenticate();
                } else {
                    throw new AuthException('登录信息已失效', 410001);
                }
            } catch (JWTException) {
                throw new AuthException('登录信息已失效', 410001);
            }
        } catch (\Exception|JWTException) {
            throw new AuthException(__('登录信息已失效'), 410003);
        }

        if (! isset($userInfo) || ! $userInfo) {
            throw new AuthException('用户信息不存在', 410002);
        }
        $request->macro('uuId', function () use ($userInfo) {
            return $userInfo->uid;
        });
        $request->macro('userInfo', function (?string $key = null) use ($userInfo) {
            $userInfo = $userInfo->toArray();
            if ($key) {
                return $userInfo[$key] ?? null;
            }
            return $userInfo;
        });
    }

    public function after($response)
    {
        if (isset($this->other['token'])) {
            $this->setAuthenticationHeader($response, $this->other['token']);
        }
    }

    /**
     * 带并发锁的 token 刷新方法.
     */
    private function refreshTokenWithLock(Request $request): ?string
    {
        $currentToken = $request->bearerToken();
        if (! $currentToken) {
            return null;
        }

        // 生成锁键
        $lockKey = self::REFRESH_LOCK_PREFIX . md5($currentToken);
        $lockTtl = 5; // 锁5秒，防止并发刷新

        // 尝试获取锁
        $lockAcquired = Cache::lock($lockKey, $lockTtl)->get(function () use ($currentToken) {
            // 获取锁成功，执行刷新逻辑
            try {
                // 设置为当前 token
                $this->auth->setToken($currentToken);
                $newToken = $this->auth->refresh();

                // 尝试将新 token 缓存5秒，供并发请求使用
                if ($newToken) {
                    $cacheKey = self::REFRESH_LOCK_PREFIX . 'new_token:' . md5($currentToken);
                    Cache::put($cacheKey, $newToken, 5);
                }

                return $newToken;
            } catch (\Exception $e) {
                // 记录错误
                if (config('app.debug')) {
                    \Log::error('User token refresh failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
                return null;
            }
        });

        // 如果获取锁失败（并发刷新中），尝试获取已刷新的 token
        if (! $lockAcquired) {
            $cacheKey = self::REFRESH_LOCK_PREFIX . 'new_token:' . md5($currentToken);
            $newToken = Cache::get($cacheKey);

            if ($newToken) {
                // 验证新 token 是否有效
                try {
                    $this->auth->setToken($newToken)->check();
                    return $newToken;
                } catch (\Exception) {
                    // 新 token 无效，移除缓存
                    Cache::forget($cacheKey);
                }
            }

            // 等待刷新完成（最多2秒）
            $retries    = 0;
            $maxRetries = 4;
            while ($retries < $maxRetries) {
                usleep(500000); // 等待500ms
                $newToken = Cache::get($cacheKey);
                if ($newToken) {
                    try {
                        $this->auth->setToken($newToken)->check();
                        return $newToken;
                    } catch (\Exception) {
                        Cache::forget($cacheKey);
                    }
                }
                ++$retries;
            }
        }

        return null;
    }

    /**
     * 获取自动刷新阈值，必须小于 access token TTL，避免每次请求都刷新.
     */
    private function getRefreshThreshold(): int
    {
        $threshold = max(60, (int) config('jwt.refresh_threshold', 900));
        $ttl       = $this->auth->factory()->getTTL();

        if ($ttl !== null) {
            $ttlSeconds = (int) $ttl * 60;
            if ($ttlSeconds > 0 && $threshold >= $ttlSeconds) {
                return max(60, min(900, (int) floor($ttlSeconds / 4)));
            }
        }

        return $threshold;
    }
}
