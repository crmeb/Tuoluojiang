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

use App\Http\Service\Ai\Guest\AiGuestAuthService;
use Tuoluojiang\Open\exceptions\AdminException;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;

/**
 * 外部 AI 访客凭证中间件（无需 JWT）.
 *
 * 校验 X-Ai-Guest-Token 为 UUID v4，并在 Request 上注册 guestToken()/guestFingerprint()/isGuestAi() 宏.
 * 设备指纹 Header（X-Ai-Guest-Fp）可选校验格式；创建会话与流式对话由 Service 强制要求有效指纹.
 * Kernel 别名：ai.guest.
 */
class AiGuestAuth implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    /**
     * 请求前校验访客 Header/Cookie.
     *
     * @throws AdminException 缺少或无效凭证（410003）
     */
    public function before(Request $request)
    {
        $service = app(AiGuestAuthService::class);
        $token   = $service->guestTokenFromRequest($request);
        if (! $service->isValidGuestToken($token)) {
            throw new AdminException('缺少或无效的访客凭证', 410003);
        }

        $fingerprint = $service->guestFingerprintFromRequest($request);
        if ($fingerprint !== '' && ! $service->isValidGuestFingerprint($fingerprint)) {
            throw new AdminException('缺少或无效的设备指纹', 410003);
        }

        $request->macro('guestToken', fn (): string => $token);
        $request->macro('guestFingerprint', fn (): string => $fingerprint);
        $request->macro('isGuestAi', fn (): bool => true);
    }

    public function after($response)
    {
        $this->request = null;

        return $response;
    }
}
