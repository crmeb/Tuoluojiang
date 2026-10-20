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
use App\Http\Dao\Ai\AgentPendingInputDao;
use App\Http\Model\Ai\AgentPendingInput;
use App\Http\Model\Ai\AgentRun;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Ai\Agent\Trace\AgentTracer;
use App\Http\Service\Ai\AiToolCallService;
use App\Http\Service\Ai\ChatMessageBuilderService;
use App\Http\Service\Frame\FrameService;
use Tuoluojiang\Open\services\ai\BaseOption;
use Illuminate\Support\Facades\Schema;

/**
 * Agent 信息确认：检测、持久化、流式通知与续跑注入.
 *
 * 职责：ToolLoop 检测 input_required，持久化 pending，并支持写确认与用户补录后续跑.
 * 检测 / 持久化 / 续跑注入同文件分区实现（原 Detector 已并入）.
 * 注意：当前聊天前端仅支持 select/text；人员/部门类字段需下发带选项的 select，
 * 不可把 MCP 面向 Agent 的 hint（含 tool 名）直接展示给用户.
 * 在 Agent 流程中的位置：ToolLoopExecutor 每轮结束后调用；写确认续跑由 AiConversationService 衔接.
 */
class AgentInputRequiredHelper
{
    /** 单条消息最多产生的补录项总数，防止依赖链异常时反复弹框. */
    private const MAX_PROMPTS_PER_MESSAGE = 8;

    /** 人员下拉候选上限（全公司自选时兜底加载）. */
    private const PERSONNEL_OPTION_LIMIT = 200;

    /** 部门下拉候选上限. */
    private const DEPARTMENT_OPTION_LIMIT = 300;

    /**
     * 判断业务流上下文字段是否已有非空值.
     *
     * @param array<string, mixed> $flow 业务流上下文
     * @param string $key 字段键
     *
     * @return bool 已填写时为 true；null、空串、空数组视为未填写
     */
    public static function flowHas(array $flow, string $key): bool
    {
        if ($key === '') {
            return false;
        }
        if (! array_key_exists($key, $flow)) {
            return false;
        }
        $value = $flow[$key];
        if ($value === null || $value === '') {
            return false;
        }
        if (is_array($value) && $value === []) {
            return false;
        }

        return true;
    }

    /**
     * 写入业务流上下文中已确认字段值.
     *
     * @param array<string, mixed> $flow 业务流上下文（引用传递）
     * @param string $key 字段键
     * @param mixed $value 字段值
     */
    public static function flowSet(array &$flow, string $key, mixed $value): void
    {
        if ($key === '') {
            return;
        }
        $flow[$key] = $value;
    }

    /**
     * 读取业务流上下文字段值.
     *
     * @param array<string, mixed> $flow 业务流上下文
     * @param string $key 字段键
     * @param mixed $default 默认值
     *
     * @return mixed 字段值或默认值
     */
    public static function flowGet(array $flow, string $key, mixed $default = null): mixed
    {
        return $flow[$key] ?? $default;
    }

    /**
     * 生成可注入 system 消息的已确认信息摘要.
     *
     * @param array<string, mixed> $flow 业务流上下文
     *
     * @return string Markdown 片段，无已确认字段时返回空串
     */
    public static function flowPromptSnippet(array $flow): string
    {
        if ($flow === []) {
            return '';
        }

        $lines = ['# 用户已确认信息'];
        foreach ($flow as $key => $value) {
            $encoded = is_scalar($value) ? (string) $value : (json_encode($value, JSON_UNESCAPED_UNICODE) ?: '');
            $lines[] = '- ' . $key . '：' . $encoded;
        }

        return implode("\n", $lines);
    }

