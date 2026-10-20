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

namespace App\Http\Service\Ai;

use App\Http\Service\Ai\Agent\Middleware\AgentMiddlewarePipeline;
use App\Http\Service\Ai\Agent\Middleware\AgentStreamContext;
use App\Http\Service\Ai\Agent\Middleware\ClosureMiddleware;
use App\Http\Service\Ai\Agent\Middleware\EvidenceGateMiddleware;
use App\Http\Service\Ai\Agent\ToolLoopExecutor;
use App\Http\Service\Ai\Agent\Decision\ThinDecisionBuilder;
use App\Constants\Ai\AgentEnum;
use App\Constants\Ai\AiConversationEnum;
use App\Constants\Ai\AiModelEnum;
use App\Http\Dao\Ai\AgentRunDao;
use App\Http\Dao\Ai\AiConversationDao;
use App\Http\Model\Ai\AgentRun;
use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiConversation;
use App\Http\Model\Ai\AiConversationMessage;
use App\Http\Model\Ai\AiModel;
use App\Http\Service\Ai\Agent\AgentExecutor;
use App\Http\Service\Ai\Agent\AgentConfig;
use App\Http\Service\Ai\Agent\AgentInputRequiredHelper;
use App\Http\Service\Ai\Agent\AgentRuntime;
use App\Http\Service\Ai\Agent\AgentWriteFlowDriver;
use App\Http\Service\Ai\Agent\MemoryManager;
use App\Http\Service\Ai\Agent\WriteFieldLabels;
use App\Http\Service\Ai\Agent\Intent\NarrowIntentSignals;
use App\Http\Service\Ai\Agent\Decision\McpToolAllowlist;
use App\Http\Service\Ai\Guest\AiGuestAuthService;
use App\Http\Service\Ai\Guest\AiGuestContentGuard;
use App\Http\Service\Knowledge\KnowledgeRagService;
use App\Mcp\Context\McpUserContextResolver;
use App\Http\Service\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI 会话主流程服务：会话 CRUD、鉴权/配额/落库，流式编排委托 AgentRuntime.
 *
 * 调用链：AdminApi 会话控制器 → 本服务 → AgentRuntime（Decision/Retrieval）→ AgentExecutor；
 * 流式编排委托 AgentRuntime（thin-react）；本类负责会话 CRUD、鉴权/配额/落库与写续跑入口.
 */
class AiConversationService extends BaseService
{
    /**
     * @param AiConversationDao $dao 会话数据访问对象
     */
    public function __construct(AiConversationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 分页获取当前用户的会话列表。
     *
     * @param array<string, mixed> $where 查询条件
     * @param array<int, string> $field 查询字段
     * @param null|array<int, string>|string $sort 排序规则
     * @param array<int, string> $with 预加载关联
     *
     * @return array<string, mixed> 含 list 与 count 的分页数据
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit]  = $this->getPageValue();
        $where['uid']    = (int) auth('admin')->id();
        $where['status'] = $where['status'] ?? AiConversationEnum::STATUS_ENABLED;
        $list            = $this->dao->getList($where, ['*'], $page, $limit, ['top_up' => 'desc', 'updated_at' => 'desc', 'id' => 'desc'], ['app']);

