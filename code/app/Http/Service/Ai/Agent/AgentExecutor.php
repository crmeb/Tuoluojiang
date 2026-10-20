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
use App\Http\Model\Ai\AgentPendingAction;
use App\Http\Model\Ai\AgentRun;
use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiModel;
use App\Http\Service\Ai\Agent\Decision\ThinDecisionBuilder;
use App\Http\Service\Ai\Agent\Trace\AgentTracer;
use App\Http\Service\Ai\AiStreamService;
use App\Http\Service\Ai\AiToolCallService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Agent 执行器：ReAct 循环 + 反思校验 + 写操作确认 + Run 追踪.
 *
 * 职责：协调写操作预检、工具多轮循环、信息确认/写确认收尾及反思校验.
 * 在 Agent 流程中的位置：Runtime 委托的核心执行层，位于 Bootstrap 之后、Tracer 落库之前.
 */
class AgentExecutor
{
    private ToolLoopExecutor $toolLoopExecutor;

    private AgentTracer $tracer;

    public function __construct(
        ToolLoopExecutor $toolLoopExecutor,
        AgentTracer $tracer
    ) {
        $this->toolLoopExecutor = $toolLoopExecutor;
        $this->tracer           = $tracer;
    }

    /**
     * 构造 Agent 单次执行请求数组.
     *
     * @param array<int, array<string, mixed>> $references 知识库引用
     * @param array $intent 路由得到的意图
     * @param callable(): bool|null $interruptedChecker 中断检测回调
     * @param array{mcp?: bool, sql?: bool, answer_policy?: string, max_tool_rounds?: int} $decision 插件启用决策
     *
     * @return array<string, mixed> 含 app/model/option 等 snake_case 键的执行请求
     */
    public static function makeRequest(
        AiApplication $app,
        AiModel $model,
        $option,
        AiToolCallService $toolService,
        AiStreamService $streamService,
        string $messageUuid,
        array $references,
        AgentConfig $config,
        array $intent,
        ?AgentRun $run = null,
        $interruptedChecker = null,
        int $conversationId = 0,
        int $uid = 0,
        string $question = '',
        array $decision = []
    ): array {
        return [
            'app'                 => $app,
            'model'               => $model,
            'option'              => $option,
            'tool_service'        => $toolService,
            'stream_service'      => $streamService,
            'message_uuid'        => $messageUuid,
            'references'          => $references,
            'config'              => $config,
            'intent'              => $intent,
            'run'                 => $run,
            'interrupted_checker' => $interruptedChecker,
            'conversation_id'     => $conversationId,
            'uid'                 => $uid,
            'question'            => $question,
            'decision'            => $decision,
        ];
    }

    /**
     * 执行完整 Agent 流程：预检、工具循环、确认处理与可选反思.
     *
     * @param array<string, mixed> $request 单次 Agent 执行请求
     *
     * @return array 工具循环执行结果
     */
    public function execute(array $request): array
    {
        $stream = $request['stream_service'];
        $stream->status('agent_step_start', '开始 Agent 执行', [
            'intent'    => $request['intent']['type'],
            'max_steps' => $request['config']->maxSteps,
            'run_id'    => (int) ($request['run']->id ?? 0),
        ]);

        $writeContext = $this->createWriteContext($request);

        $flow = [];

        $loopResult = $this->toolLoopExecutor->execute(ToolLoopExecutor::makeRequest(
            $request['app'],
            $request['model'],
            $request['option'],
            $request['tool_service'],
            $stream,
            $request['message_uuid'],
            $request['references'],
            $request['config']->maxSteps,
            $request['interrupted_checker'],
            $writeContext,
            $flow,
            null,
            $request['run'],
            (int) ($request['conversation_id'] ?? 0),
            (int) ($request['uid'] ?? 0),
            (string) ($request['question'] ?? ''),
            array_merge((array) ($request['decision'] ?? []), [
                // 有 MCP 时暂扣旁白（避免与工具/状态事件抢屏）；显式 write_requested 续跑同理.
                'write_requested' => (bool) ($request['decision']['mcp'] ?? false)
                    || ! empty($request['decision']['write_requested']),
            ]),
            true
        ));

        return $this->finalizeAfterLoop(
            $request,
            $loopResult,
            $request['config'],
            $request['run'],
            markFailedOnReflect: true,
            appendAgentRun: true,
            resumed: false
        );
    }

