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

use App\Constants\Ai\AgentEnum;
use App\Constants\Ai\AiConversationEnum;
use App\Http\Service\Ai\Agent\AgentWriteFlowDriver;
use App\Http\Dao\Ai\AiConversationMessageDao;
use App\Http\Service\Ai\Agent\WriteFieldLabels;
use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiConversation;
use App\Http\Model\Ai\AiConversationMessage;
use App\Http\Service\Ai\Agent\AgentInputRequiredHelper;
use App\Http\Service\Ai\Agent\AnswerSanitizePipeline;
use App\Http\Service\BaseService;
use App\Http\Controller\Mcp\McpRequest;
use App\Http\Model\Admin\Admin;
use App\Mcp\Context\McpUserContextResolver;
use App\Mcp\ToolRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AI 会话消息记录服务：读写 chat_record 表，对外拆分为 user/assistant 两条消息。
 *
 * 调用链：AiConversationService 流式对话与列表接口 → 本服务 → AiConversationMessageDao；含 MCP 可分页结果提取与翻页回放。
 * 列名 `sql_text` 历史遗留，实际存 message_meta JSON（references/tool_calls/page/agent_trace/agent_intent/pending_input 等），
 * 一律经 decodeMeta / encodeMeta / mergeMeta 读写，勿当纯 SQL 文本解析.
 */
class AiConversationMessageService extends BaseService
{
    /**
     * @param AiConversationMessageDao $dao 消息数据访问对象
     */
    public function __construct(AiConversationMessageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取会话全部消息，每条记录拆成 user 与 assistant 两条。
     *
     * @param AiConversation $conversation 会话模型
     *
     * @return array<int, array<string, mixed>> 消息列表
     */
    public function records(AiConversation $conversation): array
    {
        return $this->dao->search(['conversation_id' => $conversation->id])
            ->orderBy('id')
            ->get()
            ->flatMap(fn ($record) => $this->payload($record))
            ->values()
            ->toArray();
    }

    /**
     * 清空指定会话的全部消息记录。
     *
     * @param AiConversation $conversation 会话模型
     */
    public function clear(AiConversation $conversation): void
    {
        $this->dao->search(['conversation_id' => $conversation->id])->delete();
    }

    /**
     * 获取模型上下文历史，只取已完成的问答对，避免把半截失败回答喂回模型。
     *
     * @param int $conversationId 会话 ID
     * @param int $limit 最多取几轮问答
     * @param int $beforeId 重新生成时截断于此消息 ID 之前
     *
     * @return array<int, array{role: string, content: string}> OpenAI 格式消息
     */
    public function history(int $conversationId, int $limit, int $beforeId = 0): array
    {
        if ($limit <= 0) {
            return [];
        }

        $records = $this->dao->search(['conversation_id' => $conversationId])
            ->when($beforeId > 0, fn ($query) => $query->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'problem_text', 'answer_text'])
            ->reverse()
            ->values()
            ->toArray();

        $messages = [];
        foreach ($records as $record) {
            if (! empty($record['problem_text']) && ! empty($record['answer_text'])) {
                $messages[] = ['role' => AiConversationEnum::ROLE_USER, 'content' => $record['problem_text']];
                $messages[] = ['role' => AiConversationEnum::ROLE_ASSISTANT, 'content' => $record['answer_text']];
            }
        }

        return $messages;
    }

