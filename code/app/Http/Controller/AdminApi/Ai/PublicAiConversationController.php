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

namespace App\Http\Controller\AdminApi\Ai;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Ai\AiConversationRequest;
use App\Http\Service\Ai\AiConversationService;
use App\Http\Service\Ai\Guest\AiGuestAuthService;
use App\Http\Service\Ai\AiStreamService;
use Illuminate\Support\Facades\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 外部 AI 访客公开 API（无需 JWT）.
 *
 * 路由前缀 api/ent/ai/public。需访客凭证的接口通过路由属性 middleware 挂载 ai.guest
 * （Spatie #[Middleware] 仅支持类级，方法级无效，须写在 Get/Post/Delete 的 middleware 参数）.
 * 创建会话与流式对话另需 X-Ai-Guest-Fp（前端由 UA+屏幕+时区+语言+shareKey 生成的 SHA-256）.
 * 访客模式下禁用 SQL、内部 MCP 与写操作工具.
 */
#[Prefix('ent/ai/public')]
class PublicAiConversationController extends AuthController
{
    public function __construct(
        AiConversationService $service,
        private AiGuestAuthService $guestAuth
    ) {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 通过 share_key 获取应用公开信息.
     *
     * @param string $shareKey 应用分享密钥
     */
    #[Get('apps/{shareKey}', '获取外部AI应用公开信息')]
    public function showApp(string $shareKey)
    {
        $this->guestAuth->assertRateLimit('app_info_per_minute', (string) $this->request->ip(), 0);
        $app = $this->guestAuth->resolvePublicApp($shareKey);

        return $this->success($this->guestAuth->publicAppPayload($app, $this->request));
    }

    /**
     * 创建访客会话，返回 session_token 与开场白.
     *
     * 需 Header：X-Ai-Guest-Token、X-Ai-Guest-Fp（设备指纹）.
     *
     * @param string $shareKey 应用分享密钥
     */
    #[Post('apps/{shareKey}/conversations', '创建外部AI访客会话', middleware: ['ai.guest'])]
    public function storeConversation(string $shareKey)
    {
        $guestToken = (string) $this->request->guestToken();
        $this->guestAuth->assertGuestFingerprint($this->request);
        $app = $this->guestAuth->resolvePublicApp($shareKey);
        $this->guestAuth->assertRateLimit('create_per_minute', $guestToken, (int) $app->id);

        $title = (string) $this->request->post('title', '');
        $conversation = $this->service->createGuestConversation($app, $guestToken, $title);
        $sessionToken = $this->guestAuth->makeSessionToken(
            (int) $conversation->id,
            $guestToken,
            (string) $app->share_key
        );

        return $this->success(array_merge($conversation->toArray(), [
            'session_token' => $sessionToken,
            'prologue_text' => (string) $app->prologue_text,
            'prologue_list' => $app->prologue_list ?? [],
        ]));
    }

    /**
     * 获取访客会话消息列表.
     *
     * @param int $id 会话 ID
     */
    #[Get('conversations/{id}/records', '获取外部AI会话消息记录', middleware: ['ai.guest'])]
    public function records(int $id)
    {
        $guestToken = (string) $this->request->guestToken();

        return $this->success($this->service->guestRecords($id, $guestToken));
    }

    /**
     * 清空访客会话全部消息.
     *
     * @param int $id 会话 ID
     */
    #[Delete('conversations/{id}/records', '清空外部AI会话消息记录', middleware: ['ai.guest'])]
    public function clearRecords(int $id)
    {
        $guestToken = (string) $this->request->guestToken();
        $this->service->guestClearRecords($id, $guestToken);

        return $this->success('清理成功');
    }

    /**
     * 访客 SSE 流式对话.
     *
     * @param int $id 会话 ID
     */
    #[Post('conversations/{id}/stream', '外部AI流式对话', middleware: ['ai.guest'])]
    public function stream(int $id)
    {
        $request = app(AiConversationRequest::class)->scene('stream');
        $request->check();
        $guestToken = (string) $this->request->guestToken();
        $message    = (string) $this->request->post('message', '');

        $this->service->assertGuestStreamAllowed($id, $guestToken, $this->request);

        return $this->streamResponse(fn () => $this->service->stream($id, $message, true, 0, $guestToken));
    }

    /**
     * 中断访客流式输出.
     *
     * @param int $id 会话 ID
     */
    #[Post('conversations/{id}/interrupt', '中断外部AI流式对话', middleware: ['ai.guest'])]
    public function interrupt(int $id)
    {
        $guestToken = (string) $this->request->guestToken();
        $messageUuid = (string) $this->request->post('message_uuid', '');
        if ($messageUuid === '') {
            return $this->fail('缺少消息ID');
        }

        return $this->success(['interrupt' => $this->service->guestInterrupt($id, $guestToken, $messageUuid)]);
    }

    /**
     * 封装 SSE 响应头与异常兜底.
     */
    private function streamResponse(callable $callback)
    {
        return Response::stream(function () use ($callback) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            try {
                $callback();
            } catch (\Throwable $e) {
                app(AiStreamService::class)->error($e->getMessage());
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=utf-8',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Pragma'            => 'no-cache',
            'Expires'           => '0',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Content-Encoding'  => 'identity',
        ]);
    }
}
