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

namespace App\Http\Service\Ai\Guest;

use App\Http\Model\Ai\AiApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * 外部 AI 访客风险评分与临时封禁（IP / guest_token / 设备指纹、违规计数）.
 */
final class AiGuestRiskService
{
    private const IP_TOKEN_PREFIX = 'ai_guest:ip_tokens:';

    private const IP_FP_PREFIX = 'ai_guest:ip_fps:';

    private const DUP_MSG_PREFIX = 'ai_guest:dup_msg:';

    private const BAN_PREFIX = 'ai_guest:ban:';

    private const VIOLATION_PREFIX = 'ai_guest:violation:';

    public function __construct(
        private AiGuestContentGuard $contentGuard
    ) {
    }

    /**
     * 计算 0~100 风险分并记录 IP-Token / IP-指纹关联.
     */
    public function score(Request $request, AiApplication $app, string $guestToken, string $message, string $fingerprint = ''): int
    {
        $appId = (int) $app->id;
        $ip    = (string) $request->ip();
        $score = 0;

        if ($fingerprint === '') {
            $score += 25;
        }

        $this->trackGuestTokenForIp($ip, $appId, $guestToken);
        $this->trackFingerprintForIp($ip, $appId, $fingerprint);

        $ipTokenThreshold = (int) config('ai.guest.risk.ip_token_threshold', 5);
        if ($this->distinctTokenCount($ip, $appId) > $ipTokenThreshold) {
            $score += 40;
        }

        $ipFpThreshold = (int) config('ai.guest.risk.ip_fp_threshold', 8);
        if ($fingerprint !== '' && $this->distinctFingerprintCount($ip, $appId) > $ipFpThreshold) {
            $score += 30;
        }

        $config = AiGuestAuthService::guestConfigFromApplication($app);
        $maxLen = (int) ($config['max_message_length'] ?? 0);
        if ($maxLen > 0 && mb_strlen(trim($message)) > (int) ($maxLen * 0.9)) {
            $score += 15;
        }

        if ($this->isDuplicateMessage($guestToken, $appId, $message)) {
            $score += 30;
        }

        if ($this->contentGuard->hasInjectionPattern($message)) {
            $score += 50;
        }

        return min(100, $score);
    }

    /**
     * 根据风险分执行拒绝或自动封禁.
     *
     * @throws \Tuoluojiang\Open\exceptions\AdminException 风险过高
     */
    public function assertAcceptable(
        Request $request,
        AiApplication $app,
        string $guestToken,
        string $message,
        int $score,
        string $fingerprint = ''
    ): void {
        $banThreshold    = (int) config('ai.guest.risk.ban_threshold', 85);
        $rejectThreshold = (int) config('ai.guest.risk.reject_threshold', 70);
        $ip              = (string) $request->ip();
        $appId           = (int) $app->id;

        if ($score >= $banThreshold) {
            $this->ban(
                $ip,
                $guestToken,
                $appId,
                (int) config('ai.guest.ban.ttl_sec', 3600),
                'risk_score_' . $score,
                $fingerprint
            );
            throw new \Tuoluojiang\Open\exceptions\AdminException('访问受限，请稍后再试', 429);
        }

        if ($score >= $rejectThreshold) {
            $this->recordViolation($ip, $guestToken, $appId, 'high_risk', $fingerprint);
            throw new \Tuoluojiang\Open\exceptions\AdminException('请求被拒绝，请稍后再试', 429);
        }
    }

    /**
     * 记录同 IP 下出现过的 guest_token.
     */
    private function trackGuestTokenForIp(string $ip, int $appId, string $guestToken): void
    {
        if ($ip === '' || $guestToken === '') {
            return;
        }

        $this->trackSetMember(self::IP_TOKEN_PREFIX . $appId . ':' . md5($ip), md5($guestToken));
    }

    /**
     * 记录同 IP 下出现过的设备指纹.
     */
    private function trackFingerprintForIp(string $ip, int $appId, string $fingerprint): void
    {
        if ($ip === '' || $fingerprint === '') {
            return;
        }

        $this->trackSetMember(self::IP_FP_PREFIX . $appId . ':' . md5($ip), md5($fingerprint));
    }