    /**
     * 获取会话最近一条消息记录（用于重新生成）。
     *
     * @param AiConversation $conversation 会话模型
     *
     * @return null|AiConversationMessage 最近一条记录
     */
    public function latestQuestion(AiConversation $conversation): ?AiConversationMessage
    {
        return $this->dao->search(['conversation_id' => $conversation->id])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * 查找重新生成时需覆盖的原消息记录。
     *
     * @param AiConversation $conversation 会话模型
     * @param int $recordId 原消息 ID，0 表示新增
     *
     * @return null|AiConversationMessage 待覆盖记录
     */
    public function replacement(AiConversation $conversation, int $recordId): ?AiConversationMessage
    {
        if ($recordId <= 0) {
            return null;
        }

        return $this->dao->get(['id' => $recordId, 'conversation_id' => $conversation->id]);
    }

    /**
     * 判断指定 message_uuid 是否属于该会话。
     *
     * @param AiConversation $conversation 会话模型
     * @param string $messageUuid 消息 UUID
     */
    public function exists(AiConversation $conversation, string $messageUuid): bool
    {
        return $this->dao->exists([
            'conversation_id' => $conversation->id,
            'message_uuid'    => $messageUuid,
        ]);
    }

    /**
     * 按 message_uuid 查找会话内消息记录。
     *
     * @param AiConversation $conversation 会话模型
     * @param string $messageUuid 消息 UUID
     *
     * @return null|AiConversationMessage 消息记录
     */
    public function findByMessageUuid(AiConversation $conversation, string $messageUuid): ?AiConversationMessage
    {
        return $this->dao->get([
            'chat_history_id'  => $conversation->id,
            'chat_record_uuid' => $messageUuid,
        ]);
    }

    /**
     * 将真实写确认结果转为用户回复，并更新原消息，保留引用与工具轨迹。
     *
     * @param AiConversationMessage $record 原消息记录
     * @param array<string, mixed> $confirmation 确认接口执行结果
     * @param bool $approved 是否批准写入
     * @param array<string, mixed> $toolArgs 已确认的写入参数
     * @param bool $continuing 是否还将续跑后续写步骤（用中间态文案，避免终态记忆）
     * @return string 与历史记录一致的回复正文
     */
    public function saveConfirmedAction(
        AiConversationMessage $record,
        array $confirmation,
        bool $approved,
        array $toolArgs = [],
        bool $continuing = false
    ): string {
        $result = (array) ($confirmation['result'] ?? []);
        $toolName = (string) ($confirmation['tool_name'] ?? '');
        $failed = $approved && ! AgentWriteFlowDriver::writeSucceeded($result);

        if (! $approved) {
            $answer = '已取消本次写入，数据未保存。如需办理，可重新发起该操作。';
        } elseif ($failed) {
            $reason = AgentWriteFlowDriver::writeFailureMessage($result, $toolName);
            $answer = '本次写入未成功：' . $reason . ' 请修改后重新确认写入。';
        } elseif ($continuing) {
            $answer = $this->describeWriteContinuing($toolName, $toolArgs);
        } else {
            $answer = $this->describeWriteSuccess($toolName, $toolArgs);
        }
        $answer = (new AnswerSanitizePipeline())->sanitize($answer);

        $meta = $this->decodeMeta($record);
        $meta['status'] = $failed ? AiConversationEnum::MESSAGE_FAILED : AiConversationEnum::MESSAGE_DONE;
        foreach ($meta['tool_calls'] ?? [] as $index => $call) {
            if ((int) ($call['pending_id'] ?? 0) !== (int) $confirmation['pending_id']) {
                continue;
            }
            $meta['tool_calls'][$index]['pending_confirmation'] = false;
            $meta['tool_calls'][$index]['approved'] = $approved;
            $meta['tool_calls'][$index]['result'] = $result;
            $meta['tool_calls'][$index]['content'] = $answer;
        }
        $this->dao->update((int) $record->id, [
            'answer_text' => $answer,
            'sql_text'    => $this->encodeMeta($meta),
        ]);

        return $answer;
    }

    /**
     * 写确认业务失败后保留等待确认态，并把错误说明写入正文供用户改表重试.
     *
     * @param AiConversationMessage $record 原消息记录
     * @param array<string, mixed> $confirmation confirm() 失败结果
     * @param array<string, mixed> $toolArgs 当前待写入参数
     *
     * @return string 展示给用户的错误说明
     */
    public function saveWriteRetryPrompt(
        AiConversationMessage $record,
        array $confirmation,
        array $toolArgs = []
    ): string {
        $result   = (array) ($confirmation['result'] ?? []);
        $toolName = (string) ($confirmation['tool_name'] ?? '');
        $reason   = AgentWriteFlowDriver::writeFailureMessage($result, $toolName);
        $answer   = (new AnswerSanitizePipeline())->sanitize(
            '本次写入未成功：' . $reason . ' 请修改下方表单后重新确认写入。'
        );

        $meta = $this->decodeMeta($record);
        $meta['status'] = AiConversationEnum::MESSAGE_AWAITING_INPUT;
        foreach ($meta['tool_calls'] ?? [] as $index => $call) {
            if ((int) ($call['pending_id'] ?? 0) !== (int) ($confirmation['pending_id'] ?? 0)) {
                continue;
            }
            $meta['tool_calls'][$index]['pending_confirmation'] = true;
            $meta['tool_calls'][$index]['approved'] = false;
            $meta['tool_calls'][$index]['result'] = $result;
            $meta['tool_calls'][$index]['content'] = $answer;
            $meta['tool_calls'][$index]['tool_args'] = $toolArgs !== []
                ? $toolArgs
                : ($meta['tool_calls'][$index]['tool_args'] ?? []);
        }
        $this->dao->update((int) $record->id, [
            'answer_text' => $answer,
            'sql_text'    => $this->encodeMeta($meta),
        ]);

        return $answer;
    }

    /**
     * 用户放弃补参后把消息改写为取消终态，避免历史停留在等待补参的中间态.
     *
     * @param AiConversationMessage $record 消息记录
     * @param string $answer 取消说明文案
     */
    public function markInputDismissed(AiConversationMessage $record, string $answer): void
    {
        $meta = $this->decodeMeta($record);
        $meta['status'] = AiConversationEnum::MESSAGE_DONE;
        $this->dao->update((int) $record->id, [
            'answer_text' => $answer,
            'sql_text'    => $this->encodeMeta($meta),
        ]);
    }

    /**
     * 判断会话是否存在挂起业务流：窗口内仍待补参，或近期办理过业务操作.
     *
     * 供写续跑/补参判定：按窗口内任一消息回溯，而非仅看最后一条.
     *
     * @param AiConversation $conversation 会话模型
     * @param int $windowMinutes 业务流视为活跃的窗口（分钟）
     *
     * @return bool 存在挂起业务流时为 true
     */
    public function hasActiveBusinessFlow(AiConversation $conversation, int $windowMinutes = 60): bool
    {
        /** @var array<int, AiConversationMessage> $recent */
        $recent = AiConversationMessage::query()
            ->where('chat_history_id', (int) $conversation->id)
            ->where('is_show', 1)
            ->where('updated_at', '>=', now()->subMinutes($windowMinutes))
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->all();
        if ($recent === []) {
            return false;
        }

        foreach ($recent as $message) {
            $meta = $this->decodeMeta($message);
            if ((int) ($meta['status'] ?? 0) === AiConversationEnum::MESSAGE_AWAITING_INPUT) {
                return true;
            }
            if ((string) (($meta['agent_intent']['type'] ?? '')) === AgentEnum::INTENT_ACTION_REQUEST) {
                return true;
            }
        }

        return false;
    }

    /**
     * 将推荐问题补写进消息元数据，供刷新后还原（与 save() 的落库结构一致）.
     *
     * @param AiConversationMessage $record 消息记录
     * @param array<int, string> $suggestions 推荐问题列表
     */
    public function appendSuggestions(AiConversationMessage $record, array $suggestions): void
    {
        if ($suggestions === []) {
            return;
        }
        $meta = $this->decodeMeta($record);
        if (! empty($meta['suggestions'])) {
            return;
        }
        $meta['suggestions'] = array_values(array_map('strval', $suggestions));
        $this->dao->update((int) $record->id, ['sql_text' => $this->encodeMeta($meta)]);
    }

    /**
     * 多步写续跑时的中间态成功文案（非终态）.
     *
     * @param string $toolName MCP 写工具名
     * @param array<string, mixed> $toolArgs 已确认的写入参数
     */
    private function describeWriteContinuing(string $toolName, array $toolArgs): string
    {
        $arg = static fn (string $key): string => trim((string) ($toolArgs[$key] ?? ''));

        return match ($toolName) {
            'customer_create' => ($arg('customer_name') !== '' ? '客户「' . $arg('customer_name') . '」' : '客户')
                . '已创建，继续处理后续步骤…',
            'contact_create' => ($arg('liaison_name') !== '' ? '联系人「' . $arg('liaison_name') . '」' : '联系人')
                . '已创建，继续处理后续步骤…',
            'lead_create' => ($arg('name') !== '' ? '线索「' . $arg('name') . '」' : '线索')
                . '已创建，继续处理后续步骤…',
            'opportunity_create' => ($arg('name') !== '' ? '商机「' . $arg('name') . '」' : '商机')
                . '已创建，继续处理后续步骤…',
            'order_create' => '订单已创建，继续处理后续步骤…',
            default => '数据已写入，继续处理后续步骤…',
        };
    }

    /**
     * 按写工具生成具体的成功文案。
     *
     * 统一的「数据已成功写入」在会话历史与长期记忆里等同于没有信息，模型后续既
     * 无法引用写了什么，也无法判断该不该重复写；故按工具名回填关键业务字段。
     * 按 ChatMessageBuilderService 的约定，一律不回报记录 ID。
     *
     * @param string $toolName MCP 写工具名
     * @param array<string, mixed> $toolArgs 已确认的写入参数
     *
     * @return string 面向用户的成功文案
     */
    private function describeWriteSuccess(string $toolName, array $toolArgs): string
    {
        $arg = static fn (string $key): string => trim((string) ($toolArgs[$key] ?? ''));

        return match ($toolName) {
            'customer_create' => (($arg('customer_name') !== '' ? '客户「' . $arg('customer_name') . '」' : '客户')
                . '已创建成功，您可以在客户列表中查看。')
                . $this->appendSubmittedFields($toolName, $toolArgs, ['customer_name']),
            'contact_create' => (($arg('liaison_name') !== '' ? '联系人「' . $arg('liaison_name') . '」' : '联系人')
                . '已创建成功，您可以在客户详情的联系人中查看。')
                . $this->appendSubmittedFields($toolName, $toolArgs, ['liaison_name']),
            'lead_create' => (($arg('name') !== '' ? '线索「' . $arg('name') . '」' : '线索')
                . '已创建成功，您可以在线索列表中查看。')
                . $this->appendSubmittedFields($toolName, $toolArgs, ['name']),
            'opportunity_create' => (($arg('name') !== '' ? '商机「' . $arg('name') . '」' : '商机')
                . '已创建成功，您可以在商机列表中查看。')
                . $this->appendSubmittedFields($toolName, $toolArgs, ['name']),
            'order_create' => '订单已创建成功，您可以在订单列表中查看。',
            'schedule_create' => $this->describeScheduleCreated($arg('title'), $arg('start_time')),
            'schedule_batch_create' => '已批量创建 ' . count((array) ($toolArgs['items'] ?? [])) . ' 条日程，您可以在日程中查看。',
            'schedule_complete' => '该日程已标记为完成。',
            'report_create' => $this->reportTypeName((int) ($toolArgs['types'] ?? 0)) . '已提交，您可以在工作汇报中查看。',
            'follow_up_create', 'follow_up_create', 'follow_up_create' => $this->describeFollowUpCreated($arg('content')),
            'assess_self_eval'  => '绩效自评已提交，您可以在绩效考核中查看。',
            'payment_create'      => '回款记录已提交，后续进度可在客户账目中查看。',
            'renewal_create'      => '续费记录已提交，后续进度可在客户账目中查看。',
            'expense_create'      => '支出记录已提交，后续进度可在客户账目中查看。',
            'invoice_apply'       => '开票申请已提交，后续进度可在发票管理中查看。',
            'invoice_void_apply'  => '作废发票申请已提交，后续进度可在发票管理中查看。',
            'approve_create'    => ($arg('name') !== '' ? '审批「' . $arg('name') . '」' : '审批')
                . '已提交，您可以在我发起的审批中查看进度。',
            default => '数据已成功写入。',
        };
    }

    /** 拼接日程创建成功文案，有开始时间时一并给出。 */
    private function describeScheduleCreated(string $title, string $startTime): string
    {
        $answer = $title !== '' ? '日程「' . $title . '」已创建成功' : '日程已创建成功';
        if ($startTime !== '') {
            $answer .= '，开始时间 ' . $startTime;
        }

        return $answer . '，您可以在日程中查看。';
    }

    /** 拼接跟进记录成功文案，附带内容摘要便于后续轮次引用。 */
    private function describeFollowUpCreated(string $content): string
    {
        $summary = mb_substr($content, 0, 30);

        return $summary !== ''
            ? '跟进记录已保存：' . $summary . (mb_strlen($content) > 30 ? '…' : '')
            : '跟进记录已保存。';
    }

    /**
     * 拼接写操作已提交字段的回显摘要，便于用户核对 AI 实际落库内容.
     *
     * 标签取 WriteFieldLabels（租户自定义表单优先），字典类字段把选项值回译成选项文字；
     * 主名称与内部键不重复回显，复杂值跳过.
     *
     * @param string $toolName MCP 写工具名
     * @param array<string, mixed> $toolArgs 已提交的写入参数
     * @param array<int, string> $excludeKeys 已在主文案中体现、无需重复回显的参数键
     *
     * @return string 形如「，已录入信息：电话 158…、客户来源 线上推广」的后缀；无可回显字段时为空串
     */
    private function appendSubmittedFields(string $toolName, array $toolArgs, array $excludeKeys): string
    {
        $meta  = WriteFieldLabels::fieldMeta($toolName);
        $skip  = array_merge($excludeKeys, ['id', 'uid', 'eid', 'link_id']);
        $items = [];
        foreach ($toolArgs as $key => $value) {
            $key = (string) $key;
            if (in_array($key, $skip, true) || $value === '' || $value === null || ! is_scalar($value)) {
                continue;
            }
            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }
            $text = WriteFieldLabels::displayValue($meta, $key, $text);
            if (mb_strlen($text) > 50) {
                $text = rtrim(mb_substr($text, 0, 50)) . '…';
            }
            $items[] = ($meta[$key]['label'] ?? $key) . ' ' . $text;
            if (count($items) >= 6) {
                break;
            }
        }

        return $items === [] ? '' : '，已录入信息：' . implode('、', $items);
    }