        return $this->listData($list, $this->dao->count($where));
    }

    /**
     * 创建内部用户会话并校验应用可用性。
     *
     * @param int $appId 应用 ID
     * @param string $title 会话标题，空则使用应用名称
     *
     * @return AiConversation 新建会话
     *
     * @throws \Throwable 应用不可用或无权限
     */
    public function createConversation(int $appId, string $title = ''): AiConversation
    {
        $uid = (int) auth('admin')->id();
        $app = app(AiApplicationService::class)->checkUsable($appId, $uid);

        return $this->dao->create([
            'uid'             => $uid,
            'app_id'          => $app->id,
            'title'           => $title ?: (string) $app->name,
            'status'          => AiConversationEnum::STATUS_ENABLED,
            'session_type'    => AiConversationEnum::SESSION_TYPE_USER,
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 创建访客会话（user_id=0，session_type=GUEST）。
     *
     * @param AiApplication $app 已校验可用的外部应用
     * @param string $guestToken 访客 UUID，写入 guest_token 并用于归属校验
     * @param string $title 会话标题
     *
     * @return AiConversation 新建访客会话
     *
     * @throws \Throwable 应用不可用或访客限额已满
     */
    public function createGuestConversation(AiApplication $app, string $guestToken, string $title = ''): AiConversation
    {
        app(AiApplicationService::class)->checkUsableForGuest((int) $app->id, $guestToken);

        return $this->dao->create([
            'uid'             => 0,
            'guest_token'     => $guestToken,
            'session_type'    => AiConversationEnum::SESSION_TYPE_GUEST,
            'app_id'          => $app->id,
            'title'           => $title ?: (string) $app->name,
            'status'          => AiConversationEnum::STATUS_ENABLED,
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 更新会话标题、状态或置顶时间。
     *
     * @param int $id 会话 ID
     * @param array<string, mixed> $data 含 title、status、top 等字段
     *
     * @throws \Throwable 会话不存在或无权限
     */
    public function updateConversation(int $id, array $data): void
    {
        $conversation = $this->checkConversation($id);
        $save         = [];
        if (($data['title'] ?? '') !== '') {
            $save['title'] = (string) $data['title'];
        }
        if (isset($data['status']) && $data['status'] !== '') {
            $save['status'] = (int) $data['status'];
        }
        if (isset($data['top']) && $data['top'] !== '') {
            $save['top_at'] = (int) $data['top'] ? date('Y-m-d H:i:s') : null;
        }
        if ($save) {
            $this->dao->update($conversation->id, $save);
        }
    }

    /**
     * 删除会话及其全部消息记录。
     *
     * @param int $id 会话 ID
     *
     * @throws \Throwable 会话不存在或无权限
     */
    public function deleteConversation(int $id): void
    {
        $conversation = $this->checkConversation($id);
        $this->transaction(function () use ($conversation) {
            app(AiConversationMessageService::class)->clear($conversation);
            $this->dao->delete($conversation->id);
        });
    }

    /**
     * 获取会话消息列表（拆分为 user/assistant 两条）。
     *
     * @param int $id 会话 ID
     *
     * @return array<int, array<string, mixed>> 消息列表
     *
     * @throws \Throwable 会话不存在或无权限
     */
    public function records(int $id): array
    {
        $conversation = $this->checkConversation($id);

        return app(AiConversationMessageService::class)->records($conversation);
    }

    /**
     * 清空会话全部消息记录。
     *
     * @param int $id 会话 ID
     *
     * @throws \Throwable 会话不存在或无权限
     */
    public function clearRecords(int $id): void
    {
        $conversation = $this->checkConversation($id);
        app(AiConversationMessageService::class)->clear($conversation);
    }

    /**
     * 按 message_uuid 分页查询 SQL 工具生成的表格数据。
     *
     * @param string $uuid 消息 UUID
     *
     * @return array<string, mixed> 含 list、count 与 table_fields 的分页数据
     *
     * @throws \Throwable 记录不存在或 SQL 已失效
     */
    public function getRecord(string $uuid): array
    {
        return app(AiConversationMessageService::class)->getRecord($uuid, (int) auth('admin')->id());
    }

    /**
     * 执行一轮流式对话：RAG/MCP/SQL 准备 → Agent 工具循环 → 落库消息。
     *
     * @param int $conversationId 会话 ID
     * @param string $question 用户问题
     * @param bool $saveUserMessage 是否计入日限额（重新生成时为 false）
     * @param int $historyBeforeId 重新生成时截断历史的消息 ID
     * @param null|string $guestToken 访客 token，非空时走访客校验与沙箱
     *
     * @return array<string, mixed> 助手消息 payload
     *
     * @throws \Throwable 模型不可用、配额超限或流式失败
     */
    public function stream(int $conversationId, string $question, bool $saveUserMessage = true, int $historyBeforeId = 0, ?string $guestToken = null): array
    {
        $start = microtime(true);
        // T-3：权限 / 配额 / 访客 / 证据闸门前置中间件链；stream 本体只保留编排.
        $boot = $this->runStreamBootstrapMiddleware($conversationId, $question, $saveUserMessage, $historyBeforeId, $guestToken);
        $conversation  = $boot->conversation;
        $isGuest       = $boot->isGuest;
        $app           = $boot->app;
        $model         = $boot->model;
        $agentConfig   = $boot->agentConfig;
        $quotaAcquired = $boot->quotaAcquired;
        $streamService = $boot->streamService;
        $toolService   = $boot->toolService;
        $agentRuntime  = app(AgentRuntime::class);

        // 重新生成时只取原问题之前的上下文，避免把待替换回答再次喂给模型.
        $messageService = app(AiConversationMessageService::class);
        $replaceRecord  = $messageService->replacement($conversation, $historyBeforeId);
        $messageUuid    = $replaceRecord ? (string) $replaceRecord->chat_record_uuid : (string) Str::uuid();
        $history        = $messageService->history((int) $conversation->id, (int) $app->count_number, (int) ($replaceRecord->id ?? 0));
        $routingQuestion = self::routingQuestion($question, $history);
        $streamService->resetAnnouncedToolCalls();
        $streamService->message($messageUuid);

        $option         = app(AiModelService::class)->makeChatOption($model);
        $option->stream = true;

        try {
            $prepared = app(AgentRuntime::class)->prepareStream(
                $app,
                $conversation,
                $routingQuestion,
                $messageUuid,
                $streamService,
                $agentConfig,
                $isGuest,
                $toolService
            );
            $rag            = $prepared['rag'];
            $toolService    = $prepared['toolService'];
            $externalTools  = $prepared['externalTools'];
            $decision       = $prepared['decision'];
            $agentBootstrap = $prepared['bootstrap'];
            $memoryPrompt   = $prepared['memoryPrompt'];
            $skillPrompt    = $prepared['skillPrompt'];
            $availableTools = $prepared['availableTools'];
            $streamService->setRequestMeta([
                // thin-react：有 MCP 时暂扣工具轮旁白，避免与状态事件抢屏.
                'hold_write_answer' => (bool) ($decision['mcp'] ?? false),
            ]);
            $this->applyModelToolRoundCeiling($agentBootstrap['config'], $model);
            $agentBootstrap['config']->applyToolRoundLimit((int) ($decision['max_tool_rounds'] ?? 0));
            $hasRag       = ! empty($rag['references']);
            $thinkingMode = $this->streamThinkingMode(
                $app,
                $model,
                $decision,
                $hasRag
            );
            app(AiModelService::class)->applyThinkingOption($option, $model, $thinkingMode);
            $streamService->setRequestMeta(['thinking_enabled' => $thinkingMode !== AiModelEnum::THINKING_OFF]);
            $sqlPrompt    = '';
            $sqlToolCalls = [];
            $sqlService   = app(AiSqlQueryService::class);
            // SQL 仅走编排计划 + query_business_data.
            if ($decision['sql']) {
                $streamService->status('sql_prepare', '正在准备数据源说明');
                $sqlPrompt = $streamService->runWithHeartbeat(
                    fn (): string => $sqlService->context($app, $question)
                );
            }
            if ($decision['mcp'] || $decision['sql']) {
                // 工具发现已在 Runtime 推 mcp_prepare；此处仅绑定/注入 tools，避免同名双推.
                $streamService->status('tools_prepare', $decision['sql'] && ! $decision['mcp']
                    ? '正在加载数据库查询能力'
                    : '正在加载工具能力');
                $streamService->runWithHeartbeat(
                    function () use ($toolService, $option, $app, $externalTools, $decision): void {
                        $toolService->prepare($option, $app, $externalTools, $decision);
                    }
                );
            }
            $toolContext = $toolService->context($app, $question, $hasRag, $decision['sql'], $decision['mcp']);
            if ($memoryPrompt !== '') {
                $streamService->status('agent_memory', '已加载会话与长期记忆');
            }
            $streamService->status('ready', '准备完成，开始生成');
            $streamService->ping();

            app(ChatMessageBuilderService::class)->build(
                $option,
                $app,
                $history,
                $question,
                (string) $rag['prompt'],
                trim($sqlPrompt . ($toolContext ? "\n\n" . $toolContext : '')),
                $hasRag,
                $decision,
                $agentBootstrap['intent'],
                $memoryPrompt,
                $skillPrompt,
                $availableTools,
                app(AiModelService::class)->advancedSettings($model)
            );
            // RAG 检索图片随本轮用户消息注入多模态模型，让模型读图后再作答；
            // 工具循环与续跑复用该 option，注入只需在首轮完成.
            app(ChatMessageBuilderService::class)->applyRagImages(
                $option,
                (array) ($rag['images'] ?? []),
                app(AiModelService::class)->supportsVision($model)
            );

            $loopResult = app(AgentRuntime::class)->execute(AgentExecutor::makeRequest(
                $app,
                $model,
                $option,
                $toolService,
                $streamService,
                $messageUuid,
                (array) $rag['references'],
                $agentBootstrap['config'],
                $agentBootstrap['intent'],
                $agentBootstrap['run'],
                fn (): bool => $this->interrupted($messageUuid),
                (int) $conversation->id,
                (int) $conversation->uid,
                $routingQuestion,
                $decision
            ));
            $result     = ToolLoopExecutor::resultToArray($loopResult);
            $toolCalls  = array_merge($sqlToolCalls, (array) ($result['tool_calls'] ?? []));
            $agentTrace = (array) ($result['agent_trace'] ?? []);
            // thin_react：RAG 引用由 search_knowledge 累积回流.
            $knowledgeHits = $toolService->drainKnowledgeHits();
            if (! empty($knowledgeHits['references'])) {
                $rag['references'] = array_values(array_merge(
                    (array) ($rag['references'] ?? []),
                    (array) $knowledgeHits['references']
                ));
            }
            if (! empty($knowledgeHits['images'])) {
                $rag['images'] = array_values(array_merge(
                    (array) ($rag['images'] ?? []),
                    (array) $knowledgeHits['images']
                ));
            }

            $assistant = $this->persistAssistantTurn(
                $conversation,
                $app,
                $messageUuid,
                $question,
                (string) $result['answer'],
                (string) $result['raw'],
                (array) $rag['references'],
                $toolCalls,
                (int) ((microtime(true) - $start) * 1000),
                (int) $result['status'],
                $replaceRecord,
                [
                    'prompt_tokens'     => (int) ($result['usage']['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int) ($result['usage']['completion_tokens'] ?? 0),
                    'tokens'            => (int) ($result['usage']['total_tokens'] ?? 0),
                ],
                $agentTrace,
                (array) ($result['suggestions'] ?? []),
                $agentBootstrap['run'] ? (int) $agentBootstrap['run']->id : null,
                (int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_AWAITING_INPUT
                    ? [
                        'id'  => (int) ($result['pending_input_id'] ?? 0),
                        'ids' => (array) ($result['pending_input_ids'] ?? []),
                    ]
                    : null,
                AiConversationMessageService::intentSnapshot(
                    (array) ($agentBootstrap['intent'] ?? []),
                    $decision
                )
            );

            $this->dao->update($conversation->id, [
                'title'           => $conversation->title ?: mb_substr($question, 0, 30),
                'last_message_at' => date('Y-m-d H:i:s'),
            ]);

            if (! empty($rag['references']) && ! $isGuest) {
                app(KnowledgeRagService::class)->saveLog((int) $app->id, (int) $conversation->uid, $messageUuid, $question, (array) $rag['references'], (string) $result['answer']);
            }

            if ((int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_DONE && ! $isGuest) {
                app(MemoryManager::class)->captureAfterTurn(
                    $conversation,
                    (int) $app->id,
                    $messageUuid,
                    $question,
                    (string) $result['answer'],
                    $agentBootstrap['intent'],
                    $agentBootstrap['run'],
                    $agentBootstrap['config'],
                    ! empty($toolCalls),
                    ! empty($result['pending_confirmation'])
                );
            }

            if ((int) ($result['status'] ?? 0) !== AiConversationEnum::MESSAGE_AWAITING_INPUT && ! $isGuest) {
                $agentRuntime->recordRun(
                    (int) $app->id,
                    (int) $conversation->uid,
                    (int) ($result['usage']['total_tokens'] ?? 0),
                    $agentConfig
                );
            }

            return $this->messagePayload($assistant)[1];
        } catch (\Throwable $e) {
            if (isset($agentBootstrap) && $agentBootstrap['run']) {
                app(AgentRuntime::class)->failRun($agentBootstrap['run'], $e->getMessage());
            }
            $messageService->save($conversation, $app, $messageUuid, $question, $e->getMessage(), '', [], [], (int) ((microtime(true) - $start) * 1000), AiConversationEnum::MESSAGE_FAILED, $replaceRecord);
            $this->dao->update($conversation->id, ['last_message_at' => date('Y-m-d H:i:s')]);

            throw $e;
        } finally {
            if ($quotaAcquired) {
                $agentRuntime->releaseConcurrent((int) $app->id, (int) $conversation->uid, $agentConfig);
            }
            $this->endGuestSession($toolService);
        }
    }

    /** 缺资料而未完成的写请求，下一轮补充字段时仍按原任务路由工具。 */
    public static function routingQuestion(string $question, array $history): string
    {
        if (mb_strlen($question) > 120 || preg_match('/取消|不要|停止|撤销/u', $question)) {
            return $question;
        }

        $previousUser = (string) ($history[count($history) - 2]['content'] ?? '');
        $previousAnswer = (string) ($history[count($history) - 1]['content'] ?? '');

        // 系统缺参追问后的补充。
        if (NarrowIntentSignals::isWriteLike($previousUser)
            && preg_match('/不存在|未找到|失败|未成功|未创建|缺少|请.{0,20}(提供|补充)|重试|重新/u', $previousAnswer)) {
            return $previousUser . "\n用户补充：" . $question;
        }

        // 用户明确说「没成功/重新生成/再创建」：回溯最近一次写意图原题，避免口语重试丢失业务对象.
        if (self::isWriteRetryUtterance($question)) {
            $priorWrite = self::latestWriteQuestionFromHistory($history);
            if ($priorWrite !== '') {
                return $priorWrite . "\n用户补充：" . $question;
            }
        }

        // thin-react：指代续问交由模型结合 history 理解，避免本地语义拼接误导.
        return $question;
    }

    /** 是否像写操作重试口语（含裸「重新生成」，供历史回溯，不单独等于写意图）. */
    private static function isWriteRetryUtterance(string $question): bool
    {
        return NarrowIntentSignals::isWriteRetry($question)
            || (bool) preg_match('/^(重新生成|再生成|再次生成|再创建|重新创建|再提交|重试一下|再试一次|再办一次)[\s!！。.?？]*$/u', trim($question));
    }

    /**
     * 从会话历史回溯最近一条写操作意图的用户原题.
     *
     * @param array<int, array<string, mixed>> $history 对话历史
     */
    private static function latestWriteQuestionFromHistory(array $history): string
    {
        for ($i = count($history) - 1; $i >= 0; --$i) {
            $row = $history[$i] ?? null;
            if (! is_array($row)) {
                continue;
            }
            $role = (string) ($row['role'] ?? '');
            if ($role !== '' && $role !== AiConversationEnum::ROLE_USER && $role !== 'user') {
                continue;
            }
            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '' || NarrowIntentSignals::isWriteRetry($content)) {
                continue;
            }
            if (NarrowIntentSignals::isWriteLike($content)) {
                return $content;
            }
        }

        return '';
    }

    /**
     * 访客会话消息列表（校验 guest_token 归属）。
     *
     * @param int $id 会话 ID
     * @param string $guestToken 访客 token
     *
     * @return array<int, array<string, mixed>> 消息列表
     *
     * @throws \Throwable 会话不存在或不属于该访客
     */
    public function guestRecords(int $id, string $guestToken): array
    {
        $conversation = $this->checkGuestConversation($id, $guestToken);

        return app(AiConversationMessageService::class)->records($conversation);
    }

    /**
     * 清空访客会话消息（校验 guest_token 归属）。
     *
     * @param int $id 会话 ID
     * @param string $guestToken 访客 token
     *
     * @throws \Throwable 会话不存在或不属于该访客
     */
    public function guestClearRecords(int $id, string $guestToken): void
    {
        $conversation = $this->checkGuestConversation($id, $guestToken);
        app(AiConversationMessageService::class)->clear($conversation);
    }

    /**
     * 中断访客 SSE 流（校验会话与 message_uuid 归属）。
     *
     * @param int $conversationId 会话 ID
     * @param string $guestToken 访客 token
     * @param string $messageUuid 消息 UUID
     *
     * @return bool 是否成功写入中断标记
     *
     * @throws \Throwable 消息不存在或无权限中断
     */
    public function guestInterrupt(int $conversationId, string $guestToken, string $messageUuid): bool
    {
        $conversation = $this->checkGuestConversation($conversationId, $guestToken);
        if (! app(AiConversationMessageService::class)->exists($conversation, $messageUuid)) {
            throw $this->exception('消息不存在或无权限中断');
        }

        return app(AiStreamService::class)->interrupt($messageUuid);
    }

    /**
     * 访客流式对话前校验：会话归属、应用可用、指纹、内容安全与限流。
     *
     * @param int $conversationId 会话 ID
     * @param string $guestToken 访客 token
     * @param Request $request 当前 HTTP 请求
     *
     * @throws \Throwable 校验失败
     */
    public function assertGuestStreamAllowed(int $conversationId, string $guestToken, Request $request): void
    {
        $conversation = $this->checkGuestConversation($conversationId, $guestToken);
        $app          = app(AiApplicationService::class)->checkUsableForGuest((int) $conversation->app_id, $guestToken);
        app(AiGuestAuthService::class)->assertStreamAllowed($app, $guestToken, $request);
    }

    /**
     * 重新生成最近一条问题的回答。
     *
     * @param int $conversationId 会话 ID
     *
     * @return array<string, mixed> 助手消息 payload
     *
     * @throws \Throwable 无可重新生成的问题或流式失败
     */
    public function regenerate(int $conversationId): array
    {
        $conversation = $this->checkConversation($conversationId);
        $userMessage  = app(AiConversationMessageService::class)->latestQuestion($conversation);
        if (! $userMessage) {
            throw $this->exception('暂无可重新生成的问题');
        }

        return $this->stream((int) $conversation->id, (string) $userMessage->problem_text, false, (int) $userMessage->id);
    }

    /**
     * 中断内部用户 SSE 流式输出。
     *
     * @param int $conversationId 会话 ID
     * @param string $messageUuid 消息 UUID
     *
     * @return bool 是否成功写入中断标记
     *
     * @throws \Throwable 消息不存在或无权限
     */
    public function interrupt(int $conversationId, string $messageUuid): bool
    {
        $conversation = $this->checkConversation($conversationId);
        if (! app(AiConversationMessageService::class)->exists($conversation, $messageUuid)) {
            throw $this->exception('消息不存在或无权限中断');
        }

        return app(AiStreamService::class)->interrupt($messageUuid);
    }

    /**
     * 确认或拒绝 Agent 写操作待办。
     *
     * 批准且写入成功、且存在可恢复循环状态时，自动续跑 ToolLoop（对齐信息确认续跑），
     * 以便「添加客户并创建订单」等多步写在确认第一步后继续后续 create。
     * 可编辑确认表单可通过 $toolArgsOverride 覆盖 pending 参数；缺必填时重新推送表单。
     *
     * @param int $conversationId 会话 ID
     * @param int $pendingId 待确认记录 ID
     * @param bool $approved 是否批准执行
     * @param array<string, mixed> $toolArgsOverride 表单提交的参数覆盖；空数组表示沿用 pending
     *
     * @return array<string, mixed> 确认结果；续跑时含 streamed=true（SSE 已由本方法推送）
     *
     * @throws \Throwable 会话或待办不存在
     */
    public function confirmAgentAction(
        int $conversationId,
        int $pendingId,
        bool $approved,
        array $toolArgsOverride = []
    ): array {
        $start          = microtime(true);
        $conversation   = $this->checkConversation($conversationId);
        $driver         = app(AgentWriteFlowDriver::class);
        $pending        = $driver->find($pendingId);
        $messageService = app(AiConversationMessageService::class);
        $record         = $pending ? $messageService->findByMessageUuid($conversation, (string) $pending->message_uuid) : null;
        if (! $record) {
            throw $this->exception('待确认操作的会话消息不存在');
        }

        if ($approved && $toolArgsOverride !== []) {
            $merged = $driver->mergeConfirmToolArgs(
                (string) ($pending->tool_name ?? ''),
                (array) ($pending->tool_args ?? []),
                $toolArgsOverride
            );
            $missing = $driver->missingWriteFields((string) ($pending->tool_name ?? ''), $merged);
            // 确认提交后仍缺必填：更新 pending 参数并重新推送可编辑表单，不落假成功.
            if ($missing !== []) {
                $driver->updatePendingArgs($pending, $merged);
                $stream = app(AiStreamService::class);
                $stream->message((string) $pending->message_uuid);
                $stream->status('agent_confirm', '请补全必填字段后再确认写入', [
                    'pending_id'   => $pendingId,
                    'approved'     => false,
                    'write_status' => 'incomplete',
                    'missing'      => array_values(array_map(
                        static fn (array $field): string => (string) ($field['field_key'] ?? ''),
                        $missing
                    )),
                ]);
                $stream->confirmRequired([[
                    'pending_id'      => $pendingId,
                    'tool_name'       => (string) ($pending->tool_name ?? ''),
                    'tool_args'       => $merged,
                    'operation_id'    => 'agent_pending_' . $pendingId,
                    'risk_level'      => 'write',
                    'summary'         => '',
                    'run_id'          => (int) ($pending->run_id ?? 0),
                    'message_uuid'    => (string) $pending->message_uuid,
                    'message'         => '请补全必填字段后再确认写入',
                    'operation_label' => WriteFieldLabels::operationLabel((string) ($pending->tool_name ?? '')),
                    'fields'          => [],
                    'form_mode'       => 'editable',
                    'form_fields'     => $driver->buildEditableFormFields((string) ($pending->tool_name ?? ''), $merged),
                ]]);
                $stream->complete([]);

                return [
                    'pending_id'   => $pendingId,
                    'message_uuid' => (string) $pending->message_uuid,
                    'status'       => 'incomplete',
                    'message'      => '请补全必填字段后再确认写入',
                    'streamed'     => true,
                ];
            }
            $driver->updatePendingArgs($pending, $merged);
        }

        $toolArgs = (array) ($pending->tool_args ?? []);
        $result   = $driver->confirm(
            (int) $conversation->id,
            (int) $conversation->uid,
            $pendingId,
            $approved
        );

        $resumable = $approved
            && ! empty($result['resumable'])
            && (string) ($result['status'] ?? '') === AgentEnum::PENDING_STATUS_EXECUTED;

        $writeResult = (array) ($result['result'] ?? []);
        // 确认后仍缺自选审批人/抄送人：重开 pending 并弹人员选择，禁止当业务错误重开确认卡死循环.
        if ($approved
            && (string) ($result['status'] ?? '') === AgentEnum::PENDING_STATUS_FAILED
            && (! empty($writeResult['needs_selection']) || ! empty($writeResult['selection_nodes']))
        ) {
            return $this->resumeWriteConfirmNeedsSelection(
                $conversation,
                $record,
                $pending,
                $pendingId,
                $result,
                $writeResult,
                $toolArgs,
                $messageService
            );
        }

        $retryable = $approved
            && (string) ($result['status'] ?? '') === AgentEnum::PENDING_STATUS_FAILED
            && AgentWriteFlowDriver::isRetryableWriteFailure($writeResult);

        // MCP 业务错误（如库存不足）：提示真实原因并重新打开可编辑确认表单，避免会话直接失败.
        if ($retryable) {
            $pending->refresh();
            $driver->reopenPendingAfterBusinessFailure($pending, $writeResult);
            $toolArgs = (array) ($pending->tool_args ?? $toolArgs);
            $toolName = (string) ($result['tool_name'] ?? $pending->tool_name ?? '');
            $failMsg  = AgentWriteFlowDriver::writeFailureMessage($writeResult, $toolName);

            $result['answer'] = $messageService->saveWriteRetryPrompt($record, $result, $toolArgs);
            $result['status'] = 'retryable';
            $result['message'] = $failMsg;

            $stream = app(AiStreamService::class);
            $stream->message((string) ($result['message_uuid'] ?? $pending->message_uuid));
            $stream->status('agent_confirm', $failMsg, [
                'pending_id'   => $pendingId,
                'approved'     => true,
                'write_status' => 'retryable',
                'resumable'    => false,
            ]);
            $stream->resetUserContent();
            $stream->emitAnswer((string) $result['answer']);
            $retryFormFields = $driver->buildEditableFormFields($toolName, $toolArgs);
            $stream->confirmRequired([[
                'pending_id'      => $pendingId,
                'tool_name'       => $toolName,
                'tool_args'       => $toolArgs,
                'operation_id'    => 'agent_pending_' . $pendingId,
                'risk_level'      => 'write',
                'summary'         => '',
                'run_id'          => (int) ($pending->run_id ?? 0),
                'message_uuid'    => (string) $pending->message_uuid,
                'message'         => '写入失败：' . $failMsg . ' 请修改后重新确认。',
                'operation_label' => WriteFieldLabels::operationLabel($toolName),
                'fields'          => app(AgentExecutor::class)->buildConfirmFields($toolName, $toolArgs),
                'form_mode'       => 'editable',
                'form_fields'     => $retryFormFields,
            ]]);
            $this->dao->update((int) $conversation->id, ['last_message_at' => date('Y-m-d H:i:s')]);
            $stream->complete([]);
            $result['streamed'] = true;

            return $result;
        }

        $result['answer'] = $messageService->saveConfirmedAction(
            $record,
            $result,
            $approved,
            $toolArgs,
            $resumable
        );

        $stream = app(AiStreamService::class);
        $stream->message((string) ($result['message_uuid'] ?? $pending->message_uuid));
        $failedStatus = (string) ($result['status'] ?? '') === AgentEnum::PENDING_STATUS_FAILED;
        $statusText   = (string) ($result['message'] ?? '');
        if ($statusText === '') {
            $statusText = match ($result['status'] ?? '') {
                AgentEnum::PENDING_STATUS_EXECUTED => $resumable ? '写入成功，继续处理后续步骤' : '写入成功',
                AgentEnum::PENDING_STATUS_FAILED   => AgentWriteFlowDriver::writeFailureMessage($writeResult, (string) ($result['tool_name'] ?? '')),
                default                            => '已取消写入',
            };
        }
        $stream->status('agent_confirm', $statusText, [
            'pending_id'   => (int) ($result['pending_id'] ?? 0),
            'approved'     => $approved,
            'write_status' => (string) ($result['status'] ?? ''),
            'resumable'    => $resumable,
        ]);

        if ($resumable) {
            return $this->continueAfterWriteConfirm(
                $conversation,
                $record,
                $pendingId,
                (string) ($result['tool_name'] ?? ''),
                (string) ($result['tool_call_id'] ?? ''),
                (array) ($result['result'] ?? []),
                $start,
                $result
            );
        }

        app(MemoryManager::class)->syncAfterWriteConfirmed(
            $conversation,
            (int) $conversation->app_id,
            (string) $pending->message_uuid,
            (string) $record->problem_text,
            (string) $result['answer'],
            (int) $pending->run_id
        );
        $this->dao->update((int) $conversation->id, ['last_message_at' => date('Y-m-d H:i:s')]);

        // 单步写确认直接收尾：与 ToolLoopExecutor 正常收尾对齐，成功终态补推推荐问题并落库.
        // 硬失败时正文用详细错误，避免前端只看到泛化 status.
        $stream->emitAnswer((string) $result['answer']);
        if (! $failedStatus) {
            $suggestions = $this->emitConfirmSuggestions($conversation, $record, $result, $approved, $stream);
            if ($suggestions !== []) {
                $messageService->appendSuggestions($record, $suggestions);
            }
        } else {
            $stream->complete([]);
        }
        $result['streamed'] = true;

        return $result;
    }

    /**
     * 补选审批人后：若 flow 挂有 write_pending_id，注入 process 并重新推送写确认卡.
     *
     * @param array<string, mixed> $loopState
     *
     * @return null|array<string, mixed>
     */
    private function reconfirmWritePendingAfterProcessSelection(
        AiConversation $conversation,
        array $loopState,
        string $fieldKey,
        AiStreamService $stream,
        ?\App\Http\Model\Ai\AgentPendingInput $sourcePendingInput = null
    ): ?array {
        if (! str_starts_with($fieldKey, 'process_users_')) {
            return null;
        }
        $flow           = (array) ($loopState['flow_context'] ?? []);
        $writePendingId = $this->resolveWritePendingIdFromSelectionState($flow, $sourcePendingInput);
        if ($writePendingId <= 0) {
            return null;
        }
        $flow['write_pending_id'] = $writePendingId;

        $driver  = app(AgentWriteFlowDriver::class);
        $pending = $driver->find($writePendingId);
        if (! $pending || (string) $pending->status !== AgentEnum::PENDING_STATUS_PENDING) {
            return null;
        }
        if ((int) ($pending->run_id ?? 0) > 0) {
            $run = app(AgentRunDao::class)->get((int) $pending->run_id);
            if (! $run || (int) $run->conversation_id !== (int) $conversation->id) {
                return null;
            }
        }

        $toolName = (string) ($pending->tool_name ?? '');
        $toolArgs = $driver->enrichApproveCreateArgs((array) ($pending->tool_args ?? []), $flow);
        unset($toolArgs['_awaiting_process_selection']);
        $driver->updatePendingArgs($pending, $toolArgs);

        $stream->status('agent_confirm', '已选择审批人员，请再次确认写入', [
            'pending_id'   => $writePendingId,
            'approved'     => false,
            'write_status' => 'pending',
            'resumable'    => false,
        ]);
        $stream->confirmRequired([[
            'pending_id'      => $writePendingId,
            'tool_name'       => $toolName,
            'tool_args'       => $toolArgs,
            'operation_id'    => 'agent_pending_' . $writePendingId,
            'risk_level'      => 'write',
            'summary'         => '',
            'run_id'          => (int) ($pending->run_id ?? 0),
            'message_uuid'    => (string) $pending->message_uuid,
            'message'         => '已选择审批/抄送人，请确认后提交。',
            'operation_label' => WriteFieldLabels::operationLabel($toolName),
            'fields'          => app(AgentExecutor::class)->buildConfirmFields($toolName, $toolArgs),
            'form_mode'       => 'editable',
            'form_fields'     => $driver->buildEditableFormFields($toolName, $toolArgs),
        ]]);

        return [
            'pending_id'   => $writePendingId,
            'message_uuid' => (string) $pending->message_uuid,
            'status'       => AgentEnum::PENDING_STATUS_PENDING,
            'message'      => '已选择审批人员，请再次确认写入',
            'tool_name'    => $toolName,
        ];
    }

    /**
     * 从 flow / pending_input.meta / pending_action.tool_args 解析 write_pending_id.
     *
     * @param array<string, mixed> $flow
     */
    private function resolveWritePendingIdFromSelectionState(
        array $flow,
        ?\App\Http\Model\Ai\AgentPendingInput $sourcePendingInput = null
    ): int {
        $writePendingId = (int) ($flow['write_pending_id'] ?? 0);
        if ($writePendingId > 0) {
            return $writePendingId;
        }
        if ($sourcePendingInput === null) {
            return 0;
        }
        $meta = (array) ($sourcePendingInput->meta ?? []);
        $writePendingId = (int) ($meta['write_pending_id'] ?? 0);
        if ($writePendingId > 0) {
            return $writePendingId;
        }
        // cache 丢失时：同 message 下从 pending_action.tool_args._write_flow 回灌.
        $messageUuid = (string) ($sourcePendingInput->message_uuid ?? '');
        if ($messageUuid === '') {
            return 0;
        }
        foreach (app(AgentWriteFlowDriver::class)->listPending($messageUuid) as $action) {
            $args = (array) ($action->tool_args ?? []);
            $id   = (int) ($args['_write_flow']['write_pending_id'] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    /**
     * 写确认后因自选审批人/抄送人失败：重开 pending 并弹人员选择，避免确认卡死循环.
     *
     * @param array<string, mixed> $result
     * @param array<string, mixed> $writeResult
     * @param array<string, mixed> $toolArgs
     *
     * @return array<string, mixed>
     */
    private function resumeWriteConfirmNeedsSelection(
        AiConversation $conversation,
        AiConversationMessage $record,
        \App\Http\Model\Ai\AgentPendingAction $pending,
        int $pendingId,
        array $result,
        array $writeResult,
        array $toolArgs,
        AiConversationMessageService $messageService
    ): array {
        $driver   = app(AgentWriteFlowDriver::class);
        $helper   = app(AgentInputRequiredHelper::class);
        $toolName = (string) ($result['tool_name'] ?? $pending->tool_name ?? '');
        $pending->refresh();
        $driver->reopenPendingAfterBusinessFailure($pending, $writeResult);

        // write_pending_id 落 DB（tool_args），不单靠 loop cache.
        $persistedArgs = (array) ($pending->tool_args ?? $toolArgs);
        $persistedArgs['_write_flow'] = array_merge(
            is_array($persistedArgs['_write_flow'] ?? null) ? $persistedArgs['_write_flow'] : [],
            ['write_pending_id' => $pendingId]
        );
        $driver->updatePendingArgs($pending, $persistedArgs);

        $loopState = AgentInputRequiredHelper::loadLoopStateCache($pendingId) ?? [];
        $flow      = is_array($loopState['flow_context'] ?? null) ? $loopState['flow_context'] : [];
        unset($flow['process']);
        $flow['write_pending_id'] = $pendingId;

        $stream = app(AiStreamService::class);
        $stream->message((string) ($result['message_uuid'] ?? $pending->message_uuid));
        $failMsg = AgentWriteFlowDriver::writeFailureMessage($writeResult, $toolName);
        $stream->status('thinking', $failMsg);

        $run = app(AgentRunDao::class)->get((int) ($pending->run_id ?? 0));
        /** @var AiApplication $app */
        $app = app(AiApplicationService::class)->checkUsable((int) $conversation->app_id, (int) $conversation->uid);
        /** @var AiModel $model */
        $model          = $app->model;
        $option         = app(AiModelService::class)->makeChatOption($model);
        $option->stream = true;
        if ($loopState !== []) {
            $helper->restoreOption($loopState, $option);
        }
        $request = [
            'conversation_id' => (int) $conversation->id,
            'app'             => $app,
            'model'           => $model,
            'uid'             => (int) $conversation->uid,
            'run'             => $run,
            'message_uuid'    => (string) $pending->message_uuid,
            'question'        => (string) ($record->problem_text ?? ''),
            'option'          => $option,
            'references'      => (array) ($loopState['references'] ?? []),
            'decision'        => (array) ($loopState['decision'] ?? []),
            'flow_context'    => $flow,
            'stream_service'  => $stream,
            'next_round'      => (int) ($loopState['next_round'] ?? 0),
            'max_rounds'      => (int) ($loopState['max_rounds'] ?? 5),
            'final'           => [
                'answer' => '',
                'raw'    => (string) ($loopState['raw'] ?? ''),
                'usage'  => (array) ($loopState['usage'] ?? []),
            ],
            'trace'           => (array) ($loopState['trace'] ?? []),
            'tool_results'    => array_merge((array) ($loopState['tool_results'] ?? []), [[
                'name'   => $toolName,
                'result' => $writeResult,
            ]]),
            'resume'          => null,
        ];

        $pendingInput = $helper->emitProcessSelectionFromWriteResult($toolName, $writeResult, $flow, $request);

        // 同步 loop state（含 write_pending_id），供选人后续跑注入 process；并回写 pending_input.meta.
        if (($loopState['message_uuid'] ?? '') === '') {
            $loopState = AgentInputRequiredHelper::normalizeLoopState([
                'conversation_id' => (int) $conversation->id,
                'app_id'          => (int) $app->id,
                'model_id'        => (int) $model->id,
                'uid'             => (int) $conversation->uid,
                'run_id'          => (int) ($run?->id ?? 0),
                'message_uuid'    => (string) $pending->message_uuid,
                'question'        => (string) ($record->problem_text ?? ''),
                'messages'        => (array) ($option->messages ?? []),
                'tools'           => (array) ($option->tools ?? []),
                'next_round'      => (int) ($request['next_round'] ?? 0),
                'references'      => (array) ($request['references'] ?? []),
                'decision'        => (array) ($request['decision'] ?? []),
                'flow_context'    => $flow,
                'tool_results'    => (array) ($request['tool_results'] ?? []),
                'raw'             => (string) ($loopState['raw'] ?? ''),
                'usage'           => (array) ($loopState['usage'] ?? []),
                'trace'           => (array) ($loopState['trace'] ?? []),
                'max_rounds'      => (int) ($request['max_rounds'] ?? 5),
            ]);
        } else {
            $loopState['flow_context'] = $flow;
        }
        AgentInputRequiredHelper::saveLoopStateCache($pendingId, $loopState);
        if ($pendingInput !== null && (int) ($pendingInput->id ?? 0) > 0) {
            AgentInputRequiredHelper::saveLoopStateCache((int) $pendingInput->id, $loopState);
            $helper->persistWritePendingIdOnInput((int) $pendingInput->id, $pendingId);
        }

        $answer = $failMsg . ($pendingInput !== null ? ' 请选择审批/抄送人后继续。' : ' 请补充审批人员后重试。');
        $result['answer']  = $messageService->saveWriteRetryPrompt($record, array_merge($result, ['message' => $answer]), $toolArgs);
        $result['status']  = 'needs_selection';
        $result['message'] = $answer;
        $stream->resetUserContent();
        $stream->emitAnswer((string) $result['answer']);
        $this->dao->update((int) $conversation->id, ['last_message_at' => date('Y-m-d H:i:s')]);
        $stream->complete([]);
        $result['streamed'] = true;
        $result['pending_input_id'] = (int) ($pendingInput->id ?? 0);

        return $result;
    }

    /**
     * 单步写确认直接收尾时生成并推送推荐问题，与 ToolLoopExecutor 正常收尾对齐.
     *
     * 仅在批准且写入成功的终态推送；生成或推送失败不影响确认收尾。
     *
     * @param AiConversation $conversation 会话模型
     * @param AiConversationMessage $record 消息记录
     * @param array<string, mixed> $result confirm() 返回结果（含 answer/result）
     * @param bool $approved 是否批准写入
     * @param AiStreamService $stream 流式服务
     *
     * @return array<int, string> 实际推送的推荐问题；未推送时为空数组
     */
    private function emitConfirmSuggestions(
        AiConversation $conversation,
        AiConversationMessage $record,
        array $result,
        bool $approved,
        AiStreamService $stream
    ): array {
        if (! $approved
            || ! AgentWriteFlowDriver::writeSucceeded((array) ($result['result'] ?? []))
            || trim((string) ($result['answer'] ?? '')) === ''
            || trim((string) $record->problem_text) === ''
        ) {
            $stream->complete([]);

            return [];
        }

        try {
            /** @var AiApplication $app */
            $app = app(AiApplicationService::class)->checkUsable((int) $conversation->app_id, (int) $conversation->uid);
            /** @var AiModel $model */
            $model = $app->model;

            // 结束帧先行：回答先收尾，推荐问题生成完再补推
            return $stream->completeThenSuggestions(
                fn (): array => $stream->runWithHeartbeat(
                    fn (): array => app(AiFollowUpSuggestionService::class)->suggest(
                        $app,
                        $model,
                        (string) $record->problem_text,
                        (string) ($result['answer'] ?? '')
                    )
                )
            );
        } catch (\Throwable $e) {
            Log::channel((string) config('ai.http_log.channel', 'ai'))->warning('AI写确认推荐问题推送失败', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 写确认成功后续跑 ToolLoop：回填真实写入结果并继续未完成步骤.
     *
     * @param array<string, mixed> $confirmResult confirm() 原始返回
     * @param array<string, mixed> $writeResult 真实工具执行结果
     *
     * @return array<string, mixed>
     */
    private function continueAfterWriteConfirm(
        AiConversation $conversation,
        AiConversationMessage $replaceRecord,
        int $pendingId,
        string $toolName,
        string $toolCallId,
        array $writeResult,
        float $start,
        array $confirmResult
    ): array {
        $inputHelper = app(AgentInputRequiredHelper::class);
        $loopState   = $inputHelper->applyWriteConfirmResult($pendingId, $toolName, $toolCallId, $writeResult);
        if ($loopState === null) {
            // 缓存竞态丢失时退回单步收尾，避免卡在 EXECUTING。
            $pending = app(AgentWriteFlowDriver::class)->find($pendingId);
            $answer  = app(AiConversationMessageService::class)->saveConfirmedAction(
                $replaceRecord,
                $confirmResult,
                true,
                (array) ($pending?->tool_args ?? []),
                false
            );
            $confirmResult['answer'] = $answer;
            if ($pending) {
                app(AgentRunDao::class)->update((int) $pending->run_id, ['status' => AgentEnum::RUN_STATUS_DONE]);
                app(MemoryManager::class)->syncAfterWriteConfirmed(
                    $conversation,
                    (int) $conversation->app_id,
                    (string) $pending->message_uuid,
                    (string) $replaceRecord->problem_text,
                    $answer,
                    (int) $pending->run_id
                );
            }
            $stream = app(AiStreamService::class);
            // 缓存竞态退回单步收尾同样属成功终态：补推推荐问题并落库.
            $stream->emitAnswer($answer);
            $suggestions = $this->emitConfirmSuggestions($conversation, $replaceRecord, $confirmResult, true, $stream);
            if ($suggestions !== []) {
                app(AiConversationMessageService::class)->appendSuggestions($replaceRecord, $suggestions);
            }
            $confirmResult['streamed']  = true;
            $confirmResult['resumable'] = false;

            return $confirmResult;
        }

        $runDao = app(AgentRunDao::class);
        $run    = $runDao->get((int) $loopState['run_id']);
        if (! $run || (int) $run->conversation_id !== (int) $conversation->id) {
            throw $this->exception('无权限续跑该写操作');
        }
        $runDao->update((int) $run->id, ['status' => AgentEnum::RUN_STATUS_EXECUTING]);

        $messageUuid   = (string) $loopState['message_uuid'];
        $priorAnswer   = (string) ($confirmResult['answer'] ?? '');
        $agentRuntime  = app(AgentRuntime::class);
        $agentConfig   = null;
        $quotaAcquired = false;
        $resumeFrame   = '';
        $streamService = app(AiStreamService::class);
        $priorDetails  = (string) ($replaceRecord->details ?? '');

        try {
            /** @var AiApplication $app */
            $app = app(AiApplicationService::class)->checkUsable((int) $conversation->app_id, (int) $conversation->uid);
            /** @var AiModel $model */
            $model = $app->model;
            $this->assertModelUsable($model);

            if ($user = auth('admin')->user()) {
                app(McpUserContextResolver::class)->merge(request(), $user);
            }

            $agentConfig = $agentRuntime->config($app);
            $this->applyModelToolRoundCeiling($agentConfig, $model);
            $quotaAcquired = $agentRuntime->assertAllowed(
                (int) $app->id,
                (int) $conversation->uid,
                $agentConfig
            );

            $streamService->resetAnnouncedToolCalls();
            $streamService->message($messageUuid);
            $resumeMessage = '写操作已确认，继续执行后续步骤';
            $resumeExtra   = [
                'pending_id' => $pendingId,
                'tool_name'  => $toolName,
            ];
            $streamService->status('agent_resume', $resumeMessage, $resumeExtra);
            $resumeFrame = $inputHelper->formatResumeThinkingFrame($resumeMessage, $resumeExtra);

            $option         = app(AiModelService::class)->makeChatOption($model);
            $option->stream = true;
            $inputHelper->restoreOption($loopState, $option);

            $toolService = app(AiToolCallService::class);
            $decision    = $loopState['decision'];
            $decision['write_requested'] = ! empty($loopState['flow_context']['target_write_tool'])
                || ! empty($decision['write_requested']);
            $loopState['decision'] = $decision;
            $thinkingMode          = $this->streamThinkingMode(
                $app,
                $model,
                $decision,
                ! empty($loopState['references'])
            );
            app(AiModelService::class)->applyThinkingOption($option, $model, $thinkingMode);
            $streamService->setRequestMeta([
                'thinking_enabled'  => $thinkingMode !== AiModelEnum::THINKING_OFF,
                'hold_write_answer' => $decision['write_requested'],
            ]);
            if ($decision['mcp'] || $decision['sql']) {
                $allowlist     = McpToolAllowlist::discoveryNames(
                    (array) ($decision['mcp_allowlist'] ?? [])
                );
                $externalTools = $decision['mcp']
                    ? app(AiMcpService::class)->toolsForApp($app, $allowlist)
                    : [];
                $toolService->prepare($option, $app, $externalTools, $decision);
            }

            $writeContext = $loopState['write_confirmation']
                ? [
                    'runId'              => (int) $run->id,
                    'messageUuid'        => $messageUuid,
                    'writeConfirmation'  => true,
                    'approvedPendingIds' => [$pendingId],
                    'question'           => (string) ($loopState['question'] ?? ''),
                    'conversation_id'    => (int) $conversation->id,
                    'uid'                => (int) $conversation->uid,
                ]
                : null;

            $baseRequest = ToolLoopExecutor::makeRequest(
                $app,
                $model,
                $option,
                $toolService,
                $streamService,
                $messageUuid,
                $loopState['references'],
                $loopState['max_rounds'],
                fn (): bool => $this->interrupted($messageUuid),
                $writeContext,
                (array) $loopState['flow_context'],
                null,
                $run,
                (int) $conversation->id,
                (int) $conversation->uid,
                $loopState['question'],
                $decision,
                $loopState['write_confirmation']
            );

            $loopState['messages'] = $option->messages;
            $inputHelper->restoreOption($loopState, $option);

            $resumeRequest = $inputHelper->buildResumeRequest($loopState, $run, $baseRequest);
            $loopResult    = app(AgentExecutor::class)->resume($resumeRequest, $agentConfig, $run);
            $result        = ToolLoopExecutor::resultToArray($loopResult);
            AgentInputRequiredHelper::forgetLoopStateCache($pendingId);

            $stillWaiting = ! empty($result['pending_confirmation'])
                || (int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_AWAITING_INPUT;
            $answer       = $this->composeWriteConfirmResumeAnswer(
                $priorAnswer,
                (string) ($result['answer'] ?? ''),
                $stillWaiting
            );

            $assistant = $this->persistAssistantTurn(
                $conversation,
                $app,
                $messageUuid,
                (string) $replaceRecord->problem_text,
                $answer,
                $inputHelper->mergeResumeDetails($resumeFrame, (string) ($result['raw'] ?? ''), $priorDetails),
                $loopState['references'],
                (array) ($result['tool_calls'] ?? []),
                (int) ((microtime(true) - $start) * 1000),
                (int) $result['status'],
                $replaceRecord,
                [
                    'prompt_tokens'     => (int) ($result['usage']['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int) ($result['usage']['completion_tokens'] ?? 0),
                    'tokens'            => (int) ($result['usage']['total_tokens'] ?? 0),
                ],
                (array) ($result['agent_trace'] ?? []),
                (array) ($result['suggestions'] ?? []),
                null,
                (int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_AWAITING_INPUT
                    ? [
                        'id'  => (int) ($result['pending_input_id'] ?? 0),
                        'ids' => (array) ($result['pending_input_ids'] ?? []),
                    ]
                    : null,
                $this->intentSnapshotFromRecord($replaceRecord, $decision)
            );

            app(MemoryManager::class)->syncAfterWriteConfirmed(
                $conversation,
                (int) $app->id,
                $messageUuid,
                (string) $replaceRecord->problem_text,
                $answer,
                (int) $run->id
            );

            if ((int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_DONE
                && empty($result['pending_confirmation'])) {
                $agentRuntime->recordRun(
                    (int) $app->id,
                    (int) $conversation->uid,
                    (int) ($result['usage']['total_tokens'] ?? 0),
                    $agentConfig
                );
            }

            $payload                               = $this->messagePayload($assistant)[1];
            $confirmResult['answer']               = $answer;
            $confirmResult['streamed']             = true;
            $confirmResult['resumed']              = true;
            $confirmResult['assistant']            = $payload;
            $confirmResult['pending_confirmation'] = ! empty($result['pending_confirmation']);

            return $confirmResult;
        } catch (\Throwable $e) {
            app(AgentRuntime::class)->failRun($run, $e->getMessage());
            AgentInputRequiredHelper::forgetLoopStateCache($pendingId);
            throw $e;
        } finally {
            if ($quotaAcquired && $agentConfig !== null) {
                $agentRuntime->releaseConcurrent((int) $app->id, (int) $conversation->uid, $agentConfig);
            }
            if (isset($toolService)) {
                $toolService->bindWriteContext(null);
            }
        }
    }

    /**
     * 合并写确认中间成功文案与续跑答复，避免再次 pending 时冲掉「客户已创建」等事实.
     */
    private function composeWriteConfirmResumeAnswer(string $prior, string $resume, bool $stillWaiting): string
    {
        $prior  = trim((string) preg_replace('/，继续处理后续步骤…$/u', '', $prior));
        $resume = trim($resume);
        if ($prior === '') {
            return $resume;
        }
        if (! $stillWaiting) {
            if ($resume === ''
                || str_contains($resume, '等待用户确认')
                || str_contains($resume, '数据尚未写入')) {
                return $prior . '。';
            }
            if (str_contains($resume, $prior)) {
                return $resume;
            }

            return $prior . '。' . $resume;
        }
        if ($resume === '') {
            return $prior . '。下一写操作等待您确认后才会执行。';
        }

        return $prior . '。' . $resume;
    }

    /**
     * 列出指定消息下待用户补充的信息确认项。
     *
     * @param int $conversationId 会话 ID
     * @param string $messageUuid 消息 UUID
     *
     * @return array<int, array<string, mixed>> 待确认项列表
     *
     * @throws \Throwable 会话不存在或无权限
     */
    public function pendingAgentInputs(int $conversationId, string $messageUuid): array
    {
        $conversation = $this->checkConversation($conversationId);

        return app(AgentInputRequiredHelper::class)->listPending($messageUuid, (int) $conversation->uid);
    }

    /**
     * 用户关闭补参卡片：取消该消息全部待补项并收尾该轮.
     *
     * 对齐写确认取消的流式收尾：pending 项标记 cancelled、终止关联 Run，
     * 消息正文改写为取消说明，前端据此落到正常终态（思考停住、无悬挂状态）.
     *
     * @param int $conversationId 会话 ID
     * @param string $messageUuid 消息 UUID
     *
     * @return array<string, mixed> 收尾结果（含 answer 与 streamed 标记）
     *
     * @throws \Throwable 会话或消息不存在
     */
    public function dismissAgentInput(int $conversationId, string $messageUuid): array
    {
        $conversation = $this->checkConversation($conversationId);
        $messageUuid  = trim($messageUuid);
        if ($messageUuid === '') {
            throw $this->exception('缺少待取消的消息ID');
        }

        $inputHelper = app(AgentInputRequiredHelper::class);
        $cancelled   = $inputHelper->cancelPendingByMessage($messageUuid, (int) $conversation->uid);

        $messageService = app(AiConversationMessageService::class);
        $record = $messageService->findByMessageUuid($conversation, $messageUuid);
        if (! $record) {
            throw $this->exception('待取消操作的会话消息不存在');
        }

        // 取消文案带上任务上下文与被取消的字段，避免干巴巴的模板句
        $question    = mb_substr(trim((string) $record->problem_text), 0, 40);
        $fieldLabels = array_values(array_filter(array_map(
            static fn ($pending): string => (string) $pending->field_label,
            $cancelled
        )));
        $answer = $question !== ''
            ? '已取消补充' . ($fieldLabels !== [] ? implode('、', $fieldLabels) : '补充信息')
                . '，「' . $question . '」未完成办理。如需继续，请补充相关信息后重试，或直接告诉我调整后的需求。'
            : '已取消本次补充，当前任务未完成办理。如需继续，请重新描述需求或补全相关信息。';

        // 补参已取消，关联 Run 不再可恢复，统一置为中断态
        $runDao = app(AgentRunDao::class);
        foreach ($cancelled as $pending) {
            if ($run = $runDao->get((int) $pending->run_id)) {
                $runDao->update((int) $run->id, ['status' => AgentEnum::RUN_STATUS_INTERRUPTED]);
            }
        }
        
        $messageService->markInputDismissed($record, $answer);

        $stream = app(AiStreamService::class);
        $stream->message($messageUuid);
        $stream->status('agent_input_dismissed', '已取消补充信息', [
            'message_uuid' => $messageUuid,
            'cancelled'    => count($cancelled),
        ]);
        $stream->emitAnswer($answer);

        // 取消收尾走全局统一入口：结束帧先行，推荐问题生成完再补推；失败不影响收尾
        $suggestions = $stream->completeThenSuggestions(function () use ($conversation, $question, $answer): array {
            /** @var AiApplication $app */
            $app   = app(AiApplicationService::class)->checkUsable((int) $conversation->app_id, (int) $conversation->uid);
            $model = $app->model;

            return app(AiFollowUpSuggestionService::class)->suggest($app, $model, $question, $answer);
        });
        if ($suggestions !== []) {
            $messageService->appendSuggestions($record, $suggestions);
        }

        return [
            'message_uuid' => $messageUuid,
            'cancelled'    => count($cancelled),
            'answer'       => $answer,
            'streamed'     => true,
        ];
    }

    /**
     * 批量提交信息确认并续跑 Agent 工具循环。
     *
     * @param int $conversationId 会话 ID
     * @param array<int, array{prompt_id: int, value: mixed, label?: string}> $inputs 确认项列表
     *
     * @return array<string, mixed> 助手消息 payload
     *
     * @throws \Throwable 确认项无效、无权限或续跑失败
     */
    public function resumeAgentInputs(int $conversationId, array $inputs): array
    {
        if ($inputs === []) {
            throw $this->exception('缺少确认项');
        }

        $promptId = (int) ($inputs[0]['prompt_id'] ?? 0);
        if ($promptId <= 0) {
            throw $this->exception('缺少确认项ID');
        }

        $start        = microtime(true);
        $conversation = $this->checkConversation($conversationId);
        $inputHelper  = app(AgentInputRequiredHelper::class);
        $pending      = $inputHelper->findPending($promptId);
        if (! $pending || (string) $pending->status !== AgentEnum::PENDING_INPUT_STATUS_PENDING) {
            throw $this->exception('待确认信息不存在或已处理');
        }

        $messageUuid    = (string) $pending->message_uuid;
        $messageService = app(AiConversationMessageService::class);
        $replaceRecord  = $messageService->findByMessageUuid($conversation, $messageUuid);
        if (! $replaceRecord) {
            throw $this->exception('消息不存在或无权限访问');
        }

        /** @var AiApplication $app */
        $app = app(AiApplicationService::class)->checkUsable((int) $conversation->app_id, (int) $conversation->uid);
        /** @var AiModel $model */
        $model = $app->model;
        $this->assertModelUsable($model);

        if ($user = auth('admin')->user()) {
            app(McpUserContextResolver::class)->merge(request(), $user);
        }

        $runDao = app(AgentRunDao::class);
        $run    = $runDao->get((int) $pending->run_id);
        if (! $run || (int) $run->conversation_id !== (int) $conversation->id) {
            throw $this->exception('无权限处理该确认项');
        }
        $runDao->update((int) $run->id, ['status' => AgentEnum::RUN_STATUS_EXECUTING]);

        $confirmationItems = [];
        foreach ($inputs as $input) {
            $itemPromptId = (int) ($input['prompt_id'] ?? 0);
            $itemPending  = $itemPromptId === $promptId
                ? $pending
                : $inputHelper->findPending($itemPromptId);
            $confirmationItems[] = [
                'field_label' => (string) ($itemPending?->field_label ?? ''),
                'label'       => (string) ($input['label'] ?? ''),
                'value'       => $input['value'] ?? null,
            ];
        }

        $loopState = $inputHelper->applyBatchUserInput($inputs);

        return $this->continueAgentInputAfterApply(
            $conversation,
            $app,
            $model,
            $run,
            $inputHelper,
            $loopState,
            $messageUuid,
            $replaceRecord,
            $promptId,
            (string) $pending->field_key,
            $start,
            array_map(static fn (array $input): int => (int) ($input['prompt_id'] ?? 0), $inputs),
            $confirmationItems
        );
    }

    /**
     * 单条信息确认后续跑（resumeAgentInputs 的便捷封装）。
     *
     * @param int $conversationId 会话 ID
     * @param int $promptId 待确认项 ID
     * @param mixed $value 用户填写的值
     * @param string $label 展示标签
     *
     * @return array<string, mixed> 助手消息 payload
     *
     * @throws \Throwable 确认项无效或续跑失败
     */
    public function resumeAgentInput(int $conversationId, int $promptId, mixed $value, string $label = ''): array
    {
        return $this->resumeAgentInputs($conversationId, [[
            'prompt_id' => $promptId,
            'value'     => $value,
            'label'     => $label,
        ]]);
    }

    /** 将自然语言映射到当前待补字段，成功后复用既有批量续跑链路。 */
    public function resumeAgentInputText(int $conversationId, string $messageUuid, string $message): array
    {
        $conversation = $this->checkConversation($conversationId);
        $inputHelper  = app(AgentInputRequiredHelper::class);
        $pending      = $inputHelper->listPending($messageUuid, (int) $conversation->uid);
        if ($pending === []) {
            throw $this->exception('当前没有待补充信息');
        }

        $inputs = [];
        foreach ($pending as $item) {
            $fieldMessage = trim((string) ($item['message'] ?? $item['field_label'] ?? ''));
            $fieldLabel    = trim((string) ($item['field_label'] ?? ''));
            $value         = $this->extractPendingTextValue(
                $message,
                $fieldLabel,
                $fieldMessage,
                (array) ($item['options'] ?? []),
                count($pending) === 1
            );
            if ($value === null) {
                continue;
            }
            $inputs[] = [
                'prompt_id' => (int) ($item['prompt_id'] ?? 0),
                'value'     => $value,
                'label'     => is_scalar($value) ? (string) $value : '',
            ];
        }

        if ($inputs === []) {
            throw $this->exception('未识别到待补充字段，请按提示填写');
        }

        return $this->resumeAgentInputs($conversationId, $inputs);
    }

    private function extractPendingTextValue(
        string $message,
        string $fieldLabel,
        string $fieldMessage,
        array $options,
        bool $allowPlainText
    ): mixed
    {
        $text = trim($message);
        if ($text === '') {
            return null;
        }

        $option = $this->matchPendingOption($text, $options);
        if ($option !== null) {
            return $option['value'];
        }
        if ($options !== []) {
            return null;
        }

        foreach (array_filter([$fieldLabel, $fieldMessage]) as $label) {
            $pattern = '/' . preg_quote($label, '/') . '\\s*[:：]\\s*(.+?)(?=\\s+[\\p{Han}A-Za-z0-9_]+\\s*[:：]|$)/u';
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return $allowPlainText ? $text : null;
    }

    /** 仅在候选项唯一匹配时自动回填，避免自然语言补答误选业务对象。 */
    private function matchPendingOption(string $text, array $options): ?array
    {
        $normalized = mb_strtolower(trim($text));
        $matches    = [];
        foreach ($options as $option) {
            if (! is_array($option)) {
                continue;
            }
            $label = mb_strtolower(trim((string) ($option['label'] ?? '')));
            $value = $option['value'] ?? null;
            if ($label === '' || $value === null) {
                continue;
            }
            if ($label === $normalized || str_contains($label, $normalized) || str_contains($normalized, $label)) {
                $matches[] = ['value' => $value, 'label' => $label];
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    /**
     * 列出指定消息下待确认的 Agent 写操作。
     *
     * @param int $conversationId 会话 ID
     * @param string $messageUuid 消息 UUID
     *
     * @return array<int, array<string, mixed>> 待确认写操作列表
     *
     * @throws \Throwable 会话不存在或无权限
     */
    public function pendingAgentActions(int $conversationId, string $messageUuid): array
    {
        $conversation = $this->checkConversation($conversationId);

        return app(AgentWriteFlowDriver::class)->listPendingForUser($messageUuid, (int) $conversation->uid);
    }

    /**
     * 应用用户确认输入后恢复 Agent 工具循环并落库结果。
     *
     * 续跑开始前 Run 已为 EXECUTING，异常必须 failRun；并发槽仅在实际占用时释放。
     * agent_resume 思考帧会合并进 details/raw，供刷新还原确认回显。
     *
     * @param array<int, int> $promptIds 已处理的确认项 ID 列表
     * @param array<int, array{field_label?: string, label?: string, value?: mixed}> $confirmationItems 确认摘要项
     *
     * @return array<string, mixed> 助手消息 payload
     *
     * @throws \Throwable 续跑失败或配额超限
     */
    private function continueAgentInputAfterApply(
        AiConversation $conversation,
        AiApplication $app,
        AiModel $model,
        AgentRun $run,
        AgentInputRequiredHelper $inputHelper,
        array $loopState,
        string $messageUuid,
        AiConversationMessage $replaceRecord,
        int $promptId,
        string $fieldKey,
        float $start,
        array $promptIds = [],
        array $confirmationItems = []
    ): array {
        $agentConfig    = app(AgentRuntime::class)->config($app);
        $this->applyModelToolRoundCeiling($agentConfig, $model);
        $agentRuntime   = app(AgentRuntime::class);
        $quotaAcquired  = false;
        $resumeFrame    = '';
        $streamService  = app(AiStreamService::class);
        $priorDetails   = (string) ($replaceRecord->details ?? '');

        try {
            $quotaAcquired = $agentRuntime->assertAllowed(
                (int) $app->id,
                (int) $conversation->uid,
                $agentConfig
            );

            $streamService->resetAnnouncedToolCalls();
            $streamService->message($messageUuid);
            $resumeMessage = $inputHelper->formatResumeStatusMessage($confirmationItems);
            $resumeExtra   = [
                'prompt_id'  => $promptId,
                'prompt_ids' => $promptIds !== [] ? $promptIds : [$promptId],
                'field_key'  => $fieldKey,
            ];
            $streamService->status('agent_resume', $resumeMessage, $resumeExtra);
            $resumeFrame = $inputHelper->formatResumeThinkingFrame($resumeMessage, $resumeExtra);

            // 写确认后因缺抄送/审批人而补选：把人选注入 pending 并重新弹出确认卡.
            $sourcePendingInput = $inputHelper->findPending($promptId);
            $reconfirm = $this->reconfirmWritePendingAfterProcessSelection(
                $conversation,
                $loopState,
                $fieldKey,
                $streamService,
                $sourcePendingInput
            );
            if ($reconfirm !== null) {
                $this->dao->update((int) $conversation->id, ['last_message_at' => date('Y-m-d H:i:s')]);
                $streamService->complete([]);
                $reconfirm['streamed'] = true;
                $reconfirm['answer']   = (string) ($reconfirm['message'] ?? '');

                return $reconfirm;
            }

            $option         = app(AiModelService::class)->makeChatOption($model);
            $option->stream = true;
            $inputHelper->restoreOption($loopState, $option);
            // 补参续跑强制指令：参数已齐，第一优先级是重新调用目标写工具，禁止再查询核对.
            $inputHelper->appendResumeWriteDirective($loopState, $option, $confirmationItems);

            $toolService = app(AiToolCallService::class);
            $decision    = $loopState['decision'];
            $decision['write_requested'] = ! empty($loopState['flow_context']['target_write_tool'])
                || ! empty($decision['write_requested']);
            $loopState['decision'] = $decision;
            $thinkingMode = $this->streamThinkingMode(
                $app,
                $model,
                $decision,
                ! empty($loopState['references'])
            );
            app(AiModelService::class)->applyThinkingOption($option, $model, $thinkingMode);
            $streamService->setRequestMeta([
                'thinking_enabled' => $thinkingMode !== AiModelEnum::THINKING_OFF,
                'hold_write_answer' => $decision['write_requested'],
            ]);
            if ($decision['mcp'] || $decision['sql']) {
                $allowlist = McpToolAllowlist::discoveryNames(
                    (array) ($decision['mcp_allowlist'] ?? [])
                );
                $externalTools = $decision['mcp']
                    ? app(AiMcpService::class)->toolsForApp($app, $allowlist)
                    : [];
                $toolService->prepare($option, $app, $externalTools, $decision);
            }

            $writeContext = $loopState['write_confirmation']
                ? [
                    'runId'              => (int) $run->id,
                    'messageUuid'        => $messageUuid,
                    'writeConfirmation'  => true,
                    'approvedPendingIds' => [],
                    'question'           => (string) ($loopState['question'] ?? ''),
                    'conversation_id'    => (int) $conversation->id,
                    'uid'                => (int) $conversation->uid,
                ]
                : null;

            $baseRequest = ToolLoopExecutor::makeRequest(
                $app,
                $model,
                $option,
                $toolService,
                $streamService,
                $messageUuid,
                $loopState['references'],
                $loopState['max_rounds'],
                fn (): bool => $this->interrupted($messageUuid),
                $writeContext,
                (array) $loopState['flow_context'],
                null,
                $run,
                (int) $conversation->id,
                (int) $conversation->uid,
                $loopState['question'],
                $decision,
                $loopState['write_confirmation']
            );

            $loopState['messages'] = $option->messages;
            $inputHelper->restoreOption($loopState, $option);

            $resumeRequest = $inputHelper->buildResumeRequest($loopState, $run, $baseRequest);
            $loopResult    = app(AgentExecutor::class)->resume($resumeRequest, $agentConfig, $run);
            $result        = ToolLoopExecutor::resultToArray($loopResult);
            $inputHelper->forgetBatch($promptIds !== [] ? $promptIds : [$promptId]);

                $assistant = $this->persistAssistantTurn(
                $conversation,
                $app,
                $messageUuid,
                (string) $replaceRecord->problem_text,
                (string) $result['answer'],
                $inputHelper->mergeResumeDetails($resumeFrame, (string) ($result['raw'] ?? ''), $priorDetails),
                $loopState['references'],
                (array) ($result['tool_calls'] ?? []),
                (int) ((microtime(true) - $start) * 1000),
                (int) $result['status'],
                $replaceRecord,
                [
                    'prompt_tokens'     => (int) ($result['usage']['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int) ($result['usage']['completion_tokens'] ?? 0),
                    'tokens'            => (int) ($result['usage']['total_tokens'] ?? 0),
                ],
                (array) ($result['agent_trace'] ?? []),
                (array) ($result['suggestions'] ?? []),
                null,
                (int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_AWAITING_INPUT
                    ? [
                        'id'  => (int) ($result['pending_input_id'] ?? 0),
                        'ids' => (array) ($result['pending_input_ids'] ?? []),
                    ]
                    : null,
                $this->intentSnapshotFromRecord($replaceRecord, $decision)
            );

            if ((int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_DONE) {
                $agentRuntime->recordRun(
                    (int) $app->id,
                    (int) $conversation->uid,
                    (int) ($result['usage']['total_tokens'] ?? 0),
                    $agentConfig
                );
            }

            return $this->messagePayload($assistant)[1];
        } catch (\Throwable $e) {
            // Run 在 resumeAgentInputs 中已标为 EXECUTING，失败必须 failRun；并发槽仅在实际占用时释放.
            app(AgentRuntime::class)->failRun($run, $e->getMessage());
            throw $e;
        } finally {
            if ($quotaAcquired) {
                $agentRuntime->releaseConcurrent((int) $app->id, (int) $conversation->uid, $agentConfig);
            }
            if (isset($toolService)) {
                $toolService->bindWriteContext(null);
            }
        }
    }

    /** 从缓存读取流式中断标记。 */
    private function interrupted(string $messageUuid): bool
    {
        try {
            $cache = Cache::tags([AiConversationEnum::CACHE_TAG]);

            return $cache->get($messageUuid) === 'stop' || (bool) $cache->get($messageUuid . ':interrupted');
        } catch (\Throwable) {
            return Cache::get($messageUuid) === 'stop' || (bool) Cache::get($messageUuid . ':interrupted');
        }
    }

    /**
     * 解析本轮最终思考模式（0 关闭 / 1 开启 / 2 跟随默认）.
     *
     * 应用关闭优先；否则采用模型高级配置；普通闲聊可自动关闭以降低延迟.
     *
     * @param AiApplication $app 当前应用
     * @param AiModel|\Illuminate\Database\Eloquent\Model|array<string, mixed> $model 绑定模型
     * @param array<string, mixed> $decision 编排决策
     */
    private function streamThinkingMode(
        AiApplication $app,
        AiModel|array|\Illuminate\Database\Eloquent\Model $model,
        array $decision,
        bool $hasRag
    ): int {
        $appThinking = (int) ($app->enable_thinking ?? AiModelEnum::THINKING_ON);
        $mode        = app(AiModelService::class)->resolveThinkingMode($appThinking, $model);
        if ($mode === AiModelEnum::THINKING_OFF) {
            return AiModelEnum::THINKING_OFF;
        }

        // 应用开启时，保留模型侧「跟随默认 / 强制开启」差异，供 applyThinkingOption 使用.
        $modelMode = app(AiModelService::class)->modelThinkingMode($model);
        if ($modelMode === AiModelEnum::THINKING_DEFAULT) {
            $mode = AiModelEnum::THINKING_DEFAULT;
        }

        if (! (bool) config('ai.stream.auto_disable_thinking', true)) {
            return $mode;
        }

        $answerPolicy = (string) ($decision['answer_policy'] ?? '');
        // 工具 Agent：强制关 thinking。多轮 tool 回传未带 reasoning_content 时，
        // GLM interleaved thinking 会每轮重想，拖垮 SchemaSlotFiller / 写链路。
        if ((bool) ($decision['mcp'] ?? false)
            || (bool) ($decision['sql'] ?? false)
            || (bool) ($decision['thin_react'] ?? false)
            || $answerPolicy === 'tool_first'
        ) {
            return AiModelEnum::THINKING_OFF;
        }

        // 纯闲聊关 thinking（豆包 Seed 成本）；仅无工具的 RAG/hybrid 沿用应用侧开关.
        if ($hasRag || $answerPolicy === 'hybrid') {
            return $mode;
        }

        return AiModelEnum::THINKING_OFF;
    }

    /**
     * 校验会话绑定模型可用（已启用且已配置 API Key）.
     *
     * 缺少 API Key 时一号通模型单独提示去后台配置.
     *
     * @param null|AiModel $model 应用绑定模型
     *
     * @throws \Throwable 模型不可用
     */
    private function assertModelUsable(?AiModel $model): void
    {
        if (! $model || (int) $model->status !== AiModelEnum::STATUS_ENABLED) {
            throw $this->exception('AI模型配置不可用');
        }
        if (! $model->api_key) {
            throw $this->exception(app(AiModelService::class)->missingApiKeyMessage($model, 'AI模型配置不可用'));
        }
    }

    /**
     * 用模型高级配置的工具调用轮数抬高 Agent max_steps 上限.
     *
     * @param AiModel|\Illuminate\Database\Eloquent\Model|array<string, mixed> $model 绑定模型
     */
    private function applyModelToolRoundCeiling(AgentConfig $config, AiModel|array|\Illuminate\Database\Eloquent\Model $model): void
    {
        $rounds = app(AiModelService::class)->advancedSettings($model)[AiModelEnum::ADVANCED_MAX_TOOL_ROUNDS] ?? null;
        if ($rounds === null || $rounds <= 0) {
            return;
        }

        $config->maxSteps = max(1, (int) $rounds);
    }

    /**
     * T-3：流式前置中间件链（会话解析 → MCP 上下文 → 配额 → 访客沙箱 → 证据闸门）.
     */
    private function runStreamBootstrapMiddleware(
        int $conversationId,
        string $question,
        bool $saveUserMessage,
        int $historyBeforeId,
        ?string $guestToken
    ): AgentStreamContext {
        return AgentMiddlewarePipeline::make([
            new ClosureMiddleware(function (AgentStreamContext $ctx, callable $next): AgentStreamContext {
                $guestToken = $ctx->guestToken;
                $conversation = $guestToken !== null
                    ? $this->checkGuestConversation($ctx->conversationId, $guestToken)
                    : $this->checkConversation($ctx->conversationId);
                $ctx->conversation = $conversation;
                $ctx->isGuest      = $guestToken !== null || $conversation->isGuestSession();
                /** @var AiApplication $app */
                $app = $ctx->isGuest
                    ? app(AiApplicationService::class)->checkUsableForGuest(
                        (int) $conversation->app_id,
                        (string) ($guestToken ?: $conversation->guest_token)
                    )
                    : app(AiApplicationService::class)->checkUsable((int) $conversation->app_id, (int) $conversation->uid);
                $ctx->app = $app;
                /** @var AiModel $model */
                $model = $app->model;
                $this->assertModelUsable($model);
                $ctx->model = $model;
                $ctx->agentConfig = app(AgentRuntime::class)->config($app);
                $this->applyModelToolRoundCeiling($ctx->agentConfig, $model);
                $ctx->streamService = app(AiStreamService::class);
                $ctx->toolService   = app(AiToolCallService::class);

                return $next($ctx);
            }),
            new ClosureMiddleware(static function (AgentStreamContext $ctx, callable $next): AgentStreamContext {
                if (! $ctx->isGuest && ($user = auth('admin')->user())) {
                    app(McpUserContextResolver::class)->merge(request(), $user);
                }

                return $next($ctx);
            }),
            new ClosureMiddleware(static function (AgentStreamContext $ctx, callable $next): AgentStreamContext {
                if (! $ctx->isGuest && $ctx->app && $ctx->conversation && $ctx->agentConfig) {
                    $ctx->quotaAcquired = app(AgentRuntime::class)->assertAllowed(
                        (int) $ctx->app->id,
                        (int) $ctx->conversation->uid,
                        $ctx->agentConfig
                    );
                }

                return $next($ctx);
            }),
            new ClosureMiddleware(function (AgentStreamContext $ctx, callable $next): AgentStreamContext {
                if ($ctx->isGuest && $ctx->toolService && $ctx->streamService && $ctx->app) {
                    $this->beginGuestSession($ctx->toolService, $ctx->streamService, $ctx->app);
                }

                return $next($ctx);
            }),
            new EvidenceGateMiddleware(),
        ])->run(new AgentStreamContext(
            $conversationId,
            $question,
            $saveUserMessage,
            $historyBeforeId,
            $guestToken
        ));
    }

    /**
     * 续跑落库时优先复用首轮 agent_intent，避免被硬编码写意图覆盖观测数据.
     *
     * @param array<string, mixed> $decision
     *
     * @return array<string, mixed>
     */
    private function intentSnapshotFromRecord(AiConversationMessage $record, array $decision = []): array
    {
        $meta = app(AiConversationMessageService::class)->decodeMeta($record);
        $existing = (array) ($meta['agent_intent'] ?? []);
        if ($existing !== []) {
            if ($decision !== [] && ($decision['answer_policy'] ?? '') !== '') {
                $existing['answer_policy'] = (string) $decision['answer_policy'];
            }

            return $existing;
        }

        return AiConversationMessageService::intentSnapshot(
            ThinDecisionBuilder::makeIntent(AgentEnum::INTENT_ACTION_REQUEST),
            $decision
        );
    }

    /**
     * 落库助手消息并挂载 agent_run / pending_input，刷新 last_message_at.
     *
     * @param array<string, int> $usage
     * @param array<int, array<string, mixed>> $agentTrace
     * @param array<int, string> $suggestions 推荐问题，随消息元数据落库
     * @param array<string, mixed> $agentIntent
     * @param null|array{id: int, ids: array<int, int>} $pendingInput
     */
    private function persistAssistantTurn(
        AiConversation $conversation,
        AiApplication $app,
        string $messageUuid,
        string $question,
        string $answer,
        string $raw,
        array $references,
        array $toolCalls,
        int $runTimeMs,
        int $status,
        ?AiConversationMessage $replaceRecord,
        array $usage,
        array $agentTrace = [],
        array $suggestions = [],
        ?int $agentRunId = null,
        ?array $pendingInput = null,
        array $agentIntent = []
    ): AiConversationMessage {
        $messageService = app(AiConversationMessageService::class);
        $assistant = $messageService->save(
            $conversation,
            $app,
            $messageUuid,
            $question,
            $answer,
            $raw,
            $references,
            $toolCalls,
            $runTimeMs,
            $status,
            $replaceRecord,
            $usage,
            $agentTrace,
            $suggestions,
            $agentIntent
        );

        if ($agentRunId !== null && $agentRunId > 0) {
            $assistant = $messageService->attachAgentRun($assistant, $agentRunId);
        }

        if ($pendingInput !== null) {
            $pendingId = (int) ($pendingInput['id'] ?? 0);
            if ($pendingId > 0) {
                $assistant = $messageService->attachPendingInput(
                    $assistant,
                    $pendingId,
                    (array) ($pendingInput['ids'] ?? [])
                );
            }
        }

        $this->dao->update($conversation->id, ['last_message_at' => date('Y-m-d H:i:s')]);

        return $assistant;
    }

    /**
     * 绑定访客沙箱（工具 guestMode + 流式敏感词过滤）.
     */
    private function beginGuestSession(
        AiToolCallService $toolService,
        AiStreamService $streamService,
        AiApplication $app
    ): void {
        $toolService->setGuestMode(true);
        $streamService->setGuestOutputFilter(
            app(AiGuestContentGuard::class)->createOutputFilter($app)
        );
    }

    /**
     * 清理访客请求级状态，避免 Swoole Worker 串请求.
     */
    private function endGuestSession(AiToolCallService $toolService): void
    {
        $toolService->setGuestMode(false);
        $toolService->bindWriteContext(null);
        $toolService->setPrefetchedIdentity(null);
        app(AiStreamService::class)->setGuestOutputFilter(null);
    }

    /** 将会话消息记录转换为对外 payload。 */
    private function messagePayload($record): array
    {
        return app(AiConversationMessageService::class)->payload($record);
    }

    /** 校验内部用户会话归属。 */
    private function checkConversation(int $id): AiConversation
    {
        $conversation = $this->dao->get(['id' => $id, 'uid' => (int) auth('admin')->id()]);
        if (! $conversation) {
            throw $this->exception('会话不存在或无权限访问');
        }

        return $conversation;
    }

    /**
     * 按 guest_token + session_type 加载访客会话.
     *
     * @throws \Throwable 会话不存在或不属于该访客
     */
    private function checkGuestConversation(int $id, string $guestToken): AiConversation
    {
        $conversation = $this->dao->get([
            'id'           => $id,
            'guest_token'  => $guestToken,
            'session_type' => AiConversationEnum::SESSION_TYPE_GUEST,
        ]);
        if (! $conversation) {
            throw $this->exception('会话不存在或无权限访问');
        }

        return $conversation;
    }
}