    /**
     * 信息确认后续跑：跳过完整 Agent 启动，直接续跑工具循环.
     *
     * @param array<string, mixed> $request 续跑用的工具循环请求
     * @param AgentConfig $config Agent 配置
     * @param AgentRun $run 关联的 Run 记录
     *
     * @return array 续跑后的工具循环结果
     */
    public function resume(array $request, AgentConfig $config, AgentRun $run): array
    {
        $stream = $request['stream_service'];
        $loopResult = $this->toolLoopExecutor->execute($request);

        $agentRequest = self::makeRequest(
            $request['app'],
            $request['model'],
            $request['option'],
            $request['tool_service'],
            $stream,
            $request['message_uuid'],
            $request['references'],
            $config,
            ThinDecisionBuilder::makeIntent(AgentEnum::INTENT_ACTION_REQUEST),
            $run,
            $request['interrupted_checker'],
            $request['conversation_id'],
            $request['uid'],
            $request['question'],
            $request['decision']
        );

        return $this->finalizeAfterLoop(
            $agentRequest,
            $loopResult,
            $config,
            $run,
            markFailedOnReflect: false,
            appendAgentRun: false,
            resumed: true
        );
    }

    /**
     * execute / resume 共用收尾：写确认、待填、工具轨迹、反思、completeRun.
     *
     * @param bool $markFailedOnReflect 反思失败时是否将 MESSAGE_DONE 改写为 FAILED（仅首跑）
     * @param bool $appendAgentRun 是否向 trace 追加 agent_run（仅首跑）
     * @param bool $resumed 状态帧是否标记 resumed
     */
    private function finalizeAfterLoop(
        array $request,
        array &$loopResult,
        AgentConfig $config,
        ?AgentRun $run,
        bool $markFailedOnReflect,
        bool $appendAgentRun,
        bool $resumed
    ): array {
        $stream = $request['stream_service'];

        $this->handleWriteConfirmationPending($request, $loopResult);
        AgentInputRequiredHelper::handlePending($request, $loopResult);
        $this->tracer->persistToolTrace($run, $loopResult['trace']);

        // thin-react：evaluate 仅拦截「调了工具却空答」；半截续查/假成功已在 ToolLoop 主循环处理.
        if ($config->enableReflection && ! $loopResult['pending_input'] && ! $loopResult['pending_confirmation']) {
            $stream->status('agent_reflect', '正在校验执行结果');
            $reflection = $this->toolLoopExecutor->evaluate(
                $loopResult['answer'],
                $loopResult['tool_calls']
            );
            $this->tracer->recordStep($run, AgentEnum::STEP_TYPE_REFLECT, [], $reflection);

            if ($markFailedOnReflect
                && $this->toolLoopExecutor->shouldMarkFailed($reflection, $loopResult['status'])
                && (int) $loopResult['status'] === AiConversationEnum::MESSAGE_DONE
                && empty($loopResult['pending_confirmation'])
                && empty($loopResult['pending_input'])) {
                $loopResult = ToolLoopExecutor::makeResult(
                    $loopResult['answer'] !== ''
                        ? $loopResult['answer']
                        : '执行未产生有效回答，请重试或调整问题。',
                    $loopResult['raw'],
                    $loopResult['usage'],
                    AiConversationEnum::MESSAGE_FAILED,
                    $loopResult['tool_calls'],
                    array_merge($loopResult['trace'], [['type' => 'reflect', 'result' => $reflection]]),
                    $loopResult['pending_confirmation']
                );
            }
        }

        $this->tracer->completeRun($run, $loopResult);
        $statusPayload = [
            'run_id'     => (int) ($run->id ?? 0),
            'tool_count' => count($loopResult['tool_calls']),
            'status'     => $loopResult['status'],
        ];
        if ($resumed) {
            $statusPayload['resumed'] = true;
        }
        $pendingWrite = ! empty($loopResult['pending_confirmation']);
        $failed = (int) $loopResult['status'] === AiConversationEnum::MESSAGE_FAILED;
        $stream->status(
            $pendingWrite ? 'agent_awaiting_confirmation' : ($failed ? 'agent_failed' : 'agent_done'),
            $pendingWrite ? '写操作等待确认，数据尚未写入' : ($failed ? 'Agent 执行未成功' : 'Agent 执行完成'),
            $statusPayload
        );
        if (! empty($loopResult['pending_confirmation'])) {
            $stream->complete($request['references']);
        }

        if ($appendAgentRun && $run) {
            $loopResult['trace'][] = [
                'type'   => 'agent_run',
                'run_id' => (int) $run->id,
            ];
        }

        return $loopResult;
    }

    /**
     * 根据请求配置创建写操作守卫上下文.
     */
    /**
     * @return array{
     *   runId: int,
     *   messageUuid: string,
     *   writeConfirmation: bool,
     *   approvedPendingIds: array<int, int>,
     *   question: string,
     *   conversation_id: int,
     *   uid: int
     * }
     */
    private function createWriteContext(array $request): array
    {
        return [
            'runId'               => (int) ($request['run']->id ?? 0),
            'messageUuid'         => $request['message_uuid'],
            'writeConfirmation'   => true,
            'approvedPendingIds'  => [],
            'question'            => (string) ($request['question'] ?? ''),
            'conversation_id'     => (int) ($request['conversation_id'] ?? 0),
            'uid'                 => (int) ($request['uid'] ?? 0),
        ];
    }