    /** 汇报类型数值转中文名称（0=日报 1=周报 2=月报 3=汇报）。 */
    private function reportTypeName(int $types): string
    {
        return match ($types) {
            1       => '周报',
            2       => '月报',
            3       => '工作汇报',
            default => '日报',
        };
    }

    /**
     * 保存一轮对话；重新生成时更新原记录，普通提问时新增记录。
     *
     * @param AiConversation $conversation 会话模型
     * @param AiApplication $app 应用模型
     * @param string $messageUuid 消息 UUID
     * @param string $question 用户问题
     * @param string $answer 助手回答
     * @param string $raw 模型原始响应
     * @param array<int, array<string, mixed>> $references 知识库引用
     * @param array<int, array<string, mixed>> $toolCalls 工具调用记录
     * @param int $runTime 耗时（毫秒）
     * @param int $status 消息状态
     * @param null|AiConversationMessage $replaceRecord 重新生成时覆盖的原记录
     * @param array<string, int> $usage Token 用量
     * @param array<int, array<string, mixed>> $agentTrace Agent 追踪信息
     * @param array<int, string> $suggestions 推荐问题（写入元数据，刷新后可还原）
     * @param array<string, mixed> $agentIntent 意图快照（type/confidence/source/modules 等）
     *
     * @return AiConversationMessage 保存后的消息记录
     */
    public function save(
        AiConversation $conversation,
        AiApplication $app,
        string $messageUuid,
        string $question,
        string $answer,
        string $raw,
        array $references,
        array $toolCalls,
        int $runTime,
        int $status,
        ?AiConversationMessage $replaceRecord = null,
        array $usage = [],
        array $agentTrace = [],
        array $suggestions = [],
        array $agentIntent = []
    ): AiConversationMessage {
        $sqlMeta = [
            'status'     => $status,
            'references' => $references,
            'tool_calls' => $toolCalls,
        ];
        if ($suggestions !== []) {
            $sqlMeta['suggestions'] = $suggestions;
        }
        $images = app(\App\Http\Service\Knowledge\KnowledgeRagService::class)->collectReplyImages($references);
        if ($images !== []) {
            $sqlMeta['images'] = $images;
        }
        if ($agentTrace !== []) {
            $sqlMeta['agent_trace'] = $agentTrace;
        }
        if ($agentIntent !== []) {
            $sqlMeta['agent_intent'] = $agentIntent;
        }
        $pageMeta = $this->extractPageableFromToolCalls($toolCalls);
        if ($pageMeta !== null) {
            $sqlMeta = array_merge($sqlMeta, $pageMeta);
        }
        $data = [
            'chat_record_uuid'     => $messageUuid,
            'chat_history_id'      => $conversation->id,
            'uid'                  => $conversation->uid,
            'chat_applications_id' => $app->id,
            'problem_text'         => $question,
            'answer_text'          => $answer,
            'sql_text'             => $this->encodeMeta($sqlMeta),
            'details'           => $raw,
            'prompt_tokens'     => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'tokens'            => $this->resolveTotalTokens($usage),
            'run_time'          => $runTime,
            'is_show'           => 1,
        ];

        if ($replaceRecord) {
            $this->dao->update((int) $replaceRecord->id, $data);
            return $this->dao->get((int) $replaceRecord->id);
        }

        return $this->dao->create($data);
    }

