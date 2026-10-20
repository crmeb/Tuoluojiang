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

namespace App\Http\Service\Ai\Agent;

use App\Constants\Ai\AgentEnum;
use App\Constants\Ai\AiConversationEnum;
use App\Http\Model\Ai\AgentRun;
use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiModel;
use App\Http\Service\Ai\Agent\Decision\McpToolAllowlist;
use App\Http\Service\Ai\Agent\Decision\ReactPhase;
use App\Http\Service\Ai\Agent\Trace\AgentTracer;
use App\Events\Ai\AgentPrompted;
use App\Events\Ai\ToolInvoked;
use App\Http\Service\Ai\AiFollowUpSuggestionService;
use App\Http\Service\Ai\AiModelService;
use App\Http\Service\Ai\AiStreamService;
use App\Http\Service\Ai\AiToolCallService;
use App\Http\Service\Ai\Support\AiProviderFailover;
use App\Http\Service\Ai\Testing\AiAgent;
use Tuoluojiang\Open\services\ai\BaseOption;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * 流式 LLM + 工具多轮循环执行器（从 AiConversationService 抽取）.
 *
 * 职责：驱动 LLM 流式推理、执行工具调用、检测信息确认并支持断点续跑.
 * 在 Agent 流程中的位置：Executor 内核，位于写操作预检之后；含回答充分性反思校验.
 */
class ToolLoopExecutor
{
    public const DEFAULT_MAX_ROUNDS = 5;

    /**
     * 「仍在承诺继续查询/重试」的话术特征.
     *
     * 第一人称承诺必须带后续动作词（如「我再查询」），避免「我再为您生成图表」这类
     * 条件性提议被误判为半截续查.
     */
    private const CONTINUATION_PROMISE_PATTERN = '/('
        . '将(会)?调整参数|重新(获取|查询|调用|请求|拉取)|继续(调用|查询|获取|尝试|检索)|'
        . '(我|让我)(将|会|再|继续){1,2}.{0,12}(重新|再次|查询|查一下|查看|获取|调用|请求|拉取|检索|尝试|核实|确认一下)|'
        . '稍等|请稍候|接下来(我)?(将|会)|马上(再|重新)|'
        . '换个(参数|条件|方式)|改用|再试一次|返回空.{0,40}(将|会|重新)|'
        . '查询.{0,16}(为空|没有|空).{0,40}(将|会|重新|继续|调整)|'
        // 口头承诺写步骤（审批/创建）但未发 tool_calls，按半截续写纠偏.
        . '将(会)?(发起|提交|创建|启动).{0,12}(审批|申请|订单|客户)|'
        . '(我|让我)(将|会|再|继续).{0,16}(发起|提交|创建).{0,12}(审批|申请)'
        . ')/u';

    /** 明确承诺由模型自行重跑（而非交回用户）的话术，优先级高于终态豁免. */
    private const CONTINUATION_SELF_RETRY_PATTERN = '/('
        . '将(调整|重新|继续)|重新(获取|查询|调用|请求|拉取)|再试一次|马上(再|重新)|'
        . '(我|让我)(将|会|再|继续){1,2}.{0,12}(重新|再次)'
        . ')/u';

    /** 回答已交付明细/结论结构，属终态. */
    private const CONTINUATION_CONCLUSION_PATTERN = '/共有\s*\d+|如下[:：]|接口列表|明细如下|结论[:：]|汇总[:：]/u';

    /** 回答已把下一步交回用户（澄清、征询、给选项），属终态而非半截续查. */
    private const CONTINUATION_HANDOFF_PATTERN = '/('
        . '您可以|你可以|请告知|请提供|请补充|请明确|请确认|请指定|需要您|如需|若需要|是否需要|要不要|可以告诉我'
        . ')/u';

    /**
     * 构造工具循环执行请求数组.
     *
     * @param array<int, array<string, mixed>> $references 知识库引用
     * @param callable(): bool|null $interruptedChecker 中断检测回调
     * @param null|array{runId: int, messageUuid: string, writeConfirmation: bool, approvedPendingIds?: array<int, int>} $writeContext 写操作确认上下文
     * @param array<string, mixed> $flowContext 多步业务流上下文
     * @param null|array{startRound: int, final: array, toolResults: array, trace: array} $resume 续跑上下文
     * @param array{mcp?: bool, sql?: bool} $decision 插件启用决策
     *
     * @return array<string, mixed> 含 app/model/option 等 snake_case 键的工具循环请求
     */
    public static function makeRequest(
        AiApplication $app,
        AiModel $model,
        BaseOption $option,
        AiToolCallService $toolService,
        AiStreamService $streamService,
        string $messageUuid,
        array $references = [],
        int $maxRounds = 5,
        $interruptedChecker = null,
        ?array $writeContext = null,
        array $flowContext = [],
        ?array $resume = null,
        ?AgentRun $run = null,
        int $conversationId = 0,
        int $uid = 0,
        string $question = '',
        array $decision = [],
        bool $writeConfirmation = false
    ): array {
        return [
            'app'                 => $app,
            'model'               => $model,
            'option'              => $option,
            'tool_service'        => $toolService,
            'stream_service'      => $streamService,
            'message_uuid'        => $messageUuid,
            'references'          => $references,
            'max_rounds'          => $maxRounds,
            'interrupted_checker' => $interruptedChecker,
            'write_context'       => $writeContext,
            'flow_context'        => $flowContext,
            'resume'              => $resume,
            'run'                 => $run,
            'conversation_id'     => $conversationId,
            'uid'                 => $uid,
            'question'            => $question,
            'decision'            => $decision,
            'write_confirmation'  => $writeConfirmation,
        ];
    }

