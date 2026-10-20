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

use App\Constants\Ai\AiConversationEnum;
use App\Http\Contract\Runtime\RequestScopedResettable;
use App\Http\Service\Ai\Agent\AnswerSanitizePipeline;
use App\Http\Service\Ai\Guest\AiGuestContentGuard;
use Tuoluojiang\Open\services\ai\BaseCurl;
use Tuoluojiang\Open\services\ai\BaseOption;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Response;

/**
 * AI 流式输出服务：转发模型 SSE、聚合回答内容，并通过缓存支持中断。
 *
 * 调用链：AiConversationService/AgentExecutor → 本服务 → BaseCurl/Swoole Response；面向前端推送 SSE 事件。
 * 实现 RequestScopedResettable：Swoole 请求结束由 RequestStateCleaner 清空访客过滤器与工具宣告缓存。
 */
class AiStreamService implements RequestScopedResettable
{
    /**
     * 上游流式分片中前端不消费的协议信封字段。
     *
     * usage 仅在服务端聚合，不随 SSE 下发。
     *
     * @var list<string>
     */
    private const UNUSED_STREAM_FIELDS = [
        'id',
        'object',
        'created',
        'model',
        'service_tier',
        'system_fingerprint',
        'usage',
    ];

    /** @var array<string, true> */
    private array $announcedToolCalls = [];

    /**
     * 访客模式流式输出敏感词过滤器状态，须在 finally 中重置为 null.
     *
     * @var array{words?: array<int, string>, max_word_length?: int, replacement?: string, buffer?: string}|null
     */
    private ?array $guestOutputFilter = null;

    /**
     * 请求级日志元数据（意图/allowlist），供 AI HTTP 体积拆分日志使用.
     *
     * @var array<string, mixed>
     */
    private array $requestMeta = [];

    /**
     * 绑定访客流式输出过滤器（Swoole 长生命周期下请求结束须重置）.
     *
     * @param array{words?: array<int, string>, max_word_length?: int, replacement?: string, buffer?: string}|null $state
     */
    public function setGuestOutputFilter(?array $state): void
    {
        $this->guestOutputFilter = $state;
    }

    /**
     * 设置本轮流式请求的观测元数据（intent / allowlist 等）.
     *
     * @param array<string, mixed> $meta
     */
    public function setRequestMeta(array $meta): void
    {
        $this->requestMeta = array_merge($this->requestMeta, $meta);
    }

    /**
     * 清空请求级状态，供 RequestStateCleaner 在请求间隙调用.
     */
    public function resetRequestState(): void
    {
        $this->guestOutputFilter = null;
        $this->announcedToolCalls = [];
        $this->requestMeta = [];
    }