    /**
     * 将 Agent 运行 ID 写入消息元数据（sql_text 列）.
     *
     * @param AiConversationMessage $record 消息记录
     * @param int $agentRunId Agent 运行 ID
     *
     * @return AiConversationMessage 更新后的消息记录
     */
    public function attachAgentRun(AiConversationMessage $record, int $agentRunId): AiConversationMessage
    {
        if ($agentRunId <= 0) {
            return $record;
        }

        $this->dao->update((int) $record->id, [
            'sql_text' => $this->encodeMeta($this->mergeMeta($record, ['agent_run_id' => $agentRunId])),
        ]);

        return $this->dao->get((int) $record->id);
    }

    /**
     * 将待用户确认的信息项写入消息元数据，供前端渲染表单。
     *
     * @param AiConversationMessage $record 消息记录
     * @param int $pendingInputId 首个待确认项 ID
     * @param array<int, int> $pendingInputIds 全部待确认项 ID
     *
     * @return AiConversationMessage 更新后的消息记录
     */
    public function attachPendingInput(AiConversationMessage $record, int $pendingInputId, array $pendingInputIds = []): AiConversationMessage
    {
        if ($pendingInputId <= 0) {
            return $record;
        }

        $helper = app(AgentInputRequiredHelper::class);
        $ids = $pendingInputIds !== [] ? $pendingInputIds : [$pendingInputId];
        $pendingInputs = [];
        foreach ($ids as $id) {
            $pending = $helper->findPending((int) $id);
            if ($pending) {
                $pendingInputs[] = $helper->formatStreamPayload($pending);
            }
        }
        $this->dao->update((int) $record->id, [
            'sql_text' => $this->encodeMeta($this->mergeMeta($record, [
                'pending_input_id'  => $pendingInputId,
                'pending_input_ids' => $ids,
                'pending_inputs'    => $pendingInputs,
            ])),
        ]);

        return $this->dao->get((int) $record->id);
    }