    /**
     * 构造待补充信息描述（原 AgentInputPrompt）.
     *
     * @param array<int, array<string, mixed>> $options
     * @param array<string, mixed> $meta
     *
     * @return array{field_key: string, field_label: string, input_type: string, options: array, message: string, source_tool: string, meta: array}
     */
    public static function makePrompt(
        string $fieldKey,
        string $fieldLabel,
        string $inputType = 'select',
        array $options = [],
        string $message = '',
        string $sourceTool = '',
        array $meta = []
    ): array {
        return [
            'field_key'   => $fieldKey,
            'field_label' => $fieldLabel,
            'input_type'  => $inputType,
            'options'     => $options,
            'message'     => $message !== '' ? $message : ('请填写' . $fieldLabel),
            'source_tool' => $sourceTool,
            'meta'        => $meta,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $options
     * @param array<string, mixed> $meta
     *
     * @return array{field_key: string, field_label: string, input_type: string, options: array, message: string, source_tool: string, meta: array}
     */
    public static function selectPrompt(
        string $fieldKey,
        string $fieldLabel,
        array $options,
        string $message = '',
        string $sourceTool = '',
        array $meta = []
    ): array {
        return self::makePrompt($fieldKey, $fieldLabel, 'select', $options, $message, $sourceTool, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     *
     * @return array{field_key: string, field_label: string, input_type: string, options: array, message: string, source_tool: string, meta: array}
     */
    public static function textPrompt(
        string $fieldKey,
        string $fieldLabel,
        string $message = '',
        string $sourceTool = '',
        array $meta = []
    ): array {
        return self::makePrompt($fieldKey, $fieldLabel, 'text', [], $message, $sourceTool, $meta);
    }

    /**
     * 扫描一轮工具结果，提取首个待用户补充的信息项.
     *
     * @param array<int, array<string, mixed>> $roundResults 单轮工具执行结果
     * @param array<string, mixed> $flow 已累积的业务流上下文
     *
     * @return array<int, array> 待补充项列表，优先级：选择 > 表单 > 错误
     */
    public function detectPrompts(array $roundResults, array &$flow): array
    {
        $selectionPrompts = [];
        $formPrompts      = [];
        $errorPrompts     = [];
        $seenKeys         = [];

        foreach ($roundResults as $toolResult) {
            $name   = (string) ($toolResult['name'] ?? '');
            $result = $toolResult['result'] ?? [];
            if (! is_array($result)) {
                continue;
            }

            if (! empty($result['needs_input']) && is_array($result['fields'] ?? null)) {
                foreach ($result['fields'] as $field) {
                    if (! is_array($field)) {
                        continue;
                    }
                    $fieldKey = (string) ($field['field_key'] ?? '');
                    // reselect：上一轮选中的值导致后续无可用候选（如该仓库无可售产品），
                    // 作废旧值以便重新询问，否则会被 flowHas 命中而静默跳过.
                    if (! empty($field['meta']['reselect']) && $fieldKey !== '') {
                        unset($flow[$fieldKey]);
                    }
                    if ($fieldKey === '' || self::flowHas($flow, $fieldKey) || isset($seenKeys[$fieldKey])) {
                        continue;
                    }
                    $prompt = self::makePrompt(
                        $fieldKey,
                        (string) ($field['field_label'] ?? $fieldKey),
                        (string) ($field['input_type'] ?? 'text'),
                        (array) ($field['options'] ?? []),
                        (string) ($field['message'] ?? ('请填写' . ($field['field_label'] ?? $fieldKey))),
                        $name,
                        (array) ($field['meta'] ?? [])
                    );
                    $formPrompts[] = $prompt;
                    $seenKeys[$fieldKey] = true;
                }
                continue;
            }

            if ($this->tryAutoFillSingleOption($name, $result, $flow)) {
                continue;
            }

            if (! empty($result['needs_selection'])) {
                $prompt = ($name === 'approve_process_get' || ! empty($result['selection_nodes']))
                    ? $this->fromProcessSelection($name, $result, $flow)
                    : $this->fromNeedsSelection($name, $result);
                if ($prompt !== null && ! self::flowHas($flow,$prompt['field_key']) && ! isset($seenKeys[$prompt['field_key']])) {
                    $selectionPrompts[] = $prompt;
                    $seenKeys[$prompt['field_key']] = true;
                }
                continue;
            }

            if ($this->isFormGetTool($name)) {
                // 动态表单字段改由 confirm_required 可编辑表单一次补齐，不再逐步弹补录卡.
                // 仅保留请假时长等复合字段的拆问（仍需 input_required）；
                // 勿用 message 含「开始/结束时间」启发式，否则出差 datePicker 会误入逐步补录.
                foreach ($this->collectFormFieldPrompts($result, $flow, $name) as $prompt) {
                    if (! $this->isComplexFormFieldPrompt($prompt)) {
                        continue;
                    }
                    if (! isset($seenKeys[$prompt['field_key']])) {
                        $formPrompts[] = $prompt;
                        $seenKeys[$prompt['field_key']] = true;
                    }
                }
                continue;
            }

            $toolPrompt = match ($name) {
                'approve_holiday_type_list' => $this->fromHolidayTypeList($result, $flow),
                'approve_type_list'         => $this->fromApproveTypeList($result, $flow),
                default                     => null,
            };
            if ($toolPrompt !== null && ! isset($seenKeys[$toolPrompt['field_key']])) {
                $selectionPrompts[] = $toolPrompt;
                $seenKeys[$toolPrompt['field_key']] = true;
                continue;
            }

            if (! empty($result['error']) && ! empty($result['field']) && ! self::flowHas($flow,(string) $result['field'])) {
                $fieldKey = (string) ($result['symbol'] ?? $result['field'] ?? 'field');
                if (! isset($seenKeys[$fieldKey])) {
                    $errorPrompts[] = $this->fromFieldError($name, $result);
                    $seenKeys[$fieldKey] = true;
                }
            }
        }

        if ($selectionPrompts !== []) {
            return [$selectionPrompts[0]];
        }
        if ($formPrompts !== []) {
            return $formPrompts;
        }
        if ($errorPrompts !== []) {
            return $errorPrompts;
        }

        return [];
    }

    /** 仅有一个选项时自动写入 flow 上下文，跳过用户选择. */
    private function tryAutoFillSingleOption(string $toolName, array $result, array &$flow): bool
    {
        if ($toolName === 'approve_holiday_type_list' && ! self::flowHas($flow,'holidayType')) {
            $list = (array) ($result['list'] ?? []);
            if (count($list) === 1) {
                $item = $list[0];
                self::flowSet($flow,'holidayType', $item['value'] ?? $item['id'] ?? '');

                return true;
            }
        }

        if ($toolName === 'approve_type_list' && ! self::flowHas($flow,'approve_id')) {
            $list = (array) ($result['list'] ?? []);
            if (count($list) === 1) {
                self::flowSet($flow,'approve_id', $list[0]['id'] ?? 0);

                return true;
            }
        }

        return false;
    }

    /**
     * 审批流程自选节点：推送带人员名单的 select，供前端下拉选择.
     *
     * @param array<string, mixed> $result
     */
    private function fromProcessSelection(string $toolName, array $result, array $flow): ?array
    {
        if (self::flowHas($flow,'process')) {
            return null;
        }

        foreach ((array) ($result['selection_nodes'] ?? []) as $node) {
            if (! is_array($node)) {
                continue;
            }

            $selection = (array) ($node['selection'] ?? []);
            $uniqued    = (string) ($node['uniqued'] ?? $selection['uniqued'] ?? '');
            if ($uniqued === '') {
                continue;
            }

            $fieldKey = 'process_users_' . $uniqued;
            if (self::flowHas($flow,$fieldKey)) {
                continue;
            }

            $needsSelection = ! empty($node['needs_selection']) || ! empty($selection['needs_selection']);
            if (! $needsSelection) {
                continue;
            }

            $required = (bool) ($selection['required'] ?? true);
            $preset   = (array) ($selection['preset_users'] ?? $node['users'] ?? []);
            if (! $required && $preset !== []) {
                continue;
            }

            $title      = (string) ($node['title'] ?? $selection['title'] ?? '审批人');
            $selectMode = (int) ($selection['select_mode'] ?? $node['select_mode'] ?? 1);
            $options    = $this->normalizeOptions((array) ($selection['options'] ?? $node['options'] ?? []));
            // 指定范围为全公司时 options 为空，需兜底加载在职人员供前端下拉.
            if ($options === []) {
                $options = $this->loadPersonnelSelectOptions();
            }
            $mode  = (string) ($selection['mode'] ?? $node['mode'] ?? '');
            $label = match ($mode) {
                'cc_self_select'       => '抄送人',
                'approver_self_select' => $title !== '' ? $title : '审批人',
                default                => $title !== '' ? $title : '流程节点',
            };
            // 用户可见文案：禁止透出 MCP Agent hint（含 options/personnel_search 等内部词）.
            $message = '请选择' . $label . ($selectMode === 2 ? '（可多选）' : '（单选）');

            return self::makePrompt(
                $fieldKey,
                $label,
                $options !== [] ? 'select' : 'text',
                $options,
                $message,
                $toolName,
                [
                    'uniqued'     => $uniqued,
                    'select_mode' => $selectMode,
                    'mode'        => $mode,
                    'node_title'  => $title,
                    'required'    => $required,
                    'picker'      => 'personnel',
                ]
            );
        }

        return null;
    }

    /** 从 needs_selection 结构构建下拉选择类 input_required. */
    private function fromNeedsSelection(string $toolName, array $result): ?array
    {
        $fieldKey = (string) ($result['field_key'] ?? 'approve_id');

        $options = $this->normalizeOptions(
            (array) ($result['candidates'] ?? $result['selection_nodes'] ?? $result['options'] ?? $result['list'] ?? [])
        );
        if ($options === []) {
            return null;
        }

        $label = (string) ($result['types_label'] ?? $result['title'] ?? $result['field_label'] ?? '选项');
        if ($fieldKey === 'approve_id') {
            $label = '审批模板';
        } elseif ($fieldKey === 'process') {
            $label = '审批流程节点';
        }

        return self::selectPrompt(
            $fieldKey,
            $label,
            $options,
            (string) ($result['message'] ?? ('请选择' . $label)),
            $toolName,
            ['hint' => (string) ($result['hint'] ?? '')]
        );
    }

    /** 从假期类型列表构建多选其一的 input_required. */
    private function fromHolidayTypeList(array $result, array $flow): ?array
    {
        if (self::flowHas($flow,'holidayType')) {
            return null;
        }

        $options = $this->normalizeOptions((array) ($result['list'] ?? []));
        if (count($options) <= 1) {
            return null;
        }

        return self::selectPrompt(
            'holidayType',
            '假期类型',
            $options,
            '请选择假期类型',
            'approve_holiday_type_list',
            ['hint' => (string) ($result['hint'] ?? '')]
        );
    }

    /** 从审批模板列表构建模板选择 input_required. */
    private function fromApproveTypeList(array $result, array $flow): ?array
    {
        if (self::flowHas($flow,'approve_id')) {
            return null;
        }

        // 仅「同一 types 存在多个模板」才弹选；未筛选返回的全部人事类型不得当成候选逼用户手选.
        $duplicateTypes = array_values(array_filter(
            array_map('intval', (array) ($result['duplicate_types'] ?? [])),
            static fn (int $types): bool => $types > 0
        ));
        if ($duplicateTypes === []) {
            return null;
        }

        $dupSet = array_fill_keys($duplicateTypes, true);
        $list   = array_values(array_filter(
            (array) ($result['list'] ?? []),
            static fn ($item): bool => is_array($item) && isset($dupSet[(int) ($item['types'] ?? 0)])
        ));
        if (count($list) <= 1) {
            return null;
        }

        return self::selectPrompt(
            'approve_id',
            '审批模板',
            $this->normalizeOptions($list),
            '同一业务类型存在多个审批模板，请选择要发起的模板',
            'approve_type_list',
            ['duplicate_types' => $duplicateTypes]
        );
    }

    /** 从表单字段定义收集必填且未填写的 input_required 列表. */
    private function collectFormFieldPrompts(array $result, array &$flow, string $sourceTool): array
    {
        $prompts = [];

        foreach ((array) ($result['fields'] ?? []) as $field) {
            if (! is_array($field) || ! ($field['required'] ?? false)) {
                continue;
            }
            $fieldKey   = (string) ($field['field'] ?? '');
            $symbol     = (string) ($field['symbol'] ?? '');
            $contextKey = $symbol !== '' ? $symbol : $fieldKey;
            if ($fieldKey === '' || self::flowHas($flow,$contextKey) || self::flowHas($flow,$fieldKey)) {
                continue;
            }
            if ((bool) ($field['hidden'] ?? false) || (bool) ($field['disabled'] ?? false)) {
                continue;
            }

            // 请假时长为复合对象：拆成开始/结束时间询问，时长由起止自动计算.
            if (self::isLeaveDurationField($field)) {
                self::assembleIntoFlow($flow, $field);
                if (self::flowHas($flow,$contextKey) || self::flowHas($flow,$fieldKey)) {
                    continue;
                }
                foreach (self::expandFieldPrompts($field, $sourceTool) as $prompt) {
                    if (self::flowHas($flow,$prompt['field_key'])) {
                        continue;
                    }
                    $prompts[] = $prompt;
                }
                continue;
            }

            $title   = (string) ($field['title'] ?? $contextKey);
            $options = $this->normalizeOptions((array) ($field['options'] ?? []));
            $picker  = $this->resolvePickerKind($field);
            if ($picker !== null && $options === []) {
                $options = $picker === 'department'
                    ? $this->loadDepartmentSelectOptions()
                    : $this->loadPersonnelSelectOptions();
            }
            if ($options !== []) {
                $prompts[] = self::selectPrompt($contextKey, $title, $options, '请选择' . $title, $sourceTool, [
                    'field'  => $fieldKey,
                    'symbol' => $symbol,
                    'type'   => (string) ($field['type'] ?? ''),
                    'picker' => $picker ?? 'select',
                    'aliases' => (array) ($field['aliases'] ?? []),
                    'normalizer' => (string) ($field['normalizer'] ?? ''),
                ]);
                continue;
            }

            $inputType = $this->mapFieldInputType($field);
            if ($inputType === 'hidden') {
                continue;
            }
            // 人员/部门无候选时仍给友好提示，避免透出内部字段类型英文名.
            if ($picker !== null) {
                $prompts[] = self::makePrompt(
                    $contextKey,
                    $title,
                    'text',
                    [],
                    '请填写' . $title . '姓名',
                    $sourceTool,
                    [
                    'field'  => $fieldKey,
                    'symbol' => $symbol,
                    'type'   => (string) ($field['type'] ?? ''),
                    'picker' => $picker,
                    'aliases' => (array) ($field['aliases'] ?? []),
                    'normalizer' => (string) ($field['normalizer'] ?? ''),
                ]
                );
                continue;
            }

            $prompts[] = self::makePrompt(
                $contextKey,
                $title,
                $inputType,
                [],
                '请填写' . $title,
                $sourceTool,
                [
                    'field'         => $fieldKey,
                    'symbol'        => $symbol,
                    'type'          => (string) ($field['type'] ?? ''),
                    'value_example' => $field['value_example'] ?? null,
                    'aliases'       => (array) ($field['aliases'] ?? []),
                    'normalizer'    => (string) ($field['normalizer'] ?? ''),
                ]
            );
        }

        return $prompts;
    }

    /** 判断工具名是否为表单读取类（*_form_get）. */
    private function isFormGetTool(string $toolName): bool
    {
        return in_array($toolName, ['approve_form_get', 'custom_form_get', 'write_form_get'], true)
            || str_ends_with($toolName, '_form_get');
    }

    /**
     * 表单字段补录是否仍需逐步弹卡（请假时长复合拆问）.
     *
     * 普通 datePicker（如「出差开始时间」）交给 confirm_required 一次编辑，不算复杂字段.
     *
     * @param array<string, mixed> $prompt
     */
    private function isComplexFormFieldPrompt(array $prompt): bool
    {
        $meta         = (array) ($prompt['meta'] ?? []);
        $fieldKey     = (string) ($prompt['field_key'] ?? '');
        $fieldLabel   = (string) ($prompt['field_label'] ?? '');
        $type         = (string) ($meta['type'] ?? '');
        $parentSymbol = (string) ($meta['parent_symbol'] ?? '');

        return ! empty($meta['composite'])
            || isset($meta['part'])
            || str_contains($fieldKey, 'leave')
            || str_contains($fieldLabel, '请假时长')
            || $type === 'timeFrom'
            || $parentSymbol === 'leaveDuration';
    }

    /** 从工具返回的 field 错误构建文本输入类 input_required. */
    private function fromFieldError(string $toolName, array $result): array
    {
        $fieldKey = (string) ($result['symbol'] ?? $result['field'] ?? 'field');
        $label    = (string) ($result['message'] ?? $fieldKey);

        return self::textPrompt(
            $fieldKey,
            $label,
            $label,
            $toolName,
            ['field' => (string) ($result['field'] ?? '')]
        );
    }

    /** 将候选列表规范化为前端可选的 value/label 结构. */
    private function normalizeOptions(array $items): array
    {
        $options = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $value = $item['value'] ?? $item['id'] ?? $item['uid'] ?? $item['uniqued'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $label = (string) ($item['label'] ?? $item['name'] ?? $item['title'] ?? $value);
            $options[] = [
                'value' => $value,
                'label' => $label,
                'meta'  => array_diff_key($item, array_flip(['value', 'label', 'name', 'title', 'id'])),
            ];
        }

        return $options;
    }

    /** 将表单字段 type/symbol 映射为前端 input_type. */
    private function mapFieldInputType(array $field): string
    {
        $symbol = (string) ($field['symbol'] ?? '');
        $type   = (string) ($field['type'] ?? '');

        if ($symbol === 'leaveDuration' || $type === 'timeFrom') {
            return 'duration';
        }
        if ($this->resolvePickerKind($field) !== null) {
            // 聊天前端仅支持 select/text；有选项时由 collectFormFieldPrompts 走 select.
            return 'select';
        }
        if (in_array($type, ['date', 'datePicker'], true)) {
            return 'date';
        }
        if (in_array($type, ['datetime', 'dateTimePicker'], true)) {
            return 'datetime';
        }
        if (in_array($type, ['textarea', 'textArea'], true)) {
            return 'textarea';
        }
        if (in_array($type, ['number'], true)) {
            return 'number';
        }

        return 'text';
    }

    /**
     * 识别人员/部门选择类字段.
     *
     * @param array<string, mixed> $field
     *
     * @return null|string personnel|department
     */
    private function resolvePickerKind(array $field): ?string
    {
        $type = strtolower((string) ($field['type'] ?? ''));

        if (in_array($type, ['personnel', 'userselect', 'member', 'multiplemember'], true)) {
            return 'personnel';
        }
        if (in_array($type, ['department', 'departmenttree', 'frame'], true)) {
            return 'department';
        }

        return null;
    }

    /**
     * 加载在职人员下拉选项（全公司自选兜底）.
     *
     * @return array<int, array{value: mixed, label: string, meta?: array<string, mixed>}>
     */
    private function loadPersonnelSelectOptions(): array
    {
        try {
            $result = app(AdminService::class)->adminList(
                ['entid' => 1, 'status' => 1, 'types' => [1, 2, 3], 'time' => ''],
                1,
                self::PERSONNEL_OPTION_LIMIT
            );
            $items = [];
            foreach ((array) ($result['list'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $id = (int) ($row['id'] ?? $row['card_id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $name = (string) ($row['name'] ?? '');
                if ($name === '' && is_array($row['card'] ?? null)) {
                    $name = (string) ($row['card']['name'] ?? '');
                }
                $items[] = [
                    'id'   => $id,
                    'name' => $name !== '' ? $name : ('员工#' . $id),
                ];
            }

            return $this->normalizeOptions($items);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * 加载部门下拉选项（扁平化树形结构）.
     *
     * @return array<int, array{value: mixed, label: string, meta?: array<string, mixed>}>
     */
    private function loadDepartmentSelectOptions(): array
    {
        try {
            $tree = app(FrameService::class)->getList(['status' => 1, 'is_show' => 1]);
            $flat = [];
            $this->flattenDepartmentTree($tree, $flat, '');

            if (count($flat) > self::DEPARTMENT_OPTION_LIMIT) {
                $flat = array_slice($flat, 0, self::DEPARTMENT_OPTION_LIMIT);
            }

            return $this->normalizeOptions($flat);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * 将部门树展平为 [{id,name}]，子级名称带路径前缀便于区分.
     *
     * @param array<int, mixed> $nodes
     * @param array<int, array{id: int|string, name: string}> $flat
     */
    private function flattenDepartmentTree(array $nodes, array &$flat, string $prefix): void
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            $id = $node['value'] ?? $node['id'] ?? null;
            if ($id === null || $id === '') {
                continue;
            }
            $label = (string) ($node['label'] ?? $node['name'] ?? $id);
            $name  = $prefix !== '' ? ($prefix . ' / ' . $label) : $label;
            $flat[] = ['id' => $id, 'name' => $name];
            $children = (array) ($node['children'] ?? []);
            if ($children !== []) {
                $this->flattenDepartmentTree($children, $flat, $name);
            }
        }
    }
    private AgentPendingInputDao $pendingDao;

    private AgentTracer $tracer;

    public function __construct(
        AgentPendingInputDao $pendingDao,
        AgentTracer $tracer
    ) {
        $this->pendingDao = $pendingDao;
        $this->tracer     = $tracer;
    }

    /**
     * 判断 agent_pending_inputs 表是否可用.
     *
     * @return bool 表存在时为 true
     */
    public function isEnabled(): bool
    {
        return Schema::hasTable('agent_pending_inputs');
    }

    /**
     * 在工具循环中检测是否需要用户补充信息.
     *
     * @param array<int, array<string, mixed>> $roundResults 单轮工具结果
     * @param array<string, mixed> $flow 业务流上下文
     * @param array<string, mixed> $request 工具循环请求
     * @param int $nextRound 下一执行轮次
     * @param array<string, mixed> $final 当前累积 final 状态
     * @param array $trace 轨迹记录器
     * @param array<int, array<string, mixed>> $accumulatedToolResults 累计工具结果
     *
     * @return AgentPendingInput|null 主待输入项，无需补充时返回 null
     */
    public function detect(
        array $roundResults,
        array &$flow,
        array $request,
        int $nextRound,
        array $final,
        array $trace,
        array $accumulatedToolResults = []
    ): ?AgentPendingInput {
        // 续跑轮同样允许再次询问：仓库→产品这类字段存在依赖，必须分步收集，
        // 「补录只发生一轮」的旧假设会让第二个字段永远问不出来。
        // 已填字段由 detectPrompts 内的 flowHas 拦下，不会重复询问；
        // 这里再按消息维度设一个轮次上限，兜底防止异常情况下反复弹框。
        if ($request['resume'] !== null && $this->promptRoundsExhausted($request)) {
            return null;
        }

        $prompts = $this->detectPrompts($roundResults, $flow);
        if ($prompts === []) {
            return null;
        }

        return $this->emitPrompts($prompts, $flow, $request, $nextRound, $final, $trace, $accumulatedToolResults);
    }

    /** 单条消息累计产生的补录项是否已达上限（防止异常情况下无限弹框）. */
    private function promptRoundsExhausted(array $request): bool
    {
        $messageUuid = (string) ($request['message_uuid'] ?? '');
        if ($messageUuid === '' || ! $this->isEnabled()) {
            return true;
        }

        try {
            return AgentPendingInput::query()
                ->where('message_uuid', $messageUuid)
                ->count() >= self::MAX_PROMPTS_PER_MESSAGE;
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * 写确认执行后仍缺自选审批人/抄送人：按 selection_nodes 弹补录卡.
     *
     * @param array<string, mixed> $writeResult
     * @param array<string, mixed> $flow
     * @param array<string, mixed> $request 需含 stream_service / message_uuid / run 等
     */
    public function emitProcessSelectionFromWriteResult(
        string $toolName,
        array $writeResult,
        array &$flow,
        array $request
    ): ?AgentPendingInput {
        unset($flow['process']);
        $prompts = $this->detectPrompts([[
            'name'   => $toolName,
            'result' => $writeResult,
        ]], $flow);
        if ($prompts === []) {
            return null;
        }

        return $this->emitPrompts(
            $prompts,
            $flow,
            $request,
            (int) ($request['next_round'] ?? 0),
            (array) ($request['final'] ?? []),
            (array) ($request['trace'] ?? []),
            (array) ($request['tool_results'] ?? [])
        );
    }

    /** 推送 input_required 流式事件并持久化循环状态. */
    private function emitPrompts(
        array $prompts,
        array &$flow,
        array $request,
        int $nextRound,
        array $final,
        array $trace,
        array $accumulatedToolResults = []
    ): ?AgentPendingInput {
        if ($prompts === []) {
            return null;
        }

        $state    = $this->buildLoopState($request, $nextRound, $final, $trace, $accumulatedToolResults);
        $pendings = [];
        $payloads = [];

        foreach ($prompts as $prompt) {
            $pendings[] = $this->createPendingInput($request, $prompt);
        }

        $primary = $pendings[0];
        foreach ($pendings as $pending) {
            $pendingId = (int) ($pending->id ?? 0);
            if ($pendingId > 0) {
                self::saveLoopStateCache($pendingId, $state);
            }
            $payloads[] = $this->formatStreamPayload($pending, $prompts[count($payloads)]);
        }

        if ($request['run']) {
            $this->tracer->markAwaitingInput($request['run']);
            $this->tracer->recordStep(
                $request['run'],
                AgentEnum::STEP_TYPE_INPUT_REQUIRED,
                ['prompts' => $prompts],
                [
                    'pending_input_id'  => (int) ($primary->id ?? 0),
                    'pending_input_ids' => array_map(static fn (AgentPendingInput $pending): int => (int) ($pending->id ?? 0), $pendings),
                ]
            );
        }

        $request['stream_service']->inputRequired($payloads);

        return $primary;
    }

    /** 持久化 pending input 记录（表不可用时返回内存对象）. */
    private function createPendingInput(array $request, array $prompt): AgentPendingInput
    {
        $meta = array_merge($prompt['meta'], [
            'source_tool' => $prompt['source_tool'],
            'message'     => $prompt['message'],
        ]);
        // 选人续跑依赖 write_pending_id：写入 meta，避免仅靠 loop cache.
        $flowWritePendingId = (int) (($request['flow_context']['write_pending_id'] ?? 0));
        if ($flowWritePendingId > 0) {
            $meta['write_pending_id'] = $flowWritePendingId;
        }

        if ($this->isEnabled()) {
            /** @var AgentPendingInput $pending */
            return $this->pendingDao->create([
                'run_id'       => (int) ($request['run']?->id ?? 0),
                'message_uuid' => $request['message_uuid'],
                'field_key'    => $prompt['field_key'],
                'field_label'  => $prompt['field_label'],
                'input_type'   => $prompt['input_type'],
                'options'      => $prompt['options'],
                'meta'         => $meta,
                'status'       => AgentEnum::PENDING_INPUT_STATUS_PENDING,
            ]);
        }

        return new AgentPendingInput([
            'id'           => 0,
            'run_id'       => (int) ($request['run']?->id ?? 0),
            'message_uuid' => $request['message_uuid'],
            'field_key'    => $prompt['field_key'],
            'field_label'  => $prompt['field_label'],
            'input_type'   => $prompt['input_type'],
            'options'      => $prompt['options'],
            'meta'         => $meta,
            'status'       => AgentEnum::PENDING_INPUT_STATUS_PENDING,
        ]);
    }

    /**
     * 将写确认 pending id 回写到选人 pending_input.meta（cache 丢失时可恢复）.
     */
    public function persistWritePendingIdOnInput(int $pendingInputId, int $writePendingId): void
    {
        if ($pendingInputId <= 0 || $writePendingId <= 0 || ! $this->isEnabled()) {
            return;
        }
        $pending = $this->findPending($pendingInputId);
        if (! $pending) {
            return;
        }
        $meta = (array) ($pending->meta ?? []);
        $meta['write_pending_id'] = $writePendingId;
        $this->pendingDao->update($pendingInputId, ['meta' => $meta]);
        $pending->meta = $meta;
    }

    /**
     * loop cache 丢失时，从 pending_input 重建最小可续跑状态（至少保留 write_pending_id）.
     *
     * @return null|array<string, mixed>
     */
    public function recoverLoopStateFromPendingInput(int $promptId): ?array
    {
        $pending = $this->findPending($promptId);
        if (! $pending || (string) ($pending->message_uuid ?? '') === '') {
            return null;
        }
        $meta = (array) ($pending->meta ?? []);
        $flow = [];
        $writePendingId = (int) ($meta['write_pending_id'] ?? 0);
        if ($writePendingId > 0) {
            $flow['write_pending_id'] = $writePendingId;
        }
        $runId = (int) ($pending->run_id ?? 0);
        $run   = $runId > 0 ? app(\App\Http\Dao\Ai\AgentRunDao::class)->get($runId) : null;

        return self::normalizeLoopState([
            'conversation_id' => (int) ($run->conversation_id ?? 0),
            'app_id'          => (int) ($run->app_id ?? 0),
            'model_id'        => 0,
            'uid'             => (int) ($run->uid ?? 0),
            'run_id'          => $runId,
            'message_uuid'    => (string) $pending->message_uuid,
            'question'        => '',
            'messages'        => [],
            'tools'           => [],
            'next_round'      => 0,
            'references'      => [],
            'decision'        => ['mcp' => true, 'sql' => false],
            'flow_context'    => $flow,
            'tool_results'    => [],
            'answer'          => '',
            'raw'             => '',
            'usage'           => [],
            'trace'           => [],
            'max_rounds'      => 5,
        ]);
    }

    /**
     * 将用户确认项格式化为 agent_resume 状态文案，供流式推送与 raw_response 回显.
     *
     * 单项：已收到确认：{display}，继续执行；
     * 多项：已收到确认：{field}：{display}；…，继续执行。
     * display 优先取非空 label，否则标量 value，否则 json_encode.
     *
     * @param array<int, array{field_label?: string, label?: string, value?: mixed}> $items 确认摘要项
     *
     * @return string 状态消息文案
     */
    public function formatResumeStatusMessage(array $items): string
    {
        if ($items === []) {
            return '已收到确认信息，继续执行';
        }

        $parts = [];
        foreach ($items as $item) {
            $fieldLabel = (string) ($item['field_label'] ?? '');
            $label      = (string) ($item['label'] ?? '');
            $value      = $item['value'] ?? null;
            $display    = $label !== ''
                ? $label
                : (is_scalar($value) ? (string) $value : (json_encode($value, JSON_UNESCAPED_UNICODE) ?: ''));

            if (count($items) === 1) {
                $parts[] = $display;
            } else {
                $parts[] = $fieldLabel . '：' . $display;
            }
        }

        return '已收到确认：' . implode('；', $parts) . '，继续执行';
    }

    /**
     * 将 resume 状态文案编码为前端 parseRawResponse 可识别的 SSE thinking 帧.
     *
     * 与 AiStreamService::status('agent_resume', ...) 发出的 data 帧结构一致：
     * type=thinking、stage=agent_resume、message=文案，并合并 extra 字段。
     *
     * @param string $message 状态文案
     * @param array<string, mixed> $extra 附加字段（prompt_id / prompt_ids / field_key 等）
     *
     * @return string SSE data 帧，编码失败时为空串
     */
    public function formatResumeThinkingFrame(string $message, array $extra = []): string
    {
        $data = array_merge([
            'type'    => 'thinking',
            'stage'   => 'agent_resume',
            'message' => $message,
        ], $extra);
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return '';
        }

        return 'data: ' . $encoded . "\n\n";
    }

    /**
     * 将 agent_resume 思考帧合并进待落库的 details/raw，避免空串覆盖已有思考过程.
     *
     * 新 raw 非空时前置 resume 帧；新 raw 为空时保留既有 details（仍前置 resume 帧）。
     *
     * @param string $resumeFrame SSE thinking 帧（可为空）
     * @param string $newRaw 本次循环产出的 raw
     * @param string $priorDetails 消息上已有的 details
     *
     * @return string 合并后的 details
     */
    public function mergeResumeDetails(string $resumeFrame, string $newRaw, string $priorDetails = ''): string
    {
        $body = $newRaw !== '' ? $newRaw : $priorDetails;
        if ($resumeFrame === '') {
            return $body;
        }
        if ($body === '') {
            return $resumeFrame;
        }

        return $resumeFrame . $body;
    }

    /**
     * 格式化 pending input 为前端 input_required 流式 payload.
     *
     * @param AgentPendingInput $pending 待输入记录
     * @param array|null $prompt 可选原始 prompt 描述
     *
     * @return array<string, mixed> 流式推送结构
     */
    public function formatStreamPayload(AgentPendingInput $pending, ?array $prompt = null): array
    {
        $meta = (array) ($pending->meta ?? []);

        $payload = [
            'prompt_id'    => (int) $pending->id,
            'run_id'       => (int) $pending->run_id,
            'message_uuid' => (string) $pending->message_uuid,
            'field_key'    => (string) $pending->field_key,
            'field_label'  => (string) $pending->field_label,
            'input_type'   => (string) $pending->input_type,
            'message'      => (string) ($meta['message'] ?? ((is_array($prompt) ? ($prompt['message'] ?? null) : null) ?? ('请填写' . $pending->field_label))),
            'options'      => (array) ($pending->options ?? []),
            'meta'         => $meta,
        ];
        if ((int) ($pending->id ?? 0) <= 0) {
            $payload['prompt_id'] = 0;
            $payload['ephemeral'] = true;
        }

        return $payload;
    }

    /**
     * 按 ID 查找待输入记录.
     *
     * @param int $promptId 待输入项 ID
     *
     * @return AgentPendingInput|null 记录对象，无效时返回 null
     */
    public function findPending(int $promptId): ?AgentPendingInput
    {
        if ($promptId <= 0 || ! $this->isEnabled()) {
            return null;
        }

        return $this->pendingDao->get($promptId);
    }

    /**
     * 列出指定消息下当前用户的全部待输入项.
     *
     * @param string $messageUuid 消息 UUID
     * @param int $uid 用户 ID
     *
     * @return array<int, array<string, mixed>> 待输入 payload 列表
     */
    public function listPending(string $messageUuid, int $uid): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $items = [];
        foreach (AgentPendingInput::query()
            ->where('message_uuid', $messageUuid)
            ->where('status', AgentEnum::PENDING_INPUT_STATUS_PENDING)
            ->orderBy('id')
            ->get() as $pending) {
            $run = app(\App\Http\Dao\Ai\AgentRunDao::class)->get((int) $pending->run_id);
            if (! $run || (int) $run->uid !== $uid) {
                continue;
            }
            $items[] = $this->formatStreamPayload($pending);
        }

        return $items;
    }

    /**
     * 写确认执行成功后，把真实工具结果回填到可恢复循环状态，供续跑 ToolLoop.
     *
     * @param int $pendingId 已执行的写确认 pending ID（亦为 loop cache key）
     * @param string $toolName 写工具名
     * @param string $toolCallId LLM tool_call_id
     * @param array<string, mixed> $result 真实写入结果
     *
     * @return null|array 更新后的循环状态；缓存过期时返回 null
     */
    public function applyWriteConfirmResult(
        int $pendingId,
        string $toolName,
        string $toolCallId,
        array $result
    ): ?array {
        $state = self::loadLoopStateCache($pendingId);
        if ($state === null) {
            return null;
        }

        $state = self::patchLoopStateAfterWriteConfirm($state, $pendingId, $toolName, $toolCallId, $result);
        self::saveLoopStateCache($pendingId, $state);

        return $state;
    }

    /**
     * 纯函数：将写确认真实结果写回 messages / tool_results，并追加续跑提示.
     *
     * @param array<string, mixed> $state 循环状态
     * @param array<string, mixed> $result 真实写入结果
     *
     * @return array<string, mixed>
     */
    public static function patchLoopStateAfterWriteConfirm(
        array $state,
        int $pendingId,
        string $toolName,
        string $toolCallId,
        array $result
    ): array {
        $content = json_encode($result, JSON_UNESCAPED_UNICODE) ?: (string) ($result['message'] ?? '写入成功');
        try {
            $maxChars = (int) (function_exists('config')
                ? config('ai.plugins.tool_result.max_chars_write', 2500)
                : 2500);
            $content = app(AiToolCallService::class)->normalizeToolResult(
                $result,
                max(200, $maxChars),
                null,
                $toolName
            );
        } catch (\Throwable) {
            if (mb_strlen($content) > 2500) {
                $content = mb_substr($content, 0, 2499) . '…';
            }
        }

        foreach ($state['tool_results'] as $index => $toolResult) {
            $matchPending = ! empty($toolResult['pending_confirmation'])
                && (int) ($toolResult['pending_id'] ?? 0) === $pendingId;
            $matchCall = $toolCallId !== '' && (string) ($toolResult['id'] ?? '') === $toolCallId;
            $matchName = $toolCallId === '' && (string) ($toolResult['name'] ?? '') === $toolName
                && ! empty($toolResult['pending_confirmation']);
            if (! $matchPending && ! $matchCall && ! $matchName) {
                continue;
            }
            $state['tool_results'][$index]['result']  = $result;
            $state['tool_results'][$index]['content'] = $content;
            unset(
                $state['tool_results'][$index]['pending_confirmation'],
                $state['tool_results'][$index]['pending_id']
            );
        }

        foreach ($state['messages'] as $index => $message) {
            if (($message['role'] ?? '') !== AiConversationEnum::ROLE_TOOL) {
                continue;
            }
            $matchCall = $toolCallId !== '' && (string) ($message['tool_call_id'] ?? '') === $toolCallId;
            $matchName = $toolCallId === '' && (string) ($message['name'] ?? '') === $toolName;
            if (! $matchCall && ! $matchName) {
                continue;
            }
            $state['messages'][$index]['content'] = $content;
        }

        $state['messages'][] = [
            'role'    => AiConversationEnum::ROLE_SYSTEM,
            'content' => '写操作「' . $toolName . '」已确认并执行成功。'
                . '若用户原问题仍有未完成的写步骤（例如继续创建订单），必须继续调用对应 create 工具；'
                . '不要在第一步成功后结束，也不要要求用户重复描述已确认的信息。',
        ];
        try {
            if ($languageContext = ChatMessageBuilderService::buildLanguageContext()) {
                $state['messages'][] = [
                    'role'    => AiConversationEnum::ROLE_SYSTEM,
                    'content' => $languageContext,
                ];
            }
            $state['messages'][] = [
                'role'    => AiConversationEnum::ROLE_SYSTEM,
                'content' => ChatMessageBuilderService::buildTimeContext(),
            ];
        } catch (\Throwable) {
            // 单测或无容器环境跳过语言/时间注入，不影响回填与续跑提示。
        }
        // 清空「等待确认」占位答复，避免续跑时模型误以为任务已结束。
        $state['answer'] = '';

        // 复合写（如订单→审批）确认后续跑常只剩 0~1 轮，模型只能口头承诺「将发起」后 done.
        // 至少预留若干工具轮，供 form_get / create 等后续写步骤真正发起.
        $nextRound   = (int) ($state['next_round'] ?? 0);
        $maxRounds   = (int) ($state['max_rounds'] ?? 0);
        $minHeadroom = 4;
        if ($maxRounds - $nextRound < $minHeadroom) {
            $state['max_rounds'] = $nextRound + $minHeadroom;
        }

        return $state;
    }

    /**
     * 批量应用用户确认输入并更新循环状态.
     *
     * @param array<int, array{prompt_id: int, value: mixed, label?: string}> $inputs 用户提交的确认项
     *
     * @return array 更新后的可恢复循环状态
     *
     * @throws \RuntimeException 状态过期或确认项无效时
     */
    public function applyBatchUserInput(array $inputs): array
    {
        if ($inputs === []) {
            throw new \RuntimeException('缺少确认项');
        }

        $primaryId = (int) ($inputs[0]['prompt_id'] ?? 0);
        $state     = self::loadLoopStateCache($primaryId);
        if ($state === null) {
            $state = $this->recoverLoopStateFromPendingInput($primaryId);
        }
        if ($state === null) {
            throw new \RuntimeException('会话状态已过期，请重新发起对话');
        }

        $flow = (array) $state['flow_context'];
        // DB 回灌：确保 write_pending_id 在 flow 中（选人后再确认依赖此字段）.
        if ((int) ($flow['write_pending_id'] ?? 0) <= 0) {
            $primaryPending = $this->findPending($primaryId);
            $metaWriteId    = (int) (($primaryPending->meta['write_pending_id'] ?? 0));
            if ($metaWriteId > 0) {
                $flow['write_pending_id'] = $metaWriteId;
            }
        }
        foreach ($inputs as $input) {
            $promptId = (int) ($input['prompt_id'] ?? 0);
            if ($promptId <= 0) {
                continue;
            }
            $pending = $this->findPending($promptId);
            if (! $pending || (string) $pending->status !== AgentEnum::PENDING_INPUT_STATUS_PENDING) {
                throw new \RuntimeException('待确认信息不存在或已处理');
            }
            if ((string) $pending->message_uuid !== $state['message_uuid']) {
                throw new \RuntimeException('确认项与当前消息不匹配');
            }

            $value = $input['value'] ?? null;
            $label = (string) ($input['label'] ?? '');
            $storedValue = is_array($value) ? $value : ['value' => $value];
            $this->pendingDao->update($promptId, [
                'status'     => AgentEnum::PENDING_INPUT_STATUS_FILLED,
                'user_value' => $storedValue,
                'user_label' => $label,
            ]);

            $value = $this->expandPendingValue($pending, $value);
            self::flowSet($flow,(string) $pending->field_key, $value);
            $display = $label !== '' ? $label : (is_scalar($value) ? (string) $value : (json_encode($value, JSON_UNESCAPED_UNICODE) ?: ''));
            $state['messages'][] = [
                'role'    => AiConversationEnum::ROLE_USER,
                'content' => '[信息确认] ' . $pending->field_label . '：' . $display,
            ];
        }

        self::tryAssembleFromFlow($flow);
        $this->assembleLeaveDurationFromPendingMeta($flow, $inputs);

        $state['flow_context'] = $flow;
        if ($snippet = self::flowPromptSnippet($flow)) {
            $state['messages'][] = [
                'role'    => AiConversationEnum::ROLE_SYSTEM,
                'content' => $snippet,
            ];
        }
        // 续跑时刷新语言约束与当前时间，避免确认后仍受模型默认语言影响、或无法换算相对日期.
        if ($languageContext = ChatMessageBuilderService::buildLanguageContext()) {
            $state['messages'][] = [
                'role'    => AiConversationEnum::ROLE_SYSTEM,
                'content' => $languageContext,
            ];
        }
        $state['messages'][] = [
            'role'    => AiConversationEnum::ROLE_SYSTEM,
            'content' => ChatMessageBuilderService::buildTimeContext(),
        ];

        self::saveLoopStateCache($primaryId, $state);

        return $state;
    }

    /**
     * 把补录框回填的标量值还原成工具需要的结构。
     *
     * 前端只接受标量选项值，产品规格下拉传回的是 SKU unique；
     * 直接塞进 flow 模型只能拿到一串 ID，仍得自己去凑单价和规格，
     * 这里补成完整的产品明细，模型按需改数量即可。
     *
     * @return mixed 需要展开的字段返回结构化值，其余原样返回
     */
    private function expandPendingValue(AgentPendingInput $pending, mixed $value): mixed
    {
        $picker = (string) (((array) ($pending->meta ?? []))['picker'] ?? '');
        if ($picker !== 'product' || ! is_scalar($value)) {
            return $value;
        }
        $unique = trim((string) $value);
        if ($unique === '') {
            return $value;
        }

        try {
            $sku = app(\App\Http\Service\Customer\ProductAttrValueService::class)
                ->select(['unique' => [$unique]], with: ['product'])?->toArray()[0] ?? null;
        } catch (\Throwable) {
            $sku = null;
        }
        if (! is_array($sku)) {
            return $value;
        }

        $price = (string) ($sku['price'] ?? '0.00');

        // 数量未询问，按 1 件起；模型可依据用户后续说明覆盖.
        return [[
            'unique'      => $unique,
            'sku'         => (string) ($sku['sku'] ?? ''),
            'price'       => $price,
            'ot_price'    => (string) ($sku['ot_price'] ?? '0.00'),
            'count'       => 1,
            'discount'    => 100,
            'total_price' => $price,
            'remark'      => '',
        ]];
    }

    /**
     * 根据 pending meta 中的 parent_field/time_type 组装请假时长对象.
     *
     * @param array<int, array{prompt_id?: int, value?: mixed, label?: string}> $inputs
     */
    private function assembleLeaveDurationFromPendingMeta(array &$flow, array $inputs): void
    {
        foreach ($inputs as $input) {
            $promptId = (int) ($input['prompt_id'] ?? 0);
            if ($promptId <= 0) {
                continue;
            }
            $pending = $this->findPending($promptId);
            if (! $pending) {
                continue;
            }
            $meta = (array) ($pending->meta ?? []);
            $isLeaveDuration = ($meta['part'] ?? '') !== ''
                || (string) ($meta['parent_symbol'] ?? '') === 'leaveDuration'
                || (string) ($meta['symbol'] ?? '') === 'leaveDuration';
            if (! $isLeaveDuration) {
                continue;
            }
            self::assembleIntoFlow($flow, [
                'field'     => (string) ($meta['field'] ?? $meta['parent_field'] ?? ''),
                'symbol'    => (string) ($meta['parent_symbol'] ?? $meta['symbol'] ?? 'leaveDuration'),
                'time_type' => (string) ($meta['time_type'] ?? 'day'),
            ]);
        }
    }

    /**
     * 将缓存的 messages/tools 还原到 BaseOption.
     *
     * @param array $state 可恢复循环状态
     * @param BaseOption $option LLM 对话选项
     */
    public function restoreOption(array $state, BaseOption $option): void
    {
        $option->messages = $state['messages'];
        $option->tools    = $state['tools'];
    }

    /**
     * 补参续跑时注入强制指令：参数已齐，立即调用目标写工具完成写入.
     *
     * 弱模型续跑后常见失当行为是再次查询核对而不执行写工具，收口轮会被
     * 写护栏判为失败；此指令把「重新执行写工具」提为唯一允许的下一步。
     *
     * @param array<string, mixed> $loopState 可恢复循环状态
     * @param BaseOption $option 续跑模型选项（messages 会被追加 system 指令）
     * @param array<int, array{field_label?: string, label?: string, value?: mixed}> $confirmationItems 用户确认的字段
     */
    public function appendResumeWriteDirective(array $loopState, BaseOption $option, array $confirmationItems = []): void
    {
        $toolName = '';
        foreach ((array) ($loopState['tool_results'] ?? []) as $toolResult) {
            $result = is_array($toolResult['result'] ?? null) ? $toolResult['result'] : [];
            if (empty($result['needs_input']) && empty($result['needs_selection'])) {
                continue;
            }
            $name = (string) ($toolResult['name'] ?? '');
            if ($name !== '' && app(AgentWriteFlowDriver::class)->isWriteTool($name)) {
                $toolName = $name;
            }
        }
        if ($toolName === '') {
            return;
        }

        $confirmed = [];
        foreach ($confirmationItems as $item) {
            $label = (string) ($item['field_label'] ?? $item['label'] ?? '');
            $value = $item['value'] ?? null;
            if ($label === '' || $value === null || $value === '') {
                continue;
            }
            $confirmed[] = $label . '=' . (is_scalar($value) ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        $lines   = [];
        $lines[] = '用户已通过补录卡片确认' . ($confirmed !== [] ? '：' . implode('、', $confirmed) : '全部缺失参数') . '。';
        $lines[] = '现在必须立即调用 ' . $toolName . ' 工具完成写入，arguments 直接使用上述已确认值；';
        $lines[] = '禁止再次查询、核对、追问或输出文字说明——唯一正确的下一步就是发起该工具调用，调用后系统会推送写确认卡片。';
        $option->setMessage(implode("\n", $lines), AiConversationEnum::ROLE_SYSTEM);
    }

    /**
     * 从循环状态构建续跑用的工具循环请求数组.
     *
     * @param array $state 可恢复循环状态
     * @param AgentRun $run 关联 Run
     * @param array<string, mixed> $baseRequest 基础请求模板
     *
     * @return array<string, mixed> 含 ResumeContext 的续跑请求
     */
    public function buildResumeRequest(
        array $state,
        AgentRun $run,
        array $baseRequest
    ): array {
        $resume = [
            'startRound'  => $state['next_round'],
            'final'       => [
                'answer' => $state['answer'],
                'raw'    => $state['raw'],
                'usage'  => $state['usage'],
                'status' => AiConversationEnum::MESSAGE_DONE,
            ],
            'toolResults' => $state['tool_results'],
            'trace'       => AgentTracer::memoryTraceFromSteps($state['trace']),
        ];

        return ToolLoopExecutor::makeRequest(
            $baseRequest['app'],
            $baseRequest['model'],
            $baseRequest['option'],
            $baseRequest['tool_service'],
            $baseRequest['stream_service'],
            $baseRequest['message_uuid'],
            $baseRequest['references'],
            $state['max_rounds'],
            $baseRequest['interrupted_checker'],
            $baseRequest['write_context'],
            (array) $state['flow_context'],
            $resume,
            $run,
            $state['conversation_id'],
            $state['uid'],
            $state['question'],
            $state['decision'],
            $state['write_confirmation']
        );
    }

    /**
     * 组装待用户补充信息时的助手可见提示文案（多字段汇总 / 单字段 meta.message）.
     *
     * @param AgentPendingInput $primary 主待输入项
     * @param array<int, int> $pendingIds 全部待确认项 ID；空则仅用 primary
     *
     * @return string 面向用户的提示
     */
    public function formatPendingAnswer(AgentPendingInput $primary, array $pendingIds = []): string
    {
        $ids = $pendingIds !== [] ? $pendingIds : [(int) $primary->id];
        if (count($ids) > 1) {
            $labels = [];
            foreach ($ids as $id) {
                $item = $this->findPending((int) $id);
                if ($item) {
                    $labels[] = (string) $item->field_label;
                }
            }
            $labels = array_values(array_filter($labels));
            if ($labels !== []) {
                return '请补充以下信息：' . implode('、', $labels);
            }
        }

        $meta = (array) ($primary->meta ?? []);

        return (string) ($meta['message'] ?? ('请填写' . $primary->field_label));
    }

    /**
     * 若 loopResult 处于 pendingInput 且 answer 为空，补全提示文案.
     *
     * @param array<string, mixed> $request Agent 执行请求
     * @param array $loopResult 工具循环结果（会被修改 answer）
     */
    public static function handlePending(array $request, array &$loopResult): void
    {
        if (! $loopResult['pending_input']) {
            return;
        }
        if ($loopResult['answer'] === '' && $loopResult['pending_input_id'] > 0) {
            $helper = app(self::class);
            $pending = $helper->findPending($loopResult['pending_input_id']);
            if ($pending) {
                $pendingIds = $loopResult['pending_input_ids'] !== []
                    ? $loopResult['pending_input_ids']
                    : $helper->listPendingIds((string) $pending->message_uuid, (int) ($request['uid'] ?? 0));
                $loopResult['answer'] = $helper->formatPendingAnswer($pending, $pendingIds);
            }
        }
    }

    /**
     * 清除指定 promptId 的循环状态缓存.
     *
     * @param int $promptId 待输入项 ID
     */
    public function forget(int $promptId): void
    {
        self::forgetLoopStateCache($promptId);
    }

    /**
     * 列出指定消息下当前用户的待输入项 ID.
     *
     * @param string $messageUuid 消息 UUID
     * @param int $uid 用户 ID
     *
     * @return array<int, int> 待输入项 ID 列表
     */
    public function listPendingIds(string $messageUuid, int $uid): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $ids = [];
        foreach (AgentPendingInput::query()
            ->where('message_uuid', $messageUuid)
            ->where('status', AgentEnum::PENDING_INPUT_STATUS_PENDING)
            ->orderBy('id')
            ->get() as $pending) {
            $run = app(\App\Http\Dao\Ai\AgentRunDao::class)->get((int) $pending->run_id);
            if (! $run || (int) $run->uid !== $uid) {
                continue;
            }
            $ids[] = (int) $pending->id;
        }

        return $ids;
    }

    /**
     * 批量清除多个 promptId 的循环状态缓存.
     *
     * @param array<int, int> $promptIds 待输入项 ID 列表
     */
    public function forgetBatch(array $promptIds): void
    {
        foreach ($promptIds as $promptId) {
            if ((int) $promptId > 0) {
                $this->forget((int) $promptId);
            }
        }
    }

    /**
     * 按消息取消当前用户全部待补参项（用户关闭补参卡片时调用）.
     *
     * 将 pending 记录标记为 cancelled 并清除对应循环状态缓存，返回取消的记录；
     * 供收尾方法据此改写消息正文并终止关联 Run.
     *
     * @param string $messageUuid 消息 UUID
     * @param int $uid 用户 ID
     *
     * @return array<int, AgentPendingInput> 本次取消的待输入记录
     */
    public function cancelPendingByMessage(string $messageUuid, int $uid): array
    {
        if ($messageUuid === '' || ! $this->isEnabled()) {
            return [];
        }

        $cancelled = [];
        foreach (AgentPendingInput::query()
            ->where('message_uuid', $messageUuid)
            ->where('status', AgentEnum::PENDING_INPUT_STATUS_PENDING)
            ->orderBy('id')
            ->get() as $pending) {
            $run = app(\App\Http\Dao\Ai\AgentRunDao::class)->get((int) $pending->run_id);
            if (! $run || (int) $run->uid !== $uid) {
                continue;
            }
            $this->pendingDao->update((int) $pending->id, [
                'status' => AgentEnum::PENDING_INPUT_STATUS_CANCELLED,
            ]);
            $cancelled[] = $pending;
        }
        $this->forgetBatch(array_map(
            static fn (AgentPendingInput $pending): int => (int) $pending->id,
            $cancelled
        ));

        return $cancelled;
    }

    /** 从当前 ToolLoop 上下文构建可序列化的 AgentLoopState. */
    private function buildLoopState(
        array $request,
        int $nextRound,
        array $final,
        array $trace,
        array $toolResults = []
    ): array {
        return self::makeLoopState(
            (int) ($request['conversation_id'] ?? 0),
            (int) $request['app']->id,
            (int) $request['model']->id,
            (int) ($request['uid'] ?? 0),
            (int) ($request['run']?->id ?? 0),
            $request['message_uuid'],
            (string) ($request['question'] ?? ''),
            (array) $request['option']->messages,
            (array) ($request['option']->tools ?? []),
            $nextRound,
            $request['references'],
            (array) ($request['decision'] ?? ['mcp' => false, 'sql' => false]),
            (array) ($request['flow_context'] ?? []),
            $toolResults,
            (string) ($final['answer'] ?? ''),
            (string) ($final['raw'] ?? ''),
            (array) ($final['usage'] ?? []),
            AgentTracer::memoryTraceSteps($trace),
            (bool) ($request['write_confirmation'] ?? false),
            $request['max_rounds']
        );
    }

    // ---- leaveDuration（原 AgentLeaveDurationHelper）----

    public const PART_DATE_START = 'dateStart';

    public const PART_DATE_END = 'dateEnd';

    /**
     * 判断表单字段是否为请假时长复合组件.
     *
     * @param array<string, mixed> $field MCP 表单字段描述
     */
    public static function isLeaveDurationField(array $field): bool
    {
        $symbol = (string) ($field['symbol'] ?? '');
        $type   = (string) ($field['type'] ?? '');

        return $symbol === 'leaveDuration' || $type === 'timeFrom';
    }

    /**
     * 解析请假计时模式：day=按天，hour=按小时.
     *
     * @param array<string, mixed> $field MCP 表单字段描述
     */
    public static function resolveTimeMode(array $field): string
    {
        $timeType = strtolower((string) (
            $field['time_type']
            ?? $field['props']['timeType']
            ?? $field['meta']['time_type']
            ?? 'day'
        ));

        return in_array($timeType, ['hour', 'time'], true) ? 'hour' : 'day';
    }

    /**
     * 子字段在 FlowContext 中的键名.
     *
     * @param string $part dateStart|dateEnd|duration
     */
    public static function partKey(string $part, string $symbol = 'leaveDuration'): string
    {
        $prefix = $symbol !== '' ? $symbol : 'leaveDuration';

        return $prefix . '_' . $part;
    }

    /**
     * 将 leaveDuration 拆成开始/结束时间交互询问（时长自动计算，不单独询问）.
     *
     * @param array<string, mixed> $field MCP 表单字段描述
     * @param string $sourceTool 来源工具名
     *
     * @return array<int, array>
     */
    public static function expandFieldPrompts(array $field, string $sourceTool): array
    {
        $fieldKey = (string) ($field['field'] ?? '');
        $symbol   = (string) ($field['symbol'] ?? '');
        if ($symbol === '') {
            $symbol = 'leaveDuration';
        }
        $mode      = self::resolveTimeMode($field);
        $isHour    = $mode === 'hour';
        $inputType = $isHour ? 'datetime' : 'date';
        $baseMeta  = [
            'composite'      => true,
            'field'          => $fieldKey,
            'symbol'         => $symbol,
            'parent_symbol'  => $symbol,
            'parent_field'   => $fieldKey,
            'type'           => (string) ($field['type'] ?? 'timeFrom'),
            'time_type'      => $isHour ? 'hour' : 'day',
            'value_example'  => $isHour
                ? ($field['value_example_hour'] ?? null)
                : ($field['value_example_day'] ?? null),
        ];

        $startMessage = $isHour
            ? '请填写请假开始时间，例如 2026-08-28 09:00:00'
            : '请填写请假开始日期，例如 2026-08-28';
        $endMessage = $isHour
            ? '请填写请假结束时间，例如 2026-08-28 18:00:00'
            : '请填写请假结束日期，例如 2026-08-28';

        return [
            self::makePrompt(
                self::partKey(self::PART_DATE_START, $symbol),
                '开始时间',
                $inputType,
                [],
                $startMessage,
                $sourceTool,
                array_merge($baseMeta, ['part' => self::PART_DATE_START])
            ),
            self::makePrompt(
                self::partKey(self::PART_DATE_END, $symbol),
                '结束时间',
                $inputType,
                [],
                $endMessage,
                $sourceTool,
                array_merge($baseMeta, ['part' => self::PART_DATE_END])
            ),
        ];
    }

    /**
     * 若 FlowContext 已有起止时间，组装 leaveDuration 对象写入上下文.
     *
     * 时长始终按起止日期/上下午重算，不信任模型或上游传入的 duration
     * （闭区间：同日=1 天，隔日=2 天）.
     *
     * @param array<string, mixed>|null $field 可选字段描述（提供 time_type / field 键）
     *
     * @return bool 是否成功组装
     */
    public static function assembleIntoFlow(array &$flow, ?array $field = null): bool
    {
        $symbol = (string) ($field['symbol'] ?? 'leaveDuration');
        if ($symbol === '') {
            $symbol = 'leaveDuration';
        }
        $fieldKey = (string) ($field['field'] ?? '');
        $mode     = $field !== null ? self::resolveTimeMode($field) : self::inferTimeModeFromFlow($flow, $symbol);

        $dateStart = self::scalarString(self::flowGet($flow, self::partKey(self::PART_DATE_START, $symbol)));
        $dateEnd   = self::scalarString(self::flowGet($flow, self::partKey(self::PART_DATE_END, $symbol)));
        $timeStart = 1;
        $timeEnd   = 0;

        $existing = null;
        if (self::flowHas($flow, $symbol) && is_array(self::flowGet($flow, $symbol))) {
            $existing = self::flowGet($flow, $symbol);
        } elseif ($fieldKey !== '' && self::flowHas($flow, $fieldKey) && is_array(self::flowGet($flow, $fieldKey))) {
            $existing = self::flowGet($flow, $fieldKey);
        } elseif ($symbol !== 'leaveDuration' && self::flowHas($flow, 'leaveDuration') && is_array(self::flowGet($flow, 'leaveDuration'))) {
            $existing = self::flowGet($flow, 'leaveDuration');
        }

        if (is_array($existing)) {
            if ($dateStart === '') {
                $dateStart = self::scalarString($existing['dateStart'] ?? '');
            }
            if ($dateEnd === '') {
                $dateEnd = self::scalarString($existing['dateEnd'] ?? '');
            }
            if (array_key_exists('timeStart', $existing) && $existing['timeStart'] !== '' && $existing['timeStart'] !== null) {
                $timeStart = (int) $existing['timeStart'];
            }
            if (array_key_exists('timeEnd', $existing) && $existing['timeEnd'] !== '' && $existing['timeEnd'] !== null) {
                $timeEnd = (int) $existing['timeEnd'];
            }
            $existingType = strtolower((string) ($existing['timeType'] ?? ''));
            if (in_array($existingType, ['hour', 'time'], true)) {
                $mode = 'hour';
            } elseif ($existingType === 'day') {
                $mode = 'day';
            }
        }

        if ($dateStart === '' || $dateEnd === '') {
            return false;
        }

        $value = self::buildValue($dateStart, $dateEnd, $mode, null, $timeStart, $timeEnd);

        self::flowSet($flow, $symbol, $value);
        if ($fieldKey !== '' && $fieldKey !== $symbol) {
            self::flowSet($flow, $fieldKey, $value);
        }

        return true;
    }

    /**
     * 尝试从 flow 中已有的子键推断并组装（不依赖字段描述）.
     */
    public static function tryAssembleFromFlow(array &$flow): bool
    {
        if (self::flowHas($flow, 'leaveDuration') && is_array(self::flowGet($flow, 'leaveDuration'))) {
            $existing = self::flowGet($flow, 'leaveDuration');
            if (isset($existing['dateStart'], $existing['dateEnd'])) {
                return self::assembleIntoFlow($flow, [
                    'symbol'    => 'leaveDuration',
                    'time_type' => self::inferTimeModeFromFlow($flow, 'leaveDuration'),
                ]);
            }
        }

        if (! self::flowHas($flow, self::partKey(self::PART_DATE_START)) || ! self::flowHas($flow, self::partKey(self::PART_DATE_END))) {
            return false;
        }

        return self::assembleIntoFlow($flow, [
            'symbol'    => 'leaveDuration',
            'time_type' => self::inferTimeModeFromFlow($flow, 'leaveDuration'),
        ]);
    }

    /**
     * 按天请假时长（与管理端 approva-time 一致，自然日闭区间）.
     *
     * 同日上午→下午 = 1 天；23 号→24 号上午→下午 = 2 天.
     * timeStart/timeEnd：1=上午，0=下午.
     */
    public static function calcDayDuration(string $startDay, string $endDay, int $timeStart = 1, int $timeEnd = 0): float
    {
        $startTs = strtotime(substr($startDay, 0, 10));
        $endTs   = strtotime(substr($endDay, 0, 10));
        if ($startTs === false || $endTs === false || $endTs < $startTs) {
            return 0.5;
        }

        // 与前端 divTime(..., 'day') 一致：不含首尾闭区间的差值天数
        $num = (int) (($endTs - $startTs) / 86400);
        $len = $timeStart - $timeEnd;
        if ($len === 1) {
            $calc = $num + 1;
        } elseif ($len === 0) {
            $calc = 0.5 + $num;
        } elseif ($len === -1) {
            $calc = (float) $num;
        } else {
            $calc = (float) ($num + 1);
        }

        return max(0.5, (float) $calc);
    }

    /**
     * 构造 approve_create 所需的 leaveDuration 值.
     *
     * 时长始终由起止时间计算；`$duration` 保留兼容签名但会被忽略
     * （避免模型按「结束−开始」少算一天）.
     *
     * @return array<string, mixed>
     */
    public static function buildValue(
        string $startDate,
        string $endDate,
        string $mode = 'day',
        ?float $duration = null,
        int|string $timeStart = 1,
        int|string $timeEnd = 0
    ): array {
        // $duration 仅保留兼容签名，始终按起止重算，防止模型少算一天
        $duration = null;

        if ($mode === 'hour') {
            $startOnlyDate = strlen($startDate) <= 10;
            $endOnlyDate   = strlen($endDate) <= 10;
            $start         = $startOnlyDate ? ($startDate . ' 09:00:00') : $startDate;
            $end           = $endOnlyDate ? ($endDate . ' 18:00:00') : $endDate;
            $startTs       = strtotime($start);
            $endTs         = strtotime($end);
            // 仅有日期时按「闭区间天数 × 8」；有完整时分时按墙钟差
            if ($startOnlyDate && $endOnlyDate) {
                $days  = (int) self::calcDayDuration(substr($start, 0, 10), substr($end, 0, 10), 1, 0);
                $hours = (float) ($days * 8);
            } elseif ($startTs !== false && $endTs !== false && $endTs > $startTs) {
                $hours = round(($endTs - $startTs) / 3600, 1);
            } else {
                $days  = (int) self::calcDayDuration(substr($start, 0, 10), substr($end, 0, 10), 1, 0);
                $hours = (float) ($days * 8);
            }

            return [
                'timeType'  => 'hour',
                'dateStart' => $start,
                'dateEnd'   => $end,
                'timeStart' => 1,
                'timeEnd'   => 1,
                'duration'  => max(0.5, $hours),
            ];
        }

        $startDay    = substr($startDate, 0, 10);
        $endDay      = substr($endDate, 0, 10);
        $timeStartInt = (int) $timeStart;
        $timeEndInt   = (int) $timeEnd;
        $calc         = self::calcDayDuration($startDay, $endDay, $timeStartInt, $timeEndInt);

        return [
            'timeType'  => 'day',
            'dateStart' => $startDay,
            'dateEnd'   => $endDay,
            'timeStart' => $timeStartInt,
            'timeEnd'   => $timeEndInt,
            'duration'  => $calc,
        ];
    }

    /** @param mixed $value */
    private static function scalarString(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['value'] ?? $value['dateStart'] ?? '';
        }
        if (! is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private static function inferTimeModeFromFlow(array $flow, string $symbol): string
    {
        $start = self::scalarString(self::flowGet($flow,self::partKey(self::PART_DATE_START, $symbol)));
        if (strlen($start) > 10) {
            return 'hour';
        }

        return 'day';
    }

    // --- 循环状态缓存（原 AgentLoopState）---

    private const LOOP_CACHE_TTL_SECONDS = 86400;

    /**
     * 构造可恢复循环状态数组（原 AgentLoopState）.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, mixed> $tools
     * @param array<int, array<string, mixed>> $references
     * @param array{mcp?: bool, sql?: bool} $decision
     * @param array<string, mixed> $flowContext
     * @param array<int, array<string, mixed>> $toolResults
     * @param array<string, mixed> $usage
     * @param array<int, array<string, mixed>> $trace
     *
     * @return array<string, mixed>
     */
    public static function makeLoopState(
        int $conversationId,
        int $appId,
        int $modelId,
        int $uid,
        int $runId,
        string $messageUuid,
        string $question,
        array $messages,
        array $tools,
        int $nextRound,
        array $references,
        array $decision,
        array $flowContext,
        array $toolResults,
        string $answer,
        string $raw,
        array $usage,
        array $trace,
        bool $writeConfirmation = false,
        int $maxRounds = 5
    ): array {
        return [
            'conversation_id'    => $conversationId,
            'app_id'             => $appId,
            'model_id'           => $modelId,
            'uid'                => $uid,
            'run_id'             => $runId,
            'message_uuid'       => $messageUuid,
            'question'           => $question,
            'messages'           => $messages,
            'tools'              => $tools,
            'next_round'         => $nextRound,
            'references'         => $references,
            'decision'           => $decision,
            'flow_context'       => $flowContext,
            'tool_results'       => $toolResults,
            'answer'             => $answer,
            'raw'                => $raw,
            'usage'              => $usage,
            'trace'              => $trace,
            'write_confirmation' => $writeConfirmation,
            'max_rounds'         => $maxRounds,
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function normalizeLoopState(array $data): array
    {
        return self::makeLoopState(
            (int) ($data['conversation_id'] ?? 0),
            (int) ($data['app_id'] ?? 0),
            (int) ($data['model_id'] ?? 0),
            (int) ($data['uid'] ?? 0),
            (int) ($data['run_id'] ?? 0),
            (string) ($data['message_uuid'] ?? ''),
            (string) ($data['question'] ?? ''),
            (array) ($data['messages'] ?? []),
            (array) ($data['tools'] ?? []),
            (int) ($data['next_round'] ?? 0),
            (array) ($data['references'] ?? []),
            (array) ($data['decision'] ?? ['mcp' => false, 'sql' => false]),
            (array) ($data['flow_context'] ?? []),
            (array) ($data['tool_results'] ?? []),
            (string) ($data['answer'] ?? ''),
            (string) ($data['raw'] ?? ''),
            (array) ($data['usage'] ?? []),
            (array) ($data['trace'] ?? []),
            (bool) ($data['write_confirmation'] ?? false),
            (int) ($data['max_rounds'] ?? 5)
        );
    }

    /**
     * 保存工具循环状态到缓存.
     *
     * @param array<string, mixed> $state
     */
    public static function saveLoopStateCache(int $promptId, array $state): void
    {
        self::loopCacheSet(self::loopCacheKey($promptId), $state, self::LOOP_CACHE_TTL_SECONDS);
    }

    /**
     * @return null|array<string, mixed>
     */
    public static function loadLoopStateCache(int $promptId): ?array
    {
        $data = self::loopCacheGet(self::loopCacheKey($promptId));
        if (! is_array($data) || ($data['message_uuid'] ?? '') === '') {
            return null;
        }

        return self::normalizeLoopState($data);
    }

    public static function forgetLoopStateCache(int $promptId): void
    {
        self::loopCacheForget(self::loopCacheKey($promptId));
    }

    private static function loopCacheKey(int $promptId): string
    {
        return 'agent:loop:input:' . $promptId;
    }

    private static function loopCacheGet(string $key): mixed
    {
        try {
            return \Illuminate\Support\Facades\Cache::tags([\App\Constants\Ai\AiConversationEnum::CACHE_TAG])->get($key);
        } catch (\Throwable) {
            return \Illuminate\Support\Facades\Cache::get($key);
        }
    }

    private static function loopCacheForget(string $key): void
    {
        try {
            \Illuminate\Support\Facades\Cache::tags([\App\Constants\Ai\AiConversationEnum::CACHE_TAG])->forget($key);
        } catch (\Throwable) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }
    }

    private static function loopCacheSet(string $key, mixed $value, int $ttl): void
    {
        try {
            \Illuminate\Support\Facades\Cache::tags([\App\Constants\Ai\AiConversationEnum::CACHE_TAG])->set($key, $value, $ttl);
        } catch (\Throwable) {
            \Illuminate\Support\Facades\Cache::set($key, $value, $ttl);
        }
    }


}
