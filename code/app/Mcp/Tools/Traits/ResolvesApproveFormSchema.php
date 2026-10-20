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

namespace App\Mcp\Tools\Traits;

use App\Constants\CommonEnum;
use App\Http\Service\Approve\ApproveFormService;

trait ResolvesApproveFormSchema
{
    protected function fetchApproveForm(int $approveId, int $uid, array $context = [], string $origin = CommonEnum::ORIGIN_WEB): array
    {
        $data = [
            'customer_id' => (int) ($context['customer_id'] ?? 0),
            'bill_id'     => (array) ($context['bill_id'] ?? []),
            'invoice_id'  => (int) ($context['invoice_id'] ?? 0),
            'contract_id' => (int) ($context['contract_id'] ?? 0),
        ];

        return app(ApproveFormService::class)->getApplyForm($approveId, $uid, $data, $origin);
    }

    /**
     * 将 getApplyForm 结果组装为 MCP 工具可用的完整 payload.
     */
    protected function buildMcpApplyFormPayload(array $payload): array
    {
        $fields = $this->buildMcpApplyFormFields((array) ($payload['forms'] ?? []));

        return array_merge($payload, [
            'fields'          => $fields,
            'required_fields' => array_values(array_map(
                static fn (array $field): string => (string) $field['field'],
                array_filter($fields, static fn (array $field): bool => (bool) ($field['required'] ?? false))
            )),
            'hint'            => '将 fields[].field 作为 approve_create 的 form 键提交；symbol/title 也可作为别名自动映射',
        ]);
    }

    /**
     * 获取 MCP 审批表单（含 fields/required_fields）.
     */
    protected function fetchMcpApproveForm(int $approveId, int $uid, array $context = [], string $origin = CommonEnum::ORIGIN_WEB): array
    {
        return $this->buildMcpApplyFormPayload($this->fetchApproveForm($approveId, $uid, $context, $origin));
    }

    /**
     * 仅校验当前表单中的必填子字段（忽略历史残留 uniqued）.
     *
     * 上传类（uploadFrom 等）必填时同样拦截：空数组 / 无有效附件视为缺失.
     */
    protected function validateFormAgainstUniques(array $form, int $approveId, ?array $formPayload = null): ?array
    {
        $formPayload ??= $this->fetchMcpApproveForm($approveId, $this->getUserDbId());
        $fields      = $this->flattenApproveFormFields($formPayload);

        foreach ($fields as $field) {
            if (! ($field['required'] ?? false)) {
                continue;
            }
            $key = (string) ($field['field'] ?? '');
            if ($key === '') {
                continue;
            }
            $type  = (string) ($field['type'] ?? '');
            $value = array_key_exists($key, $form) ? $form[$key] : null;
            if ($this->isMissingApproveFormValue($value, $type)) {
                return [
                    'error'   => true,
                    'message' => '缺少审批字段：' . ($field['title'] ?: ($field['symbol'] ?: $key)),
                    'field'   => $key,
                    'symbol'  => $field['symbol'] ?? '',
                ];
            }
        }

        return null;
    }

    /**
     * 是否为附件/图片上传类审批字段.
     */
    protected function isUploadApproveFieldType(string $type): bool
    {
        $type = strtolower(trim($type));
        if ($type === '') {
            return false;
        }

        return in_array($type, ['uploadfrom', 'upload', 'file', 'image', 'imagefrom'], true)
            || str_contains($type, 'upload');
    }

