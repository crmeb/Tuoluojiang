<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\User;

use App\Constants\CacheEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Dao\User\UserTokenDao;
use App\Http\Service\Admin\AdminInfoService;
use App\Constants\UserEnum;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

/**
 * 用户token记录.
 */
class UserTokenService extends BaseService
{
    /**
     * refresh_token 轮换后的短窗口回放秒数.
     * 同一旧 refresh hash 在此窗口内再次请求，直接返回同一次轮换结果，
     * 用于吸收多 Tab / 并发续期竞态；不是无限复用旧 refresh。
     * 与 JWT_BLACKLIST_GRACE_PERIOD 默认 300s 对齐.
     */
    private const REFRESH_REPLAY_SECONDS = 300;

    public function __construct(UserTokenDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 登录后记录 access token，并签发滑动 refresh token.
     */
    public function issueLoginTokens(Admin $userInfo, string $accessToken, string $client = '', string $mac = '', string $ip = '', bool $isSingle = false): array
    {
        $refreshToken = $this->generateRefreshToken();
        $now          = Carbon::now();
        $data         = [
            'uid'                    => (string) $userInfo->uid,
            'client'                 => (string) $client,
            'last_ip'                => (string) $ip,
            'mac'                    => (string) $mac,
            'last_token'             => $accessToken,
            'remember_token'         => $accessToken,
            'refresh_token_hash'     => $this->hashRefreshToken($refreshToken),
            'refresh_expires_at'     => $now->copy()->addDays($this->getRefreshIdleDays())->toDateTimeString(),
            'refresh_last_used_at'   => $now->toDateTimeString(),
            'refresh_revoked_at'     => null,
            'fail_time'              => null,
        ];

        if ($isSingle) {
            $tokens = $this->getSingleClientTokens((string) $userInfo->uid, $client);
            $info   = $tokens->first();
            if ($tokens->isNotEmpty()) {
                foreach ($tokens as $tokenInfo) {
                    $oldTokens = array_unique(array_filter([$tokenInfo->remember_token, $tokenInfo->last_token]));
                    foreach ($oldTokens as $oldToken) {
                        $this->invalidateAccessToken($oldToken, '单点登录Token失效失败');
                    }
                    $this->forgetRefreshReplayCache($tokenInfo->refresh_token_hash);
                    if ($info && $tokenInfo->id !== $info->id) {
                        $this->revokeTokenRecord($tokenInfo);
                    }
                }
                $info->fill($data);
                $info->save();
            } else {
                $this->create($data);
            }
        } else {
            $this->create($data);
        }

        return $this->formatTokenResponse($accessToken, $refreshToken, $data['refresh_expires_at']);
    }

    /**
     * 使用 refresh token 轮换 access token 与 refresh token.
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $refreshToken = trim($refreshToken);
        if ($refreshToken === '') {
            throw $this->exception('刷新TOKEN不能为空');
        }

        $refreshHash = $this->hashRefreshToken($refreshToken);
        $cacheKey    = $this->replayCacheKey($refreshHash);
        if ($cached = Cache::tags([CacheEnum::TAG_OTHER])->get($cacheKey)) {
            return $cached;
        }

        $tokenInfo = $this->dao->getModel(false)
            ->where('refresh_token_hash', $refreshHash)
            ->whereNull('refresh_revoked_at')
            ->first();

        if (! $tokenInfo || ! $tokenInfo->refresh_expires_at || Carbon::parse($tokenInfo->refresh_expires_at)->isPast()) {
            throw $this->exception('登录信息已失效，请重新登录');
        }

        $userInfo = app()->get(\App\Http\Service\Admin\AdminService::class)->get(['uid' => $tokenInfo->uid]);
        if (! $userInfo) {
            $this->revokeTokenRecord($tokenInfo);
            throw $this->exception('用户信息不存在');
        }
        if ($userInfo->status == UserEnum::USER_LOCKING || app()->get(AdminInfoService::class)->value($userInfo->id, 'type') == 4) {
            $this->revokeTokenRecord($tokenInfo);
            throw $this->exception('您的账号已被锁定,无法登录!');
        }

        if ($tokenInfo->remember_token) {
            $this->invalidateAccessToken($tokenInfo->remember_token, '刷新登录Token失效失败');
        }

        $accessToken      = auth('admin')->login($userInfo, true);
        $newRefreshToken  = $this->generateRefreshToken();
        $refreshExpiresAt = Carbon::now()->addDays($this->getRefreshIdleDays())->toDateTimeString();

        $tokenInfo->last_token           = $tokenInfo->remember_token;
        $tokenInfo->remember_token       = $accessToken;
        $tokenInfo->refresh_token_hash   = $this->hashRefreshToken($newRefreshToken);
        $tokenInfo->refresh_expires_at   = $refreshExpiresAt;
        $tokenInfo->refresh_last_used_at = Carbon::now()->toDateTimeString();
        $tokenInfo->refresh_revoked_at   = null;
        $tokenInfo->last_ip              = request()->ip();
        $tokenInfo->fail_time            = null;
        $tokenInfo->save();

        $response = $this->formatTokenResponse($accessToken, $newRefreshToken, $refreshExpiresAt);
        Cache::tags([CacheEnum::TAG_OTHER])->put($cacheKey, $response, self::REFRESH_REPLAY_SECONDS);

        return $response;
    }

    /**
     * 退出登录时撤销当前 access token 关联的 refresh token.
     */
    public function revokeByAccessToken(?string $accessToken): void
    {
        if (! $accessToken) {
            return;
        }

        $tokenInfo = $this->dao->getModel(false)
            ->where(function ($query) use ($accessToken) {
                $query->where('remember_token', $accessToken)
                    ->orWhere('last_token', $accessToken);
            })
            ->whereNull('refresh_revoked_at')
            ->first();

        if ($tokenInfo) {
            $this->revokeTokenRecord($tokenInfo);
        }
    }

    private function getRefreshIdleDays(): int
    {
        return max(1, (int) config('jwt.refresh_idle_days', 7));
    }

    private function formatTokenResponse(string $accessToken, string $refreshToken, string $refreshExpiresAt): array
    {
        return [
            'token'              => $accessToken,
            'refresh_token'      => $refreshToken,
            'token_type'         => 'bearer',
            'expires_in'         => auth('admin')->factory()->getTTL() * 60,
            'refresh_expires_in' => max(0, Carbon::now()->diffInSeconds(Carbon::parse($refreshExpiresAt), false)),
        ];
    }

    private function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function hashRefreshToken(string $refreshToken): string
    {
        return hash('sha256', $refreshToken);
    }

    private function replayCacheKey(string $refreshHash): string
    {
        return 'user_refresh_token_replay:' . $refreshHash;
    }

    private function forgetRefreshReplayCache(?string $refreshHash): void
    {
        if ($refreshHash) {
            Cache::tags([CacheEnum::TAG_OTHER])->forget($this->replayCacheKey($refreshHash));
        }
    }

    private function getSingleClientTokens(string $uid, string $client)
    {
        return $this->dao->getModel(false)
            ->where('uid', $uid)
            ->when($client === 'web', function ($query) {
                $query->whereIn('client', ['web', '']);
            }, function ($query) use ($client) {
                $query->where('client', $client);
            })
            ->get();
    }

    private function revokeTokenRecord($tokenInfo): void
    {
        $this->forgetRefreshReplayCache($tokenInfo->refresh_token_hash);
        $now = Carbon::now()->toDateTimeString();
        $tokenInfo->refresh_revoked_at = $now;
        $tokenInfo->fail_time          = $now;
        $tokenInfo->save();
    }

    private function invalidateAccessToken(string $token, string $message): void
    {
        try {
            auth('admin')->setToken($token)->invalidate(true);
        } catch (TokenExpiredException|TokenBlacklistedException) {
            return;
        } catch (\Exception $e) {
            Log::error($message . '：' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }
}
