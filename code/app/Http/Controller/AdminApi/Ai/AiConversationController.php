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
use App\Http\Service\Ai\Agent\Trace\AgentTracer;
use App\Http\Service\Ai\AiConversationService;
use App\Http\Service\Ai\AiStreamService;
use Illuminate\Support\Facades\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * AI 会话管理控制器（登录用户）.
 *
 * 路由前缀 ent/ai/conversations，对应表 chat_history、chat_record.
 * 鉴权：auth.admin + ent.auth + ent.log（JWT 管理端登录）.
 * 提供会话 CRUD、消息记录、SSE 流式对话及 Agent 人机确认能力.
 */
#[Prefix('ent/ai/conversations')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AiConversationController extends AuthController
{
    public function __construct(AiConversationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取当前用户的 AI 会话列表.
     */
    #[Get('/', '获取AI会话列表')]
    public function index()
    {
        $where = $this->request->getMore([
            ['app_id', ''],
            ['status', ''],
        ]);

        return $this->success($this->service->getList($where));
    }

    /**
     * 创建新的 AI 会话.
     */
    #[Post('/', '创建AI会话')]
    public function store()
    {
        $request = app(AiConversationRequest::class)->scene('store');
        $request->check();
        [$appId, $title] = $this->request->postMore([
            ['app_id', 0],
            ['title', ''],
        ], true);
        $conversation = $this->service->createConversation((int) $appId, (string) $title);

        return $this->success($conversation->toArray());
    }

    /**
     * 更新会话标题、显示状态或置顶.
     *
     * @param int|string $id 会话 ID
     */
    #[Put('{id}', '更新AI会话')]
    public function update($id)
    {
        $request = app(AiConversationRequest::class)->scene('update');
        $request->check();
        $this->service->updateConversation((int) $id, $this->request->postMore([
            ['title', ''],
            ['status', ''],
            ['top', ''],
        ]));

        return $this->success('修改成功');
    }

    /**
     * 软删除指定 AI 会话.
     *
     * @param int|string $id 会话 ID
     */
    #[Delete('{id}', '删除AI会话')]
    public function delete($id)
    {
        $this->service->deleteConversation((int) $id);

        return $this->success('删除成功');
    }

    /**
     * 获取会话下的全部消息记录.
     *
     * @param int|string $id 会话 ID
     */
    #[Get('{id}/records', '获取AI会话消息记录')]
    public function records($id)
    {
        return $this->success($this->service->records((int) $id));
    }

    /**
     * 按消息 UUID 获取可分页查询结果（SQL 或 MCP 日程汇总等）.
     *
     * @param string $uuid 消息 UUID
     */
    #[Get('records/{uuid}', '获取AI会话SQL记录结果')]
    public function record($uuid)
    {
        if (! $uuid) {
            return $this->fail('缺少对话记录ID');
        }

        return $this->success($this->service->getRecord((string) $uuid));
    }

    /**
     * 清空会话内全部消息记录.
     *
     * @param int|string $id 会话 ID
     */
    #[Delete('{id}/records', '清空AI会话消息记录')]
    public function clear($id)
    {
        $this->service->clearRecords((int) $id);

        return $this->success('清理成功');
    }

    /**
     * SSE 流式对话，返回 text/event-stream.
     *
     * @param int|string $id 会话 ID
     */
    #[Post('{id}/stream', 'AI流式对话')]
    public function stream($id)
    {
        $request = app(AiConversationRequest::class)->scene('stream');
        $request->check();
        $message = (string) $this->request->post('message', '');

        return $this->streamResponse(fn () => $this->service->stream((int) $id, $message));
    }

    /**
     * 重新生成上一条 AI 回答（SSE 流式）.
     *
     * @param int|string $id 会话 ID
     */
    #[Post('{id}/regenerate', '重新生成AI回答')]
    public function regenerate($id)
    {
        return $this->streamResponse(fn () => $this->service->regenerate((int) $id));
    }

    /**
     * 中断正在进行的流式输出.
     *
     * @param int|string $id 会话 ID
     */
    #[Post('{id}/interrupt', '中断AI流式对话')]
    public function interrupt($id)
    {
        $messageUuid = (string) $this->request->post('message_uuid', '');
        if ($messageUuid === '') {
            return $this->fail('缺少消息ID');
        }

        return $this->success(['interrupt' => $this->service->interrupt((int) $id, $messageUuid)]);
    }

    /**
     * 提交 Agent 待确认信息并续跑（支持单条或批量 inputs）.
     *
     * @param int|string $id 会话 ID
     */
    #[Post('{id}/agent/input', '提交Agent信息确认并续跑')]
    public function submitAgentInput($id)
    {
        $inputs = $this->request->post('inputs', []);
        if (is_array($inputs) && $inputs !== []) {
            return $this->streamResponse(fn () => $this->service->resumeAgentInputs((int) $id, $inputs));
        }

        [$promptId, $value, $label] = $this->request->postMore([
            ['prompt_id', 0],
            ['value', null],
            ['label', ''],
        ], true);

        if ((int) $promptId <= 0) {
            return $this->fail('缺少确认项ID');
        }

        return $this->streamResponse(fn () => $this->service->resumeAgentInput(
            (int) $id,
            (int) $promptId,
            $value,
            (string) $label
        ));
    }

    /** 用自然语言回答当前待补充字段并续跑 Agent。 */
    #[Post('{id}/agent/input/text', '用自然语言提交Agent信息并续跑')]
    public function submitAgentInputText($id)
    {
        $messageUuid = (string) $this->request->post('message_uuid', '');
        $message     = trim((string) $this->request->post('message', ''));
        if ($messageUuid === '' || $message === '') {
            return $this->fail('缺少待确认消息或补充内容');
        }

        return $this->streamResponse(fn () => $this->service->resumeAgentInputText(
            (int) $id,
            $messageUuid,
            $message
        ));
    }

    /**
     * 用户关闭补参卡片：取消该消息下全部待补项并收尾该轮.
     *
     * @param int|string $id 会话 ID
     */
    #[Post('{id}/agent/input/dismiss', '取消Agent信息确认')]
    public function dismissAgentInput($id)
    {
        $messageUuid = (string) $this->request->post('message_uuid', '');
        if ($messageUuid === '') {
            return $this->fail('缺少待取消消息ID');
        }

        // SSE 由 dismissAgentInput 内部推送，对齐 agent/confirm 的取消收尾模式。
        return $this->streamResponse(fn () => $this->service->dismissAgentInput(
            (int) $id,
            $messageUuid
        ));
    }

    /**
     * 获取当前消息下待用户补充的 Agent 输入项.
     *
     * @param int|string $id 会话 ID
     */
    #[Get('{id}/agent/input/pending', '获取待确认Agent信息')]
    public function pendingAgentInputs($id)
    {
        $messageUuid = (string) $this->request->get('message_uuid', '');

        return $this->success($this->service->pendingAgentInputs((int) $id, $messageUuid));
    }

    /**
     * 确认或拒绝 Agent 写操作（工具调用）.
     *
     * @param int|string $id 会话 ID
     */
    #[Post('{id}/agent/confirm', '确认Agent写操作')]
    public function confirmAgentAction($id)
    {
        [$pendingId, $approved, $toolArgs] = $this->request->postMore([
            ['pending_id', 0],
            ['approved', true],
            ['tool_args', []],
        ], true);

        // SSE 由 confirmAgentAction 内部推送（含写确认后续跑），对齐 agent/input 续跑模式。
        return $this->streamResponse(fn () => $this->service->confirmAgentAction(
            (int) $id,
            (int) $pendingId,
            (bool) $approved,
            is_array($toolArgs) ? $toolArgs : []
        ));
    }

    /**
     * 获取当前消息下待确认的 Agent 写操作列表.
     *
     * @param int|string $id 会话 ID
     */
    #[Get('{id}/agent/pending', '获取待确认Agent写操作')]
    public function pendingAgentActions($id)
    {
        $messageUuid = (string) $this->request->get('message_uuid', '');

        return $this->success($this->service->pendingAgentActions((int) $id, $messageUuid));
    }

    /**
     * 分页获取会话关联的 Agent 运行记录.
     *
     * @param int|string $id 会话 ID
     */
    #[Get('{id}/agent/runs', '获取会话Agent运行列表')]
    public function agentRuns($id)
    {
        [$page, $limit] = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
        ], true);

        return $this->success(app(AgentTracer::class)->listByConversation(
            (int) $id,
            (int) auth('admin')->id(),
            (int) $page,
            (int) $limit
        ));
    }

    /**
     * 封装 SSE 响应头与异常兜底输出.
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