    /**
     * 将单条 chat_record 拆分为 user 与 assistant 两条对外消息。
     *
     * @param AiConversationMessage|mixed $record 消息记录
     *
     * @return array<int, array<string, mixed>> 含两条消息的数组
     */
    public function payload($record): array
    {
        $meta = $this->decodeMeta($record);

        return [
            [
                'id'              => (int) $record->id,
                'conversation_id' => (int) $record->chat_history_id,
                'message_uuid'    => (string) $record->chat_record_uuid,
                'role'            => AiConversationEnum::ROLE_USER,
                'content'         => (string) $record->problem_text,
                'status'          => AiConversationEnum::MESSAGE_DONE,
                'created_at'      => $record->created_at,
                'updated_at'      => $record->updated_at,
            ],
            [
                'id'                => (int) $record->id,
                'conversation_id'   => (int) $record->chat_history_id,
                'message_uuid'      => (string) $record->chat_record_uuid,
                'role'              => AiConversationEnum::ROLE_ASSISTANT,
                'content'           => (string) $record->answer_text,
                'raw_response'      => (string) $record->details,
                'references'        => (array) ($meta['references'] ?? []),
                'images'            => (array) ($meta['images'] ?? []),
                'tool_calls'        => (array) ($meta['tool_calls'] ?? []),
                'suggestions'       => (array) ($meta['suggestions'] ?? []),
                'sql_text'          => $meta,
                'is_page'           => $this->isPageableMeta($meta),
                'prompt_tokens'     => (int) $record->prompt_tokens,
                'completion_tokens' => (int) $record->completion_tokens,
                'tokens'            => (int) $record->tokens,
                'run_time'          => (int) $record->run_time,
                'status'            => (int) ($meta['status'] ?? AiConversationEnum::MESSAGE_DONE),
                'created_at'        => $record->created_at,
                'updated_at'        => $record->updated_at,
            ],
        ];
    }