    /**
     * 检测工具循环结果中的待确认写操作并推送流式通知.
     */
    private function handleWriteConfirmationPending(array $request, array &$loopResult): void
    {
        foreach ($loopResult['tool_calls'] as $call) {
            if (empty($call['pending_confirmation'])) {
                continue;
            }
            $loopResult['pending_confirmation'] = true;
            $pendingId = (int) ($call['pending_id'] ?? 0);
            $toolName  = (string) ($call['name'] ?? '');
            $this->emitRecognizedSlotsStatus($request, $call);
            $request['stream_service']->confirmRequired([
                $this->buildConfirmActionPayload($request, $pendingId, $toolName, $call),
            ]);
            $this->tracer->markAwaitingConfirmation($request['run']);
            if ($loopResult['answer'] === '') {
                $loopResult['answer'] = '检测到写操作「' . $toolName . '」，需您确认后才会执行。请在确认表单中核对或修改后提交。';
            }

            return;
        }
    }

    /**
     * 推送已自动填入的槽位提示（仅成功写入子集）.
     *
     * @param array<string, mixed> $call
     */
    private function emitRecognizedSlotsStatus(array $request, array $call): void
    {
        $slots = (array) ($call['applied_slots'] ?? ($call['result']['applied_slots'] ?? []));
        if ($slots === []) {
            return;
        }
        $toolName = (string) ($call['name'] ?? '');
        $meta     = WriteFieldLabels::fieldMeta($toolName);
        $parts    = [];
        foreach ($slots as $key => $value) {
            if (! is_scalar($value) || $value === '') {
                continue;
            }
            $key     = (string) $key;
            $label   = (string) ($meta[$key]['label'] ?? $key);
            $display = WriteFieldLabels::displayValue($meta, $key, trim((string) $value));
            $parts[] = $label . $display;
            if (count($parts) >= 8) {
                break;
            }
        }
        if ($parts === []) {
            return;
        }
        $request['stream_service']->status('slot_recognized', '已自动填入：' . implode('、', $parts), [
            'slots' => $slots,
        ]);
    }

    /**
     * 组装 confirm_required 单条 action 载荷.
     *
     * @param array<string, mixed> $call 工具循环返回的 tool call
     *
     * @return array<string, mixed>
     */
    private function buildConfirmActionPayload(
        array $request,
        int $pendingId,
        string $toolName,
        array $call
    ): array {
        $toolArgs = [];
        if ($pendingId > 0 && Schema::hasTable('agent_pending_actions')) {
            $pending = AgentPendingAction::query()->find($pendingId);
            if ($pending) {
                $toolArgs = is_array($pending->tool_args) ? $pending->tool_args : [];
                if ($toolName === '') {
                    $toolName = (string) ($pending->tool_name ?? '');
                }
            }
        }

        $driver = app(AgentWriteFlowDriver::class);
        // 回款等：把 contract_id 灌进 form 订单编号，并写回 pending，避免确认时缺字段.
        $toolArgs = $driver->hydrateCustomerApprovalArgs($toolName, $toolArgs);
        if ($pendingId > 0 && Schema::hasTable('agent_pending_actions')) {
            $pending = AgentPendingAction::query()->find($pendingId);
            if ($pending) {
                $driver->updatePendingArgs($pending, $toolArgs);
            }
        }

        $summary = $this->buildWritePreview($toolName, $toolArgs);
        $message = $summary !== ''
            ? '将执行以下写操作：' . $summary . '。请核对或修改后确认写入。'
            : '写操作「' . ($toolName !== '' ? $toolName : '未知工具') . '」需您确认后才会执行';

        $formFields = $driver->buildEditableFormFields($toolName, $toolArgs);

        return [
            'pending_id'      => $pendingId,
            'tool_name'       => $toolName,
            'tool_args'       => $toolArgs,
            'operation_id'    => $pendingId > 0 ? 'agent_pending_' . $pendingId : '',
            'risk_level'      => 'write',
            'summary'         => $summary,
            'run_id'          => (int) ($request['run']->id ?? 0),
            'message_uuid'    => (string) $request['message_uuid'],
            'message'         => $message,
            'operation_label' => WriteFieldLabels::operationLabel($toolName),
            'fields'          => $this->buildConfirmFields($toolName, $toolArgs),
            'form_mode'       => 'editable',
            'form_fields'     => $formFields,
        ];
    }