    /**
     * 发起模型流式请求，聚合回答、工具调用与用量，并转发 SSE 分片。
     *
     * @param string $apiKey 模型 API Key
     * @param BaseOption $option 模型请求参数（须开启 stream）
     * @param string $messageUuid 消息 UUID，用于中断标记
     * @param array<int, array<string, mixed>> $references 知识库引用
     * @param bool $finalize 是否在结束时推送 references 与 [DONE]
     *
     * @return array{answer: string, raw: string, usage: array<string, mixed>, status: int, tool_calls: array<int, array<string, mixed>>, content_held: bool, content_streamed: bool} 聚合结果
     */
    public function stream(string $apiKey, BaseOption $option, string $messageUuid, array $references = [], bool $finalize = true): array
    {
        $answer       = '';
        $reasoning    = '';
        $usage        = [];
        $toolCalls    = [];
        $interruptKey = $messageUuid . ':interrupted';
        $buffer       = '';
        $errorMessage = '';
        $textParser   = app(AiToolCallService::class);
        $pipeline     = app(AnswerSanitizePipeline::class);
        $streamState  = $pipeline->createStreamState();
        $citationState = &$streamState['citation'];
        $chartState    = &$streamState['chart'];
        $leakSuppress  = &$streamState['leak_suppress'];
        $leakProbe     = '';
        $toolCallSuppress = false;
        $toolCallHoldBuf  = '';
        // 工具轮次：正文可能是「下一步计划」旁白，暂不推给用户，避免思考文案与工具状态并行刷屏.
        $holdUserContent = $this->shouldHoldUserContent($option);
        // 暂扣缓冲达到阈值即放闸实时转发；若随后又出现 tool_calls，撤回已直播内容并重新暂扣.
        $holdReleaseChars = max(0, (int) config('ai.streaming.hold_release_chars', 80));
        $holdReleased     = false;
        $holdBuf          = '';
        $startedAt        = microtime(true);
        $streamMetrics    = [
            'callback_count'      => 0,
            'event_count'         => 0,
            'forward_count'       => 0,
            'bytes'               => 0,
            'forward_bytes'       => 0,
            'first_callback_ms'   => null,
            'first_content_ms'    => null,
            'first_forward_ms'    => null,
            'max_callback_gap_ms' => 0,
            'last_callback_at'    => null,
        ];
        $this->logHttpRequestStart($option, $messageUuid);
        // 多轮/恢复会话时，历史 tool_calls.arguments 若为 "[]" 会触发上游校验失败，发请求前统一纠正.
        $this->sanitizeMessageToolCallArguments($option);

        try {
            $raw = (new BaseCurl($apiKey))->setBody($option)->stream(
                url: $option->url,
                stream: function (string $data) use (
                    &$answer,
                    &$reasoning,
                    &$usage,
                    &$buffer,
                    &$errorMessage,
                    &$toolCalls,
                    &$leakSuppress,
                    &$leakProbe,
                    &$toolCallSuppress,
                    &$toolCallHoldBuf,
                    &$holdReleased,
                    &$holdBuf,
                    $holdReleaseChars,
                    $pipeline,
                    &$chartState,
                    &$citationState,
                    &$streamMetrics,
                    $startedAt,
                    $holdUserContent
                ): void {
                    $callbackAt = microtime(true);
                    ++$streamMetrics['callback_count'];
                    $streamMetrics['bytes'] += strlen($data);
                    if ($streamMetrics['first_callback_ms'] === null) {
                        $streamMetrics['first_callback_ms'] = (int) round(($callbackAt - $startedAt) * 1000);
                    }
                    if ($streamMetrics['last_callback_at'] !== null) {
                        $streamMetrics['max_callback_gap_ms'] = max(
                            (int) $streamMetrics['max_callback_gap_ms'],
                            (int) round(($callbackAt - $streamMetrics['last_callback_at']) * 1000)
                        );
                    }
                    $streamMetrics['last_callback_at'] = $callbackAt;

                    foreach ($this->payloads($data, $buffer) as $payload) {
                        if ($payload === '' || $payload === '[DONE]') {
                            continue;
                        }
                        ++$streamMetrics['event_count'];
                        $json = json_decode($payload, true, 512, JSON_BIGINT_AS_STRING);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            continue;
                        }
                        $errorMessage = $this->errorMessage($json);
                        if ($errorMessage !== '') {
                            continue;
                        }
                        $this->mergeToolCalls($toolCalls, $json);
                        $this->announceToolCalls($toolCalls);
                        // 放闸后本轮又出现工具调用：撤回已直播正文（按旁白处理）并重新暂扣.
                        if ($toolCalls !== [] && $holdReleased) {
                            $holdReleased = false;
                            $holdBuf      = '';
                            $this->resetUserContent();
                        }
                        $content = $this->extractContent($json);
                        $think   = $this->extractReasoning($json);
                        $filteredContent = '';
                        $answerLenBefore = mb_strlen($answer);
                        if ($content !== '') {
                            if ($streamMetrics['first_content_ms'] === null) {
                                $streamMetrics['first_content_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                            }
                            // Delivery：Citation → Guest，再累计；InternalLeak/Chart 在转发前处理.
                            $chunk = $pipeline->takeCitationForwardable($content, $citationState);
                            if ($this->guestOutputFilter !== null) {
                                $chunk = AiGuestContentGuard::filterOutputChunk($this->guestOutputFilter, $chunk);
                            }
                            // 工具调用标记：跨分片暂扣半截前缀；一旦确认出现则截断并停止继续转发.
                            if ($toolCallSuppress) {
                                $filteredContent = '';
                            } elseif ($chunk !== '' || $toolCallHoldBuf !== '') {
                                $incoming        = $toolCallHoldBuf . $chunk;
                                $toolCallHoldBuf = '';
                                $probe           = $answer . $incoming;
                                $cut             = $pipeline->findToolCallArtifactStart($probe);
                                if ($cut !== null) {
                                    $toolCallSuppress = true;
                                    $filteredContent  = $cut > mb_strlen($answer)
                                        ? mb_substr($probe, mb_strlen($answer), $cut - mb_strlen($answer))
                                        : '';
                                    $answer = mb_substr($probe, 0, $cut);
                                } else {
                                    $hold = $pipeline->toolCallHoldSuffix($probe);
                                    if ($hold !== '') {
                                        $releaseLen      = mb_strlen($probe) - mb_strlen($hold);
                                        $toolCallHoldBuf = $hold;
                                        if ($releaseLen > mb_strlen($answer)) {
                                            $filteredContent = mb_substr(
                                                $probe,
                                                mb_strlen($answer),
                                                $releaseLen - mb_strlen($answer)
                                            );
                                            $answer = mb_substr($probe, 0, $releaseLen);
                                        }
                                    } else {
                                        $filteredContent = $incoming;
                                        $answer .= $incoming;
                                    }
                                }
                            }
                        }
                        if ($think !== '') {
                            if ($streamMetrics['first_content_ms'] === null) {
                                $streamMetrics['first_content_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                            }
                            $reasoning .= $think;
                        }
                        if (! empty($json['usage']) && is_array($json['usage'])) {
                            $usage = self::normalizeUsage($json['usage']);
                        }
                        // 编排策略/能力优先级等内部规则一旦出现，截断其后正文并停止继续转发.
                        if ($leakSuppress && $filteredContent !== '') {
                            $filteredContent = '';
                            if ($think === '') {
                                continue;
                            }
                        } elseif ($filteredContent !== '') {
                            // 只探测最近尾部；命中后再对完整答案定位，避免每个分片重复扫描全文.
                            $leakProbe = mb_substr($leakProbe . $filteredContent, -128);
                            if ($pipeline->containsInternalLeak($leakProbe)) {
                                $cut = $pipeline->findInternalLeakStart($answer);
                                if ($cut !== null) {
                                    $leakSuppress = true;
                                    $filteredContent = $cut <= $answerLenBefore
                                        ? ''
                                        : mb_substr($filteredContent, 0, $cut - $answerLenBefore);
                                    if ($filteredContent === '' && $think === '') {
                                        continue;
                                    }
                                }
                            }
                        }
                        if ($holdUserContent && ! $holdReleased) {
                            // 暂扣期：正文先过图表门禁再进缓冲，reasoning 照常实时转发.
                            if ($toolCalls === [] && $filteredContent !== '') {
                                $holdBuf .= $pipeline->takeChartForwardable($filteredContent, $chartState);
                                if ($holdBuf !== '' && mb_strlen($holdBuf) >= $holdReleaseChars) {
                                    // 达到阈值仍无工具调用迹象：放闸补推缓冲，后续分片转入实时转发.
                                    $holdReleased = true;
                                    $payload      = $this->encodeContentDelta($holdBuf);
                                    $holdBuf      = '';
                                    $this->forwardModelChunk($payload);
                                    ++$streamMetrics['forward_count'];
                                    $streamMetrics['forward_bytes'] += strlen($payload);
                                    if ($streamMetrics['first_forward_ms'] === null) {
                                        $streamMetrics['first_forward_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                                    }
                                }
                            }
                            if ($think !== '') {
                                $payload = $this->contentPayload($json, '', $think);
                                $this->forwardModelChunk($payload);
                                ++$streamMetrics['forward_count'];
                                $streamMetrics['forward_bytes'] += strlen($payload);
                                if ($streamMetrics['first_forward_ms'] === null) {
                                    $streamMetrics['first_forward_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                                }
                            }
                            continue;
                        }
                        if (! $this->shouldForwardChunk($json) && $filteredContent === '' && $think === '') {
                            continue;
                        }
                        $payload = null;
                        // 无效/遗留图表缓冲后丢弃，有效 GPT-Vis 再整块放行.
                        if ($filteredContent !== '') {
                            $forwardable = $pipeline->takeChartForwardable($filteredContent, $chartState);
                            if ($forwardable === '' && $think === '') {
                                continue;
                            }
                            $payload = $this->contentPayload($json, $forwardable, $think);
                        } elseif ($think !== '') {
                            $payload = $this->contentPayload($json, '', $think);
                        }
                        if ($payload !== null) {
                            $this->forwardModelChunk($payload);
                            ++$streamMetrics['forward_count'];
                            $streamMetrics['forward_bytes'] += strlen($payload);
                            if ($streamMetrics['first_forward_ms'] === null) {
                                $streamMetrics['first_forward_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                            }
                        }
                    }
                },
                uuid: $messageUuid,
                tagName: AiConversationEnum::CACHE_TAG,
                timeout: AiConversationEnum::STREAM_TIMEOUT
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $streamMetrics['answer_chars'] = mb_strlen($answer);
            $streamMetrics['reasoning_chars'] = mb_strlen($reasoning);
            $streamMetrics['tool_calls'] = count($toolCalls);
            $this->logHttpRequestComplete($option, $messageUuid, $startedAt, AiConversationEnum::MESSAGE_FAILED, $e->getMessage(), $streamMetrics);

            return [
                'answer'           => $e->getMessage(),
                'raw'              => '',
                'usage'            => [],
                'status'           => AiConversationEnum::MESSAGE_FAILED,
                'tool_calls'       => [],
                'content_held'     => false,
                'content_streamed' => false,
            ];
        }

        $citationTail = $leakSuppress ? '' : $pipeline->flushCitation($citationState);
        if ($citationTail !== '') {
            if ($this->guestOutputFilter !== null) {
                $citationTail = AiGuestContentGuard::filterOutputChunk($this->guestOutputFilter, $citationTail);
            }
            $answer .= $citationTail;
            if ($citationTail !== '' && (! $holdUserContent || $holdReleased)) {
                // 先经图表门禁再转发，与正文分片路径一致.
                $forwardable = $pipeline->takeChartForwardable($citationTail, $chartState);
                if ($forwardable !== '') {
                    $this->forwardModelChunk($this->encodeContentDelta($forwardable));
                }
            }
        }

        $flushed = $leakSuppress ? '' : $pipeline->flushChart($chartState);
        if ($flushed !== '') {
            if ($this->guestOutputFilter !== null) {
                $flushed = AiGuestContentGuard::filterOutputChunk($this->guestOutputFilter, $flushed);
                $answer .= $flushed;
            } else {
                $answer .= $flushed;
            }
            if ($flushed !== '' && (! $holdUserContent || $holdReleased)) {
                $this->forwardModelChunk($this->encodeContentDelta($flushed));
            }
        }

        if ($this->guestOutputFilter !== null) {
            $tail = AiGuestContentGuard::flushOutputFilter($this->guestOutputFilter);
            if ($tail !== '') {
                $answer .= $tail;
                if (! $leakSuppress && (! $holdUserContent || $holdReleased)) {
                    $this->forwardModelChunk($this->encodeContentDelta($tail));
                }
            }
        }

        $toolCalls   = $this->normalizeToolCalls($toolCalls);
        $interrupted = $this->cacheGet($interruptKey);
        $this->cacheForget($interruptKey);
        $answer      = $this->resolveAnswer($answer, $reasoning);

        // 正文特殊标记：默认只剔除泄露；allow_text_tool_parse=true 时才回吸为可执行 tool_calls（T-2）.
        $absorbed = $textParser->parseToolCallText($answer);
        if ($absorbed['tool_calls'] !== [] || $textParser->containsToolCallMarker($answer) || $absorbed['content'] !== $answer) {
            $answer = (string) $absorbed['content'];
            // 放闸后直播出去的正文混入了工具调用标记：撤回，避免脏标记残留（后续按暂扣/补推交付净化文案）.
            if ($holdReleased) {
                $holdReleased = false;
                $this->resetUserContent();
            }
            if ($toolCalls === [] && $absorbed['tool_calls'] !== [] && $textParser->allowTextToolParse()) {
                $toolCalls = $this->normalizeToolCalls($absorbed['tool_calls']);
                $this->announceToolCalls($toolCalls);
            }
        }

        // 流式回调已收到 reasoning/content 时，不因 curl 超时或空 raw 误判为接口无响应.
        $errorMessage = $this->ignoreTransportErrorIfAnswered($raw, $errorMessage, $answer, $toolCalls);
        if (! $interrupted && $answer === '' && $errorMessage === '' && ! $toolCalls) {
            $errorMessage = 'AI接口没有返回有效回复，请检查模型地址、模型名称或 API Key';
        }
        if (! $interrupted && $errorMessage !== '') {
            if ($references) {
                $this->sendEvent(['type' => 'references', 'references' => $references]);
                $images = app(\App\Http\Service\Knowledge\KnowledgeRagService::class)->collectReplyImages($references);
                if ($images !== []) {
                    $this->sendEvent(['type' => 'images', 'images' => $images]);
                }
            }
            $this->error($errorMessage);
            $streamMetrics['answer_chars'] = mb_strlen($answer);
            $streamMetrics['reasoning_chars'] = mb_strlen($reasoning);
            $streamMetrics['tool_calls'] = count($toolCalls);
            $this->logHttpRequestComplete($option, $messageUuid, $startedAt, AiConversationEnum::MESSAGE_FAILED, $errorMessage, $streamMetrics);

            return [
                'answer'           => $errorMessage,
                'raw'              => (string) $raw,
                'usage'            => [],
                'status'           => AiConversationEnum::MESSAGE_FAILED,
                'tool_calls'       => [],
                'content_held'     => false,
                'content_streamed' => false,
            ];
        }

        if ($finalize) {
            $this->complete($references, (bool) $interrupted);
        }

        $status = $interrupted
            ? AiConversationEnum::MESSAGE_INTERRUPTED
            : AiConversationEnum::MESSAGE_DONE;
        $streamMetrics['answer_chars'] = mb_strlen($answer);
        $streamMetrics['reasoning_chars'] = mb_strlen($reasoning);
        $streamMetrics['tool_calls'] = count($toolCalls);
        $this->logHttpRequestComplete($option, $messageUuid, $startedAt, $status, '', $streamMetrics);

        return [
            'answer'           => $answer,
            'raw'              => (string) $raw,
            'usage'            => $usage === [] ? [] : self::normalizeUsage($usage),
            'status'           => $status,
            'tool_calls'       => $toolCalls,
            'content_held'     => $holdUserContent,
            'content_streamed' => $holdReleased,
        ];
    }

    /**
     * 归一化供应商 usage 字段，保证 prompt/completion/total 可落库统计.
     *
     * @param array<string, mixed> $usage 原始 usage
     *
     * @return array{prompt_tokens: int, completion_tokens: int, total_tokens: int}
     */
    public static function normalizeUsage(array $usage): array
    {
        $prompt = (int) ($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0);
        $completion = (int) ($usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0);
        $total = (int) ($usage['total_tokens'] ?? 0);
        if ($total <= 0 && ($prompt > 0 || $completion > 0)) {
            $total = $prompt + $completion;
        }

        return [
            'prompt_tokens'     => max(0, $prompt),
            'completion_tokens' => max(0, $completion),
            'total_tokens'      => max(0, $total),
        ];
    }

    /**
     * 记录流式请求体积拆分（messages / tools），便于上下文优化验收.
     */
    private function logHttpRequestStart(BaseOption $option, string $messageUuid): void
    {
        if (! (bool) config('ai.http_log.enabled', true)) {
            return;
        }

        $metrics = $this->requestSizeMetrics($option);
        $payload = array_merge($metrics, [
            'mode'         => 'stream',
            'message_uuid' => $messageUuid,
            'model'        => (string) ($option->model ?? ''),
            'url'          => (string) ($option->url ?? ''),
        ], $this->requestMeta);

        try {
            Log::channel((string) config('ai.http_log.channel', 'ai'))->info('AI HTTP 调用开始', $payload);
        } catch (\Throwable) {
            Log::info('AI HTTP 调用开始', $payload);
        }
    }

    /**
     * 记录流式调用完成（含耗时）.
     */
    private function logHttpRequestComplete(
        BaseOption $option,
        string $messageUuid,
        float $startedAt,
        int $status,
        string $error = '',
        array $streamMetrics = []
    ): void {
        if (! (bool) config('ai.http_log.enabled', true)) {
            return;
        }

        $metrics = $this->requestSizeMetrics($option);
        $payload = array_merge($metrics, [
            'mode'         => 'stream',
            'message_uuid' => $messageUuid,
            'model'        => (string) ($option->model ?? ''),
            'url'          => (string) ($option->url ?? ''),
            'http_status'  => $status,
            'duration_ms'  => (int) round((microtime(true) - $startedAt) * 1000),
            'error'        => $error !== '' ? mb_substr($error, 0, 500) : null,
        ], $this->requestMeta);
        foreach ($streamMetrics as $key => $value) {
            if ($key === 'last_callback_at') {
                continue;
            }
            $payload['stream_' . $key] = $value;
        }

        try {
            Log::channel((string) config('ai.http_log.channel', 'ai'))->info('AI HTTP 调用完成', $payload);
        } catch (\Throwable) {
            Log::info('AI HTTP 调用完成', $payload);
        }
    }

    /**
     * @return array{
     *     request_chars: int,
     *     messages_chars: int,
     *     messages_count: int,
     *     tools_count: int,
     *     tools_chars: int
     * }
     */
    private function requestSizeMetrics(BaseOption $option): array
    {
        $messages = is_array($option->messages) ? $option->messages : [];
        $tools    = is_array($option->tools) ? $option->tools : [];
        $messagesJson = json_encode($messages, JSON_UNESCAPED_UNICODE) ?: '[]';
        $toolsJson    = json_encode($tools, JSON_UNESCAPED_UNICODE) ?: '[]';
        $bodyApprox   = [
            'messages' => $messages,
            'tools'    => $tools,
            'model'    => $option->model,
        ];

        return [
            'request_chars'  => strlen(json_encode($bodyApprox, JSON_UNESCAPED_UNICODE) ?: '{}'),
            'messages_chars' => strlen($messagesJson),
            'messages_count' => count($messages),
            'tools_count'    => count($tools),
            'tools_chars'    => strlen($toolsJson),
        ];
    }

    /**
     * 当前请求是否启用了工具（工具轮次应暂扣用户可见正文）.
     */
    public function shouldHoldUserContent(BaseOption $option): bool
    {
        return ! empty($this->requestMeta['hold_write_answer']) || (is_array($option->tools) && $option->tools !== []);
    }

    /**
     * 将最终正文一次性推给前端（用于工具轮次暂扣后的确认输出）.
     */
    public function emitAnswer(string $answer): void
    {
        // 与 resolveAnswer 共用 Delivery 管道，避免暂扣后推送漏掉 InternalLeak/Chart.
        $answer = app(AnswerSanitizePipeline::class)->sanitize($answer);
        if ($answer === '') {
            return;
        }
        $this->forwardModelChunk($this->encodeContentDelta($answer));
    }

    /**
     * 撤回已直播的暂定正文（工具轮次放闸后确认是旁白，或终稿需整段替换时）.
     */
    public function resetUserContent(): void
    {
        $this->sendEvent(['type' => 'content_reset']);
    }

    /**
     * 写入中断标记，终止进行中的流式请求。
     *
     * @param string $messageUuid 消息 UUID
     *
     * @return bool 是否成功写入缓存
     */
    public function interrupt(string $messageUuid): bool
    {
        $this->cacheSet($messageUuid . ':interrupted', 1, 8400);

        return $this->cacheSet($messageUuid, 'stop', 8400);
    }

    /**
     * 向前端推送错误事件并结束 SSE 流。
     *
     * @param string $message 错误信息
     */
    public function error(string $message): void
    {
        $this->sendEvent(['type' => 'error', 'message' => $message]);
        $this->done();
    }

    /**
     * 推送 message_uuid 数据事件，供前端绑定当前回答；可选标记可翻页表格.
     *
     * @param string $messageUuid 消息 UUID
     * @param bool $isPage 是否挂载手动分页表格（hasPageTable）
     */
    public function message(string $messageUuid, bool $isPage = false): void
    {
        $payload = ['type' => 'data', 'chat_record_uuid' => $messageUuid];
        if ($isPage) {
            $payload['is_page'] = true;
        }
        $this->sendEvent($payload);
    }

    /**
     * 推送思考过程或工具阶段状态事件。
     *
     * @param string $status 阶段标识
     * @param string $message 展示文案
     * @param array<string, mixed> $extra 附加字段
     */
    public function status(string $status, string $message = '', array $extra = []): void
    {
        if ($message === '' && $extra === []) {
            return;
        }
        $type = str_starts_with($status, 'tool_') ? 'tool' : 'thinking';
        $data = array_merge([
            'type'    => $type,
            'stage'   => $status,
            'message' => $message,
        ], $extra);
        if ($type === 'tool') {
            $data['tool_name'] = (string) ($extra['tools'][0] ?? $extra['name'] ?? $message);
        }
        $this->sendEvent($data);
    }

    /**
     * 推送会话结束后的推荐问题，供前端渲染为可点击续问。
     *
     * 须在 [DONE] 之前调用。中断、失败、待补参与待确认不推送。
     *
     * @param array<int, string> $questions 推荐问题，最多 3 条
     *
     * @return array<int, string> 实际推送的列表（去空、去重、截断后）；未推送时为空数组
     */
    public function suggestions(array $questions): array
    {
        $items = [];
        foreach ($questions as $question) {
            $question = trim((string) $question);
            if ($question === '' || in_array($question, $items, true)) {
                continue;
            }
            $items[] = $question;
            if (count($items) >= 3) {
                break;
            }
        }
        if ($items === []) {
            return [];
        }

        $this->sendEvent(['type' => 'suggestions', 'suggestions' => $items]);

        return $items;
    }

    /**
     * 推送知识库引用并结束 SSE 流。
     *
     * @param array<int, array<string, mixed>> $references 知识库引用
     * @param bool $interrupted 是否因用户中断而跳过引用
     */
    public function complete(array $references = [], bool $interrupted = false): void
    {
        if ($references && ! $interrupted) {
            $this->sendEvent(['type' => 'references', 'references' => $references]);
            $images = app(\App\Http\Service\Knowledge\KnowledgeRagService::class)->collectReplyImages($references);
            if ($images !== []) {
                $this->sendEvent(['type' => 'images', 'images' => $images]);
            }
        }
        $this->done();
    }

    /**
     * 收帧先结束、推荐问题后补的全局统一收尾.
     *
     * [DONE] 立即发出让前端先落到回答终态，推荐问题同步生成后再补推一帧——
     * 生成耗时的几秒只推迟推荐区出现，不再拖慢整条回答的收尾。
     * 所有收尾路径（工具循环 / 写确认 / 取消补参）统一走此入口。
     *
     * @param callable(): array<int, string> $generator 推荐问题生成器
     * @param array<int, array<string, mixed>> $references 知识库引用
     * @param bool $interrupted 是否因用户中断而跳过引用
     *
     * @return array<int, string> 实际推送的推荐问题；生成失败或为空时不推送
     */
    public function completeThenSuggestions(callable $generator, array $references = [], bool $interrupted = false): array
    {
        $this->complete($references, $interrupted);

        try {
            return $this->suggestions((array) $generator());
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * SSE 心跳，避免长耗时准备/工具调用阶段连接被中间层断开。
     */
    public function ping(): void
    {
        $this->sendEvent(['type' => 'info']);
    }

    /**
     * 在阻塞回调执行期间保持 SSE 心跳；协程可用时边执行边 ping。
     *
     * @template T
     *
     * @param callable(): T $callback 阻塞任务
     * @param null|float $intervalSeconds 心跳间隔（秒）
     *
     * @return T 回调返回值
     *
     * @throws \Throwable 回调抛出异常时原样抛出
     */
    public function runWithHeartbeat(callable $callback, ?float $intervalSeconds = null)
    {
        $interval = $intervalSeconds ?? AiConversationEnum::STREAM_HEARTBEAT_SECONDS;
        $this->ping();

        // 仅在已有协程内做并发心跳；LaravelS 下禁止 Coroutine\run() 新建 Scheduler.
        if (! $this->canHeartbeatConcurrently() || $interval <= 0) {
            try {
                return $callback();
            } finally {
                $this->ping();
            }
        }

        $result = null;
        $error  = null;
        $done   = false;

        \Swoole\Coroutine::create(function () use ($callback, &$result, &$error, &$done): void {
            try {
                $result = $callback();
            } catch (\Throwable $e) {
                $error = $e;
            } finally {
                $done = true;
            }
        });
        \Swoole\Coroutine::create(function () use ($interval, &$done): void {
            while (! $done) {
                \Swoole\Coroutine::sleep($interval);
                if (! $done) {
                    $this->ping();
                }
            }
        });
        while (! $done) {
            \Swoole\Coroutine::sleep(0.05);
        }

        if ($error instanceof \Throwable) {
            throw $error;
        }

        $this->ping();

        return $result;
    }

    /**
     * 并行执行多个阻塞任务并保持 SSE 心跳；非协程环境退化为串行。
     *
     * @template T
     *
     * @param array<string, callable(): T> $tasks 键名到任务的映射
     * @param null|float $intervalSeconds 心跳间隔（秒）
     *
     * @return array<string, T> 各任务返回值
     *
     * @throws \Throwable 任一任务失败时抛出
     */
    public function runParallelWithHeartbeat(array $tasks, ?float $intervalSeconds = null): array
    {
        if ($tasks === []) {
            return [];
        }

        if (count($tasks) === 1) {
            $key = array_key_first($tasks);

            return [$key => $this->runWithHeartbeat($tasks[$key], $intervalSeconds)];
        }

        $interval = $intervalSeconds ?? AiConversationEnum::STREAM_HEARTBEAT_SECONDS;
        $this->ping();

        if (! $this->canHeartbeatConcurrently() || $interval <= 0) {
            $results = [];
            foreach ($tasks as $key => $task) {
                $results[$key] = $task();
            }
            $this->ping();

            return $results;
        }

        $results = [];
        $errors  = [];
        $done    = array_fill_keys(array_keys($tasks), false);

        foreach ($tasks as $key => $task) {
            \Swoole\Coroutine::create(function () use ($key, $task, &$results, &$errors, &$done): void {
                try {
                    $results[$key] = $task();
                } catch (\Throwable $e) {
                    $errors[$key] = $e;
                } finally {
                    $done[$key] = true;
                }
            });
        }

        \Swoole\Coroutine::create(function () use ($interval, &$done): void {
            while (in_array(false, $done, true)) {
                \Swoole\Coroutine::sleep($interval);
                if (in_array(false, $done, true)) {
                    $this->ping();
                }
            }
        });

        while (in_array(false, $done, true)) {
            \Swoole\Coroutine::sleep(0.05);
        }

        foreach ($errors as $error) {
            if ($error instanceof \Throwable) {
                throw $error;
            }
        }

        $this->ping();

        return $results;
    }

    /**
     * 推送结构化信息确认事件，供前端渲染选择/填写控件。
     *
     * @param array<int, array<string, mixed>> $payloads 待确认项 payload 列表
     */
    public function inputRequired(array $payloads): void
    {
        $this->sendEvent([
            'type'   => 'input_required',
            'inputs' => array_values($payloads),
        ]);
    }

    /**
     * 推送写操作二次确认事件，供前端渲染同意/拒绝控件（独立 type，不走 thinking）.
     *
     * @param array<int, array<string, mixed>> $actions 待确认写操作列表
     */
    public function confirmRequired(array $actions): void
    {
        $this->sendEvent([
            'type'    => 'confirm_required',
            'actions' => array_values($actions),
        ]);
    }

    /**
     * 模型开始产出 tool_calls 时立即告知前端，避免「选工具」阶段黑洞。
     *
     * @param array<int, array<string, mixed>> $toolCalls 工具调用列表
     */
    public function announceToolCalls(array $toolCalls): void
    {
        foreach ($toolCalls as $call) {
            $name = (string) ($call['function']['name'] ?? '');
            if ($name === '' || isset($this->announcedToolCalls[$name])) {
                continue;
            }
            $this->announcedToolCalls[$name] = true;
            $this->status('tool_selected', '正在调用工具', ['tools' => [$name]]);
        }
    }

    /**
     * 重置已宣告工具名，供新一轮会话或单测复用同一服务实例。
     */
    public function resetAnnouncedToolCalls(): void
    {
        $this->announcedToolCalls = [];
    }

    /** 判断当前是否处于 Swoole 协程内，可并发发送心跳。 */
    private function canHeartbeatConcurrently(): bool
    {
        return extension_loaded('swoole')
            && class_exists(\Swoole\Coroutine::class)
            && \Swoole\Coroutine::getCid() > 0;
    }

    /**
     * 流式请求已收到部分回答时，忽略传输层空响应错误。
     *
     * @param mixed $raw 原始响应体
     * @param string $errorMessage 当前错误信息
     * @param string $answer 已聚合的回答
     * @param array<int, array<string, mixed>> $toolCalls 已聚合的工具调用
     *
     * @return string 修正后的错误信息，空字符串表示无错误
     */
    public function ignoreTransportErrorIfAnswered($raw, string $errorMessage, string $answer, array $toolCalls): string
    {
        if ($errorMessage === '') {
            $errorMessage = $this->rawErrorMessage($raw);
        }
        if ($errorMessage !== '' && ($answer !== '' || $toolCalls !== [])) {
            return '';
        }

        return $errorMessage;
    }

    /**
     * 识别上游模型错误信息，供流式回调与单测复用。
     *
     * @param array<string, mixed> $json 模型 JSON 分片
     *
     * @return string 错误信息，无错误时为空
     */
    public function providerErrorMessage(array $json): string
    {
        return $this->errorMessage($json);
    }

    /**
     * 把已解析的模型 JSON 聚合成完整 tool_calls，供流式回调和单测复用。
     *
     * @param array<int, array<string, mixed>> $payloads JSON 分片列表
     *
     * @return array<int, array<string, mixed>> 规范化后的 tool_calls
     */
    public function collectToolCalls(array $payloads): array
    {
        $toolCalls = [];
        foreach ($payloads as $json) {
            if (is_array($json)) {
                $this->mergeToolCalls($toolCalls, $json);
            }
        }

        return $this->normalizeToolCalls($toolCalls);
    }

    /**
     * 判断模型分片是否需要转发给前端。
     *
     * @param array<string, mixed> $json 模型 JSON 分片
     */
    public function shouldForwardChunk(array $json): bool
    {
        if ($this->extractContent($json) !== '' || $this->extractReasoning($json) !== '') {
            return true;
        }

        return ! $this->isToolCallChunk($json);
    }

    /**
     * 提取最终可落库的回答文本，优先使用 content，其次 reasoning；并剔除装饰性信息图。
     *
     * @param string $content 正文内容
     * @param string $reasoning 推理内容
     *
     * @return string 净化后的回答
     */
    public function resolveAnswer(string $content, string $reasoning): string
    {
        // 内容优先；推理回退时丢弃密文载荷，避免 encrypted_content 误落库为回答.
        $fallback = $this->isOpaqueEncryptedReasoning($reasoning) ? '' : $reasoning;
        $answer   = $content !== '' ? $content : $fallback;

        return app(AnswerSanitizePipeline::class)->sanitize($answer);
    }

    /**
     * 用过滤后的 content 重建可转发的模型分片 JSON。
     *
     * 会剥离 encrypted_content 等仅供模型多轮回传的密文字段，以及 id、model、usage、choices.index 等前端不消费的信封字段。
     *
     * @param array<string, mixed> $json 原始分片
     * @param string $content 替换后的正文
     * @param string $reasoning 替换后的推理内容
     *
     * @return string 重建后的 JSON 字符串
     */
    public function contentPayload(array $json, string $content, string $reasoning = ''): string
    {
        foreach (self::UNUSED_STREAM_FIELDS as $field) {
            unset($json[$field]);
        }
        $choice = is_array($json['choices'][0] ?? null) ? $json['choices'][0] : [];
        // 单候选流式回答恒为 0，前端按 choices[0] 读取，不依赖该序号。
        unset($choice['index']);
        $delta  = is_array($choice['delta'] ?? null) ? $choice['delta'] : [];
        $this->stripOpaqueModelFields($delta);
        if ($content === '') {
            unset($delta['content']);
        } else {
            $delta['content'] = $content;
        }
        if ($reasoning !== '' && ! $this->isOpaqueEncryptedReasoning($reasoning)) {
            if (isset($delta['reasoning_content'])) {
                $delta['reasoning_content'] = $reasoning;
            } elseif (isset($delta['reasoning'])) {
                $delta['reasoning'] = $reasoning;
            } elseif (isset($delta['reasoning_text'])) {
                $delta['reasoning_text'] = $reasoning;
            } elseif (isset($delta['think'])) {
                $delta['think'] = $reasoning;
            } else {
                // 原始分片可能只有 encrypted_content：可读思考统一落到 reasoning_content.
                $delta['reasoning_content'] = $reasoning;
            }
        } else {
            unset(
                $delta['reasoning_content'],
                $delta['reasoning'],
                $delta['reasoning_text'],
                $delta['think'],
            );
        }
        if (is_array($choice['message'] ?? null)) {
            $message = $choice['message'];
            $this->stripOpaqueModelFields($message);
            $choice['message'] = $message;
        }
        $choice['delta']    = $delta;
        $json['choices'][0] = $choice;
        $encoded            = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '' : $encoded;
    }

    /** 构造仅含 content delta 的 SSE 分片 JSON。 */
    private function encodeContentDelta(string $content): string
    {
        return $this->contentPayload([
            'choices' => [[
                'delta' => ['content' => $content],
            ]],
        ], $content);
    }

    /** 从模型 JSON 分片提取正文 content 字段。 */
    private function extractContent(array $json): string
    {
        $choice  = $json['choices'][0] ?? [];
        $delta   = is_array($choice['delta'] ?? null) ? $choice['delta'] : [];
        $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];

        return (string) ($delta['content'] ?? $message['content'] ?? '');
    }

    /**
     * 从模型 JSON 分片提取可读 reasoning 字段.
     *
     * 忽略 encrypted_content，并丢弃误写入 reasoning_content 的密文载荷.
     */
    private function extractReasoning(array $json): string
    {
        $choice  = $json['choices'][0] ?? [];
        $delta   = is_array($choice['delta'] ?? null) ? $choice['delta'] : [];
        $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];

        foreach ([
            $delta['reasoning_content'] ?? null,
            $delta['reasoning'] ?? null,
            $delta['reasoning_text'] ?? null,
            $delta['think'] ?? null,
            $message['reasoning_content'] ?? null,
            $message['reasoning'] ?? null,
            $message['reasoning_text'] ?? null,
            $message['think'] ?? null,
        ] as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }
            if ($this->isOpaqueEncryptedReasoning($value)) {
                continue;
            }

            return $value;
        }

        return '';
    }

    /**
     * 剥离仅供模型多轮回传、不可展示的密文字段.
     *
     * @param array<string, mixed> $payload delta 或 message
     */
    private function stripOpaqueModelFields(array &$payload): void
    {
        unset(
            $payload['encrypted_content'],
            $payload['reasoning_encrypted_content'],
        );
    }

    /**
     * 判断文本是否为厂商加密推理载荷（Base64 密文），不可作为思考过程展示.
     *
     * @param string $text 候选推理文本
     */
    private function isOpaqueEncryptedReasoning(string $text): bool
    {
        $trimmed = trim($text);
        if ($trimmed === '' || strlen($trimmed) < 64) {
            return false;
        }
        // 可读思考通常含空白或 CJK；纯密文多为连续 Base64/URL-safe 字符.
        if (preg_match('/\s/u', $trimmed)
            || preg_match('/[\x{4e00}-\x{9fff}\x{3040}-\x{30ff}\x{ac00}-\x{d7af}]/u', $trimmed)
        ) {
            return false;
        }

        return (bool) preg_match('#^[A-Za-z0-9+/_\-=]+$#', $trimmed);
    }

    /** 将原始 SSE 分片转发给前端（Swoole Response 或 echo）。 */
    private function forwardModelChunk(string $payload): void
    {
        if ($payload === '' || $payload === '[DONE]') {
            return;
        }

        $this->send('data: ' . $payload . "\n\n");
    }

    /** 增量合并流式 tool_calls 分片到完整调用列表。 */
    private function mergeToolCalls(array &$toolCalls, array $json): void
    {
        foreach ($this->toolCallChunks($json) as $chunk) {
            $index = (int) ($chunk['index'] ?? count($toolCalls));
            $toolCalls[$index] ??= [
                'id'       => '',
                'type'     => 'function',
                'function' => [
                    'name'      => '',
                    'arguments' => '',
                ],
            ];
            if (isset($chunk['id'])) {
                $toolCalls[$index]['id'] = (string) $chunk['id'];
            }
            if (isset($chunk['type'])) {
                $toolCalls[$index]['type'] = (string) $chunk['type'];
            }
            if (($chunk['function']['name'] ?? '') !== '') {
                $toolCalls[$index]['function']['name'] = (string) $chunk['function']['name'];
            }
            if (isset($chunk['function']['arguments'])) {
                $piece = $chunk['function']['arguments'];
                // 部分供应商非流式/半兼容接口直接回传已解析的 object/array.
                // 同一 call 若已开始拼接字符串，忽略后续 array，避免覆盖丢失 delta。
                if (is_array($piece)) {
                    $existing = (string) ($toolCalls[$index]['function']['arguments'] ?? '');
                    if ($existing === '') {
                        $toolCalls[$index]['function']['arguments'] = AiToolCallService::encodeFunctionArguments($piece);
                    }
                } else {
                    $toolCalls[$index]['function']['arguments'] .= (string) $piece;
                }
            }
        }
    }

    private function toolCallChunks(array $json): array
    {
        $choice  = $json['choices'][0] ?? [];
        $delta   = is_array($choice['delta'] ?? null) ? $choice['delta'] : [];
        $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];

        if (! empty($delta['tool_calls']) && is_array($delta['tool_calls'])) {
            return $delta['tool_calls'];
        }
        if (! empty($message['tool_calls']) && is_array($message['tool_calls'])) {
            return $message['tool_calls'];
        }
        if (! empty($delta['function_call']) && is_array($delta['function_call'])) {
            return [['index' => 0, 'type' => 'function', 'function' => $delta['function_call']]];
        }
        if (! empty($message['function_call']) && is_array($message['function_call'])) {
            return [['index' => 0, 'type' => 'function', 'function' => $message['function_call']]];
        }

        return [];
    }

    private function isToolCallChunk(array $json): bool
    {
        $choice = $json['choices'][0] ?? [];
        $finish = (string) ($choice['finish_reason'] ?? '');
        if (in_array($finish, ['tool_calls', 'function_call'], true)) {
            return true;
        }

        return $this->toolCallChunks($json) !== [];
    }

    private function normalizeToolCalls(array $toolCalls): array
    {
        ksort($toolCalls);

        return array_values(array_filter(
            array_map(
                static fn (array $call): array => AiToolCallService::normalizeToolCallArguments($call),
                $toolCalls
            ),
            static fn (array $call): bool => (string) ($call['function']['name'] ?? '') !== ''
        ));
    }

    /**
     * 纠正 messages 中历史 tool_calls.arguments，避免 "[]"/list 触发上游 JSON object 校验失败.
     */
    private function sanitizeMessageToolCallArguments(BaseOption $option): void
    {
        foreach ($option->messages as $i => $message) {
            if (! is_array($message) || empty($message['tool_calls']) || ! is_array($message['tool_calls'])) {
                continue;
            }
            foreach ($message['tool_calls'] as $j => $call) {
                if (! is_array($call)) {
                    continue;
                }
                $option->messages[$i]['tool_calls'][$j] = AiToolCallService::normalizeToolCallArguments($call);
            }
        }
    }

    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::tags([AiConversationEnum::CACHE_TAG])->get($key);
        } catch (\Throwable) {
            return Cache::get($key);
        }
    }

    private function cacheForget(string $key): void
    {
        try {
            Cache::tags([AiConversationEnum::CACHE_TAG])->forget($key);
        } catch (\Throwable) {
            Cache::forget($key);
        }
    }

    private function cacheSet(string $key, mixed $value, int $ttl): bool
    {
        try {
            return (bool) Cache::tags([AiConversationEnum::CACHE_TAG])->set($key, $value, $ttl);
        } catch (\Throwable) {
            return (bool) Cache::set($key, $value, $ttl);
        }
    }

    /** 从 SSE 原始数据解析 data: 行 payload 列表。 */
    private function payloads(string $data, string &$buffer): array
    {
        // HttpService 已按 \n\n 切分 SSE 事件，这里保留缓冲以兼容残余半包.
        $data   = str_replace(["\r\n", "\r"], "\n", $data);
        $buffer .= $data;
        $events   = explode("\n\n", $buffer);
        $buffer   = array_pop($events) ?? '';
        $payloads = [];
        foreach ($events as $event) {
            foreach (preg_split("/\n/", trim($event)) as $line) {
                if (preg_match('/^data:\s*(.*)$/', $line, $matches)) {
                    $payloads[] = $matches[1];
                }
            }
        }

        return $payloads;
    }

    private function errorMessage(array $json): string
    {
        if (! empty($json['error'])) {
            return is_array($json['error']) ? (string) ($json['error']['message'] ?? json_encode($json['error'], JSON_UNESCAPED_UNICODE)) : (string) $json['error'];
        }

        if (isset($json['type']) && in_array((string) $json['type'], ['error', 'status', 'message', 'data', 'thinking', 'reasoning', 'tool', 'info', 'references', 'images', 'input_required', 'confirm_required'], true)) {
            return '';
        }

        if (isset($json['choices']) || isset($json['object']) || isset($json['id'])) {
            return '';
        }

        $type = (string) ($json['type'] ?? '');
        if ($type !== '' && in_array($type, ['invalid_request_error', 'server_error', 'authentication_error', 'permission_error', 'not_found_error', 'rate_limit_error'], true)) {
            return (string) ($json['message'] ?? $json['msg'] ?? '');
        }

        $code = (int) ($json['code'] ?? $json['status_code'] ?? 0);
        if ($code >= 400) {
            return (string) ($json['message'] ?? $json['msg'] ?? '');
        }

        return '';
    }

    private function rawErrorMessage($raw): string
    {
        if ($raw === false || trim((string) $raw) === '') {
            return 'AI接口没有返回内容，请检查模型地址、模型名称或 API Key';
        }

        $json = json_decode((string) $raw, true, 512, JSON_BIGINT_AS_STRING);
        if (json_last_error() === JSON_ERROR_NONE && ($message = $this->errorMessage($json)) !== '') {
            return $message;
        }

        $buffer = '';
        foreach ($this->payloads((string) $raw, $buffer) as $payload) {
            $json = json_decode($payload, true, 512, JSON_BIGINT_AS_STRING);
            if (json_last_error() === JSON_ERROR_NONE && ($message = $this->errorMessage($json)) !== '') {
                return $message;
            }
        }

        return '';
    }

    private function sendEvent(array $data): void
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return;
        }

        $this->send('data: ' . $encoded . "\n\n");
    }

    private function done(): void
    {
        $this->send("data: [DONE]\n\n");
    }

    /** 输出通道收口：Swoole 下写 Response，其余走 echo；供子类覆写捕获帧做测试. */
    protected function send(string $message): void
    {
        if (extension_loaded('swoole') && app()->bound(Response::class)) {
            app(Response::class)->write($message);

            return;
        }

        echo $message;
        // CLI / PHPUnit 下 flush 管道可能阻塞，仅在 Web/Swoole 外的 FPM 场景刷缓冲.
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }
        while (ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
    }
}