    /**
     * 兼容旧版 SQL 查询记录，并支持 MCP 可分页结果（如日程汇总）按页取表格数据.
     *
     * @param string $uuid 消息 UUID
     * @param int $uid 用户 ID
     *
     * @return array<string, mixed> 含 list、count 与 table_fields 的分页数据
     *
     * @throws \Throwable 记录不存在、SQL 失效或查询失败
     */
    public function getRecord(string $uuid, int $uid): array
    {
        $record = $this->dao->get(['chat_record_uuid' => $uuid, 'uid' => $uid]);
        if (! $record) {
            throw $this->exception('没有查询到该对话记录');
        }

        $meta = $this->decodeMeta($record);
        if (! $this->isPageableMeta($meta)) {
            throw $this->exception('该对话记录的SQL语句已失效，请重新对话');
        }

        [$page, $limit] = $this->getPageValue();

        // MCP / 业务工具分页：按 page_source 回放.
        if ((string) ($meta['page_source'] ?? '') !== '') {
            try {
                $fetched = $this->fetchPageablePage($meta, $uid, (int) $page, (int) $limit);

                return $this->listData(
                    $fetched['list'],
                    $fetched['count'],
                    ['table_fields' => $this->tableFields((array) $fetched['table_fields'])]
                );
            } catch (\Throwable $e) {
                throw $this->exception('查询失败，请重新对话:' . $e->getMessage());
            }
        }

        if (empty($meta['list_sql']) || empty($meta['page_sql']) || empty($meta['table_fields'])) {
            throw $this->exception('该对话记录的SQL语句已失效，请重新对话');
        }

        $pattern = '/LIMIT\s+\d+(?:\s*,\s*\d+)?(?:\s+OFFSET\s+\d+)?(?!\$\{)/i';
        $listSql = preg_replace($pattern, '', (string) $meta['list_sql'], 1);
        $listSql = str_replace(['${page}', '${limit}', '${user_id}'], [($page - 1) * $limit, $limit, (string) $uid], $listSql);

        try {
            $list  = DB::select($listSql);
            $count = $meta['page_sql'] === 'new'
                ? (int) ($meta['totalNum'] ?? 0)
                : (int) (((array) DB::select((string) $meta['page_sql'])[0])['count_nums'] ?? 0);

            return $this->listData($list, $count, ['table_fields' => $this->tableFields((array) $meta['table_fields'])]);
        } catch (\Throwable $e) {
            throw $this->exception('查询失败，请重新对话:' . $e->getMessage());
        }
    }