    private function trackSetMember(string $key, string $member): void
    {
        $window = (int) config('ai.guest.risk.ip_token_window_sec', 3600);

        try {
            Redis::sadd($key, $member);
            Redis::expire($key, $window);
        } catch (\Throwable) {
            $items   = (array) Cache::get($key, []);
            $items[] = $member;
            $items   = array_values(array_unique($items));
            Cache::put($key, $items, $window);
        }
    }

    private function distinctTokenCount(string $ip, int $appId): int
    {
        return $this->setCardinality(self::IP_TOKEN_PREFIX . $appId . ':' . md5($ip), $ip === '');
    }

    private function distinctFingerprintCount(string $ip, int $appId): int
    {
        return $this->setCardinality(self::IP_FP_PREFIX . $appId . ':' . md5($ip), $ip === '');
    }

    private function setCardinality(string $key, bool $empty): int
    {
        if ($empty) {
            return 0;
        }

        try {
            return (int) Redis::scard($key);
        } catch (\Throwable) {
            return count((array) Cache::get($key, []));
        }
    }

    /**
     * 短时间重复发送相同消息.
     */
    private function isDuplicateMessage(string $guestToken, int $appId, string $message): bool
    {
        $message = trim($message);
        if ($message === '') {
            return false;
        }

        $hash = md5(mb_strtolower($message));
        $key  = self::DUP_MSG_PREFIX . $appId . ':' . md5($guestToken);
        $ttl  = (int) config('ai.guest.risk.duplicate_window_sec', 120);

        $recent = (array) Cache::get($key, []);
        if (in_array($hash, $recent, true)) {
            return true;
        }

        array_unshift($recent, $hash);
        $recent = array_slice(array_values(array_unique($recent)), 0, 5);
        Cache::put($key, $recent, $ttl);

        return false;
    }


    /**
     * 是否已被封禁.
     */
    public function isBanned(string $ip, string $guestToken, int $appId, string $fingerprint = ''): bool
    {
        if ($this->hasBanKey($this->ipBanKey($ip, $appId))) {
            return true;
        }
        if ($guestToken !== '' && $this->hasBanKey($this->tokenBanKey($guestToken, $appId))) {
            return true;
        }

        return $fingerprint !== '' && $this->hasBanKey($this->fpBanKey($fingerprint, $appId));
    }

    /**
     * 封禁 IP、访客 token 与设备指纹.
     */
    public function ban(string $ip, string $guestToken, int $appId, int $ttlSec, string $reason = '', string $fingerprint = ''): void
    {
        $ttlSec = max(60, $ttlSec);
        Cache::put($this->ipBanKey($ip, $appId), $reason ?: 'abuse', $ttlSec);
        if ($guestToken !== '') {
            Cache::put($this->tokenBanKey($guestToken, $appId), $reason ?: 'abuse', $ttlSec);
        }
        if ($fingerprint !== '') {
            Cache::put($this->fpBanKey($fingerprint, $appId), $reason ?: 'abuse', $ttlSec);
        }
    }

    /**
     * 记录一次违规；达到阈值自动封禁.
     *
     * @return int 当前累计违规次数
     */
    public function recordViolation(string $ip, string $guestToken, int $appId, string $type, string $fingerprint = ''): int
    {
        $windowSec = (int) config('ai.guest.ban.violation_window_sec', 3600);
        $threshold = (int) config('ai.guest.ban.violation_threshold', 3);
        $banTtl    = (int) config('ai.guest.ban.ttl_sec', 3600);

        $key = self::VIOLATION_PREFIX . md5($ip . '|' . $guestToken . '|' . $fingerprint . '|' . $appId . '|' . $type);
        $hit = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $hit, $windowSec);

        if ($hit >= $threshold) {
            $this->ban($ip, $guestToken, $appId, $banTtl, $type, $fingerprint);
        }

        return $hit;
    }

    private function hasBanKey(string $key): bool
    {
        return Cache::get($key) !== null;
    }

    private function ipBanKey(string $ip, int $appId): string
    {
        return self::BAN_PREFIX . 'ip:' . $appId . ':' . md5($ip);
    }

    private function tokenBanKey(string $guestToken, int $appId): string
    {
        return self::BAN_PREFIX . 'token:' . $appId . ':' . md5($guestToken);
    }

    private function fpBanKey(string $fingerprint, int $appId): string
    {
        return self::BAN_PREFIX . 'fp:' . $appId . ':' . md5($fingerprint);
    }

}