    /**
     * 必填字段是否缺失（上传类空数组或无 id/src/url 视为缺失）.
     */
    protected function isMissingApproveFormValue(mixed $value, string $type): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (! $this->isUploadApproveFieldType($type)) {
            return false;
        }
        if (! is_array($value) || $value === []) {
            return true;
        }
        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (! empty($item['id']) || ! empty($item['src']) || ! empty($item['url'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 支持用 symbol / title / field 作为 form 键，统一映射为 field.
     *
     * 请假/加班等时长字段会按起止日期强制重算 duration（自然日闭区间：同日=1，隔日=2），
     * 避免模型按「结束−开始」少算一天.
     */
    protected function normalizeApproveFormInput(array $form, int $approveId, ?array $formPayload = null): array
    {
        $formPayload ??= $this->fetchMcpApproveForm($approveId, $this->getUserDbId());
        $fields      = $this->flattenApproveFormFields($formPayload);

        $aliasMap   = [];
        $fieldByKey = [];
        foreach ($fields as $field) {
            $fieldKey = (string) ($field['field'] ?? '');
            if ($fieldKey === '') {
                continue;
            }
            $aliasMap[$fieldKey]  = $fieldKey;
            $fieldByKey[$fieldKey] = $field;
            $symbol               = (string) ($field['symbol'] ?? '');
            $title                = (string) ($field['title'] ?? '');
            if ($symbol !== '') {
                $aliasMap[$symbol] = $fieldKey;
            }
            if ($title !== '') {
                $aliasMap[$title] = $fieldKey;
            }
            // 常见中文别名
            if ($symbol === 'holidayType') {
                $aliasMap['假期类型'] = $fieldKey;
            }
            if ($symbol === 'leaveDuration') {
                $aliasMap['请假时长'] = $fieldKey;
                $aliasMap['时长']     = $fieldKey;
            }
            if ($title === '请假事由' || ($symbol === '' && ($field['type'] ?? '') === 'input')) {
                $aliasMap['请假事由'] = $fieldKey;
                $aliasMap['reason']  = $fieldKey;
            }
        }

        $normalized = [];
        foreach ($form as $key => $value) {
            $mappedKey              = $aliasMap[(string) $key] ?? (string) $key;
            $normalized[$mappedKey] = $value;
        }

        foreach ($normalized as $key => $value) {
            if (! is_array($value)) {
                continue;
            }
            $field  = $fieldByKey[$key] ?? null;
            $symbol = is_array($field) ? (string) ($field['symbol'] ?? '') : '';
            $type   = is_array($field) ? (string) ($field['type'] ?? '') : '';
            // 无 schema 匹配时也兜底识别 leaveDuration 结构
            $looksLikeDuration = isset($value['dateStart'], $value['dateEnd'])
                && ($symbol === 'leaveDuration' || $type === 'timeFrom'
                    || isset($value['timeType']) || isset($value['duration']));
            if (! $looksLikeDuration) {
                continue;
            }
            $dateStart = (string) ($value['dateStart'] ?? '');
            $dateEnd   = (string) ($value['dateEnd'] ?? '');
            if ($dateStart === '' || $dateEnd === '') {
                continue;
            }
            $timeType = strtolower((string) (
                $value['timeType']
                ?? (is_array($field) ? ($field['time_type'] ?? 'day') : 'day')
            ));
            $durationType = in_array($timeType, ['hour', 'time'], true) ? 1 : 0;
            $normalized[$key] = $this->buildLeaveDurationValue(
                $dateStart,
                $dateEnd,
                $durationType,
                (int) ($value['timeStart'] ?? 1),
                (int) ($value['timeEnd'] ?? ($durationType === 1 ? 1 : 0)),
                null
            );
        }

        return $normalized;
    }

    protected function buildApprovalContextFromArguments(array $arguments): array
    {
        return [
            'customer_id' => (int) ($arguments['customer_id'] ?? 0),
            'contract_id' => (int) ($arguments['contract_id'] ?? 0),
            'bill_id'     => (array) ($arguments['bill_id'] ?? []),
            'invoice_id'  => (int) ($arguments['invoice_id'] ?? 0),
        ];
    }

    protected function flattenApproveFormFields(array $formPayload): array
    {
        if (! empty($formPayload['fields']) && is_array($formPayload['fields'])) {
            return $formPayload['fields'];
        }

        return $this->buildMcpApplyFormFields((array) ($formPayload['forms'] ?? []));
    }

    /**
     * 将审批表单配置扁平化为 MCP/AI 可填字段列表.
     */
    protected function buildMcpApplyFormFields(array $forms): array
    {
        $fields = [];
        foreach ($forms as $form) {
            if (! is_array($form)) {
                continue;
            }
            $fields = array_merge($fields, $this->extractMcpFieldsFromFormNode($form));
        }

        return $fields;
    }

    /**
     * 从单个表单节点提取 MCP 可填字段（支持 children 分组、独立字段、approvalBill）.
     */
    protected function extractMcpFieldsFromFormNode(array $node, string $parentType = ''): array
    {
        $type = (string) ($node['type'] ?? '');

        if ($type === 'approvalBill') {
            $fieldKey = (string) ($node['field'] ?? '');
            if ($fieldKey === '') {
                return [];
            }
            $nested = [];
            foreach ((array) ($node['children'] ?? []) as $child) {
                if (! is_array($child)) {
                    continue;
                }
                $childKey = (string) ($child['field'] ?? '');
                if ($childKey === '') {
                    continue;
                }
                $nested[] = $this->normalizeMcpFieldDescriptor($child, 'approvalBill');
            }

            return [$this->normalizeMcpFieldDescriptor($node, $parentType, $nested)];
        }

        if (! empty($node['children']) && is_array($node['children'])) {
            $groupType = $type !== '' ? $type : $parentType;
            $fields    = [];
            foreach ($node['children'] as $child) {
                if (is_array($child)) {
                    $fields = array_merge($fields, $this->extractMcpFieldsFromFormNode($child, $groupType));
                }
            }

            return $fields;
        }

        $fieldKey = (string) ($node['field'] ?? '');
        if ($fieldKey === '') {
            return [];
        }

        return [$this->normalizeMcpFieldDescriptor($node, $parentType)];
    }

    /**
     * 构造 MCP 字段描述（类型、必填、选项、示例值）.
     */
    protected function normalizeMcpFieldDescriptor(array $node, string $parentType = '', array $approvalBillChildren = []): array
    {
        $symbol     = (string) ($node['symbol'] ?? '');
        $type       = (string) ($node['type'] ?? '');
        $fieldKey   = (string) ($node['field'] ?? '');
        $title      = (string) ($node['title'] ?? ($node['props']['titleIpt'] ?? ''));
        $required   = $node['effect']['required'] ?? ($node['required'] ?? false);
        $isRequired = $required === true || $required === 1 || $required === '1'
            || (is_string($required) && $required !== '' && $required !== 'false');

        $item = [
            'field'       => $fieldKey,
            'symbol'      => $symbol,
            'title'       => $title !== '' ? $title : ($symbol === 'leaveDuration' ? '请假时长' : $symbol),
            'type'        => $type,
            'required'    => $isRequired,
            'hidden'      => (bool) ($node['hidden'] ?? false),
            'disabled'    => (bool) ($node['props']['disabled'] ?? false),
            'parent_type' => $parentType,
        ];

        if (array_key_exists('value', $node) && $node['value'] !== '' && $node['value'] !== null) {
            $item['default'] = $node['value'];
        }

        $options = $this->resolveMcpFieldOptions($node);
        if ($options !== []) {
            $item['options'] = $options;
        }

        if ($type === 'approvalBill') {
            $item['value_type'] = 'array';
            $item['hint']       = '数组，每项为对象，键为子字段 field';
            if ($approvalBillChildren !== []) {
                $item['children'] = $approvalBillChildren;
                $exampleRow       = [];
                foreach ($approvalBillChildren as $childField) {
                    $childKey = (string) ($childField['field'] ?? '');
                    if ($childKey !== '') {
                        $exampleRow[$childKey] = $childField['value_example'] ?? '';
                    }
                }
                if ($exampleRow !== []) {
                    $item['value_example'] = [$exampleRow];
                }
            }
        } else {
            $valueMeta = $this->resolveMcpValueMeta($symbol, $type);
            if ($valueMeta !== []) {
                $item = array_merge($item, $valueMeta);
            }
        }

        if ($symbol === 'leaveDuration' || $type === 'timeFrom') {
            $item['time_type'] = (string) ($node['props']['timeType'] ?? 'day');
        }

        return $item;
    }

    /**
     * 解析字段 options（兼容 props.options / 顶层 options）.
     */
    protected function resolveMcpFieldOptions(array $node): array
    {
        $options = [];
        if (! empty($node['options']) && is_array($node['options'])) {
            $options = $node['options'];
        } elseif (! empty($node['props']['options']) && is_array($node['props']['options'])) {
            $options = $node['props']['options'];
        }

        return $this->normalizeMcpOptions($options);
    }

    /**
     * 将 options 统一为 [{value,label}] 结构.
     */
    protected function normalizeMcpOptions(array $options): array
    {
        if ($options === []) {
            return [];
        }

        $normalized = [];
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                if (isset($option['value'], $option['label'])) {
                    $normalized[] = ['value' => $option['value'], 'label' => (string) $option['label']];
                    continue;
                }
                if (isset($option['id'], $option['name'])) {
                    $normalized[] = ['value' => $option['id'], 'label' => (string) $option['name']];
                    continue;
                }
                if (isset($option['value'], $option['text'])) {
                    $normalized[] = ['value' => $option['value'], 'label' => (string) $option['text']];
                    continue;
                }
            }
            if (is_string($key) || is_int($key)) {
                $normalized[] = ['value' => is_int($key) ? $option : $key, 'label' => (string) $option];
            }
        }

        return $normalized;
    }

    /**
     * 按组件类型给出 value 说明与示例.
     */
    protected function resolveMcpValueMeta(string $symbol, string $type): array
    {
        if ($symbol === 'leaveDuration' || $type === 'timeFrom') {
            return [
                'value_type'         => 'object',
                'value_example_day'  => [
                    'timeType'  => 'day',
                    'dateStart' => 'YYYY-MM-DD',
                    'dateEnd'   => 'YYYY-MM-DD',
                    'timeStart' => 1,
                    'timeEnd'   => 0,
                    'duration'  => 2,
                ],
                'value_example_hour' => [
                    'timeType'  => 'hour',
                    'dateStart' => 'YYYY-MM-DD HH:mm:ss',
                    'dateEnd'   => 'YYYY-MM-DD HH:mm:ss',
                    'timeStart' => 1,
                    'timeEnd'   => 1,
                    'duration'  => 16,
                ],
                'hint'               => 'timeStart/timeEnd：按天时 1=上午、0=下午；duration 由起止自动计算（自然日闭区间：同日=1天，隔日=2天），无需手算',
            ];
        }

        if ($symbol === 'holidayType') {
            return [
                'value_type' => 'integer',
                'hint'       => '填假期类型 ID（先调 approve_holiday_type_list，事假通常 value=1）',
            ];
        }

        return match ($type) {
            'select', 'radio' => [
                'value_type' => 'scalar',
                'hint'       => '填 options 中某项的 value',
            ],
            'checkbox' => [
                'value_type'    => 'array',
                'value_example' => [],
                'hint'          => '数组，元素为 options 中的 value',
            ],
            'cascader' => [
                'value_type'    => 'array',
                'value_example' => [1, 2],
                'hint'          => '级联 ID 数组，如 [父级ID, 子级ID]',
            ],
            'datePicker' => [
                'value_type'    => 'string',
                'value_example' => 'YYYY-MM-DD HH:mm:ss',
                'hint'          => '日期字符串',
            ],
            'moneyFrom', 'inputNumber' => [
                'value_type'    => 'number',
                'value_example' => 0,
                'hint'          => '数值',
            ],
            'uploadFrom' => [
                'value_type'    => 'array',
                'value_example' => [],
                'hint'          => '附件数组，元素含 id/src/name 等上传结果字段',
            ],
            'city' => [
                'value_type' => 'integer|array',
                'hint'       => '城市 ID 或 {city:[...]} 结构',
            ],
            'departmentTree', 'userSelect' => [
                'value_type'    => 'array',
                'value_example' => [['id' => 1, 'name' => '姓名']],
                'hint'          => '人员/部门选择数组',
            ],
            'input', 'textarea', '' => [
                'value_type'    => 'string',
                'value_example' => '',
            ],
            default => [
                'value_type' => 'mixed',
            ],
        };
    }

    /**
     * 构造请假时长字段值.
     *
     * 时长始终按起止重算；`$duration` 保留兼容签名但会被忽略.
     * 按天规则与管理端一致：自然日闭区间（同日=1，隔日=2），再按上下午微调 0.5.
     */
    protected function buildLeaveDurationValue(
        string $startDate,
        string $endDate,
        int $durationType = 0,
        int $timeStart = 1,
        int $timeEnd = 0,
        ?float $duration = null
    ): array {
        // $duration 仅保留兼容签名，始终按起止重算
        $duration = null;

        if ($durationType === 1) {
            // 按小时：仅有日期时用闭区间天数×8；有完整时分时用墙钟差
            $startOnlyDate = strlen($startDate) <= 10;
            $endOnlyDate   = strlen($endDate) <= 10;
            $start         = $startOnlyDate ? ($startDate . ' 09:00:00') : $startDate;
            $end           = $endOnlyDate ? ($endDate . ' 18:00:00') : $endDate;
            $startTs       = strtotime($start);
            $endTs         = strtotime($end);
            if ($startOnlyDate && $endOnlyDate) {
                $hours = $this->calcApproveDayDuration(substr($start, 0, 10), substr($end, 0, 10), 1, 0) * 8;
            } elseif ($startTs !== false && $endTs !== false && $endTs > $startTs) {
                $hours = round(($endTs - $startTs) / 3600, 1);
            } else {
                $hours = $this->calcApproveDayDuration(substr($start, 0, 10), substr($end, 0, 10), 1, 0) * 8;
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

        $startDay = substr($startDate, 0, 10);
        $endDay   = substr($endDate, 0, 10);
        $calc     = $this->calcApproveDayDuration($startDay, $endDay, $timeStart, $timeEnd);

        return [
            'timeType'  => 'day',
            'dateStart' => $startDay,
            'dateEnd'   => $endDay,
            'timeStart' => $timeStart,
            'timeEnd'   => $timeEnd,
            'duration'  => $calc,
        ];
    }

    /**
     * 按天审批时长（自然日闭区间，与管理端 approva-time / divTime+count 一致）.
     */
    protected function calcApproveDayDuration(string $startDay, string $endDay, int $timeStart = 1, int $timeEnd = 0): float
    {
        $startTs = strtotime(substr($startDay, 0, 10));
        $endTs   = strtotime(substr($endDay, 0, 10));
        if ($startTs === false || $endTs === false || $endTs < $startTs) {
            return 0.5;
        }

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
}
