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

use App\Constants\Ai\AiModelEnum;
use App\Constants\Ai\AiModelPresetEnum;
use App\Http\Model\Ai\AiModel;
use App\Http\Service\Ai\AiModelService;
use Tuoluojiang\Open\services\ai\BaseCurl;
use Tuoluojiang\Open\services\ai\BaseOption;
use Illuminate\Support\Facades\Log;

/**
 * 通用结构化抽槽：按写工具 schema/fields 从用户话术提炼标量槽位.
 *
 * 契约见 docs/AI会话改造-P2-SchemaSlotFiller契约.md。
 * 失败/超时一律返回空 slots，不中断写确认流程.
 */
class SchemaSlotFiller
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
你是 OA 写操作表单抽槽器。仅根据用户原文，把能明确对应到 fields 的标量值填入 slots。
规则：
1. 只输出 JSON：{"slots":{"字段键":"值"},"notes":[]}
2. 未知键不要输出；原文无依据不要编造。
3. select 字段的值必须是 options 里的 value 或能唯一对应的 label。
4. 只填标量（字符串/数字/布尔），不要填对象或数组。
PROMPT;

    /**
     * 从话术抽取并校验槽位.
     *
     * @param array<int, array<string, mixed>> $fields 表单字段描述
     * @param array<int, string> $texts 当前句 + 近几轮 user 原文
     * @param string $toolName 写工具名
     * @param AiModel|null $model 可选会话模型；缺省或失败时仅正则兜底
     *
     * @return array{slots: array<string, scalar>, notes: array<int, string>}
     */
    public function fill(array $fields, array $texts, string $toolName = '', ?AiModel $model = null): array
    {
        $fields = array_values(array_filter($fields, static fn ($field): bool => is_array($field)));
        $texts  = array_values(array_filter(array_map(
            static fn ($text): string => trim((string) $text),
            $texts
        ), static fn (string $text): bool => $text !== ''));

        if ($fields === [] || $texts === []) {
            return ['slots' => [], 'notes' => []];
        }

        $joined = implode("\n", $texts);
        $slots  = $this->extractByModel($fields, $texts, $toolName, $model);
        $slots  = array_merge($this->extractByRegex($fields, $joined), $slots);
        $slots  = $this->validateSlots($fields, $slots);

        return ['slots' => $slots, 'notes' => []];
    }

    /**
     * 校验并收敛 slots：未知键丢弃；select 必须命中 options.
     *
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $slots
     *
     * @return array<string, scalar>
     */
    public function validateSlots(array $fields, array $slots): array
    {
        $index = $this->indexFields($fields);
        $valid = [];
        foreach ($slots as $key => $value) {
            $key = (string) $key;
            if ($key === '' || ! isset($index[$key])) {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $field   = $index[$key];
            $options = $this->normalizeOptions((array) ($field['options'] ?? []));
            if ($options !== []) {
                $matched = $this->matchOptionValue($options, $value);
                if ($matched === null) {
                    continue;
                }
                $valid[$key] = $matched;
                continue;
            }
            if (is_bool($value)) {
                $valid[$key] = $value;
                continue;
            }
            if (is_int($value) || is_float($value)) {
                $valid[$key] = $value;
                continue;
            }
            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }
            $valid[$key] = $text;
        }

        return $valid;
    }

    /**
     * 金额 / 手机 / 日期强模式兜底（不靠事由类关键词）.
     *
     * @param array<int, array<string, mixed>> $fields
     *
     * @return array<string, scalar>
     */
    public function extractByRegex(array $fields, string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        $slots = [];
        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = $this->fieldKey($field);
            if ($key === '' || isset($slots[$key])) {
                continue;
            }
            if ((array) ($field['options'] ?? []) !== []) {
                continue;
            }

            $aliases = array_merge(
                [$key, (string) ($field['symbol'] ?? ''), (string) ($field['title'] ?? ''), (string) ($field['field_label'] ?? '')],
                array_map('strval', (array) ($field['aliases'] ?? []))
            );
            $aliases = array_values(array_filter(array_map('trim', $aliases)));
            $type    = strtolower((string) ($field['type'] ?? $field['input_type'] ?? ''));

            if ($this->looksLikeAmountField($key, $aliases, $type)) {
                $amount = $this->matchAmount($text);
                if ($amount !== null) {
                    $slots[$key] = $amount;
                }
                continue;
            }
            if ($this->looksLikePhoneField($key, $aliases, $type)) {
                $phone = $this->matchPhone($text);
                if ($phone !== null) {
                    $slots[$key] = $phone;
                }
                continue;
            }
            if ($this->looksLikeDateField($key, $aliases, $type)) {
                $date = $this->matchDate($text);
                if ($date !== null) {
                    $slots[$key] = $date;
                }
            }
        }

        return $slots;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<int, string> $texts
     *
     * @return array<string, mixed>
     */
    private function extractByModel(array $fields, array $texts, string $toolName, ?AiModel $model): array
    {
        if ($model === null || trim((string) ($model->api_key ?? '')) === '') {
            return [];
        }

        $timeoutMs = max(500, (int) config('ai.agent.schema_slot_filler.timeout_ms', 3000));
        $payload   = [
            'tool_name' => $toolName,
            'texts'     => array_map(
                static fn (string $text): string => mb_substr($text, 0, 500),
                array_slice($texts, 0, 5)
            ),
            'fields'    => array_map(static function (array $field): array {
                $item = [
                    'field'    => (string) ($field['field'] ?? $field['field_key'] ?? $field['symbol'] ?? ''),
                    'symbol'   => (string) ($field['symbol'] ?? $field['field'] ?? $field['field_key'] ?? ''),
                    'title'    => (string) ($field['title'] ?? $field['field_label'] ?? ''),
                    'type'     => (string) ($field['type'] ?? $field['input_type'] ?? 'input'),
                    'required' => (bool) ($field['required'] ?? false),
                ];
                $options = (array) ($field['options'] ?? []);
                if ($options !== []) {
                    $item['options'] = array_slice($options, 0, 30);
                }
                $aliases = (array) ($field['aliases'] ?? []);
                if ($aliases !== []) {
                    $item['aliases'] = array_slice(array_values(array_map('strval', $aliases)), 0, 8);
                }

                return $item;
            }, array_slice($fields, 0, 40)),
        ];

        try {
            $option              = app(AiModelService::class)->makeChatOption($model, false);
            $option->stream      = false;
            $option->tools       = [];
            $option->temperature = 0;
            $option->setMessage(self::SYSTEM_PROMPT, BaseOption::RULE_SYSTEM);
            $option->setMessage(
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
                BaseOption::RULE_USER
            );
            $option->options(array_merge(
                app(AiModelService::class)->followUpSamplingOptions($model),
                ['max_tokens' => (int) config('ai.agent.schema_slot_filler.max_tokens', 400)]
            ));

            $preset   = AiModelPresetEnum::get((string) $model->provider);
            $auth     = (string) ($preset['auth'] ?? AiModelEnum::AUTH_BEARER);
            $timeout  = max(1, (int) ceil($timeoutMs / 1000));
            $response = (new BaseCurl(trim((string) $model->api_key), $option->baseUrl, $auth))
                ->setBody($option)
                ->send(url: $option->url, timeout: $timeout);
            if (! empty($response['error'])) {
                return [];
            }

            $content = trim((string) ($response['choices'][0]['message']['content'] ?? ''));

            return $this->parseModelSlots($content);
        } catch (\Throwable $e) {
            Log::channel((string) config('ai.http_log.channel', 'ai'))->warning('SchemaSlotFiller 抽槽失败', [
                'tool_name' => $toolName,
                'error'     => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseModelSlots(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        $content = trim($content);
        if ($content === '') {
            return [];
        }
        if (! str_starts_with($content, '{')) {
            if (preg_match('/\{.*\}/s', $content, $matches) !== 1) {
                return [];
            }
            $content = $matches[0];
        }
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return [];
        }
        $slots = $decoded['slots'] ?? $decoded;
        if (! is_array($slots)) {
            return [];
        }

        return $slots;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     *
     * @return array<string, array<string, mixed>>
     */
    private function indexFields(array $fields): array
    {
        $index = [];
        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }
            foreach ([$this->fieldKey($field), (string) ($field['symbol'] ?? ''), (string) ($field['field'] ?? '')] as $key) {
                $key = trim($key);
                if ($key !== '') {
                    $index[$key] = $field;
                }
            }
        }

        return $index;
    }

    /** @param array<string, mixed> $field */
    private function fieldKey(array $field): string
    {
        return trim((string) ($field['field'] ?? $field['field_key'] ?? $field['symbol'] ?? ''));
    }

    /**
     * @param array<int, mixed> $options
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $option) {
            if (is_scalar($option)) {
                $value = trim((string) $option);
                if ($value === '') {
                    continue;
                }
                $normalized[] = ['value' => $value, 'label' => $value];
                continue;
            }
            if (! is_array($option)) {
                continue;
            }
            $value = $option['value'] ?? $option['id'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $normalized[] = [
                'value' => (string) $value,
                'label' => (string) ($option['label'] ?? $option['name'] ?? $option['title'] ?? $value),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array{value: string, label: string}> $options
     */
    private function matchOptionValue(array $options, mixed $value): string|int|null
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        foreach ($options as $option) {
            if ((string) $option['value'] === $raw || (string) $option['label'] === $raw) {
                $matched = $option['value'];
                if (is_numeric($matched) && ! str_contains((string) $matched, '.')) {
                    return (int) $matched;
                }

                return $matched;
            }
        }

        return null;
    }

    /** @param array<int, string> $aliases */
    private function looksLikeAmountField(string $key, array $aliases, string $type): bool
    {
        if (in_array($type, ['number', 'integer'], true)) {
            return (bool) preg_match('/amount|price|money|fee|cost|合计|金额|费用|价格/i', $key . implode('', $aliases));
        }

        return (bool) preg_match('/(?:^|_)(amount|price|money|fee|cost)(?:_|$)/i', $key)
            || (bool) preg_match('/金额|费用|合计|价格/', implode(',', $aliases));
    }

    /** @param array<int, string> $aliases */
    private function looksLikePhoneField(string $key, array $aliases, string $type): bool
    {
        return (bool) preg_match('/phone|mobile|tel/i', $key)
            || (bool) preg_match('/电话|手机|手机号/', implode(',', $aliases));
    }

    /** @param array<int, string> $aliases */
    private function looksLikeDateField(string $key, array $aliases, string $type): bool
    {
        if (in_array($type, ['date', 'datetime'], true)) {
            return true;
        }

        return (bool) preg_match('/date|time|day/i', $key)
            || (bool) preg_match('/日期|时间|开始|结束/', implode(',', $aliases));
    }

    private function matchAmount(string $text): ?string
    {
        if (preg_match('/(?:金额|费用|合计|餐费|报销)[^\d]{0,6}(\d+(?:\.\d{1,2})?)/u', $text, $m) === 1) {
            return $m[1];
        }
        if (preg_match('/¥\s*(\d+(?:\.\d{1,2})?)/u', $text, $m) === 1) {
            return $m[1];
        }
        if (preg_match('/(\d+(?:\.\d{1,2})?)\s*(?:元|块)/u', $text, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    private function matchPhone(string $text): ?string
    {
        if (preg_match('/(?<!\\d)(1\\d{10})(?!\\d)/', $text, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    private function matchDate(string $text): ?string
    {
        if (preg_match('/(\\d{4}-\\d{2}-\\d{2}(?:[ T]\\d{2}:\\d{2}(?::\\d{2})?)?)/', $text, $m) === 1) {
            return $m[1];
        }
        if (preg_match('/(\\d{1,2})月(\\d{1,2})日/u', $text, $m) === 1) {
            $year = (int) date('Y');

            return sprintf('%04d-%02d-%02d', $year, (int) $m[1], (int) $m[2]);
        }
        $today = new \DateTimeImmutable('today');
        if (str_contains($text, '今天')) {
            return $today->format('Y-m-d');
        }
        if (str_contains($text, '明天')) {
            return $today->modify('+1 day')->format('Y-m-d');
        }
        if (str_contains($text, '后天')) {
            return $today->modify('+2 day')->format('Y-m-d');
        }

        return null;
    }
}