    /**
     * 执行流式工具多轮循环，直至产出最终回答或进入待确认/待输入状态.
     *
     * @param array<string, mixed> $request 工具循环请求参数
     *
     * @return array 循环执行结果
     */
    public function execute(array $request): array
    {
        $app           = $request['app'];
        $model         = $request['model'];
        $option        = $request['option'];
        $toolService   = $request['tool_service'];
        $streamService = $request['stream_service'];
        $messageUuid   = $request['message_uuid'];
        $references    = $request['references'];
        $maxRounds     = $option->tools ? ($request['max_rounds'] ?: self::DEFAULT_MAX_ROUNDS) : 1;
        $interrupted   = $request['interrupted_checker'];
        $resume        = $request['resume'];
        $trace         = (is_array($resume) && isset($resume['trace']) && is_array($resume['trace']))
            ? $resume['trace']
            : AgentTracer::newMemoryTrace();
        if ($request['write_context']) {
            $toolService->bindWriteContext($request['write_context']);
        }

        $toolResults = is_array($resume) ? ($resume['toolResults'] ?? []) : [];
        $final       = is_array($resume) && isset($resume['final']) && is_array($resume['final'])
            ? $resume['final']
            : [
                'answer' => '',
                'raw'    => '',
                'usage'  => [],
                'status' => AiConversationEnum::MESSAGE_DONE,
            ];
        $startRound  = is_array($resume) ? (int) ($resume['startRound'] ?? 0) : 0;
        $inputHelper = app(AgentInputRequiredHelper::class);
        // 终轮会清空 option.tools；先快照本轮是否注入过写工具，供口头成功宣称检测.
        $hadWriteTools = $this->optionHasWriteTool($option);
        // focus 会裁掉跨模块写工具；保留全量快照供半截写纠偏时补回 payment_create 等.
        $fullToolsSnapshot = is_array($option->tools) ? $option->tools : [];

        // 请假/加班等人事写话术暂扣正文，便于口头假成功改写后单次推送纠正文案.
        if ($this->questionLooksLikePersonnelWrite((string) ($request['question'] ?? ''))) {
            $streamService->setRequestMeta(['hold_write_answer' => true]);
        }

        if (! isset($request['flow_context']) || ! is_array($request['flow_context'])) {
            $request['flow_context'] = [];
        }
        $flow = &$request['flow_context'];
        // 本请求基准轮次（decision / 调用方已按读/写设好）；半截写只在此基础上小幅加轮.
        $baseMaxRounds = max(1, (int) ($request['max_rounds'] ?: self::DEFAULT_MAX_ROUNDS));
        $incompleteExtend = 2;
        try {
            $incompleteExtend = max(1, (int) config('ai.plugins.tool_loop.incomplete_write_extend', 2));
        } catch (\Throwable) {
            // 单测无容器.
        }
        $nudgeCount = 0;
        $maxNudges  = 1;

        for ($round = $startRound; $round < $maxRounds; ++$round) {
            if ($interrupted && $interrupted()) {
                $streamService->complete($references, true);

                return $this->makeLoopResult(
                    $final['answer'] !== '' ? $final['answer'] : '已中断',
                    $final,
                    $toolResults,
                    $trace,
                    AiConversationEnum::MESSAGE_INTERRUPTED
                );
            }

            if ($round > 0 || ($option->tools && $resume === null)) {
                $streamService->status(
                    'thinking',
                    $round === 0 ? '正在分析并选择工具' : '正在根据工具结果继续思考'
                );
                $streamService->ping();
            }

            $result = $this->streamWithFailover($streamService, $model, $option, $messageUuid, [], false, $round);
            $calls  = $this->filterToolCalls((array) ($result['tool_calls'] ?? []));
            // 兜底撤回：本轮确认是工具轮（旁白）但仍有已直播正文，先撤回避免旁白残留叠段.
            if ($calls !== [] && ! empty($result['content_streamed'])) {
                $streamService->resetUserContent();
            }
            // 仅无 tool_calls 的终轮才把正文计入最终答案，避免中间旁白累加.
            $this->mergeStreamResult($final, $result, $calls === []);
            if ((int) $result['status'] === AiConversationEnum::MESSAGE_FAILED) {
                return $this->makeLoopResult(
                    (string) ($result['answer'] ?? $final['answer']),
                    $final,
                    $toolResults,
                    $trace,
                    AiConversationEnum::MESSAGE_FAILED,
                    (string) ($final['raw'] . ($result['raw'] ?? ''))
                );
            }
            if ((int) $result['status'] === AiConversationEnum::MESSAGE_INTERRUPTED) {
                $streamService->complete($references, true);

                return $this->makeLoopResult(
                    (string) ($result['answer'] ?? $final['answer']),
                    $final,
                    $toolResults,
                    $trace,
                    AiConversationEnum::MESSAGE_INTERRUPTED
                );
            }

            if ($calls === []) {
                $answer = trim((string) ($result['answer'] ?? ''));
                // thin-react：保留半截续查 / 复读收束；假成功与正文写确认纠偏已关闭.
                $incompleteContinuation = $this->looksLikeIncompleteContinuation($answer);
                $incompleteWrite       = $this->hasIncompleteWriteChain($toolResults)
                    || ($hadWriteTools && $this->looksLikeWritePromise($answer));

                // 已有成功工具证据却仍只输出「继续查/再试」：直接收束，禁止 nudge 诱发复读死循环.
                // 写链路未完成或口头承诺发起写操作时不得收束，留给下方纠偏.
                if ($incompleteContinuation
                    && ! $incompleteWrite
                    && $this->hasSuccessfulToolEvidence($toolResults)
                    && ! $this->writeFailed($request, $toolResults)
                ) {
                    $answer = $this->resolveIncompleteContinuationAnswer($answer, $toolResults);
                    $final['answer'] = $answer;
                    $this->deliverRoundAnswer($streamService, $result, $answer);
                    AgentTracer::memoryRecordFinalAnswer($trace, $round);
                    $this->emitPageableIfPresent($streamService, $messageUuid, $toolResults);
                    $suggestions = $this->emitFollowUpSuggestions($request, $answer, false, $references);

                    return $this->makeLoopResult(
                        $answer,
                        $final,
                        $toolResults,
                        $trace,
                        (int) $result['status'],
                        null,
                        $suggestions
                    );
                }

                // 写链路未完成（已取表单却未 create）或口头承诺「将发起审批」：强制带工具纠偏.
                if ($nudgeCount < $maxNudges
                    && $option->tools !== []
                    && (
                        ($incompleteContinuation && ! $this->hasSuccessfulToolEvidence($toolResults))
                        || $incompleteWrite
                    )
                ) {
                    ++$nudgeCount;
                    // 按场景加轮：半截写只需 form→create 的少量余量，禁止无上限抬高.
                    if ($incompleteWrite) {
                        $hardCap = min($baseMaxRounds + $incompleteExtend, $round + $incompleteExtend + 1);
                        $option->tools = McpToolAllowlist::restoreCustomerApproveWriteBridge(
                            is_array($option->tools) ? $option->tools : [],
                            $fullToolsSnapshot
                        );
                    } else {
                        $hardCap = min($baseMaxRounds + 1, $round + 2);
                    }
                    if ($round >= $maxRounds - 2 && $maxRounds < $hardCap) {
                        $maxRounds = min($hardCap, $round + $incompleteExtend);
                    }
                    $streamService->status(
                        'thinking',
                        $incompleteWrite ? '检测到写流程未完成，继续调用工具' : '检测到未完成续查，继续调用工具'
                    );
                    if ($answer !== '') {
                        $option->setMessage($answer, AiConversationEnum::ROLE_ASSISTANT);
                    }
                    $option->setMessage(
                        $incompleteWrite
                            ? '你上一轮只口头承诺将发起/提交写操作（如审批），或已取得表单/配置/审批流却未调用最终写工具。'
                              . '客户回款：customer_biz_form_get(biz=payment) → payment_create；'
                              . '续费 customer_biz_form_get(biz=renewal) → renewal_create；'
                              . '订单支出 customer_biz_form_get(biz=expense) → expense_create；'
                              . '开票 customer_biz_form_get(biz=invoice_issue) → invoice_apply；'
                              . '作废 customer_biz_form_get(biz=invoice_void) → invoice_void_apply。'
                              . '人事/自定义审批（含员工报销）请调用 approve_create，禁止把报销当成订单支出。禁止仅用文字结束。'
                            : '你上一轮只描述了下一步计划（如调整参数/重新获取），但没有发起工具调用，也没有给出最终结论。请立即用工具调用继续收窄查询；若上一轮工具已返回空结果或足够汇总，请直接给出结论，禁止再次只输出「继续/再试/先查」类计划文案。',
                        AiConversationEnum::ROLE_SYSTEM
                    );
                    if (! empty($result['content_streamed'])) {
                        $streamService->resetUserContent();
                    }
                    $final['answer'] = '';
                    continue;
                }

                // nudge 用尽仍半截 / 复读计划：替换为可交付文案.
                if ($incompleteContinuation || $this->looksLikeRepetitiveContinuationSpam($answer)) {
                    $answer = $this->resolveIncompleteContinuationAnswer($answer, $toolResults);
                    $final['answer'] = $answer;
                }

                // 补参续跑兜底：目标写工具参数已由用户补齐，但模型收口时仍未调用——
                // 服务端用已确认参数代为发起写调用（仍走写确认守卫，弹确认卡片）.
                $fallback = $this->resumeWriteFallback(
                    $request,
                    $flow,
                    $toolResults,
                    $option,
                    $app,
                    $model,
                    $toolService,
                    $streamService,
                    $references,
                    $messageUuid,
                    $trace,
                    $final,
                    $maxRounds,
                    $round
                );
                if ($fallback !== null) {
                    return $fallback;
                }

                $writeFailed = $this->writeFailed($request, $toolResults)
                    || $this->isFabricatedWriteSuccessGuarded($answer, $toolResults, $hadWriteTools);
                if ($writeFailed) {
                    $answer = $this->writeFailureMessage($toolResults);
                    $final['answer'] = $answer;
                }

                // 工具轮次暂扣或已直播的正文，确认可交付后再推给前端.
                // 假成功改写/收束改写：已直播内容与新文案不一致时先撤回再整段补推，避免叠段.
                $this->deliverRoundAnswer($streamService, $result, $answer);

                AgentTracer::memoryRecordFinalAnswer($trace, $round);
                $this->emitPageableIfPresent($streamService, $messageUuid, $toolResults);
                $suggestions = $this->emitFollowUpSuggestions($request, $answer, $writeFailed, $references);

                return $this->makeLoopResult(
                    $answer !== '' ? $answer : (string) ($final['answer'] ?? ''),
                    $final,
                    $toolResults,
                    $trace,
                    $writeFailed ? AiConversationEnum::MESSAGE_FAILED : (int) $result['status'],
                    null,
                    $suggestions
                );
            }

            // 请假等审批：把 flow 里已选的 process_users_* 注入 approve_create，避免模型漏填 process.users.
            $calls = $this->enrichToolCallsFromFlow($calls, $flow);

            $streamService->announceToolCalls($calls);
            $assistantAnswer = trim((string) ($result['answer'] ?? ''));
            $assistantMessage = [
                'role'       => AiConversationEnum::ROLE_ASSISTANT,
                'tool_calls' => $calls,
            ];
            $assistantMessage['content'] = $assistantAnswer !== '' ? $assistantAnswer : null;
            $option->messages[]          = $assistantMessage;

            $roundStarted = microtime(true);
            $roundResults = $streamService->runWithHeartbeat(
                fn (): array => $toolService->execute($app, $calls)
            );
            $this->dispatchToolInvokedEvents($messageUuid, $roundResults);
            AgentTracer::memoryRecordRound($trace, $round, $calls, $roundResults, (int) round((microtime(true) - $roundStarted) * 1000));
            $toolResults = array_merge($toolResults, $roundResults);

            // 审批驱动写工具仍 needs_selection 时清掉 process 完成标记，便于 detect 对未填节点重开选择框.
            $writeGuard = app(AgentWriteFlowDriver::class);
            foreach ($roundResults as $toolResult) {
                $toolName = (string) ($toolResult['name'] ?? '');
                if (! $writeGuard->isApproveProcessWriteTool($toolName)) {
                    continue;
                }
                $toolPayload = (array) ($toolResult['result'] ?? []);
                if (! empty($toolPayload['needs_selection']) || ! empty($toolPayload['selection_nodes'])) {
                    unset($flow['process']);
                }
            }

            $pendingConfirmationId = 0;
            foreach ($roundResults as $roundResult) {
                if (! empty($roundResult['pending_confirmation'])) {
                    $pendingConfirmationId = (int) ($roundResult['pending_id'] ?? 0);
                    break;
                }
            }

            foreach ($roundResults as $toolResult) {
                $option->setMessage(
                    (string) $toolResult['content'],
                    AiConversationEnum::ROLE_TOOL,
                    (string) $toolResult['id'],
                    (string) $toolResult['name']
                );
            }
            $this->compressOlderToolMessages($option);
            $calledNames = [];
            foreach ($roundResults as $toolResult) {
                $name = (string) ($toolResult['name'] ?? '');
                if ($name !== '') {
                    $calledNames[] = $name;
                }
            }
            if ($calledNames !== [] && is_array($option->tools) && $option->tools !== []) {
                $option->tools = McpToolAllowlist::focusToolDefinitions($option->tools, $calledNames);
            }
            // load_tools 补挂的工具在 focus 之后注入，避免被按已调用模块裁掉；并入 allowlist 供确认续跑重建.
            $loadedNames = $toolService->flushLoadedTools($option);
            if ($loadedNames !== []) {
                $request['decision']['mcp_allowlist'] = array_values(array_unique(array_merge(
                    (array) ($request['decision']['mcp_allowlist'] ?? []),
                    $loadedNames
                )));
                $hadWriteTools = $hadWriteTools || $this->optionHasWriteTool($option);
                // 新挂载的工具并入全量快照，半截写纠偏时可补回.
                if (is_array($option->tools)) {
                    $fullToolsSnapshot = $this->mergeToolDefinitions($fullToolsSnapshot, $option->tools);
                }
            }

            if ($pendingConfirmationId > 0) {
                $state = AgentInputRequiredHelper::makeLoopState(
                    (int) ($request['conversation_id'] ?? 0),
                    (int) $app->id,
                    (int) $model->id,
                    (int) ($request['uid'] ?? 0),
                    (int) ($request['run']->id ?? 0),
                    $messageUuid,
                    (string) ($request['question'] ?? ''),
                    (array) $option->messages,
                    (array) ($option->tools ?? []),
                    $round + 1,
                    (array) $references,
                    (array) ($request['decision'] ?? []),
                    (array) $flow,
                    $toolResults,
                    (string) ($final['answer'] ?? ''),
                    (string) ($final['raw'] ?? ''),
                    (array) ($final['usage'] ?? []),
                    $trace,
                    (bool) $request['write_confirmation'],
                    $maxRounds
                );
                AgentInputRequiredHelper::saveLoopStateCache($pendingConfirmationId, $state);
            }

            $pendingInput = $inputHelper->detect(
                $roundResults,
                $flow,
                $request,
                $round + 1,
                $final,
                $trace,
                $toolResults
            );
            if ($pendingInput !== null) {
                $answer = trim((string) ($final['answer'] ?? ''));
                if ($answer === '') {
                    $pendingIds = $inputHelper->listPendingIds($messageUuid, (int) ($request['uid'] ?? 0));
                    $final['answer'] = $inputHelper->formatPendingAnswer($pendingInput, $pendingIds);
                }
                $streamService->complete($references);

                return self::makeResult(
                    (string) $final['answer'],
                    (string) $final['raw'],
                    (array) $final['usage'],
                    AiConversationEnum::MESSAGE_AWAITING_INPUT,
                    $toolResults,
                    AgentTracer::memoryTraceSteps($trace),
                    false,
                    true,
                    (int) ($pendingInput->id ?? 0),
                    $inputHelper->listPendingIds($messageUuid, (int) ($request['uid'] ?? 0))
                );
            }

            if ($pendingConfirmationId > 0) {
                return self::makeResult(
                    '写操作等待用户确认，数据尚未写入。',
                    (string) $final['raw'],
                    (array) $final['usage'],
                    AiConversationEnum::MESSAGE_DONE,
                    $toolResults,
                    AgentTracer::memoryTraceSteps($trace),
                    true
                );
            }

            // 末轮前若业务 form 已成功但未 create：在基准轮次上加少量余量，补回写工具.
            if ($round >= $maxRounds - 1
                && $this->hasIncompleteWriteChain($toolResults)
                && is_array($option->tools)
                && $option->tools !== []
            ) {
                $option->tools = McpToolAllowlist::restoreCustomerApproveWriteBridge(
                    $option->tools,
                    $fullToolsSnapshot
                );
                $extended = min($baseMaxRounds + $incompleteExtend, $round + $incompleteExtend);
                if ($extended > $maxRounds) {
                    $maxRounds = $extended;
                    $streamService->status('thinking', '检测到写流程未完成，继续执行');
                    $option->setMessage(
                        '已取得写操作所需业务表单/审批流，但尚未调用最终写工具。'
                        . '客户回款请立即调用 payment_create（续费 renewal_create、订单支出 expense_create、'
                        . '开票 invoice_apply、作废 invoice_void_apply）；人事/自定义审批（含报销）请调用 approve_create。'
                        . '禁止仅用文字承诺「将发起/即将提交」，禁止再调 approve_type_list。',
                        AiConversationEnum::ROLE_SYSTEM
                    );
                }
            }

            $streamService->resetAnnouncedToolCalls();
        }

        if ($final['answer'] === '') {
            $request['option']->tools = [];
            $streamService->status('thinking', '正在生成最终回复');
            $this->emitPageableIfPresent($streamService, $messageUuid, $toolResults);
            $result = $this->streamWithFailover($streamService, $model, $option, $messageUuid, $references, false, $maxRounds);
            $this->mergeStreamResult($final, $result);
            AgentTracer::memoryRecordFinalAnswer($trace, $maxRounds);
            if ((int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_FAILED
                || (int) ($result['status'] ?? 0) === AiConversationEnum::MESSAGE_INTERRUPTED) {
                return $this->makeLoopResult(
                    (string) ($result['answer'] ?? $final['answer']),
                    $final,
                    $toolResults,
                    $trace,
                    (int) $result['status']
                );
            }

            $writeFailed = $this->writeFailed($request, $toolResults)
                || $this->isFabricatedWriteSuccessGuarded(
                    (string) $final['answer'],
                    $toolResults,
                    $hadWriteTools
                );
            if ($writeFailed) {
                $final['answer'] = $this->writeFailureMessage($toolResults);
            }
            // 轮次耗尽兜底：暂扣正文整段补推；已直播内容被写失败文案改写时先撤回再补推.
            $this->deliverRoundAnswer($streamService, $result, (string) $final['answer']);
            $suggestions = $this->emitFollowUpSuggestions($request, (string) $final['answer'], $writeFailed, $references);

            return $this->makeLoopResult(
                (string) $final['answer'],
                $final,
                $toolResults,
                $trace,
                $writeFailed ? AiConversationEnum::MESSAGE_FAILED : (int) $final['status'],
                null,
                $suggestions
            );
        }

        AgentTracer::memoryRecordFinalAnswer($trace, $maxRounds);
        $this->emitPageableIfPresent($streamService, $messageUuid, $toolResults);
        $writeFailed = $this->writeFailed($request, $toolResults)
            || $this->isFabricatedWriteSuccessGuarded(
                (string) $final['answer'],
                $toolResults,
                $hadWriteTools
            );
        if ($writeFailed) {
            $final['answer'] = $this->writeFailureMessage($toolResults);
        }
        $suggestions = $this->emitFollowUpSuggestions($request, (string) $final['answer'], $writeFailed, $references);
        if ($request['write_context']) {
            $toolService->bindWriteContext(null);
        }

        return $this->makeLoopResult(
            (string) $final['answer'],
            $final,
            $toolResults,
            $trace,
            $writeFailed ? AiConversationEnum::MESSAGE_FAILED : (int) $final['status'],
            null,
            $suggestions
        );
    }

    /**
     * 用户问题是否像人事请假/加班等写办理（用于强制暂扣正文）.
     */
    private function questionLooksLikePersonnelWrite(string $question): bool
    {
        $question = trim($question);
        if ($question === '') {
            return false;
        }

        return (bool) preg_match(
            '/请假|请.{0,6}假|休.{0,2}假|调休|加班|出差|外出|补卡|发起审批|提交审批/u',
            $question
        );
    }

    /**
     * 写失败时的用户可见文案：优先透出写工具给出的业务原因（如「可用库存不足」），
     * 统一兜底文案只在拿不到任何原因时使用，避免真实失败点被掩盖。
     *
     * @param array<int, array<string, mixed>> $toolResults
     */
    private function writeFailureMessage(array $toolResults): string
    {
        $guard  = app(AgentWriteFlowDriver::class);
        $reason = '';
        foreach ($toolResults as $item) {
            if (! $guard->isWriteTool((string) ($item['name'] ?? ''))) {
                continue;
            }
            $result = (array) ($item['result'] ?? []);
            if (AgentWriteFlowDriver::writeSucceeded($result)) {
                continue;
            }
            $message = trim((string) ($result['message'] ?? ''));
            if ($message !== '') {
                $reason = $message;
            }
        }

        return $reason !== ''
            ? $reason . '，数据未写入。'
            : '本轮未成功执行写操作，数据未写入。请检查工具权限或补齐信息后重试。';
    }

    /**
     * 只有真正调用过写工具且未成功，才把本轮判为写失败。
     * 未调用写工具时保留模型原文（例如只读问答被误判、或说明当前没有该能力），
     * 避免盖成「数据未写入」并上报 Agent 执行未成功。口头谎称已写入仍由 fabricated 检测处理。
     */
    private function writeFailed(array $request, array $toolResults): bool
    {
        if (! $this->hasAttemptedWrite($toolResults)) {
            return false;
        }

        return ! $this->hasSuccessfulWrite($toolResults);
    }

    /**
     * 补参续跑兜底：模型收口时仍未调用已补齐参数的目标写工具，由服务端代为发起.
     *
     * 触发条件（全部满足）：
     * 1. 处于补参/写确认续跑（resume 上下文存在）；
     * 2. 本轮流程中出现过写工具 needs_input（参数曾被打回）；
     * 3. 该工具此后既未成功落库也无确认挂起；
     * 4. flow 中该工具 schema 参数已齐（missingWriteFields 为空）.
     *
     * 兜底调用仍走写确认守卫：executeOne 会 storePending 并推送 confirm_required 卡片，
     * 用户确认后才真正写入；loop state 以 pendingId 落缓存，确认接口可续跑多步写.
     *
     * @param array<string, mixed> $request 工具循环请求
     * @param array<string, mixed> $flow 业务流上下文（含用户已补字段值）
     * @param array<int, array<string, mixed>> $toolResults 累计工具结果
     *
     * @return null|array<string, mixed> 命中兜底时返回带 pending_confirmation 的收尾结果
     */
    private function resumeWriteFallback(
        array $request,
        array $flow,
        array $toolResults,
        BaseOption $option,
        AiApplication $app,
        AiModel $model,
        AiToolCallService $toolService,
        AiStreamService $streamService,
        array $references,
        string $messageUuid,
        array &$trace,
        array $final,
        int $maxRounds,
        int $round
    ): ?array {
        if (! is_array($request['resume'] ?? null)) {
            return null;
        }

        $guard = app(AgentWriteFlowDriver::class);

        // 找最后一次因缺参/多命中选择被打回的写工具
        $toolName = '';
        $baseArgs = [];
        foreach ($toolResults as $toolResult) {
            $result = is_array($toolResult['result'] ?? null) ? $toolResult['result'] : [];
            if (empty($result['needs_input']) && empty($result['needs_selection'])) {
                continue;
            }
            $name = (string) ($toolResult['name'] ?? '');
            if ($name !== '' && $guard->isWriteTool($name)) {
                $toolName = $name;
                $baseArgs = is_array($result['original_arguments'] ?? null)
                    ? (array) $result['original_arguments']
                    : [];
            }
        }
        if ($toolName === '') {
            return null;
        }

        // 该工具已有确认挂起或成功落库时不代执行
        foreach ($toolResults as $toolResult) {
            if ((string) ($toolResult['name'] ?? '') !== $toolName) {
                continue;
            }
            $result = is_array($toolResult['result'] ?? null) ? $toolResult['result'] : [];
            if (! empty($result['pending_confirmation'])
                || ! empty($toolResult['pending_confirmation'])
                || AgentWriteFlowDriver::writeSucceeded($result)) {
                return null;
            }
        }

        // 原始写参 + flow 补齐（如 eid/entity_name）；缺参未齐则维持原路径
        $tool = \App\Mcp\ToolRegistry::getTool($toolName);
        if ($tool === null) {
            return null;
        }
        $properties = (array) ($tool->getInputSchema()['properties'] ?? []);
        $args       = $baseArgs;
        foreach (array_keys($properties) as $key) {
            $key = (string) $key;
            if (array_key_exists($key, $flow)) {
                $args[$key] = $flow[$key];
            }
        }
        if ($args === [] || $guard->missingWriteFields($toolName, $args) !== []) {
            return null;
        }

        $call = [
            'id'       => 'fallback_' . $toolName . '_' . substr(md5($messageUuid . (string) $round), 0, 8),
            'type'     => 'function',
            'function' => [
                'name'      => $toolName,
                'arguments' => AiToolCallService::encodeFunctionArguments($args),
            ],
        ];

        $streamService->announceToolCalls([$call]);
        $option->messages[] = [
            'role'       => AiConversationEnum::ROLE_ASSISTANT,
            'content'    => null,
            'tool_calls' => [$call],
        ];
        $roundResults = $streamService->runWithHeartbeat(
            fn (): array => $toolService->execute($app, [$call])
        );
        foreach ($roundResults as $roundResult) {
            $option->setMessage(
                (string) $roundResult['content'],
                AiConversationEnum::ROLE_TOOL,
                (string) $roundResult['id'],
                (string) $roundResult['name']
            );
        }
        $toolResults = array_merge($toolResults, $roundResults);

        $pendingId = 0;
        foreach ($roundResults as $roundResult) {
            if (! empty($roundResult['pending_confirmation'])) {
                $pendingId = (int) ($roundResult['pending_id'] ?? 0);
                break;
            }
        }
        if ($pendingId <= 0) {
            // 未命中确认分支（如权限拒绝）：回落原收口路径，由既有护栏判词
            $trace[] = ['type' => 'write_fallback', 'tool_name' => $toolName, 'result' => 'not_pending'];
            $final['tool_calls'] = array_merge((array) ($final['tool_calls'] ?? []), $roundResults);

            return null;
        }

        // 与工具循环确认挂起收尾对齐：loop state 以 pendingId 落缓存，供确认接口续跑
        $trace[] = ['type' => 'write_fallback', 'tool_name' => $toolName, 'pending_id' => $pendingId];
        $state = AgentInputRequiredHelper::makeLoopState(
            (int) ($request['conversation_id'] ?? 0),
            (int) $app->id,
            (int) $model->id,
            (int) ($request['uid'] ?? 0),
            (int) ($request['run']?->id ?? 0),
            $messageUuid,
            (string) ($request['question'] ?? ''),
            (array) $option->messages,
            (array) ($option->tools ?? []),
            $round + 1,
            (array) $references,
            (array) ($request['decision'] ?? []),
            (array) $flow,
            $toolResults,
            (string) ($final['answer'] ?? ''),
            (string) ($final['raw'] ?? ''),
            (array) ($final['usage'] ?? []),
            AgentTracer::memoryTraceSteps($trace),
            (bool) ($request['write_confirmation'] ?? false),
            $maxRounds
        );
        AgentInputRequiredHelper::saveLoopStateCache($pendingId, $state);

        $streamService->status('agent_awaiting_confirmation', '已按您补充的信息发起写操作，等待确认写入', [
            'pending_id' => $pendingId,
            'tool_name'  => $toolName,
        ]);

        return self::makeResult(
            '已按您补充的信息发起「' . $toolName . '」写操作，请在确认卡片中核对并确认写入。',
            (string) ($final['raw'] ?? ''),
            (array) ($final['usage'] ?? []),
            AiConversationEnum::MESSAGE_DONE,
            $toolResults,
            AgentTracer::memoryTraceSteps($trace),
            true
        );
    }

    /**
     * @param array<int, array<string, mixed>> $toolResults
     */
    private function hasSuccessfulWrite(array $toolResults): bool
    {
        $guard = app(AgentWriteFlowDriver::class);
        foreach ($toolResults as $item) {
            if ($guard->isWriteTool((string) ($item['name'] ?? ''))
                && AgentWriteFlowDriver::writeSucceeded((array) ($item['result'] ?? []))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $toolResults
     */
    private function hasAttemptedWrite(array $toolResults): bool
    {
        $guard = app(AgentWriteFlowDriver::class);
        foreach ($toolResults as $item) {
            if ($guard->isWriteTool((string) ($item['name'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 假成功纠偏：本轮已尝试写工具，或人事审批类口头成功宣称.
     *
     * @param array<int, array<string, mixed>> $toolResults
     * @param BaseOption|bool $writeToolsAvailable 本轮是否注入过写工具；终轮清空 tools 后请传启动时快照 bool
     */
    public function isFabricatedWriteSuccessGuarded(
        string $answer,
        array $toolResults,
        BaseOption|bool $writeToolsAvailable
    ): bool {
        if (! $this->looksLikeFabricatedWriteSuccess($answer, $toolResults, $writeToolsAvailable)) {
            return false;
        }

        return $this->hasAttemptedWrite($toolResults)
            || self::looksLikePersonnelApproveSuccessClaim($answer);
    }

    /**
     * 模型是否在没有写工具落库证据时口头声称「已生成/已创建/已提交」.
     *
     * @param array<int, array<string, mixed>> $toolResults
     * @param BaseOption|bool $writeToolsAvailable 本轮是否注入过写工具；终轮清空 tools 后请传启动时快照 bool
     */
    public function looksLikeFabricatedWriteSuccess(string $answer, array $toolResults, BaseOption|bool $writeToolsAvailable): bool
    {
        $answer = trim($answer);
        if ($answer === '' || $this->hasSuccessfulWrite($toolResults) || ! self::looksLikeWriteSuccessClaim($answer)) {
            return false;
        }

        $hasWriteTools = is_bool($writeToolsAvailable)
            ? $writeToolsAvailable
            : $this->optionHasWriteTool($writeToolsAvailable);

        // 有写工具：作为假成功候选（再由 hasAttemptedWrite / 人事专项收窄）.
        // 无写工具：仅拦请假审批类口头成功，避免闲聊误伤.
        return $hasWriteTools || self::looksLikePersonnelApproveSuccessClaim($answer);
    }

    /**
     * 回答是否像「已代用户办结」人事请假/审批（含 leave-request skill 成功模板）.
     *
     * 故意收窄：避免「审批流程已创建，请选择…」「该请假已通过」等查询/中途话术误伤.
     */
    public static function looksLikePersonnelApproveSuccessClaim(string $answer): bool
    {
        $answer = trim($answer);
        if ($answer === '' || ! self::looksLikeWriteSuccessClaim($answer)) {
            return false;
        }

        if (! preg_match(
            '/请假|事假|年假|病假|调休|加班|出差|外出|补卡|假期类型|审批/u',
            $answer
        )) {
            return false;
        }

        // 补参/选人中途话术：不是办结成功.
        if (preg_match('/请选择|请填写|还差|待您确认|需要您确认|后继续|信息确认如下/u', $answer)) {
            return false;
        }

        // 纯状态查询口吻：除非同时带「已为你发起/申请单号」等代办落库特征.
        $statusReport = (bool) preg_match(
            '/当前状态|状态为|已通过|已驳回|审批中|待审批|可在.{0,16}查看/u',
            $answer
        );
        $agentDone = (bool) preg_match(
            '/已(帮您|帮你|为您|为你).{0,16}(发起|提交|办理|完成)'
            . '|已经(帮您|帮你|为您|为你).{0,16}(发起|提交|办理|完成)'
            . '|申请单号\s*#?\d+'
            . '|您可以在我发起的审批/u',
            $answer
        );
        if ($statusReport && ! $agentDone) {
            return false;
        }

        return $agentDone || (bool) preg_match(
            '/(请假|事假|年假|病假|调休|加班|出差|外出|补卡|审批申请).{0,24}已(经)?(成功)?(提交|发起|办理)'
            . '|已(经)?(成功)?(提交|发起|办理).{0,24}(请假|事假|年假|审批申请)/u',
            $answer
        );
    }

    /** 回答是否口头宣称写操作已成功（不检查工具证据）. */
    public static function looksLikeWriteSuccessClaim(string $answer): bool
    {
        $answer = trim($answer);
        if ($answer === '') {
            return false;
        }

        // 排除澄清/反问问句：「是否已创建」「若已创建请忽略」.
        if (preg_match('/(是否|有没有|请确认|若|如果|假如).{0,16}已(成功)?(生成|创建|新建|录入|提交|发起|办理)/u', $answer)) {
            return false;
        }

        return (bool) preg_match(
            '/已(成功)?(自动)?(重新)?(生成|创建|新建|录入|提交|写入|保存|发起|办理)'
            . '|已经(帮您|帮你|为您|为你)?(生成|创建|新建|录入|提交|发起|办理)'
            . '|已(帮您|帮你|为您|为你)(成功)?(生成|创建|新建|录入|提交|发起|办理|完成)'
            . '|重新生成成功|创建成功|生成成功|办理成功|发起成功|提交成功/u',
            $answer
        );
    }

    private function optionHasWriteTool(BaseOption $option): bool
    {
        $tools = $option->tools;
        if (! is_array($tools) || $tools === []) {
            return false;
        }
        $guard = app(AgentWriteFlowDriver::class);
        foreach ($tools as $tool) {
            $name = '';
            if (is_array($tool)) {
                $name = (string) ($tool['function']['name'] ?? $tool['name'] ?? '');
            }
            if ($name !== '' && $guard->isWriteTool($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 合并工具 schema 定义（按 function.name 去重，后者补缺）.
     *
     * @param array<int, array<string, mixed>> $base
     * @param array<int, array<string, mixed>> $extra
     *
     * @return array<int, array<string, mixed>>
     */
    private function mergeToolDefinitions(array $base, array $extra): array
    {
        $seen = [];
        $out  = [];
        foreach (array_merge($base, $extra) as $tool) {
            if (! is_array($tool)) {
                continue;
            }
            $name = (string) ($tool['function']['name'] ?? $tool['name'] ?? '');
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $out[]       = $tool;
        }

        return $out;
    }

    /**
     * 从聚合 final 组装 array（中断/失败/终答共用）.
     *
     * @param array<string, mixed> $final
     * @param array<int, array<string, mixed>> $toolResults
     */

    /**
     * 构造工具循环结果数组（原 ToolLoopResult）.
     *
     * @param array<string, mixed> $usage
     * @param array<int, array<string, mixed>> $toolCalls
     * @param array<int, array<string, mixed>> $trace
     * @param array<int, int> $pendingInputIds
     *
     * @return array{
     *   answer: string,
     *   raw: string,
     *   usage: array<string, mixed>,
     *   status: int,
     *   tool_calls: array<int, array<string, mixed>>,
     *   trace: array<int, array<string, mixed>>,
     *   pending_confirmation: bool,
     *   pending_input: bool,
     *   pending_input_id: int,
     *   pending_input_ids: array<int, int>
     * }
     */
    public static function makeResult(
        string $answer = '',
        string $raw = '',
        array $usage = [],
        int $status = 0,
        array $toolCalls = [],
        array $trace = [],
        bool $pendingConfirmation = false,
        bool $pendingInput = false,
        int $pendingInputId = 0,
        array $pendingInputIds = []
    ): array {
        return [
            'answer'                => $answer,
            'raw'                   => $raw,
            'usage'                 => $usage,
            'status'                => $status,
            'tool_calls'            => $toolCalls,
            'trace'                 => $trace,
            'pending_confirmation'  => $pendingConfirmation,
            'pending_input'         => $pendingInput,
            'pending_input_id'      => $pendingInputId,
            'pending_input_ids'     => $pendingInputIds !== [] ? $pendingInputIds : ($pendingInputId > 0 ? [$pendingInputId] : []),
        ];
    }

    /**
     * 序列化为会话层 API 结构.
     *
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    public static function resultToArray(array $result): array
    {
        return [
            'answer'            => (string) ($result['answer'] ?? ''),
            'raw'               => (string) ($result['raw'] ?? ''),
            'usage'             => (array) ($result['usage'] ?? []),
            'status'            => (int) ($result['status'] ?? 0),
            'tool_calls'        => (array) ($result['tool_calls'] ?? []),
            'agent_trace'       => (array) ($result['trace'] ?? []),
            'pending_input_id'  => (int) ($result['pending_input_id'] ?? 0),
            'pending_input_ids' => (array) ($result['pending_input_ids'] ?? []),
            'suggestions'       => (array) ($result['suggestions'] ?? []),
        ];
    }

    private function makeLoopResult(
        string $answer,
        array $final,
        array $toolResults,
        array $trace,
        int $status,
        ?string $rawOverride = null,
        array $suggestions = []
    ): array {
        return array_merge(self::makeResult(
            $answer,
            $rawOverride ?? (string) $final['raw'],
            (array) $final['usage'],
            $status,
            $toolResults,
            AgentTracer::memoryTraceSteps($trace)
        ), [
            // 推送给前端的推荐问题要随结果带回落库，否则刷新后无从还原
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * 工具结果含标准 pageable 信封时，在 done 前推送 is_page（不按工具名分支）.
     *
     * @param array<int, array<string, mixed>> $toolResults
     */
    private function emitPageableIfPresent(AiStreamService $streamService, string $messageUuid, array $toolResults): void
    {
        if ($messageUuid === '' || $toolResults === []) {
            return;
        }
        if (app(\App\Http\Service\Ai\AiConversationMessageService::class)->extractPageableFromToolCalls($toolResults) === null) {
            return;
        }
        $streamService->message($messageUuid, true);
    }

    /**
     * 正常结束时推送 3 条推荐问题；失败、空回答或生成异常都不影响本轮收尾.
     *
     * @param array<string, mixed> $request 工具循环请求
     * @param string $answer 最终回答
     * @param bool $failed 本轮是否失败（写失败等），失败不推送
     *
     * @return array<int, string> 实际推送的推荐问题；未推送时为空数组
     */
    private function emitFollowUpSuggestions(
        array $request,
        string $answer,
        bool $failed,
        array $references = []
    ): array {
        $streamService = $request['stream_service'] ?? null;
        if (! $streamService instanceof AiStreamService) {
            return [];
        }

        // thin_react：合并 search_knowledge 累积引用后再推结束帧.
        $toolService = $request['tool_service'] ?? null;
        if ($toolService instanceof AiToolCallService) {
            $refs = $toolService->knowledgeReferences();
            if ($refs !== []) {
                $references = array_values(array_merge($references, $refs));
            }
        }

        // 结束帧先行是全局收尾约定：无论能否推荐，先让前端落到回答终态
        $streamService->complete($references);

        if ($failed || trim((string) $answer) === '') {
            return [];
        }
        $app = $request['app'] ?? null;
        $model = $request['model'] ?? null;
        if (! $app instanceof AiApplication || ! $model instanceof AiModel) {
            return [];
        }
        $question = trim((string) ($request['question'] ?? ''));
        if ($question === '') {
            return [];
        }

        try {
            // 落库取实际推送的列表（经去空、去重、截断），保证刷新后还原的与用户所见一致
            // 结束帧已由上方 complete 发出；此处仅补推推荐问题，references 置空避免重复挂图
            return $streamService->completeThenSuggestions(
                fn (): array => $streamService->runWithHeartbeat(
                    fn (): array => app(AiFollowUpSuggestionService::class)->suggest($app, $model, $question, (string) $answer)
                ),
                []
            );
        } catch (\Throwable $e) {
            Log::channel((string) config('ai.http_log.channel', 'ai'))->warning('AI推荐问题推送失败', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 过滤掉函数名为空的无效 tool_calls.
     *
     * @param array<int, array<string, mixed>> $toolCalls
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterToolCalls(array $toolCalls): array
    {
        return array_values(array_filter(
            $toolCalls,
            static fn (array $call): bool => (string) ($call['function']['name'] ?? '') !== ''
        ));
    }

    /**
     * 用 flow_context 补全写工具参数（approve_create / payment_create 等 process 自选人员）.
     *
     * @param array<int, array<string, mixed>> $toolCalls
     * @param array<string, mixed> $flow
     *
     * @return array<int, array<string, mixed>>
     */
    private function enrichToolCallsFromFlow(array $toolCalls, array $flow): array
    {
        if ($toolCalls === [] || $flow === []) {
            return $toolCalls;
        }

        $guard = app(AgentWriteFlowDriver::class);
        foreach ($toolCalls as &$call) {
            $name = (string) ($call['function']['name'] ?? '');
            if (! $guard->isApproveProcessWriteTool($name)) {
                continue;
            }
            $rawArgs = $call['function']['arguments'] ?? [];
            if (is_string($rawArgs)) {
                $decoded = json_decode($rawArgs, true);
                $args    = is_array($decoded) ? $decoded : [];
            } elseif (is_array($rawArgs)) {
                $args = $rawArgs;
            } else {
                $args = [];
            }
            $enriched = $guard->enrichApproveCreateArgs($args, $flow);
            $call['function']['arguments'] = AiToolCallService::encodeFunctionArguments($enriched);
        }
        unset($call);

        return $toolCalls;
    }

    /**
     * 交付单轮终稿正文：暂扣内容整段补推；已直播内容被纠偏改写时先撤回再补推.
     *
     * @param array<string, mixed> $result 单轮流式结果（含 content_held / content_streamed）
     */
    private function deliverRoundAnswer(AiStreamService $streamService, array $result, string $answer): void
    {
        if (! empty($result['content_streamed'])) {
            // 正文已实时直播：仅当纠偏/收束改写了交付文案时才撤回重推，避免与已显示内容叠段.
            if (trim($answer) !== trim((string) ($result['answer'] ?? ''))) {
                $streamService->resetUserContent();
                $streamService->emitAnswer($answer);
            }
            return;
        }
        if (! empty($result['content_held'])) {
            $streamService->emitAnswer($answer);
        }
    }

    /** 将单轮流式结果累合并入 final 聚合结构. */
    private function mergeStreamResult(array &$final, array $result, bool $appendAnswer = true): void
    {
        $final['raw'] .= (string) ($result['raw'] ?? '');
        if ($appendAnswer && (string) ($result['answer'] ?? '') !== '') {
            // 终轮正文覆盖，避免历史旁白残留.
            $final['answer'] = (string) $result['answer'];
        }
        if ((int) ($result['status'] ?? 0) > 0) {
            $final['status'] = (int) $result['status'];
        }
        foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $field) {
            $final['usage'][$field] = (int) ($final['usage'][$field] ?? 0) + (int) ($result['usage'][$field] ?? 0);
        }
        if ((int) ($final['usage']['total_tokens'] ?? 0) <= 0) {
            $final['usage'] = AiStreamService::normalizeUsage($final['usage']);
        }
    }
    // --- 反思校验（原 Reflector）---

    /**
     * 评估工具循环产出的回答是否充分可交付.
     *
     * @param string $answer 最终回答文本
     * @param array<int, array<string, mixed>> $toolCalls 已执行工具调用列表
     *
     * @return array{sufficient: bool, reason: string, failed_tools: array<int, string>} 校验结论
     */
    public function evaluate(string $answer, array $toolCalls): array
    {
        $failedTools = [];
        foreach ($toolCalls as $call) {
            $name   = (string) ($call['name'] ?? '');
            $result = (array) ($call['result'] ?? []);
            if ($name !== '' && ! empty($result['error'])) {
                $failedTools[] = $name;
            }
        }

        // 仅拦截「调了工具却空答」；半截续查/假成功等交付护栏在主循环处理，不做语义证据闸门.
        if (trim($answer) === '' && $toolCalls !== []) {
            return [
                'sufficient'   => false,
                'reason'       => 'empty_answer_after_tools',
                'failed_tools' => $failedTools,
            ];
        }

        return [
            'sufficient'   => true,
            'reason'       => 'ok',
            'failed_tools' => $failedTools,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $toolCalls
     */
    private function hasSuccessfulToolEvidence(array $toolCalls): bool
    {
        foreach ($toolCalls as $call) {
            $result = (array) ($call['result'] ?? []);
            if ($result === []) {
                continue;
            }
            if (! empty($result['error']) || ! empty($result['isError'])) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * 判断回答是否仅承诺继续/重试而未交付结论（应强制继续工具循环）.
     */
    public function looksLikeIncompleteContinuation(string $answer): bool
    {
        $answer = trim($answer);
        if ($answer === '') {
            return false;
        }

        if ($this->looksLikeRepetitiveContinuationSpam($answer)) {
            return true;
        }

        if (! preg_match(self::CONTINUATION_PROMISE_PATTERN, $answer)) {
            return false;
        }

        // 明确承诺自行重跑时，即便已给出结论或附带征询，仍按半截续查处理.
        if (preg_match(self::CONTINUATION_SELF_RETRY_PATTERN, $answer)) {
            return true;
        }

        // 已给出明细/结论结构，或已把下一步交回用户（澄清、征询、给选项），均属终态.
        return ! preg_match(self::CONTINUATION_CONCLUSION_PATTERN, $answer)
            && ! preg_match(self::CONTINUATION_HANDOFF_PATTERN, $answer);
    }

    /**
     * 正文是否口头承诺即将发起写操作（审批/创建）但未伴随 tool_calls.
     */
    public function looksLikeWritePromise(string $answer): bool
    {
        $answer = trim($answer);
        if ($answer === '') {
            return false;
        }

        return (bool) preg_match('/('
            . '将(会)?(发起|提交|创建|启动).{0,12}(审批|申请|订单|客户)|'
            . '(我|让我)(将|会|再|继续).{0,16}(发起|提交|创建).{0,12}(审批|申请)|'
            . '接下来(我)?(将|会).{0,12}(发起|提交).{0,8}(审批|申请)'
            . ')/u', $answer);
    }

    /**
     * 写前置工具已成功（业务 form_get / process_get），但尚未落到最终写工具.
     *
     * 仅业务 form（order_*_form_get 等）或 approve_process_get 计为半截写前置；
     * 裸 approve_form_get 不再无限延轮.
     *
     * @param array<int, array<string, mixed>> $toolResults
     */
    public function hasIncompleteWriteChain(array $toolResults): bool
    {
        if ($toolResults === []) {
            return false;
        }

        $lastPrepAt  = -1;
        $lastWriteAt = -1;
        foreach ($toolResults as $index => $toolResult) {
            if (! is_array($toolResult)) {
                continue;
            }
            // 已弹出写确认卡：链路暂停等用户，不算「半截写」.
            if (! empty($toolResult['pending_confirmation'])) {
                return false;
            }
            $name   = (string) ($toolResult['name'] ?? '');
            $result = (array) ($toolResult['result'] ?? []);
            if ($name === '' || $result === [] || ! empty($result['error']) || ! empty($result['isError'])) {
                continue;
            }
            if (ReactPhase::isWritePrepTool($name)) {
                $lastPrepAt = $index;
                continue;
            }
            if (preg_match('/(?:_create|_update|_delete|_submit)$/', $name)) {
                $lastWriteAt = $index;
            }
        }

        return $lastPrepAt >= 0 && $lastPrepAt > $lastWriteAt;
    }

    /**
     * 是否像「继续/再试/先查/调用工具」复读垃圾（常见于半截续查 nudge 后模型失控）.
     */
    public function looksLikeRepetitiveContinuationSpam(string $answer): bool
    {
        $answer = trim($answer);
        if ($answer === '' || mb_strlen($answer) < 60) {
            return false;
        }

        if (preg_match_all('/继续|再试|先查|尝试|查一下|重新查询|按月|查明细/u', $answer, $hits) > 0
            && count($hits[0]) >= 8
        ) {
            return true;
        }

        // 「调用 xxx」口头复读：模型把工具名当正文刷屏而不发起 tool_calls.
        if (preg_match_all('/调用\s*[a-zA-Z_\x{4e00}-\x{9fff}]{2,40}/u', $answer, $callHits) > 0
            && count($callHits[0]) >= 6
        ) {
            return true;
        }

        // 同一短句（4~24 字）重复出现 5 次以上.
        if (preg_match_all('/([\x{4e00}-\x{9fff}\w]{4,24})(?:[\s。.!！?？,，、;；]*)\1(?:[\s。.!！?？,，、;；]*\1){3,}/u', $answer)) {
            return true;
        }

        return false;
    }

    /**
     * 半截续查/复读计划无法交付时，基于已有工具结果生成可展示终答.
     *
     * @param array<int, array<string, mixed>> $toolResults
     */
    public function resolveIncompleteContinuationAnswer(string $answer, array $toolResults): string
    {
        if (! ($this->looksLikeRepetitiveContinuationSpam($answer) || $this->looksLikeIncompleteContinuation($answer))) {
            return trim($answer);
        }

        $fromFollowUps = $this->formatFollowUpsFromToolResults($toolResults);
        if ($fromFollowUps !== '') {
            return $fromFollowUps;
        }

        if ($this->toolsIndicateEmptyData($toolResults)) {
            return '已完成查询，当前条件下暂无可用数据。请换个时间范围，或确认相关业务数据是否已录入/同步。';
        }
        if ($this->hasSuccessfulToolEvidence($toolResults)) {
            return '已完成相关查询，但未能整理出明确结论。请换个更具体的时间范围或对象后再问我。';
        }

        return '查询尚未得到可汇总的结论。请换个问法或指定时间范围后重试。';
    }

    /**
     * 从 crm_activity_summary / 列表类工具结果中拼跟进记录终答.
     *
     * @param array<int, array<string, mixed>> $toolResults
     */
    private function formatFollowUpsFromToolResults(array $toolResults): string
    {
        foreach (array_reverse($toolResults) as $call) {
            $result = (array) ($call['result'] ?? []);
            if ($result === [] || ! empty($result['error']) || ! empty($result['isError'])) {
                continue;
            }

            $followUps = $result['follow_ups'] ?? null;
            if (! is_array($followUps) || $followUps === []) {
                continue;
            }

            $lines = [];
            foreach (array_slice($followUps, 0, 10) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $content = trim((string) ($item['content'] ?? $item['reason'] ?? ''));
                $time    = trim((string) ($item['created_at'] ?? ''));
                if ($content === '') {
                    continue;
                }
                $lines[] = $time !== '' ? "- {$time}：{$content}" : "- {$content}";
            }
            if ($lines === []) {
                continue;
            }

            $count = count($followUps);

            return "共找到 {$count} 条跟进记录：\n" . implode("\n", $lines);
        }

        return '';
    }

    /**
     * 成功工具结果是否整体呈现空列表/零计数（可据此直接告知暂无数据）.
     *
     * 仅当「带 list/count 的成功结果全部为空」时为 true；任一非空汇总会否决。
     *
     * @param array<int, array<string, mixed>> $toolResults
     */
    public function toolsIndicateEmptyData(array $toolResults): bool
    {
        $emptySignals = 0;
        foreach ($toolResults as $call) {
            $result = (array) ($call['result'] ?? []);
            if ($result === [] || ! empty($result['error']) || ! empty($result['isError'])) {
                continue;
            }
            $count = $result['count'] ?? null;
            if (is_numeric($count) && (int) $count > 0) {
                return false;
            }
            $sawListKey = false;
            foreach (['list', 'data', 'items', 'records'] as $key) {
                if (! array_key_exists($key, $result) || ! is_array($result[$key])) {
                    continue;
                }
                $sawListKey = true;
                if ($result[$key] !== []) {
                    return false;
                }
            }
            if ($sawListKey || (is_numeric($count) && (int) $count === 0)) {
                ++$emptySignals;
            }
        }

        return $emptySignals > 0;
    }

    /**
     * 判断是否应将消息状态标记为失败.
     *
     * @param array{sufficient: bool, reason: string, failed_tools: array<int, string>} $reflection evaluate 返回结构
     * @param int $messageStatus 当前消息状态码
     *
     * @return bool 应标记失败时为 true
     */
    public function shouldMarkFailed(array $reflection, int $messageStatus): bool
    {
        if ($messageStatus === AiConversationEnum::MESSAGE_FAILED) {
            return true;
        }

        return ! $reflection['sufficient']
            && ($reflection['reason'] ?? '') === 'empty_answer_after_tools';
    }

    /**
     * 多轮工具循环中压缩较早的 tool 消息，仅保留最近 N 轮完整结果.
     */
    private function compressOlderToolMessages(BaseOption $option): void
    {
        $keepRecent = max(1, (int) config('ai.plugins.tool_result.keep_recent_rounds', 2));
        $maxChars   = max(200, (int) config('ai.plugins.tool_result.max_chars', 2500));
        $compactTo  = max(120, (int) floor($maxChars / 4));

        $toolIndexes = [];
        foreach ($option->messages as $idx => $message) {
            if (($message['role'] ?? '') === AiConversationEnum::ROLE_TOOL) {
                $toolIndexes[] = $idx;
            }
        }
        if (count($toolIndexes) <= $keepRecent) {
            return;
        }

        $stale = array_slice($toolIndexes, 0, -$keepRecent);
        foreach ($stale as $idx) {
            $content = trim((string) ($option->messages[$idx]['content'] ?? ''));
            if ($content === '' || mb_strlen($content) <= $compactTo) {
                continue;
            }
            $head = mb_substr($content, 0, $compactTo - 1) . '…';
            // 保留截断续查线索关键字，避免模型丢失 next_action.
            if (preg_match('/truncated|next_action|pageable/i', $content)) {
                $head .= "\n[earlier_tool_result_compressed: truncated clues retained in recent rounds]";
            }
            $option->messages[$idx]['content'] = $head;
        }
    }

    /**
     * 带 Provider failover 的流式调用：主模型可重试失败时切换候选（T-4），并派发 AgentPrompted（T-5）.
     *
     * @param array<int, array<string, mixed>> $references
     *
     * @return array<string, mixed>
     */
    private function streamWithFailover(
        AiStreamService $streamService,
        AiModel $model,
        BaseOption $option,
        string $messageUuid,
        array $references,
        bool $finalize,
        int $round
    ): array {
        $failover = new AiProviderFailover();
        $this->dispatchAgentPrompted($messageUuid, (string) ($option->model ?? $model->model ?? ''), $round, [
            'model_id' => (int) ($model->id ?? 0),
            'url'      => (string) ($option->url ?? ''),
        ]);

        $result = $streamService->stream((string) $model->api_key, $option, $messageUuid, $references, $finalize);
        if (! $failover->shouldFailover($result, (string) ($result['answer'] ?? ''))) {
            return $result;
        }

        $candidateIds = $failover->candidateModelIds((int) ($model->id ?? 0));
        $maxAttempts  = min($failover->maxAttempts(), count($candidateIds));
        $attempt      = 1;
        $lastResult   = $result;

        while ($attempt < $maxAttempts) {
            $nextId = $candidateIds[$attempt] ?? 0;
            ++$attempt;
            if ($nextId <= 0 || $nextId === (int) ($model->id ?? 0)) {
                // 无异模型时同模型退避再试一次.
                $sleep = $failover->backoffSeconds($attempt - 1);
                if ($sleep > 0) {
                    usleep((int) round($sleep * 1_000_000));
                }
                Log::channel((string) config('ai.http_log.channel', 'ai'))->warning('AI failover retry same model', [
                    'message_uuid' => $messageUuid,
                    'attempt'      => $attempt,
                    'model_id'     => (int) ($model->id ?? 0),
                    'error'        => (string) ($lastResult['answer'] ?? ''),
                ]);
                $this->dispatchAgentPrompted($messageUuid, (string) ($option->model ?? ''), $round, [
                    'failover' => true,
                    'attempt'  => $attempt,
                    'model_id' => (int) ($model->id ?? 0),
                ]);
                $lastResult = $streamService->stream((string) $model->api_key, $option, $messageUuid, $references, $finalize);
                if (! $failover->shouldFailover($lastResult, (string) ($lastResult['answer'] ?? ''))) {
                    return $lastResult;
                }
                continue;
            }

            try {
                /** @var AiModel|null $nextModel */
                $nextModel = AiModel::query()->where('id', $nextId)->where('status', 1)->first();
            } catch (\Throwable) {
                $nextModel = null;
            }
            if (! $nextModel) {
                continue;
            }

            $sleep = $failover->backoffSeconds($attempt - 1);
            if ($sleep > 0) {
                usleep((int) round($sleep * 1_000_000));
            }

            $messages = $option->messages;
            $tools    = $option->tools;
            $nextOption = app(AiModelService::class)->makeChatOption($nextModel);
            $nextOption->messages = $messages;
            $nextOption->tools    = $tools;

            Log::channel((string) config('ai.http_log.channel', 'ai'))->warning('AI failover switch model', [
                'message_uuid' => $messageUuid,
                'from_model'   => (int) ($model->id ?? 0),
                'to_model'     => (int) $nextModel->id,
                'attempt'      => $attempt,
                'error'        => (string) ($lastResult['answer'] ?? ''),
            ]);
            $this->dispatchAgentPrompted($messageUuid, (string) ($nextOption->model ?? ''), $round, [
                'failover'   => true,
                'attempt'    => $attempt,
                'from_model' => (int) ($model->id ?? 0),
                'to_model'   => (int) $nextModel->id,
            ]);
            $lastResult = $streamService->stream((string) $nextModel->api_key, $nextOption, $messageUuid, $references, $finalize);
            // 后续轮继续用切换后的 option（消息已在 nextOption）.
            $option->url     = $nextOption->url;
            $option->model   = $nextOption->model;
            $option->messages = $nextOption->messages;
            if (! $failover->shouldFailover($lastResult, (string) ($lastResult['answer'] ?? ''))) {
                return $lastResult;
            }
        }

        return $lastResult;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function dispatchAgentPrompted(string $messageUuid, string $model, int $round, array $context = []): void
    {
        AiAgent::recordPrompt(array_merge([
            'message_uuid' => $messageUuid,
            'model'        => $model,
            'round'        => $round,
        ], $context));
        try {
            Event::dispatch(new AgentPrompted($messageUuid, $model, $round, $context));
        } catch (\Throwable) {
            // 事件总线不可用时不影响主链路.
        }
        try {
            Log::channel((string) config('ai.http_log.channel', 'ai'))->info('AgentPrompted', [
                'message_uuid' => $messageUuid,
                'model'        => $model,
                'round'        => $round,
                'context'      => $context,
            ]);
        } catch (\Throwable) {
            // ignore
        }
    }

    /**
     * @param list<array<string, mixed>> $roundResults
     */
    private function dispatchToolInvokedEvents(string $messageUuid, array $roundResults): void
    {
        foreach ($roundResults as $toolResult) {
            $name    = (string) ($toolResult['name'] ?? '');
            $isError = ! empty($toolResult['error'])
                || ! empty(($toolResult['result']['error'] ?? false));
            AiAgent::recordToolInvocation([
                'message_uuid' => $messageUuid,
                'tool'         => $name,
                'is_error'     => $isError,
            ]);
            try {
                Event::dispatch(new ToolInvoked($messageUuid, $name, $isError, [
                    'id' => (string) ($toolResult['id'] ?? ''),
                ]));
            } catch (\Throwable) {
                // ignore
            }
            try {
                Log::channel((string) config('ai.http_log.channel', 'ai'))->info('ToolInvoked', [
                    'message_uuid' => $messageUuid,
                    'tool'         => $name,
                    'is_error'     => $isError,
                ]);
            } catch (\Throwable) {
                // ignore
            }
        }
    }

}