    /**
     * 将安全可展示的工具参数整理成结构化字段明细，供前端确认弹窗整体预览.
     *
     * 标签取 WriteFieldLabels（自定义表单优先）；字典类字段把选项值回译成选项文字；
     * 过滤内部 ID 与控制字段，复杂值跳过.
     *
     * @param string $toolName MCP 写工具名
     * @param array<string, mixed> $args 工具参数
     *
     * @return array<int, array{key: string, label: string, value: string}>
     */
    /**
     * 构造写确认预览字段（含审批动态表单标题回译）；重试弹窗复用.
     *
     * @param array<string, mixed> $args
     *
     * @return array<int, array{key: string, label: string, value: string}>
     */
    public function buildConfirmFields(string $toolName, array $args): array
    {
        $driver = app(AgentWriteFlowDriver::class);
        // 回款/开票等：预览审批动态表单已填项（标题来自 approve_form_get），避免只露出 cid/eid 原始键名.
        if ($driver->isApproveFormDrivenTool($toolName)) {
            $schema = $driver->resolveEditableSchemaFields($toolName, $args);
            $form   = is_array($args['form'] ?? null) ? $args['form'] : [];
            $titleByKey = [];
            foreach ($schema as $field) {
                if (! is_array($field)) {
                    continue;
                }
                $key = trim((string) ($field['field'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $titleByKey[$key] = (string) ($field['title'] ?? $field['symbol'] ?? $key);
            }
            $fields = [];
            foreach ($form as $key => $value) {
                if (! is_string($key) || $value === null || $value === '' || is_array($value) || is_object($value)) {
                    continue;
                }
                $text = trim((string) $value);
                if ($text === '') {
                    continue;
                }
                $label = $titleByKey[$key] ?? $key;
                // 选项回译：用 schema options 把 ID 显示成名称.
                foreach ($schema as $field) {
                    if (! is_array($field) || (string) ($field['field'] ?? '') !== $key) {
                        continue;
                    }
                    foreach ((array) ($field['options'] ?? []) as $option) {
                        if (! is_array($option)) {
                            continue;
                        }
                        if ((string) ($option['value'] ?? '') === $text) {
                            $text = (string) ($option['label'] ?? $option['name'] ?? $text);
                            break 2;
                        }
                    }
                }
                $fields[] = [
                    'key'   => $key,
                    'label' => $label !== '' ? $label : $key,
                    'value' => mb_substr($text, 0, 200),
                ];
                if (count($fields) >= 12) {
                    break;
                }
            }
            if ($fields !== []) {
                return $fields;
            }
        }

        $meta   = WriteFieldLabels::fieldMeta($toolName);
        $fields = [];
        foreach ($args as $key => $value) {
            if (! is_string($key) || preg_match('/(?:^|_)id$|^member$|^members$|^form$|^process$/', $key)) {
                continue;
            }
            if ($value === null || $value === '' || is_array($value) || is_object($value)) {
                continue;
            }
            $key     = (string) $key;
            $text    = trim((string) $value);
            if ($text === '') {
                continue;
            }
            $display = WriteFieldLabels::displayValue($meta, $key, $text);
            // eid 等顶层关联：按客户实体回译名称.
            if ($display === $text && in_array($key, ['eid', 'customer_id'], true)) {
                $name = \App\Http\Service\Config\RelationFieldRegistry::displayLabel('eid', $text);
                if ($name !== null && $name !== '') {
                    $display = $name;
                }
            }
            // 漏网观测：纯数字值未走任何回译（字典/关联都未命中），大概率是新增内置关联字段漏配
            if ($display === $text && ctype_digit($text) && ! WriteFieldLabels::isTranslated($meta, $key, $text)) {
                Log::channel((string) config('ai.http_log.channel', 'ai'))->warning('AI写确认字段疑似漏配回译', [
                    'tool_name' => $toolName,
                    'field_key' => $key,
                    'value'     => mb_substr($text, 0, 32),
                ]);
            }
            $fields[] = [
                'key'   => $key,
                'label' => $meta[$key]['label'] ?? $key,
                'value' => mb_substr($display, 0, 200),
            ];
            if (count($fields) >= 12) {
                break;
            }
        }

        return $fields;
    }

    /** 将安全可展示的工具参数整理成确认摘要，过滤内部 ID 和控制字段。 */
    private function buildWritePreview(string $toolName, array $args): string
    {
        $meta  = WriteFieldLabels::fieldMeta($toolName);
        $parts = [];
        foreach ($args as $key => $value) {
            if (! is_string($key) || preg_match('/(?:^|_)id$|^member$|^members$|^form$|^process$/', $key)) {
                continue;
            }
            if ($value === null || $value === '' || is_array($value) || is_object($value)) {
                continue;
            }
            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }
            $key     = (string) $key;
            $display = WriteFieldLabels::displayValue($meta, $key, $text);
            $parts[] = ($meta[$key]['label'] ?? $key) . '：' . mb_substr($display, 0, 120);
        }

        return implode('；', array_slice($parts, 0, 8));
    }
}