    /**
     * 统计用户当日在指定应用下发出的消息数（用于日限额）。
     *
     * @param int $uid 用户 ID
     * @param int $appId 应用 ID
     * @return int 当日消息数
     */
    public function dailyCount(int $uid, int $appId): int
    {
        return $this->dao->search(['uid' => $uid, 'chat_applications_id' => $appId])
            ->whereDate('created_at', date('Y-m-d'))
            ->count();
    }

    /**
     * 统计指定应用下全部访客当日发出的 user 消息数（跨访客共享日配额）。
     *
     * @param int $appId 应用 ID
     * @return int 当日访客消息总数
     */
    public function guestAppDailyCount(int $appId): int
    {
        if ($appId <= 0) {
            return 0;
        }

        $conversationIds = AiConversation::query()
            ->where('chat_application_id', $appId)
            ->where('session_type', AiConversationEnum::SESSION_TYPE_GUEST)
            ->pluck('id');

        if ($conversationIds->isEmpty()) {
            return 0;
        }

        return $this->dao->search(['chat_applications_id' => $appId])
            ->whereIn('chat_history_id', $conversationIds)
            ->where('problem_text', '<>', '')
            ->whereDate('created_at', date('Y-m-d'))
            ->count();
    }

    /**
     * 解析 sql_text 列中的 message_meta JSON（兼容旧数据与解码失败）.
     *
     * @param AiConversationMessage|mixed $record 消息记录
     *
     * @return array<string, mixed>
     */
    public function decodeMeta($record): array
    {
        $raw = (string) ($record->sql_text ?? '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 将 message_meta 编码为写入 sql_text 列的 JSON 字符串.
     *
     * @param array<string, mixed> $meta
     */
    public function encodeMeta(array $meta): string
    {
        return json_encode($meta, JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /**
     * 从意图与编排决策提取可落库快照（准确度可观测）.
     *
     * @param array<string, mixed> $intent
     * @param array<string, mixed> $decision
     *
     * @return array<string, mixed>
     */
    public static function intentSnapshot(array $intent, array $decision = []): array
    {
        if ($intent === []) {
            return [];
        }

        $meta = (array) ($intent['meta'] ?? []);
        $snapshot = [
            'type'          => (string) ($intent['type'] ?? ''),
            'confidence'    => round((float) ($intent['confidence'] ?? 0), 4),
            'source'        => (string) ($meta['source'] ?? ''),
            'operation'     => (string) ($meta['operation'] ?? ''),
            'modules'       => array_values(array_filter(array_map('strval', (array) ($meta['modules'] ?? [])))),
            'skills'        => array_values(array_filter(array_map('strval', (array) ($meta['skills'] ?? [])))),
            'answer_policy' => (string) ($decision['answer_policy'] ?? ''),
        ];

        return array_filter(
            $snapshot,
            static fn ($value): bool => $value !== '' && $value !== [] && $value !== 0.0
        );
    }

    /**
     * 合并 message_meta 补丁（先 decode 再覆盖键）.
     *
     * @param AiConversationMessage|mixed $record 消息记录
     * @param array<string, mixed> $patch 待合并字段
     *
     * @return array<string, mixed>
     */
    public function mergeMeta($record, array $patch): array
    {
        return array_merge($this->decodeMeta($record), $patch);
    }

    /** 将 table_fields 规范化为列名映射表。 */
    private function tableFields(array $fields): array
    {
        $tableFields = [];
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $tableFields[$k] = $k;
                }
            } else {
                $tableFields[$key] = $value;
            }
        }

        return $tableFields;
    }
    // --- 可分页结果（原 AiPageableResultBuilder）---

    /**
     * 从工具调用轨迹提取可分页元数据（写入消息 message_meta / sql_text 列）.
     *
     * @param array<int, array<string, mixed>> $toolCalls
     *
     * @return null|array<string, mixed>
     */
    public function extractPageableFromToolCalls(array $toolCalls): ?array
    {
        for ($i = count($toolCalls) - 1; $i >= 0; --$i) {
            $result = $toolCalls[$i]['result'] ?? null;
            if (! is_array($result) || ! empty($result['error'])) {
                continue;
            }
            $meta = $this->pageableMetaFromResult($result);
            if ($meta !== null) {
                return $meta;
            }
        }

        return null;
    }

    /**
     * 是否为可手动翻页的消息元数据（含旧 SQL list_sql）.
     *
     * @param array<string, mixed> $meta
     */
    public function isPageableMeta(array $meta): bool
    {
        if (! empty($meta['list_sql'])
            && ! empty($meta['page_sql'])
            && ! empty($meta['table_fields'])
            && str_contains((string) $meta['list_sql'], '${page}')
        ) {
            return true;
        }

        return (string) ($meta['page_source'] ?? '') !== ''
            && ! empty($meta['table_fields'])
            && (int) ($meta['totalNum'] ?? 0) > 0
            && is_array($meta['page_query'] ?? null);
    }

    /**
     * 按消息元数据分页取表格行（供 getRecord）.
     *
     * @param array<string, mixed> $meta
     *
     * @return array{list: array<int, array<string, mixed>>, count: int, table_fields: array<string, string>}
     */
    public function fetchPageablePage(array $meta, int $uid, int $page, int $limit): array
    {
        $tool = (string) ($meta['page_source'] ?? '');
        $args = (array) ($meta['page_query'] ?? []);
        if ($tool === '' || $args === []) {
            throw new \InvalidArgumentException('分页元数据缺失');
        }

        $args['page']  = max(1, $page);
        $args['limit'] = max(1, min(100, $limit));

        $instance = ToolRegistry::getTool($tool);
        if ($instance === null) {
            throw new \InvalidArgumentException('分页数据源工具不存在: ' . $tool);
        }

        $this->bindPageableMcpUser($uid);
        $result = $instance->execute($args);
        if (! is_array($result) || ! empty($result['error'])) {
            $message = is_array($result) ? (string) ($result['message'] ?? '查询失败') : '查询失败';
            throw new \RuntimeException($message);
        }

        $items = $result['items'] ?? null;
        if (! is_array($items)) {
            $items = [];
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $rows[] = $this->projectPageableRow($item, (array) ($meta['table_fields'] ?? []));
        }

        $count = (int) ($meta['totalNum'] ?? ($result['total'] ?? count($rows)));

        return [
            'list'         => $rows,
            'count'        => $count,
            'table_fields' => (array) ($meta['table_fields'] ?? []),
        ];
    }

    /**
     * 解析落库总 token：优先 tokens/total_tokens，否则用 prompt+completion.
     *
     * @param array<string, mixed> $usage Token 用量
     */
    private function resolveTotalTokens(array $usage): int
    {
        $prompt     = (int) ($usage['prompt_tokens'] ?? 0);
        $completion = (int) ($usage['completion_tokens'] ?? 0);
        $total      = (int) ($usage['tokens'] ?? $usage['total_tokens'] ?? 0);
        if ($total <= 0) {
            $total = $prompt + $completion;
        }

        return max(0, $total);
    }

    /**
     * 绑定消息所属用户到 MCP 上下文，供翻页回放工具.
     */
    private function bindPageableMcpUser(int $uid): void
    {
        /** @var null|Admin $user */
        $user = Admin::query()->find($uid);
        if (! $user) {
            throw new \RuntimeException('分页回放用户不存在');
        }
        $request = Request::create('/mcp', 'POST');
        app(McpUserContextResolver::class)->merge($request, $user);
        app()->instance(McpRequest::class, new McpRequest($request));
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return null|array<string, mixed>
     */
    private function pageableMetaFromResult(array $result): ?array
    {
        $pageable = $result['pageable'] ?? null;
        if (! is_array($pageable)) {
            return null;
        }

        $tool    = (string) ($pageable['tool'] ?? '');
        $args    = $pageable['arguments'] ?? null;
        $columns = $pageable['columns'] ?? null;
        if ($tool === '' || ! is_array($args) || ! is_array($columns) || $columns === []) {
            return null;
        }

        $items = $result['items'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }
        $total = (int) ($result['total'] ?? 0);
        if ($total <= 0 || $total <= count($items)) {
            return null;
        }

        return [
            'page_source'  => $tool,
            'page_query'   => $args,
            'table_fields' => $columns,
            'totalNum'     => $total,
            'page_sql'     => 'mcp',
        ];
    }

    /**
     * @param array<string, mixed>  $item
     * @param array<string, string> $columns
     *
     * @return array<string, mixed>
     */
    private function projectPageableRow(array $item, array $columns): array
    {
        if ($columns === []) {
            return $item;
        }
        $row = [];
        foreach (array_keys($columns) as $key) {
            $row[$key] = $item[$key] ?? '';
        }

        return $row;
    }

}
