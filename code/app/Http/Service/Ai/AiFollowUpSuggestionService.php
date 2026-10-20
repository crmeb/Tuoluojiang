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
use App\Constants\Ai\AiModelEnum;
use App\Constants\Ai\AiModelPresetEnum;
use App\Http\Model\Ai\AiApplication;
use App\Http\Model\Ai\AiModel;
use Tuoluojiang\Open\services\ai\BaseCurl;
use Tuoluojiang\Open\services\ai\BaseOption;
use Illuminate\Support\Facades\Log;

/**
 * 会话结束后的推荐问题：结合本轮问答与应用配置，生成 3 条可点击续问.
 *
 * 调用链：ToolLoopExecutor 正常收尾 → 本服务 → 应用绑定模型（非流式）→ AiStreamService::suggestions。
 * 生成失败返回空数组，不中断已经完成的回答。推荐请求在 [DONE] 之前同步等待，须关闭或降到最轻推理。
 */
class AiFollowUpSuggestionService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
你是企业 AI 助手的追问推荐器。根据本轮用户问题、助手回复，以及当前应用配置，生成恰好 3 条用户可能会继续点击发送的中文问题。
要求：
1. 每条是用户口吻的完整问题，不超过 30 个字
2. 必须能被当前应用已开通的能力承接，不要推荐未配置的数据源、知识库或写操作
3. 紧扣本轮问答往下延伸，猜测用户接下来最自然的下一步（如刚创建联系人后推荐查看该客户联系人、为其创建商机）；不要原样重复用户刚问过的句子，也不要复述助手回复
4. 严禁编造本轮问答中没有出现过的具体业务数据：人名、电话号码、金额、日期、编号等一律不得虚构；推荐写操作时用开放式表述（如「为某客户创建一个商机」），具体值留给用户自己填
5. 三条彼此不同，具体可执行，不要空泛的「还有吗」「详细说说」
6. 只输出 JSON：{"suggestions":["问题1","问题2","问题3"]}
PROMPT;

    /**
     * 生成推荐问题；关闭、模型不可用或调用失败时返回空数组.
     *
     * @return array<int, string>
     */
    public function suggest(AiApplication $app, AiModel $model, string $question, string $answer): array
    {
        if (! (bool) config('ai.follow_up.enabled', true)) {
            return [];
        }

        $question = trim($question);
        $answer   = trim($answer);
        if ($question === '' || $answer === '') {
            return [];
        }
        if ((int) $model->status !== AiModelEnum::STATUS_ENABLED
            || trim((string) $model->api_key) === ''
            || trim((string) $model->model_id) === '') {
            return [];
        }

        try {
            $items = $this->generateOnce($app, $model, $question, $answer);

            // 弱模型偶尔不守「恰好 3 条」：推荐在结束帧之后补推，多一轮补足不影响回答收尾
            if (count($items) < 3) {
                foreach ($this->generateOnce($app, $model, $question, $answer, $items) as $item) {
                    if (in_array($item, $items, true)) {
                        continue;
                    }
                    $items[] = $item;
                    if (count($items) >= 3) {
                        break;
                    }
                }
            }

            return $items !== [] ? $items : $this->fallbackSuggestions($app, $question);
        } catch (\Throwable $e) {
            Log::channel((string) config('ai.http_log.channel', 'ai'))->warning('AI推荐问题生成失败', [
                'app_id'  => (int) $app->id,
                'message' => $e->getMessage(),
            ]);

            return $this->fallbackSuggestions($app, $question);
        }
    }

    /**
     * 单次调用生成推荐问题；不足 3 条时由 suggest 发起补足轮.
     *
     * @param array<int, string> $existing 已生成的推荐，补足轮要求给出不同的新条目
     *
     * @return array<int, string> 解析后的推荐；失败返回空数组
     */
    private function generateOnce(
        AiApplication $app,
        AiModel $model,
        string $question,
        string $answer,
        array $existing = []
    ): array {
        $option              = app(AiModelService::class)->makeChatOption($model, false);
        $option->stream      = false;
        $option->tools       = [];
        $option->temperature = 0;
        $option->setMessage(self::SYSTEM_PROMPT, BaseOption::RULE_SYSTEM);
        $option->setMessage($this->userPrompt($app, $question, $answer, $existing), BaseOption::RULE_USER);
        // options() 会整表替换，必须最后写入，避免 max_tokens 冲掉 thinking.
        $option->options(app(AiModelService::class)->followUpSamplingOptions($model));

        $preset  = AiModelPresetEnum::get((string) $model->provider);
        $auth    = (string) ($preset['auth'] ?? AiModelEnum::AUTH_BEARER);
        $timeout = max(5, (int) ceil((int) config('ai.follow_up.timeout_ms', 20000) / 1000));
        $response = (new BaseCurl(trim((string) $model->api_key), $option->baseUrl, $auth))
            ->setBody($option)
            ->send(url: $option->url, timeout: $timeout);
        if (! empty($response['error'])) {
            return [];
        }

        $content = trim((string) ($response['choices'][0]['message']['content'] ?? ''));

        return $this->parse(
            $content,
            (int) config('ai.follow_up.max_item_chars', 40)
        );
    }

    /**
     * 从模型输出中收敛出最多 3 条推荐问题.
     *
     * 优先解析 JSON；模型若改成逐行文本，去掉编号后同样可用。
     *
     * @return array<int, string>
     */
    public function parse(string $content, int $maxItemChars = 40): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        $limit = 3;
        $maxItemChars = max(8, $maxItemChars);
        $items = $this->rawItems($content);
        $result = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = $item['question'] ?? $item['text'] ?? $item['content'] ?? '';
            }
            $text = trim((string) $item);
            $text = preg_replace('/^\s*(?:[-*•]|\d+[.、.)])\s*/u', '', $text) ?? $text;
            $text = trim($text, " \t\"'“”");
            if ($text === '') {
                continue;
            }
            if (mb_strlen($text) > $maxItemChars) {
                $text = rtrim(mb_substr($text, 0, $maxItemChars));
            }
            if (in_array($text, $result, true)) {
                continue;
            }
            $result[] = $text;
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * 组装给模型的用户消息：本轮问答 + 应用配置快照；补足轮附上已生成列表.
     */
    private function userPrompt(AiApplication $app, string $question, string $answer, array $existing = []): string
    {
        $payload = [
            'question' => mb_substr($question, 0, max(1, (int) config('ai.follow_up.max_question_chars', 800))),
            'answer'   => mb_substr($answer, 0, max(1, (int) config('ai.follow_up.max_answer_chars', 1600))),
            'application' => $this->applicationSnapshot($app),
        ];
        if ($existing !== []) {
            $payload['already_generated'] = $existing;
            $payload['note'] = '以上推荐已生成，请再给出与它们不同的新推荐，禁止同义改写或复用相同数据';
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * 模型失败时回退到应用开场白问题，避免推荐区空白.
     *
     * @return array<int, string>
     */
    private function fallbackSuggestions(AiApplication $app, string $question): array
    {
        $maxItemChars = max(8, (int) config('ai.follow_up.max_item_chars', 40));
        $current = trim($question);
        $items = [];
        foreach ($this->stringList($app->prologue_list, 8, $maxItemChars) as $item) {
            if ($item === '' || $item === $current || in_array($item, $items, true)) {
                continue;
            }
            $items[] = $item;
            if (count($items) >= 3) {
                break;
            }
        }

        return $items;
    }

    /**
     * 应用配置现状快照（瘦身后）：只保留推荐问题需要的能力边界信息.
     *
     * persona/开场白/表名等与「猜下一步」无关，砍掉可显著缩短 prefill 耗时.
     *
     * @return array<string, mixed>
     */
    private function applicationSnapshot(AiApplication $app): array
    {
        $labels = [
            AiConversationEnum::SOURCE_NORMAL    => '基础对话',
            AiConversationEnum::SOURCE_SQL       => '数据库查询',
            AiConversationEnum::SOURCE_MCP       => '业务工具',
            AiConversationEnum::SOURCE_KNOWLEDGE => '知识库',
        ];
        $capabilities = [];
        foreach ((array) $app->source_types as $type) {
            $type = (int) $type;
            if (isset($labels[$type])) {
                $capabilities[] = $labels[$type];
            }
        }

        return [
            'name'         => trim((string) $app->name),
            'capabilities' => $capabilities,
            'tools'        => $this->toolNames($app),
            'keywords'     => $this->stringList($app->keyword, 8, 20),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function toolNames(AiApplication $app): array
    {
        $tools = [];
        foreach ([(array) ($app->mcp_json ?? []), (array) ($app->json ?? [])] as $config) {
            if (! isset($config['tools']) || ! is_array($config['tools'])) {
                continue;
            }
            foreach ($config['tools'] as $tool) {
                if (is_array($tool)) {
                    $tool = $tool['name'] ?? '';
                }
                $tool = trim((string) $tool);
                if ($tool !== '') {
                    $tools[] = $tool;
                }
            }
        }

        return array_slice(array_values(array_unique($tools)), 0, 24);
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value, int $limit, int $maxChars): array
    {
        if (! is_array($value)) {
            return [];
        }
        $items = [];
        foreach ($value as $item) {
            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }
            $items[] = mb_substr($text, 0, $maxChars);
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * @return array<int, mixed>
     */
    private function rawItems(string $content): array
    {
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            $start = strpos($content, '{');
            $end   = strrpos($content, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
            }
        }
        if (is_array($decoded)) {
            $raw = $decoded['suggestions'] ?? $decoded['questions'] ?? null;
            if (is_array($raw)) {
                return array_values($raw);
            }
            if ($decoded === [] || array_keys($decoded) === range(0, count($decoded) - 1)) {
                return array_values($decoded);
            }
        }

        return preg_split('/\r\n|\n|\r/', $content) ?: [];
    }
}
