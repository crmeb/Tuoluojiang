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

use App\Constants\Ai\AiConversationEnum;
use App\Http\Dao\Ai\AiApplicationDao;
use App\Http\Model\Ai\AiApplication;
use App\Http\Service\Ai\AiApplicationService;
use App\Http\Service\Ai\AiConversationMessageService;
use App\Http\Service\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * 外部 AI 访客鉴权与分享配置；兼管内部应用预览联调 Header 校验.
 *
 * 职责：share_key 解析、访客 UUID 校验、设备指纹、域名白名单、限流、
 * 分享/嵌入路径、Widget 嵌入代码与滑动窗口限流；内部预览通过 X-Ai-Guest-Debug 校验登录 JWT.
 * 配置项见 config/ai.guest.
 */
class AiGuestAuthService extends BaseService
{
    /**
     * 联调 Header：外部访客联调值为 share_key；内部应用预览值为当前登录用户 JWT.
     */
    private const GUEST_DEBUG_HEADER = 'X-Ai-Guest-Debug';

    private const GUEST_DEBUG_QUERY = 'guest_debug';

    public function __construct(AiApplicationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 从请求 Header 或 Cookie 读取访客凭证.
     *
     * Header 名由 config('ai.guest.header') 决定，默认 X-Ai-Guest-Token.
     */
    public function guestTokenFromRequest(Request $request): string
    {
        $header = (string) config('ai.guest.header', 'X-Ai-Guest-Token');
        $token  = trim((string) ($request->header($header) ?: $request->cookie('ai_guest_token')));

        return $token;
    }

    /**
     * 从请求 Header 读取设备指纹（SHA-256 hex）.
     *
     * Header 名由 config('ai.guest.fingerprint_header') 决定，默认 X-Ai-Guest-Fp.
     * 前端由 UA + 屏幕 + 时区 + 语言 + shareKey 生成.
     */
    public function guestFingerprintFromRequest(Request $request): string
    {
        $header = (string) config('ai.guest.fingerprint_header', 'X-Ai-Guest-Fp');

        return strtolower(trim((string) $request->header($header, '')));
    }

    /**
     * 校验访客凭证是否为非空 UUID v4.
     */
    public function isValidGuestToken(string $token): bool
    {
        return $token !== '' && Str::isUuid($token);
    }

    /**
     * 校验设备指纹是否为 64 位十六进制（SHA-256）.
     */
    public function isValidGuestFingerprint(string $fingerprint): bool
    {
        return $fingerprint !== '' && (bool) preg_match('/^[a-f0-9]{64}$/', $fingerprint);
    }

    /**
     * 强制要求有效设备指纹.
     *
     * @throws \Throwable 缺少或无效指纹
     */
    public function assertGuestFingerprint(Request $request): string
    {
        $fp = $this->guestFingerprintFromRequest($request);
        if (! $this->isValidGuestFingerprint($fp)) {
            throw $this->exception('缺少或无效的设备指纹');
        }

        return $fp;
    }

    /**
     * 从请求 Header 或 Query 读取访客联调参数 guest_debug.
     */
    public function guestDebugFromRequest(Request $request): string
    {
        $key = trim((string) $request->header(self::GUEST_DEBUG_HEADER, ''));
        if ($key !== '') {
            return $key;
        }

        return trim((string) $request->query(self::GUEST_DEBUG_QUERY, ''));
    }

    /**
     * 是否为访客联调请求（允许访问未发布外部应用）.
     *
     * 请求携带 guest_debug（Query）或 X-Ai-Guest-Debug（Header），且值与应用 share_key 一致时返回 true.
     */
    public function isGuestDebugRequest(?Request $request, string $shareKey): bool
    {
        $shareKey = trim($shareKey);
        if ($shareKey === '') {
            return false;
        }

        $request ??= request();
        $provided = $this->guestDebugFromRequest($request);
        if ($provided === '') {
            return false;
        }

        return hash_equals($shareKey, $provided);
    }

    /**
     * 是否为内部应用预览联调请求（允许访问未发布内部应用）.
     *
     * Header X-Ai-Guest-Debug 须与当前 Authorization Bearer 为同一登录 JWT，
     * 且 admin guard 已鉴权通过。不单独 setToken，避免污染同请求内的身份上下文.
     */
    public function isInternalPreviewDebugRequest(?Request $request = null): bool
    {
        $request ??= request();
        $token = $this->normalizeAuthToken((string) $request->header(self::GUEST_DEBUG_HEADER, ''));
        if ($token === '') {
            return false;
        }

        $bearer = $this->normalizeAuthToken((string) ($request->bearerToken() ?? ''));
        if ($bearer === '' || ! hash_equals($bearer, $token)) {
            return false;
        }

        try {
            return (bool) auth('admin')->check();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 规范化鉴权 token（去除 Bearer 前缀与首尾空白）.
     */
    public function normalizeAuthToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $token, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        return $token;
    }

    /**
     * 通过 share_key 解析可用的外部 AI 应用.
     *
     * 校验：应用启用、外部类型、分享开启、未过期、来源域名（若配置）.
     * 访客联调模式下（guest_debug=share_key）可跳过发布状态与分享开关/过期校验，
     * 并改读未发布草稿。正式访问只读已发布列.
     *
     * @throws \Throwable 分享无效或来源未授权
     */
    public function resolvePublicApp(string $shareKey, ?Request $request = null): AiApplication
    {
        $request = $request ?? request();

        $shareKey = trim($shareKey);
        if ($shareKey === '') {
            throw $this->exception('分享链接无效');
        }

        $debug = $this->isGuestDebugRequest($request, $shareKey);

        $where = ['share_key' => $shareKey];
        if (! $debug) {
            $where['status'] = AiConversationEnum::STATUS_ENABLED;
        }

        /** @var null|AiApplication $app */
        $app = $this->dao->get($where);
        if (! $app) {
            throw $this->exception($debug ? 'AI应用不存在' : 'AI应用不存在或已禁用');
        }
        if ((int) $app->app_type !== AiConversationEnum::APP_TYPE_EXTERNAL) {
            throw $this->exception('该应用未开放外部访问');
        }
        if (! $debug) {
            if (! (int) ($app->share_enabled ?? 0)) {
                throw $this->exception('外部分享已关闭');
            }
            $expireAt = $app->share_expire_at ?? null;
            if ($expireAt && strtotime((string) $expireAt) < time()) {
                throw $this->exception('分享链接已过期');
            }
        }

        $this->assertAllowedOrigin($app, $request);

        if ($debug) {
            $app = app(AiApplicationService::class)->applyDraftConfig($app);
        }

        return $app;
    }


    /**
     * 从 AI 应用解析访客配置（guest_json 与系统默认合并）.
     *
     * @return array{allowed_origins: array<int, string>, widget_position: string, widget_color: string, widget_title: string, max_message_length: int, sensitive_words: array<int, string>}
     */
    public static function guestConfigFromApplication(AiApplication $app): array
    {
        return self::resolveGuestConfig(is_array($app->guest_json) ? $app->guest_json : null);
    }

    /**
     * 合并 guest_json 与 config/ai.guest.defaults.
     *
     * @param array<string, mixed>|null $raw
     *
     * @return array{allowed_origins: array<int, string>, widget_position: string, widget_color: string, widget_title: string, max_message_length: int, sensitive_words: array<int, string>}
     */
    public static function resolveGuestConfig(?array $raw = null): array
    {
        $raw             = is_array($raw) ? $raw : [];
        $defaults        = (array) config('ai.guest.defaults', []);
        $contentDefaults = (array) config('ai.guest.content', []);
        $origins         = $raw['allowed_origins'] ?? $defaults['allowed_origins'] ?? [];
        $words           = $raw['sensitive_words'] ?? $defaults['sensitive_words'] ?? [];

        return [
            'allowed_origins'    => array_values(array_filter(array_map('strval', is_array($origins) ? $origins : []))),
            'widget_position'    => (string) ($raw['widget_position'] ?? $defaults['widget_position'] ?? 'bottom-right'),
            'widget_color'       => (string) ($raw['widget_color'] ?? $defaults['widget_color'] ?? '#3370ff'),
            'widget_title'       => (string) ($raw['widget_title'] ?? $defaults['widget_title'] ?? 'AI 助手'),
            'max_message_length' => (int) ($raw['max_message_length'] ?? $defaults['max_message_length'] ?? $contentDefaults['max_message_length'] ?? 4000),
            'sensitive_words'    => array_values(array_filter(array_map('strval', is_array($words) ? $words : []))),
        ];
    }

    /**
     * 校验请求 Origin/Referer 是否在 guest_json.allowed_origins 白名单内.
     *
     * 白名单为空或含 `*` 时不限制；支持精确匹配与 *.domain 通配.
     *
     * @throws \Throwable 来源域名未授权
     */
    public function assertAllowedOrigin(AiApplication $app, Request $request): void
    {
        $config  = self::guestConfigFromApplication($app);
        $allowed = array_values(array_filter(array_map('trim', (array) ($config['allowed_origins'] ?? [])), static fn (string $v) => $v !== ''));
        if ($allowed === [] || in_array('*', $allowed, true)) {
            return;
        }

        $origin = trim((string) ($request->header('Origin') ?: $request->header('Referer')));
        if ($origin === '') {
            return;
        }

        $host = (string) parse_url($origin, PHP_URL_HOST);
        if ($host === '') {
            throw $this->exception('来源域名未授权');
        }

        foreach ($allowed as $pattern) {
            if ($pattern === $host || $pattern === $origin) {
                return;
            }
            if (str_starts_with($pattern, '*.') && str_ends_with($host, substr($pattern, 1))) {
                return;
            }
        }

        throw $this->exception('来源域名未授权');
    }

    /**
     * 对外公开的应用信息（不含敏感配置）.
     *
     * @return array<string, mixed>
     */
    public function publicAppPayload(AiApplication $app, ?Request $request = null): array
    {
        $guest = self::guestConfigFromApplication($app);
        $debug = $this->isGuestDebugRequest($request, (string) ($app->share_key ?? ''));

        return [
            'id'            => (int) $app->id,
            'name'          => (string) $app->name,
            'info'          => (string) $app->info,
            'pic'           => (string) $app->pic,
            'prologue_text' => (string) $app->prologue_text,
            'prologue_list' => $app->prologue_list ?? [],
            'app_type'      => (int) $app->app_type,
            'status'        => (int) ($app->status ?? 0),
            'share_enabled' => (int) ($app->share_enabled ?? 0),
            'guest_debug'   => $debug,
            'guest_config'  => [
                'max_message_length' => (int) ($guest['max_message_length'] ?? 0),
            ],
        ];
    }

    /**
     * 生成会话归属 HMAC，供前端可选校验 session_token.
     */
    public function makeSessionToken(int $conversationId, string $guestToken, string $shareKey): string
    {
        return hash_hmac('sha256', $conversationId . '|' . $guestToken, $shareKey);
    }

    /**
     * 校验 session_token；空 token 时跳过（兼容未传场景）.
     *
     * @throws \Throwable 凭证不匹配
     */
    public function assertSessionToken(int $conversationId, string $guestToken, string $shareKey, string $sessionToken): void
    {
        if ($sessionToken === '') {
            return;
        }

        $expected = $this->makeSessionToken($conversationId, $guestToken, $shareKey);
        if (! hash_equals($expected, $sessionToken)) {
            throw $this->exception('会话凭证无效');
        }
    }

    /**
     * 递增并校验分钟级限流计数（滑动窗口）.
     *
     * @param string $action     限流键，见 config ai.guest.rate_limit
     * @param string $guestToken 访客 UUID 或 IP（app_info 场景）
     *
     * @throws \Throwable 超过上限
     */
    public function assertRateLimit(string $action, string $guestToken, int $appId = 0): void
    {
        $limits = (array) config('ai.guest.rate_limit', []);
        $max    = (int) ($limits[$action] ?? 0);
        if ($max <= 0) {
            return;
        }

        $ip       = (string) request()->ip();
        $fp       = $this->guestFingerprintFromRequest(request());
        $identity = $action === 'app_info_per_minute'
            ? $ip
            : $guestToken . '|' . $ip . ($fp !== '' ? '|' . $fp : '');
        $bucket = self::rateLimitBucket($action, $appId, $identity);
        if (! $this->rateLimitHit($bucket, 60, $max)) {
            throw $this->exception('操作过于频繁，请稍后再试');
        }
    }

    /**
     * 流式对话前的综合校验：指纹、封禁、内容、风险与多维限流.
     */
    public function assertStreamAllowed(AiApplication $app, string $guestToken, Request $request): void
    {
        $appId   = (int) $app->id;
        $ip      = (string) $request->ip();
        $message = trim((string) $request->post('message', ''));
        $fp      = $this->assertGuestFingerprint($request);

        if (app(AiGuestRiskService::class)->isBanned($ip, $guestToken, $appId, $fp)) {
            throw $this->exception('访问受限，请稍后再试');
        }

        try {
            app(AiGuestContentGuard::class)->assertInputAllowed($app, $message);
        } catch (\Throwable $e) {
            app(AiGuestRiskService::class)->recordViolation($ip, $guestToken, $appId, 'content', $fp);
            throw $e;
        }

        $riskService = app(AiGuestRiskService::class);
        $score       = $riskService->score($request, $app, $guestToken, $message, $fp);
        $riskService->assertAcceptable($request, $app, $guestToken, $message, $score, $fp);

        $limits = (array) config('ai.guest.rate_limit', []);
        $this->assertGuestStreamRateLimit($appId, $guestToken, $ip, $fp, $limits);
    }

    /**
     * 访客 stream 多维限流：guest+IP+指纹、指纹分钟、IP 分钟、IP 日配额.
     *
     * @param array<string, int> $limits
     *
     * @throws \Throwable 超过上限
     */
    private function assertGuestStreamRateLimit(
        int $appId,
        string $guestToken,
        string $ip,
        string $fingerprint,
        array $limits
    ): void {
        $guestLimit = (int) ($limits['stream_per_minute'] ?? 0);
        if ($guestLimit > 0) {
            $bucket = self::rateLimitBucket('stream_guest', $appId, $guestToken . '|' . $ip . '|' . $fingerprint);
            if (! $this->rateLimitHit($bucket, 60, $guestLimit)) {
                throw $this->exception('操作过于频繁，请稍后再试');
            }
        }

        $fpLimit = (int) ($limits['stream_fp_per_minute'] ?? 0);
        if ($fpLimit > 0 && $fingerprint !== '') {
            $bucket = self::rateLimitBucket('stream_fp', $appId, $fingerprint);
            if (! $this->rateLimitHit($bucket, 60, $fpLimit)) {
                throw $this->exception('操作过于频繁，请稍后再试');
            }
        }

        $ipLimit = (int) ($limits['stream_ip_per_minute'] ?? 0);
        if ($ipLimit > 0) {
            $bucket = self::rateLimitBucket('stream_ip', $appId, $ip);
            if (! $this->rateLimitHit($bucket, 60, $ipLimit)) {
                throw $this->exception('操作过于频繁，请稍后再试');
            }
        }

        $ipDailyLimit = (int) ($limits['stream_ip_daily'] ?? 0);
        if ($ipDailyLimit > 0) {
            $bucket = self::rateLimitBucket('stream_ip_day', $appId, $ip . ':' . date('Ymd'));
            if (! $this->rateLimitHit($bucket, 86400, $ipDailyLimit)) {
                throw $this->exception('今日访问次数已达上限');
            }
        }
    }

    /**
     * 校验访客/IP/指纹未处于封禁状态.
     *
     * @throws \Throwable 已封禁
     */
    public function assertNotBanned(string $guestToken, int $appId, string $fingerprint = ''): void
    {
        $ip = (string) request()->ip();
        if ($fingerprint === '') {
            $fingerprint = $this->guestFingerprintFromRequest(request());
        }
        if (app(AiGuestRiskService::class)->isBanned($ip, $guestToken, $appId, $fingerprint)) {
            throw $this->exception('访问受限，请稍后再试');
        }
    }

    /**
     * 校验外部应用当日访客消息总数是否超过应用或系统上限.
     *
     * 限额按应用共享（全部访客合计），不按单个 guest_token 隔离.
     *
     * @param AiApplication $app 外部应用
     * @param string $guestToken 访客 UUID（保留兼容调用方，不参与计数）
     *
     * @throws \Throwable 已达上限
     */
    public function assertGuestDailyLimit(AiApplication $app, string $guestToken): void
    {
        $limit = (int) ($app->guest_use_limit ?? 0);
        if ($limit <= 0) {
            $limit = (int) config('ai.guest.default_daily_messages', 0);
        }
        if ($limit <= 0) {
            return;
        }

        $used = app(AiConversationMessageService::class)->guestAppDailyCount((int) $app->id);
        if ($used >= $limit) {
            throw $this->exception('今日使用已达到上限');
        }
    }

    /** 生成 32 位随机分享密钥. */
    public function generateShareKey(): string
    {
        return Str::random(32);
    }

    /**
     * 组装管理端分享配置响应（含嵌入代码）.
     *
     * @return array<string, mixed>
     */
    public function shareSettings(AiApplication $app): array
    {
        $shareKey = (string) ($app->share_key ?? '');
        $guest    = self::guestConfigFromApplication($app);
        $origin   = rtrim((string) (request()->getSchemeAndHttpHost() ?: config('app.url', '')), '/');

        return [
            'share_key'         => $shareKey,
            'share_enabled'     => (int) ($app->share_enabled ?? 0),
            'share_expire_at'   => $app->share_expire_at,
            'guest_use_limit'   => (int) ($app->guest_use_limit ?? 0),
            'guest_json'        => $guest,
            'share_path'        => $this->sharePath($shareKey),
            'embed_path'        => $this->embedPath($shareKey),
            'widget_embed_code' => $this->widgetEmbedCode($shareKey, $origin, $guest),
            'guest_debug'       => $this->guestDebugSettingsHint(),
        ];
    }

    /**
     * 管理端分享配置中返回访客调试接入说明（不含密钥本身）.
     *
     * @return array<string, mixed>
     */
    public function guestDebugSettingsHint(): array
    {
        return [
            'header'      => self::GUEST_DEBUG_HEADER,
            'query_param' => self::GUEST_DEBUG_QUERY,
            'hint'        => '外部联调未发布应用：guest_debug 或 X-Ai-Guest-Debug 传 share_key；内部应用预览：X-Ai-Guest-Debug 传当前登录 JWT',
        ];
    }

    /**
     * iframe 嵌入页相对路径（由前端 SPA 实现）.
     */
    public function embedPath(string $shareKey): string
    {
        if ($shareKey === '') {
            return '';
        }

        return '/ai/embed/' . $shareKey;
    }

    /**
     * 生成第三方站点可粘贴的 Widget script 标签.
     *
     * @param array<string, mixed>|null $guest 访客配置数组；null 时用系统默认
     */
    public function widgetEmbedCode(string $shareKey, string $origin, ?array $guest = null): string
    {
        if ($shareKey === '' || $origin === '') {
            return '';
        }

        $guest ??= self::resolveGuestConfig();
        $attrs = [
            'src="' . htmlspecialchars($origin . '/ai/widget.js', ENT_QUOTES) . '"',
            'async',
            'data-share-key="' . htmlspecialchars($shareKey, ENT_QUOTES) . '"',
            'data-base-url="' . htmlspecialchars($origin, ENT_QUOTES) . '"',
            'data-position="' . htmlspecialchars((string) ($guest['widget_position'] ?? 'bottom-right'), ENT_QUOTES) . '"',
            'data-color="' . htmlspecialchars((string) ($guest['widget_color'] ?? '#3370ff'), ENT_QUOTES) . '"',
            'data-title="' . htmlspecialchars((string) ($guest['widget_title'] ?? 'AI 助手'), ENT_QUOTES) . '"',
        ];

        return '<script ' . implode(' ', $attrs) . '></script>';
    }

    /**
     * 独立分享页相对路径（由前端 SPA 实现）.
     */
    public function sharePath(string $shareKey): string
    {
        if ($shareKey === '') {
            return '';
        }

        return '/ai/share/' . $shareKey;
    }

    private const RATE_LIMIT_KEY_PREFIX = 'ai_guest:sw:';

    /**
     * 记录一次命中；超过上限返回 false.
     */
    public function rateLimitHit(string $bucket, int $windowSec, int $max): bool
    {
        if ($max <= 0 || $windowSec <= 0) {
            return true;
        }

        $key = self::RATE_LIMIT_KEY_PREFIX . $bucket;
        if ($this->rateLimitUseRedis()) {
            return $this->rateLimitHitRedis($key, $windowSec, $max);
        }

        return $this->rateLimitHitCache($key, $windowSec, $max);
    }

    /**
     * 当前窗口内计数（不递增）.
     */
    public function rateLimitCount(string $bucket, int $windowSec): int
    {
        if ($windowSec <= 0) {
            return 0;
        }

        $key = self::RATE_LIMIT_KEY_PREFIX . $bucket;
        if ($this->rateLimitUseRedis()) {
            return $this->rateLimitCountRedis($key, $windowSec);
        }

        return (int) Cache::get($key, 0);
    }

    /**
     * 清除指定桶计数.
     */
    public function rateLimitReset(string $bucket): void
    {
        $key = self::RATE_LIMIT_KEY_PREFIX . $bucket;
        if ($this->rateLimitUseRedis()) {
            try {
                Redis::del($key);
            } catch (\Throwable) {
                Cache::forget($key);
            }

            return;
        }

        Cache::forget($key);
    }

    /**
     * 组装限流桶名.
     */
    public static function rateLimitBucket(string $action, int $appId, string $identity): string
    {
        return sprintf('%s:%d:%s', $action, $appId, md5($identity));
    }

    private function rateLimitUseRedis(): bool
    {
        static $available = null;
        if ($available !== null) {
            return $available;
        }

        try {
            Redis::connection()->client();
            $available = true;
        } catch (\Throwable) {
            $available = false;
        }

        return $available;
    }

    private function rateLimitHitRedis(string $key, int $windowSec, int $max): bool
    {
        try {
            $now = microtime(true);
            Redis::zremrangebyscore($key, 0, $now - $windowSec);
            if ((int) Redis::zcard($key) >= $max) {
                return false;
            }
            Redis::zadd($key, $now, uniqid('', true));
            Redis::expire($key, $windowSec + 1);

            return true;
        } catch (\Throwable) {
            return $this->rateLimitHitCache($key, $windowSec, $max);
        }
    }

    private function rateLimitCountRedis(string $key, int $windowSec): int
    {
        try {
            $now = microtime(true);
            Redis::zremrangebyscore($key, 0, $now - $windowSec);

            return (int) Redis::zcard($key);
        } catch (\Throwable) {
            return (int) Cache::get($key, 0);
        }
    }

    private function rateLimitHitCache(string $key, int $windowSec, int $max): bool
    {
        $hit = (int) Cache::get($key, 0);
        if ($hit >= $max) {
            return false;
        }

        Cache::put($key, $hit + 1, $windowSec);

        return true;
    }

}
