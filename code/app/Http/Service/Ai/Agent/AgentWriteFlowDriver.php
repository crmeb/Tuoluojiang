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
use App\Constants\ScheduleEnum;
use App\Http\Service\Schedule\ScheduleService;
use App\Http\Dao\Ai\AgentPendingActionDao;
use App\Http\Dao\Ai\AgentRunDao;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Ai\AgentPendingAction;
use App\Http\Model\Ai\AiApplication;
use App\Http\Controller\Mcp\McpRequest;
use App\Http\Service\Ai\Agent\Trace\AgentTracer;
use App\Mcp\Context\McpUserContextResolver;
use App\Mcp\Support\McpWriteResult;
use Illuminate\Support\Facades\Schema;
use App\Http\Service\Config\RelationFieldRegistry;
use App\Mcp\ToolExecutor;
use App\Mcp\ToolRegistry;
/**
 * MCP 写操作守卫：ToolLoop 缺参检测、写工具 schema 解析与写确认 pending/确认续跑.
 */
class AgentWriteFlowDriver
{
    /** @var array<int, string> 写工具 schema 解析时默认跳过的字段 */
    private const SCHEMA_SKIP_FIELDS = [
        'form', 'process', 'products', 'attach_ids', 'link_id', 'bill_id', 'eid', 'cid',
        'customer_id', 'contract_id', 'invoice_id', 'member', 'members', 'is_submit',
        'follow_id', 'days', 'fail_time', 'all_day', 'remind', 'period', 'rate',
    ];

    /** @var array<string, array<int, string>> 按工具保留本应跳过的字段 */
    private const SCHEMA_KEEP_FIELDS_BY_TOOL = ['schedule_create' => ['cid']];

    private AgentPendingActionDao $dao;

    private AgentRunDao $runDao;

    private AgentTracer $tracer;

    public function __construct(
        AgentPendingActionDao $dao,
        AgentRunDao $runDao,
        AgentTracer $tracer
    ) {
        $this->dao    = $dao;
        $this->runDao = $runDao;
        $this->tracer = $tracer;
    }

    /** 根据 MCP JSON Schema 统一找出写工具缺失的必填字段。 */
    public function missingWriteFields(string $toolName, array $arguments): array
    {
        if (! $this->isWriteTool($toolName)) {
            return [];
        }
        $tool = ToolRegistry::getTool($toolName);
        if ($tool === null) {
            return [];
        }

        $schema = $tool->getInputSchema();
        $properties = (array) ($schema['properties'] ?? []);
        $required = array_flip((array) ($schema['required'] ?? []));
        $missing = [];
        foreach (array_keys($required) as $key) {
            $property = is_array($properties[$key] ?? null) ? $properties[$key] : [];
            $value = $arguments[$key] ?? null;
            if ($value !== null && $value !== '' && $value !== []) {
                continue;
            }
            $label = $this->extractSchemaTitle((string) ($property['description'] ?? ''), (string) $key);

            // 关联类必填项（仓库/产品）在 Schema 里没有静态 enum，候选需按当前用户权限实时加载；
            // 且存在依赖关系（产品候选取决于已选仓库），依赖未满足时本轮跳过，等下一轮再问。
            $dynamic = $this->dynamicWriteFieldCandidates((string) $key, $arguments);
            if ($dynamic !== null) {
                if ($dynamic['defer']) {
                    continue;
                }
                // 候选构建过程可能改问另一个字段（如该仓库无可售产品时改回重选仓库）.
                $targetKey = (string) ($dynamic['field_key'] ?? $key);
                if ($targetKey !== (string) $key) {
                    $targetProperty = is_array($properties[$targetKey] ?? null) ? $properties[$targetKey] : [];
                    $label = $this->extractSchemaTitle((string) ($targetProperty['description'] ?? ''), $targetKey);
                }
                $missing[] = [
                    'field_key'   => $targetKey,
                    'field_label' => $label,
                    'input_type'  => $dynamic['options'] === [] ? 'text' : 'select',
                    'options'     => $dynamic['options'],
                    'message'     => $dynamic['message'] !== '' ? $dynamic['message'] : ('请选择' . $label),
                    'meta'        => array_merge(['dynamic' => true], (array) $dynamic['meta']),
                ];
                continue;
            }

            $options   = $this->extractSchemaEnumOptions($property);
            $isSelect  = $options !== [];
            $missing[] = [
                'field_key'   => (string) $key,
                'field_label' => $label,
                'input_type'  => $isSelect ? 'select' : 'text',
                'options'     => $options,
                'message'     => ($isSelect ? '请选择' : '请填写') . $label,
                'meta'        => [
                    'aliases'    => array_values(array_filter(array_map('strval', (array) ($property['x-ai-aliases'] ?? [])))),
                    'normalizer' => (string) ($property['x-ai-normalizer'] ?? ''),
                ],
            ];
        }

        return $missing;
    }

    /**
     * 仅返回仍须走 input_required 的缺参（动态候选/依赖链）；标量缺参改由可编辑确认表单补齐.
     *
     * @param array<int, array<string, mixed>> $missing missingWriteFields 结果
     *
     * @return array<int, array<string, mixed>>
     */
    public function blockingMissingWriteFields(array $missing): array
    {
        $blockingKeys = ['outbound_warehouse_id', 'products'];
        $blocked      = [];
        foreach ($missing as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key  = (string) ($field['field_key'] ?? '');
            $meta = (array) ($field['meta'] ?? []);
            if (in_array($key, $blockingKeys, true)
                || ! empty($meta['reselect'])
                || ! empty($meta['dynamic'])
                || (($meta['picker'] ?? '') !== '' && (array) ($field['options'] ?? []) !== [])
            ) {
                $blocked[] = $field;
            }
        }

        return $blocked;
    }

    /**
     * 将抽槽结果合并进写工具参数（空值不覆盖已有）.
     *
     * @param array<string, mixed> $args 原参数
     * @param array<string, scalar> $slots 已校验槽位
     *
     * @return array{args: array<string, mixed>, applied: array<string, scalar>}
     */
    public function mergeSlotsIntoArgs(string $toolName, array $args, array $slots): array
    {
        $applied = [];
        if ($slots === []) {
            return ['args' => $args, 'applied' => $applied];
        }

        if ($toolName === 'approve_create') {
            $form = is_array($args['form'] ?? null) ? $args['form'] : [];
            foreach ($slots as $key => $value) {
                $key = (string) $key;
                if ($key === '' || $this->hasEffectiveValue($form[$key] ?? null)) {
                    continue;
                }
                $form[$key]    = $value;
                $applied[$key] = $value;
            }
            if ($applied !== []) {
                $args['form'] = $form;
            }

            return ['args' => $args, 'applied' => $applied];
        }

        foreach ($slots as $key => $value) {
            $key = (string) $key;
            if ($key === '' || $this->hasEffectiveValue($args[$key] ?? null)) {
                continue;
            }
            $args[$key]    = $value;
            $applied[$key] = $value;
        }

        return ['args' => $args, 'applied' => $applied];
    }

    /**
     * 组装可编辑确认表单字段：必填全量 + 有值选填；复杂结构不下发.
     *
     * @param array<string, mixed> $args 合并后的工具参数
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildEditableFormFields(string $toolName, array $args): array
    {
        $args = $this->hydrateCustomerApprovalArgs($toolName, $args);
        $schemaFields = $this->resolveEditableSchemaFields($toolName, $args);
        if ($schemaFields === []) {
            return [];
        }

        $labelMeta = WriteFieldLabels::fieldMeta($toolName);
        $values    = $this->extractEditableValues($toolName, $args);
        $values    = $this->mergeSchemaPrefillIntoValues($values, $schemaFields, $args);
        $rows      = [];
        foreach ($schemaFields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = trim((string) ($field['field'] ?? $field['symbol'] ?? ''));
            if ($key === '' || $this->isInternalEditableKey($key)) {
                continue;
            }
            if (! empty($field['hidden'])) {
                continue;
            }
            $type = strtolower((string) ($field['type'] ?? 'input'));
            $isUpload = in_array($type, ['file', 'upload', 'image', 'uploadfrom', 'imagefrom'], true)
                || str_contains($type, 'upload');
            if (in_array($type, ['object', 'hidden'], true)) {
                continue;
            }
            // 复合请假时长等对象字段：首期不进可编辑行（上传附件除外）.
            if (! $isUpload && self::isComplexEditableField($field)) {
                continue;
            }

            $required = (bool) ($field['required'] ?? false);
            // 出库仓库等业务外键：即使 schema 未标 required，已有值或用户改仓重试时也应展示.
            if (in_array($key, self::EDITABLE_ID_KEYS, true)) {
                $required = true;
            }
            $rawValue = $values[$key] ?? null;
            $hasValue = $this->hasEffectiveValue($rawValue);
            // disabled 必填项（如上下文预填的订单编号）仍须展示，否则用户无法补录也无法确认写入.
            if (! $required && ! $hasValue) {
                continue;
            }
            if (! empty($field['disabled']) && ! $required && $hasValue) {
                // 非必填且已预填的禁用项不占编辑行.
                continue;
            }

            $options = $this->normalizeEditableOptions((array) ($field['options'] ?? []));
            if ($options === [] && in_array($key, self::EDITABLE_ID_KEYS, true)) {
                $dynamic = $this->dynamicWriteFieldCandidates($key, $args);
                if (is_array($dynamic) && empty($dynamic['defer'])) {
                    $options = $this->normalizeEditableOptions((array) ($dynamic['options'] ?? []));
                    if (($field['title'] ?? '') === '' && ($dynamic['message'] ?? '') !== '') {
                        $field['title'] = preg_replace('/^请选择/u', '', (string) $dynamic['message']) ?: $key;
                    }
                }
            }
            // 关联字段（客户/仓库等）：有 ID 时回译名称，以 select 展示名称、提交 ID.
            if ($options === [] && RelationFieldRegistry::isRelationField($key) && $hasValue && is_scalar($rawValue)) {
                $idValue = trim((string) $rawValue);
                $relOpt  = $this->relationSelectOption($key, $idValue);
                if ($relOpt !== null) {
                    $options = [$relOpt];
                }
            }
            $inputType = $isUpload ? 'upload' : ($options !== [] ? 'select' : 'text');
            $label     = (string) ($labelMeta[$key]['label'] ?? $field['title'] ?? $key);
            if ($key === 'outbound_warehouse_id' && ($label === $key || $label === '')) {
                $label = '出库仓库';
            }
            $messagePrefix = match ($inputType) {
                'select' => '请选择',
                'upload' => '请上传',
                default  => '请填写',
            };
            $row       = [
                'key'        => $key,
                'label'      => $label,
                'input_type' => $inputType,
                'options'    => $options,
                'required'   => $required,
                'message'    => $messagePrefix . $label,
            ];
            if ($this->isApproveFormDrivenTool($toolName)) {
                $row['group'] = 'form';
            }
            if ($isUpload && $hasValue && is_array($rawValue)) {
                $files = [];
                foreach ($rawValue as $item) {
                    if (is_array($item) && (! empty($item['id']) || ! empty($item['src']) || ! empty($item['url']))) {
                        $files[] = $item;
                    }
                }
                if ($files !== []) {
                    $row['value'] = $files;
                }
            } elseif ($hasValue && is_scalar($rawValue)) {
                // 与 options.value 统一为字符串，避免前端 el-select 因 "5"≠5 无法选中.
                $row['value'] = is_bool($rawValue)
                    ? ($rawValue ? '1' : '0')
                    : mb_substr(trim((string) $rawValue), 0, 500);
                if ($options !== []) {
                    $row['value'] = $this->alignEditableValueToOptions((string) $row['value'], $options);
                }
            } elseif ($hasValue && is_array($rawValue) && $options !== []) {
                // 级联等数组值：取末级 ID 对齐 options，便于 el-select 预选.
                $leaf = $rawValue[array_key_last($rawValue)] ?? null;
                if (is_scalar($leaf) && (string) $leaf !== '') {
                    $row['value'] = $this->alignEditableValueToOptions(trim((string) $leaf), $options);
                }
            }
            $rows[] = $row;
            if (count($rows) >= 24) {
                break;
            }
        }

        return $rows;
    }

    /**
     * 将用户确认表单提交的 tool_args 合并进 pending 参数.
     *
     * @param array<string, mixed> $pendingArgs 原 pending 参数
     * @param array<string, mixed> $overrides 前端提交的覆盖值
     *
     * @return array<string, mixed>
     */
    public function mergeConfirmToolArgs(string $toolName, array $pendingArgs, array $overrides): array
    {
        if ($overrides === []) {
            return $this->hydrateCustomerApprovalArgs($toolName, $pendingArgs);
        }

        if ($this->isApproveFormDrivenTool($toolName)) {
            $topKeys = ['approve_id', 'types', 'name', 'process', 'link_id', 'form', 'eid', 'cid', 'customer_id', 'contract_id', 'bill_id', 'invoice_id'];
            $formOverrides = is_array($overrides['form'] ?? null) ? $overrides['form'] : null;
            if ($formOverrides === null) {
                // 前端可能按扁平键提交审批表单字段（group=form）.
                $formOverrides = [];
                foreach ($overrides as $key => $value) {
                    if (! is_string($key) || in_array($key, $topKeys, true)) {
                        continue;
                    }
                    $formOverrides[$key] = $value;
                }
            }
            $form = is_array($pendingArgs['form'] ?? null) ? $pendingArgs['form'] : [];
            foreach ($formOverrides as $key => $value) {
                if (! is_string($key) || $key === '') {
                    continue;
                }
                if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                    unset($form[$key]);
                    continue;
                }
                $form[$key] = $value;
            }
            $pendingArgs['form'] = $form;
            foreach (['approve_id', 'types', 'name', 'process', 'link_id', 'eid', 'cid', 'customer_id', 'contract_id', 'order_id', 'bill_id', 'invoice_id'] as $topKey) {
                if (array_key_exists($topKey, $overrides)) {
                    $pendingArgs[$topKey] = $overrides[$topKey];
                }
            }

            return $this->hydrateCustomerApprovalArgs($toolName, $pendingArgs);
        }

        foreach ($overrides as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }
            if ($value === null || $value === '') {
                unset($pendingArgs[$key]);
                continue;
            }
            $pendingArgs[$key] = $value;
        }

        return $pendingArgs;
    }

    /**
     * 客户审批写工具：把顶层 cid/contract_id/order_id 与 approve_form_get 预填值灌进 form，
     * 避免「订单编号」在表单里 disabled 预填却未写入 form 导致反复缺字段.
     *
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function hydrateCustomerApprovalArgs(string $toolName, array $args): array
    {
        if (! $this->isApproveFormDrivenTool($toolName)) {
            return $args;
        }

        try {
            $form         = is_array($args['form'] ?? null) ? $args['form'] : [];
            $schemaFields = $this->resolveEditableSchemaFields($toolName, $args);
            $args['form'] = $this->mergeSchemaPrefillIntoValues($form, $schemaFields, $args);
        } catch (\Throwable) {
            // 单测/无 MCP 容器时跳过灌入，不影响确认合并主路径.
        }

        return $args;
    }

    /**
     * 用 schema 预填值与顶层业务 ID 补齐 form 缺省项.
     *
     * @param array<string, mixed> $values
     * @param array<int, array<string, mixed>> $schemaFields
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    private function mergeSchemaPrefillIntoValues(array $values, array $schemaFields, array $args): array
    {
        $contractId = (int) ($args['contract_id'] ?? $args['cid'] ?? $args['order_id'] ?? $args['link_id'] ?? 0);
        $customerId = (int) ($args['customer_id'] ?? $args['eid'] ?? 0);

        foreach ($schemaFields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = trim((string) ($field['field'] ?? ''));
            if ($key === '' || $this->hasEffectiveValue($values[$key] ?? null)) {
                continue;
            }
            $symbol = (string) ($field['symbol'] ?? '');
            if ($this->hasEffectiveValue($field['value'] ?? null)) {
                $values[$key] = is_array($field['value'])
                    ? $field['value']
                    : (is_bool($field['value']) ? ($field['value'] ? '1' : '0') : $field['value']);
                continue;
            }
            if (in_array($symbol, ['contractList', 'contract', 'order', 'orderList'], true) && $contractId > 0) {
                $values[$key] = $contractId;
                continue;
            }
            if (in_array($symbol, ['customer', 'customerList', 'eid'], true) && $customerId > 0) {
                $values[$key] = $customerId;
            }
        }

        return $values;
    }

    /**
     * 更新待确认写操作的参数（确认前合并表单编辑结果）.
     *
     * @param array<string, mixed> $args
     */
    public function updatePendingArgs(AgentPendingAction $pending, array $args): void
    {
        $this->dao->update((int) $pending->id, ['tool_args' => $args]);
        $pending->tool_args = $args;
    }

    /**
     * 解析可编辑表单字段源：审批优先动态 form fields，其余走写工具 schema.
     *
     * @param array<string, mixed> $args
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolveEditableSchemaFields(string $toolName, array $args = []): array
    {
        if ($this->isApproveFormDrivenTool($toolName)) {
            $approveId = $this->resolveApproveIdForEditableForm($toolName, $args);
            if ($approveId > 0) {
                try {
                    $def        = $this->definitions()[$toolName] ?? null;
                    $formTool   = is_array($def) ? (string) ($def['form_tool'] ?? '') : '';
                    $formBiz    = is_array($def) ? (string) ($def['form_biz'] ?? '') : '';
                    $customerId = (int) ($args['customer_id'] ?? $args['eid'] ?? 0);
                    $contractId = (int) ($args['contract_id'] ?? $args['cid'] ?? $args['link_id'] ?? 0);
                    $formArgs   = [];
                    if ($customerId > 0) {
                        $formArgs['customer_id'] = $customerId;
                    }
                    if ($contractId > 0) {
                        $formArgs['contract_id'] = $contractId;
                    }
                    if ($formTool === '') {
                        $formTool               = 'approve_form_get';
                        $formArgs['approve_id'] = $approveId;
                    } elseif ($formTool === 'customer_biz_form_get' && $formBiz !== '') {
                        $formArgs['biz'] = $formBiz;
                    }
                    $result = app(ToolExecutor::class)->execute($formTool, $formArgs);
                    if (is_array($result) && empty($result['error']) && is_array($result['fields'] ?? null)) {
                        return array_values(array_filter($result['fields'], static fn ($field): bool => is_array($field)));
                    }
                } catch (\Throwable) {
                    // 动态表单不可用时回退 schema.
                }
            }
        }

        $resolved = $this->resolveWriteToolSchema($toolName, [
            'types' => (int) ($args['types'] ?? 0),
        ]);
        if (! empty($resolved['error']) || ! is_array($resolved['fields'] ?? null)) {
            return [];
        }

        return array_values(array_filter($resolved['fields'], static fn ($field): bool => is_array($field)));
    }

    /**
     * 回款/开票/人事审批等：参数落在 tool_args.form，可编辑行也读 form.
     *
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    private function extractEditableValues(string $toolName, array $args): array
    {
        if ($this->isApproveFormDrivenTool($toolName)) {
            return is_array($args['form'] ?? null) ? $args['form'] : [];
        }

        return $args;
    }

    /** 写确认可编辑表单是否取自审批动态 fields（值写回 tool_args.form）. */
    public function isApproveFormDrivenTool(string $toolName): bool
    {
        if ($toolName === 'approve_create') {
            return true;
        }
        $config = $this->definitions()[$toolName] ?? null;

        return is_array($config) && ($config['type'] ?? '') === 'customer_approval';
    }

    /**
     * 解析可编辑审批表单对应的 approve_id.
     *
     * @param array<string, mixed> $args
     */
    private function resolveApproveIdForEditableForm(string $toolName, array $args): int
    {
        $approveId = (int) ($args['approve_id'] ?? 0);
        if ($approveId > 0) {
            return $approveId;
        }
        $config = $this->definitions()[$toolName] ?? null;
        $configKey = is_array($config) ? (string) ($config['config_key'] ?? '') : '';
        if ($configKey === '') {
            return 0;
        }
        try {
            $configs = app(\App\Http\Service\Config\SystemConfigService::class)->getApproveConfigs([$configKey]);

            return (int) ($configs[$configKey] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function hasEffectiveValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        if (is_array($value) && $value === []) {
            return false;
        }

        return true;
    }

    /** 虽带 _id 但需出现在可编辑表单中的业务外键（如出库仓库）. */
    private const EDITABLE_ID_KEYS = [
        'outbound_warehouse_id',
    ];

    private function isInternalEditableKey(string $key): bool
    {
        if (in_array($key, self::EDITABLE_ID_KEYS, true)) {
            return false;
        }

        return (bool) preg_match('/(?:^|_)id$|^member$|^members$|^form$|^process$|^products$/i', $key);
    }

    /** @param array<string, mixed> $field */
    private static function isComplexEditableField(array $field): bool
    {
        $type = strtolower((string) ($field['type'] ?? ''));
        if ($type === 'object' || ! empty($field['normalizer']) && str_contains((string) $field['normalizer'], 'leave')) {
            return true;
        }
        $key = (string) ($field['field'] ?? $field['symbol'] ?? '');

        return in_array($key, ['products', 'process', 'attach_ids'], true)
            || str_contains(strtolower($key), 'duration')
            || str_contains((string) ($field['title'] ?? ''), '请假时长');
    }

    /**
     * 关联字段单选项：展示实体名称，值为记录 ID（字符串，与表单 value 对齐）.
     *
     * @return null|array{value: string, label: string}
     */
    protected function relationSelectOption(string $key, string $idValue): ?array
    {
        $idValue = trim($idValue);
        if ($idValue === '') {
            return null;
        }
        $label = RelationFieldRegistry::displayLabel($key, $idValue);
        if ($label === null || $label === '') {
            return null;
        }

        return ['value' => $idValue, 'label' => $label];
    }

    /**
     * 将字段 value 对齐到 options 中的同义项（一律字符串），保证前端可预选中.
     *
     * @param array<int, array{value: string, label: string}> $options
     */
    private function alignEditableValueToOptions(string $value, array $options): string
    {
        foreach ($options as $option) {
            if ((string) ($option['value'] ?? '') === $value) {
                return (string) $option['value'];
            }
        }

        return $value;
    }

    /**
     * @param array<int, mixed> $options
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function normalizeEditableOptions(array $options): array
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
            // 统一字符串：避免 value "5" 与 option 5 导致 el-select 无法选中.
            $normalized[] = [
                'value' => trim((string) $value),
                'label' => (string) ($option['label'] ?? $option['name'] ?? $option['title'] ?? $value),
            ];
            if (count($normalized) >= 100) {
                break;
            }
        }

        return $normalized;
    }

    /**
     * 为关联类必填项实时构建候选。
     *
     * @return null|array{defer: bool, options: array<int, array<string, mixed>>, message: string, meta: array<string, mixed>}
     *         null 表示该字段不走动态候选；defer=true 表示依赖未就绪，本轮不问
     */
    private function dynamicWriteFieldCandidates(string $key, array $arguments): ?array
    {
        return match ($key) {
            'outbound_warehouse_id' => $this->outboundWarehouseCandidates(),
            'products'              => $this->orderProductCandidates($arguments),
            default                 => null,
        };
    }

    /**
     * 当前用户有出库权限的仓库候选。
     *
     * @return array{defer: bool, options: array<int, array<string, mixed>>, message: string, meta: array<string, mixed>}
     */
    private function outboundWarehouseCandidates(): array
    {
        $options = [];
        try {
            $tool = ToolRegistry::getTool('warehouse_list');
            $list = $tool ? (array) ($tool->execute(['action' => 'outbound'])['list'] ?? []) : [];
            foreach ($list as $row) {
                if (! is_array($row) || (int) ($row['id'] ?? 0) <= 0) {
                    continue;
                }
                $name = (string) ($row['name'] ?? '');
                $code = (string) ($row['code'] ?? '');
                $options[] = [
                    'value' => (int) $row['id'],
                    // 同名仓库用编码消歧.
                    'label' => $code !== '' ? $name . '（' . $code . '）' : $name,
                ];
            }
        } catch (\Throwable) {
            $options = [];
        }

        return [
            'defer'   => false,
            'options' => $options,
            'message' => $options === []
                ? '当前账号没有可用的出库仓库，请联系管理员配置库管权限'
                : '请选择出库仓库',
            'meta'    => ['picker' => 'warehouse', 'aliases' => [], 'normalizer' => ''],
        ];
    }

    /**
     * 所选仓库下仍有良品可用库存的产品规格候选。
     *
     * @return array{defer: bool, options: array<int, array<string, mixed>>, message: string, meta: array<string, mixed>}
     */
    private function orderProductCandidates(array $arguments): array
    {
        $warehouseId = (int) ($arguments['outbound_warehouse_id'] ?? 0);
        // 产品候选依赖出库仓库：仓库未定时本轮不问，等用户选完仓库续跑时再弹.
        if ($warehouseId <= 0) {
            return ['defer' => true, 'options' => [], 'message' => '', 'meta' => []];
        }

        $options = [];
        try {
            $tool   = ToolRegistry::getTool('product_list');
            $result = $tool ? (array) $tool->execute([
                'warehouse_id' => $warehouseId,
                'filter_stock' => 1,
                'limit'        => 100,
            ]) : [];
            foreach ((array) ($result['list'] ?? []) as $product) {
                if (! is_array($product)) {
                    continue;
                }
                $productName = (string) ($product['name'] ?? '');
                foreach ((array) ($product['skus'] ?? []) as $sku) {
                    if (! is_array($sku)) {
                        continue;
                    }
                    $unique = (string) ($sku['unique'] ?? '');
                    $avail  = (int) ($sku['available_stock'] ?? 0);
                    if ($unique === '' || $avail <= 0) {
                        continue;
                    }
                    $specName = (string) ($sku['sku'] ?? '');
                    $options[] = [
                        // 前端只接受标量选项值（见 chat.stream-parser 的 parseInputOptions），
                        // 这里传 SKU unique，完整明细由 applyBatchUserInput 回填时补全.
                        'value' => $unique,
                        'label' => ($specName !== '' ? $productName . ' / ' . $specName : $productName)
                            . '（单价 ' . (string) ($sku['price'] ?? '0.00') . '，可售 ' . $avail . '）',
                    ];
                }
            }
        } catch (\Throwable) {
            $options = [];
        }

        // 该仓库没有任何可售产品：问产品没有意义，改回让用户重选仓库.
        if ($options === []) {
            $fallback = $this->outboundWarehouseCandidates();
            $fallback['field_key'] = 'outbound_warehouse_id';
            $fallback['message']   = $fallback['options'] === []
                ? '当前账号没有可用的出库仓库，请联系管理员配置库管权限'
                : '所选仓库下没有可用库存的产品，请重新选择出库仓库';
            // reselect 让上一轮已确认的仓库值作废，否则会被 flow 命中而不再询问.
            $fallback['meta']['reselect'] = 'outbound_warehouse_id';

            return $fallback;
        }

        return [
            'defer'   => false,
            'options' => $options,
            'message' => '请选择产品规格',
            'meta'    => ['picker' => 'product', 'warehouse_id' => $warehouseId, 'aliases' => [], 'normalizer' => ''],
        ];
    }

    /**
     * 将 flow 中已确认的 process_users_{uniqued} 注入 approve_create 的 process.
     *
     * 用户在 input_required 选完抄送/审批人后，值只写入 flow；模型常只读 system 摘要
     * 却未把 users 填进 process 节点，导致 resolveApproveProcessForSubmit 再次 needs_selection
     * 并被判为写入失败。此处在真正执行写工具前做服务端合并，不依赖模型拼装.
     *
     * @param array<string, mixed> $args approve_create 原始入参
     * @param array<string, mixed> $flow 业务流上下文
     *
     * @return array<string, mixed> 注入后的入参
     */
    public function enrichApproveCreateArgs(array $args, array $flow): array
    {
        $fromFlow = $this->processNodesFromFlowSelections($flow);
        if ($fromFlow === []) {
            return $args;
        }

        $process = (array) ($args['process'] ?? []);
        if ($process === []) {
            $args['process'] = $fromFlow;

            return $args;
        }

        $byUniqued = [];
        foreach ($process as $index => $node) {
            if (! is_array($node)) {
                continue;
            }
            $uniqued = (string) ($node['uniqued'] ?? '');
            if ($uniqued !== '') {
                $byUniqued[$uniqued] = $index;
            }
        }

        foreach ($fromFlow as $selection) {
            $uniqued = (string) ($selection['uniqued'] ?? '');
            if ($uniqued === '') {
                continue;
            }
            if (isset($byUniqued[$uniqued])) {
                $index = $byUniqued[$uniqued];
                // 模型偶发塞入空对象/[0] 等假非空 users，需能规范出有效 id 才保留，否则用 flow 覆盖.
                $existingUsers = $this->normalizeFlowProcessUserValue($process[$index]['users'] ?? []);
                if ($existingUsers === []) {
                    $process[$index]['users'] = $selection['users'];
                }
            } else {
                $process[] = $selection;
            }
        }

        $args['process'] = $process;

        return $args;
    }

    /**
     * @param array<string, mixed> $flow
     *
     * @return array<int, array{uniqued: string, users: array<int, int>}>
     */
    private function processNodesFromFlowSelections(array $flow): array
    {
        $nodes = [];
        foreach ($flow as $key => $value) {
            if (! is_string($key) || ! str_starts_with($key, 'process_users_')) {
                continue;
            }
            $uniqued = substr($key, strlen('process_users_'));
            if ($uniqued === '') {
                continue;
            }
            $users = $this->normalizeFlowProcessUserValue($value);
            if ($users === []) {
                continue;
            }
            $nodes[] = [
                'uniqued' => $uniqued,
                'users'   => $users,
            ];
        }

        return $nodes;
    }

    /**
     * 将 flow 中人员选择值规范为用户 id 列表（兼容标量、value 包装、多选列表）.
     *
     * @return array<int, int>
     */
    private function normalizeFlowProcessUserValue(mixed $value): array
    {
        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? [$id] : [];
        }
        if (is_string($value)) {
            $trim = trim($value);
            if ($trim !== '' && ctype_digit($trim)) {
                return [(int) $trim];
            }

            return [];
        }
        if (! is_array($value)) {
            return [];
        }

        if (array_key_exists('value', $value)
            && ! isset($value['id']) && ! isset($value['user_id']) && ! isset($value['card_id'])) {
            $keys   = array_keys($value);
            $isList = $keys === range(0, count($value) - 1);
            if (! $isList) {
                return $this->normalizeFlowProcessUserValue($value['value']);
            }
        }

        if (isset($value['id']) || isset($value['user_id']) || isset($value['card_id'])) {
            $id = (int) ($value['id'] ?? $value['user_id'] ?? $value['card_id'] ?? 0);

            return $id > 0 ? [$id] : [];
        }

        $ids = [];
        foreach ($value as $item) {
            foreach ($this->normalizeFlowProcessUserValue($item) as $id) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * 已知写工具的预检类型与配置键（供 WriteFormGetTool 等按工具名解析）.
     *
     * @return array<string, array{type: string, config_key?: string}>
     */
    public function definitions(): array
    {
        return [
            'approve_create'                  => ['type' => 'personnel_approve'],
            'payment_create'                  => [
                'type'      => 'customer_approval',
                'config_key'=> 'contract_refund_switch',
                'form_tool' => 'customer_biz_form_get',
                'form_biz'  => 'payment',
            ],
            'renewal_create'                  => [
                'type'      => 'customer_approval',
                'config_key'=> 'contract_renew_switch',
                'form_tool' => 'customer_biz_form_get',
                'form_biz'  => 'renewal',
            ],
            'expense_create'                  => [
                'type'      => 'customer_approval',
                'config_key'=> 'contract_disburse_switch',
                'form_tool' => 'customer_biz_form_get',
                'form_biz'  => 'expense',
            ],
            'invoice_apply'                   => [
                'type'      => 'customer_approval',
                'config_key'=> 'invoicing_switch',
                'form_tool' => 'customer_biz_form_get',
                'form_biz'  => 'invoice_issue',
            ],
            'invoice_void_apply'              => [
                'type'      => 'customer_approval',
                'config_key'=> 'void_invoice_switch',
                'form_tool' => 'customer_biz_form_get',
                'form_biz'  => 'invoice_void',
            ],
            'customer_create'                 => ['type' => 'schema'],
            'contact_create'                  => ['type' => 'schema'],
            'lead_create'                     => ['type' => 'schema'],
            'opportunity_create'              => ['type' => 'schema'],
            'order_create'                    => ['type' => 'schema'],
            'schedule_create'                 => ['type' => 'schema'],
            'schedule_batch_create'           => ['type' => 'schema'],
            'schedule_complete'               => ['type' => 'schema'],
            'report_create'                   => ['type' => 'schema'],
            'follow_up_create'                => ['type' => 'schema'],
            'follow_up_update'                => ['type' => 'schema'],
            'assess_self_eval'                => ['type' => 'schema'],
        ];
    }

    /**
     * 从 ToolRegistry 收集全部 getAction=write 的工具名.
     *
     * @return array<int, string> 已排序的写工具名列表
     */
    public function writeToolNames(): array
    {
        $names = [];
        foreach (ToolRegistry::getToolNames() as $toolName) {
            $tool = ToolRegistry::getTool($toolName);
            if ($tool !== null && method_exists($tool, 'getAction') && $tool->getAction() === 'write') {
                $names[] = $toolName;
            }
        }

        sort($names);

        return $names;
    }

    /**
     * 按工具名查找预检配置，未知 write 工具回退为 schema 类型.
     *
     * @param string $toolName MCP 工具名
     *
     * @return array<string, mixed>|null 预检配置，非 write 工具时返回 null
     */
    public function resolveByToolName(string $toolName): ?array
    {
        $config = $this->definitions()[$toolName] ?? null;
        if ($config === null) {
            if (! in_array($toolName, $this->writeToolNames(), true)) {
                return null;
            }

            return ['tool' => $toolName, 'type' => 'schema'];
        }

        return array_merge(['tool' => $toolName], $config);
    }

    // --- 写工具 schema 解析（原 WriteToolSchemaResolver）---

    /**
     * 解析指定写工具的 inputSchema 为 fields 列表.
     *
     * @param string $toolName MCP 写工具名
     * @param array<string, mixed> $context 可选上下文（如 report types）
     *
     * @return array{tool: string, fields: array<int, array<string, mixed>>, hint: string}|array{error: true, message: string}
     */
    public function resolveWriteToolSchema(string $toolName, array $context = []): array
    {
        $tool = ToolRegistry::getTool($toolName);
        if ($tool === null) {
            return ['error' => true, 'message' => '工具不存在：' . $toolName];
        }
        if (! method_exists($tool, 'getAction') || $tool->getAction() !== 'write') {
            return ['error' => true, 'message' => '非写操作工具：' . $toolName];
        }
        $schema = $tool->getInputSchema();
        $fields = $this->schemaToFields($schema, $toolName);
        $reportTypes = array_key_exists('types', $context) ? (int) $context['types'] : null;
        $fields = $this->enrichSchemaFields($toolName, $fields, $reportTypes);

        return [
            'tool'   => $toolName,
            'fields' => $fields,
            'hint'   => '将 fields[].field 作为 ' . $toolName . ' 的参数键提交；已在对话中出现的内容直接复用，勿重复追问',
        ];
    }


    /** @param array<string, mixed> $schema @return array<int, array<string, mixed>> */
    private function schemaToFields(array $schema, string $toolName): array
    {
        $required   = array_flip((array) ($schema['required'] ?? []));
        $properties = (array) ($schema['properties'] ?? []);
        $keepFields = array_flip(self::SCHEMA_KEEP_FIELDS_BY_TOOL[$toolName] ?? []);
        $fields     = [];
        foreach ($properties as $key => $property) {
            if (! is_string($key) || ! is_array($property)) {
                continue;
            }
            if (in_array($key, self::SCHEMA_SKIP_FIELDS, true) && ! isset($keepFields[$key])) {
                continue;
            }
            $field = $this->mapSchemaPropertyToField($key, $property, isset($required[$key]));
            if ($field !== null) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /** @param array<int, array<string, mixed>> $fields @return array<int, array<string, mixed>> */
    private function enrichSchemaFields(string $toolName, array $fields, ?int $reportTypes = null): array
    {
        if ($toolName === 'report_create') {
            return $this->enrichReportSchemaFields($fields, $reportTypes ?? 0);
        }
        if ($toolName !== 'schedule_create') {
            return $fields;
        }
        $typeOptions = $this->scheduleTypeOptions();
        foreach ($fields as &$field) {
            if (($field['field'] ?? '') !== 'cid') {
                continue;
            }
            $field['title']         = '日程分类';
            $field['type']          = 'select';
            $field['required']      = false;
            $field['options']       = $typeOptions;
            $field['value_example'] = ScheduleEnum::TYPE_PERSONAL;
            $field['input_hint']    = '缺省为个人提醒；仅当用户明确指定其他分类时再改';
        }
        unset($field);

        return $fields;
    }

    /** @param array<int, array<string, mixed>> $fields @return array<int, array<string, mixed>> */
    private function enrichReportSchemaFields(array $fields, int $types): array
    {
        [$finishTitle, $planTitle] = match ($types) {
            1       => ['本周工作', '下周计划'],
            2       => ['本月工作', '下月计划'],
            3       => ['汇报内容', '工作计划'],
            default => ['今日工作', '下个工作日计划'],
        };
        foreach ($fields as &$field) {
            $key = (string) ($field['field'] ?? '');
            if ($key === 'finish') {
                $field['title']      = $finishTitle;
                $field['required']   = true;
                $field['input_hint'] = '可多行填写，每行一条；优先复用对话中已说明的工作内容';
            } elseif ($key === 'plan') {
                $field['title']      = $planTitle;
                $field['required']   = $types !== 3;
                $field['input_hint'] = '可多行填写，每行一条；仅当对话中未说明计划时再填写';
            } elseif ($key === 'types') {
                $field['required'] = false;
            }
        }
        unset($field);

        return $fields;
    }

    /** @return array<int, array{value: int, label: string}> */
    private function scheduleTypeOptions(): array
    {
        try {
            $uid  = (int) (auth('admin')->id() ?: request()->input('mcp_user_db_id', 0));
            $list = app(ScheduleService::class)->typeList($uid, ['id', 'name']);
        } catch (\Throwable) {
            return [['value' => 1, 'label' => '个人提醒']];
        }
        $options = [];
        foreach ((array) $list as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $options[] = ['value' => $id, 'label' => (string) ($item['name'] ?? ('类型' . $id))];
        }

        return $options !== [] ? $options : [['value' => 1, 'label' => '个人提醒']];
    }

    private function mapSchemaPropertyToField(string $key, array $property, bool $required): ?array
    {
        $type        = (string) ($property['type'] ?? 'string');
        $description = (string) ($property['description'] ?? $key);
        $title       = $this->extractSchemaTitle($description, $key);
        if ($type === 'object') {
            return null;
        }
        if ($type === 'array') {
            $itemType = (string) (($property['items']['type'] ?? '') ?: '');
            if ($itemType === 'object') {
                return null;
            }
            // 多选字典字段：优先按选项渲染 select，避免被当成「每行一条」的 textarea.
            $options = $this->extractSchemaEnumOptions($property);
            if ($options === [] && is_array($property['items'] ?? null)) {
                $options = $this->extractSchemaEnumOptions($property['items']);
            }
            if ($options !== []) {
                return [
                    'field'    => $key, 'symbol' => $key, 'title' => $title, 'required' => $required,
                    'type'     => 'select', 'hidden' => false, 'disabled' => false, 'options' => $options,
                    'input_hint' => '可多选；提交值为选项 value 数组',
                ];
            }

            return [
                'field' => $key, 'symbol' => $key, 'title' => $title, 'required' => $required,
                'type' => 'textarea', 'hidden' => false, 'disabled' => false, 'options' => [],
                'value_example' => $itemType === 'string' ? ['每行一条内容'] : null,
                'input_hint' => $itemType === 'string' ? '可输入多行文本，每行作为数组的一项' : '请输入 JSON 数组',
            ];
        }
        $field = [
            'field' => $key, 'symbol' => $key, 'title' => $title, 'required' => $required,
            'type' => $this->mapJsonSchemaType($key, $property), 'hidden' => false,
            'disabled' => false, 'options' => $this->extractSchemaEnumOptions($property),
        ];
        if (! empty($property['x-ai-aliases']) && is_array($property['x-ai-aliases'])) {
            $field['aliases'] = array_values(array_filter(array_map('strval', $property['x-ai-aliases'])));
        }
        if (isset($property['x-ai-normalizer'])) {
            $field['normalizer'] = (string) $property['x-ai-normalizer'];
        }
        if (array_key_exists('default', $property) && ! $required) {
            $field['value_example'] = $property['default'];
        }

        return $field;
    }

    private function mapJsonSchemaType(string $key, array $property): string
    {
        if ($this->extractSchemaEnumOptions($property) !== []) {
            return 'select';
        }
        $type = (string) ($property['type'] ?? 'string');
        if (in_array($type, ['integer', 'number'], true)) {
            return 'number';
        }
        $description = (string) ($property['description'] ?? '');
        if (str_contains($key, '_time') || str_contains($key, 'Time')
            || str_contains($description, '日期时间') || str_contains($description, '时间')) {
            return 'datetime';
        }
        if (str_contains($key, '_date') || str_contains($description, '日期')) {
            return 'date';
        }
        if (str_contains($description, '内容') || str_contains($description, '备注') || str_contains($description, '说明')) {
            return 'textarea';
        }

        return 'input';
    }

    /**
     * 从 Schema 提取下拉选项：优先 x-ai-options（含中文 label），其次 enum.
     *
     * @param array<string, mixed> $property
     *
     * @return array<int, array{value: mixed, label: string}>
     */
    private function extractSchemaEnumOptions(array $property): array
    {
        $fromAi = [];
        foreach ((array) ($property['x-ai-options'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $value = $item['value'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $fromAi[] = [
                'value' => $value,
                'label' => (string) ($item['label'] ?? $value),
            ];
        }
        if ($fromAi !== []) {
            return $fromAi;
        }

        $options = [];
        foreach ((array) ($property['enum'] ?? []) as $value) {
            $label = match ((int) $value) {
                0 => '日报', 1 => '周报', 2 => '月报', 3 => '汇报', default => (string) $value,
            };
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    private function extractSchemaTitle(string $description, string $fallback): string
    {
        $title = trim(preg_replace('/（.*?）|\(.*?\)/u', '', $description) ?? $description);
        // description 尾部可能带面向 Agent 的填写说明（如「必须传仓库 ID，先用 xxx 解析」），
        // 字段标题本身不含句号，在首个句号处截断，避免内部提示透出到用户补录框.
        $title = trim((string) preg_split('/[。;；]/u', $title, 2)[0]);

        return $title !== '' ? $title : $fallback;
    }


    // --- 写确认守卫（原 WriteActionGuard）---

    /**
     * 判断工具是否为 MCP 写操作（getAction=write）.
     *
     * @param string $toolName MCP 工具名
     *
     * @return bool 写工具时为 true
     */
    public function isWriteTool(string $toolName): bool
    {
        $tool = ToolRegistry::getTool($toolName);

        return $tool !== null && method_exists($tool, 'getAction') && $tool->getAction() === 'write';
    }

    /** 业务返回须提供明确落库证据；与 ToolExecutor 共用 McpWriteResult 契约。 */
    public static function writeSucceeded(array $result): bool
    {
        return McpWriteResult::succeeded($result);
    }

    /**
     * 判断写确认失败是否可重试（有面向用户的业务错误文案）.
     *
     * 库存不足、校验失败等 MCP 业务错误应回推表单让用户修改；无文案的未知失败仍按硬失败收尾.
     *
     * @param array<string, mixed> $result 工具执行结果
     */
    public static function isRetryableWriteFailure(array $result): bool
    {
        if (self::writeSucceeded($result)) {
            return false;
        }
        // 自选审批人/抄送人须走 input_required，不能当业务错误重开确认卡（否则确认死循环）.
        if (! empty($result['needs_selection']) || ! empty($result['selection_nodes'])) {
            return false;
        }
        if (! empty($result['needs_input'])) {
            return false;
        }
        $message = trim((string) ($result['message'] ?? ''));

        return $message !== '' && (! empty($result['error']) || array_key_exists('message', $result));
    }

    /**
     * 是否为审批驱动写工具（回款/续费/支出/开票等），process 自选人员需与 approve_create 一样注入.
     */
    public function isApproveProcessWriteTool(string $toolName): bool
    {
        if ($toolName === 'approve_create') {
            return true;
        }
        $config = $this->definitions()[$toolName] ?? null;

        return is_array($config) && ($config['type'] ?? '') === 'customer_approval';
    }

    /**
     * 提取面向用户的写失败原因.
     *
     * @param array<string, mixed> $result 工具执行结果
     */
    public static function writeFailureMessage(array $result, string $toolName = ''): string
    {
        $reason = trim((string) ($result['message'] ?? ''));
        if ($reason === '') {
            return '本次数据写入未成功，请检查填写的信息和操作权限后重试。';
        }
        if ($toolName !== '') {
            $reason = str_replace($toolName, '该操作', $reason);
        }

        return $reason;
    }

    /**
     * 业务失败后重新打开 pending，供用户改表单再确认.
     *
     * @param array<string, mixed> $result 最近一次失败结果（保留便于排查）
     */
    public function reopenPendingAfterBusinessFailure(AgentPendingAction $pending, array $result): void
    {
        $toolName = (string) ($pending->tool_name ?? '');
        $toolArgs = $this->hydrateCustomerApprovalArgs($toolName, (array) ($pending->tool_args ?? []));
        $this->dao->update((int) $pending->id, [
            'status'    => AgentEnum::PENDING_STATUS_PENDING,
            'result'    => $result,
            'tool_args' => $toolArgs,
        ]);
        $pending->status    = AgentEnum::PENDING_STATUS_PENDING;
        $pending->result    = $result;
        $pending->tool_args = $toolArgs;

        if ((int) ($pending->run_id ?? 0) > 0) {
            $this->runDao->update((int) $pending->run_id, [
                'status' => AgentEnum::RUN_STATUS_AWAITING_CONFIRMATION,
            ]);
        }
    }

    /**
     * 判断当前写工具调用是否需要用户确认.
     *
     * 应用开启 writeConfirmation 时，写工具进入 pending，等待前端确认接口续跑。
     *
     * @param string $toolName MCP 工具名
     * @param array{runId: int, messageUuid: string, writeConfirmation?: bool, approvedPendingIds?: array<int, int>} $context 写操作守卫上下文
     *
     * @return bool 恒为 false（写操作直接执行）
     */
    public function requiresConfirmation(string $toolName, array $context): bool
    {
        return ! empty($context['writeConfirmation']) && $this->isWriteTool($toolName);
    }

    /**
     * 将写工具调用持久化为待确认操作.
     *
     * @param array{runId: int, messageUuid: string, writeConfirmation?: bool, approvedPendingIds?: array<int, int>} $context 写操作守卫上下文
     * @param array<string, mixed> $toolCall LLM 返回的 tool_call 结构
     *
     * @return AgentPendingAction|null 新建的 pending 记录，不可用时返回 null
     */
    public function storePending(array $context, array $toolCall): ?AgentPendingAction
    {
        if ((int) ($context['runId'] ?? 0) <= 0 || ! Schema::hasTable('agent_pending_actions')) {
            return null;
        }

        $name = (string) ($toolCall['function']['name'] ?? '');
        $args = $toolCall['function']['arguments'] ?? [];
        if (! is_array($args)) {
            $args = json_decode((string) $args, true);
        }

        /** @var AgentPendingAction $pending */
        $pending = $this->dao->create([
            'run_id'       => (int) $context['runId'],
            'message_uuid' => (string) $context['messageUuid'],
            'tool_call_id' => (string) ($toolCall['id'] ?? ''),
            'tool_name'    => $name,
            'tool_args'    => is_array($args) ? $args : [],
            'status'       => AgentEnum::PENDING_STATUS_PENDING,
        ]);

        return $pending;
    }

    /**
     * 列出指定消息下全部待确认的写操作.
     *
     * @param string $messageUuid 消息 UUID
     *
     * @return array<int, AgentPendingAction> 待确认操作列表
     */
    public function listPending(string $messageUuid): array
    {
        if (! Schema::hasTable('agent_pending_actions')) {
            return [];
        }

        return AgentPendingAction::query()
            ->where('message_uuid', $messageUuid)
            ->where('status', AgentEnum::PENDING_STATUS_PENDING)
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * 按 ID 查找待确认操作.
     *
     * @param int $pendingId 待确认操作 ID
     *
     * @return AgentPendingAction|null 记录对象，无效 ID 时返回 null
     */
    public function find(int $pendingId): ?AgentPendingAction
    {
        if ($pendingId <= 0) {
            return null;
        }

        return $this->dao->get($pendingId);
    }

    /**
     * 标记待确认操作为已拒绝.
     *
     * @param AgentPendingAction $pending 待确认操作
     */
    public function markRejected(AgentPendingAction $pending): void
    {
        $this->dao->update((int) $pending->id, [
            'status' => AgentEnum::PENDING_STATUS_REJECTED,
        ]);
    }

    /**
     * 标记待确认操作为已执行并保存结果.
     *
     * @param AgentPendingAction $pending 待确认操作
     * @param array<string, mixed> $result 工具执行结果
     */
    public function markExecuted(AgentPendingAction $pending, array $result): void
    {
        $this->dao->update((int) $pending->id, [
            'status' => self::writeSucceeded($result) ? AgentEnum::PENDING_STATUS_EXECUTED : AgentEnum::PENDING_STATUS_FAILED,
            'result' => $result,
        ]);
    }

    /**
     * 标记待确认操作为已批准（尚未执行）.
     *
     * @param AgentPendingAction $pending 待确认操作
     */
    public function markApproved(AgentPendingAction $pending): void
    {
        $this->dao->update((int) $pending->id, [
            'status' => AgentEnum::PENDING_STATUS_APPROVED,
        ]);
    }

    /**
     * 判断应用是否关闭写确认（自动批准写操作）.
     *
     * @param AiApplication $app 当前 AI 应用
     *
     * @return bool 当前版本始终要求写操作确认
     */
    public function autoApproveEnabled(AiApplication $app): bool
    {
        $config = AgentConfig::fromApplication($app);

        return false;
    }

    /**
     * 确认或拒绝待执行的写操作（原 AgentConfirmationService）.
     *
     * @return array<string, mixed>
     */
    public function confirm(int $conversationId, int $uid, int $pendingId, bool $approved): array
    {
        $pending = $this->find($pendingId);
        if (! $pending || (string) $pending->status !== AgentEnum::PENDING_STATUS_PENDING) {
            throw new \RuntimeException('待确认操作不存在或已处理');
        }

        $run = $this->runDao->get((int) $pending->run_id);
        if (! $run || (int) $run->conversation_id !== $conversationId || (int) $run->uid !== $uid) {
            throw new \RuntimeException('无权限处理该操作');
        }

        if (! $approved) {
            $changed = AgentPendingAction::query()
                ->whereKey((int) $pending->id)
                ->where('status', AgentEnum::PENDING_STATUS_PENDING)
                ->update(['status' => AgentEnum::PENDING_STATUS_REJECTED]);
            if ($changed !== 1) {
                throw new \RuntimeException('待确认操作不存在或已处理');
            }
            $this->runDao->update((int) $run->id, ['status' => $this->listPending((string) $pending->message_uuid) !== []
                ? AgentEnum::RUN_STATUS_AWAITING_CONFIRMATION : AgentEnum::RUN_STATUS_INTERRUPTED]);

            return [
                'pending_id' => $pendingId,
                'message_uuid' => (string) $pending->message_uuid,
                'status'     => AgentEnum::PENDING_STATUS_REJECTED,
                'message'    => '已拒绝执行',
            ];
        }

        // 原子抢占 pending，避免重复点击或网络重试重复执行同一写工具。
        $changed = AgentPendingAction::query()
            ->whereKey((int) $pending->id)
            ->where('status', AgentEnum::PENDING_STATUS_PENDING)
            ->update(['status' => AgentEnum::PENDING_STATUS_APPROVED]);
        if ($changed !== 1) {
            throw new \RuntimeException('待确认操作不存在或已处理');
        }
        $pending->refresh();
        $this->bindMcpUserForWrite((int) $uid);
        $toolName = (string) $pending->tool_name;
        $toolArgs = (array) ($pending->tool_args ?? []);
        // 确认执行前注入 flow 中已选抄送/审批人，避免 payment_create 等反复 needs_selection.
        if ($this->isApproveProcessWriteTool($toolName)) {
            $loopState = AgentInputRequiredHelper::loadLoopStateCache($pendingId);
            $flow      = is_array($loopState['flow_context'] ?? null) ? $loopState['flow_context'] : [];
            $toolArgs  = $this->enrichApproveCreateArgs($toolArgs, $flow);
            if ($toolArgs !== (array) ($pending->tool_args ?? [])) {
                $this->updatePendingArgs($pending, $toolArgs);
            }
        }
        $result = app(ToolExecutor::class)->execute($toolName, $toolArgs);
        $this->markExecuted($pending, $result);
        $this->tracer->recordStep(
            $run,
            AgentEnum::STEP_TYPE_TOOL_CALL,
            ['tool_name' => (string) $pending->tool_name, 'confirmed' => true],
            $result,
            '',
            (string) $pending->tool_name
        );

        $succeeded = self::writeSucceeded($result);
        // 有可恢复循环状态时勿标 DONE，交由确认后续跑继续未完成写步骤。
        $resumable = $succeeded && AgentInputRequiredHelper::loadLoopStateCache($pendingId) !== null;
        $hasOtherPending = $this->listPending((string) $pending->message_uuid) !== [];
        $hasFailed = AgentPendingAction::query()->where('run_id', (int) $run->id)
            ->where('status', AgentEnum::PENDING_STATUS_FAILED)->exists();
        if ($hasOtherPending) {
            $runStatus = AgentEnum::RUN_STATUS_AWAITING_CONFIRMATION;
        } elseif (! $succeeded || $hasFailed) {
            $runStatus = AgentEnum::RUN_STATUS_FAILED;
        } elseif ($resumable) {
            $runStatus = AgentEnum::RUN_STATUS_EXECUTING;
        } else {
            $runStatus = AgentEnum::RUN_STATUS_DONE;
        }
        $this->runDao->update((int) $run->id, ['status' => $runStatus]);

        return [
            'pending_id'   => $pendingId,
            'message_uuid' => (string) $pending->message_uuid,
            'status'       => $succeeded ? AgentEnum::PENDING_STATUS_EXECUTED : AgentEnum::PENDING_STATUS_FAILED,
            'tool_name'    => (string) $pending->tool_name,
            'tool_call_id' => (string) ($pending->tool_call_id ?? ''),
            'result'       => $result,
            'resumable'    => $resumable,
        ];
    }

    /**
     * 列出当前用户在某消息下的待确认写操作.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listPendingForUser(string $messageUuid, int $uid): array
    {
        $items = [];
        foreach ($this->listPending($messageUuid) as $pending) {
            $run = $this->runDao->get((int) $pending->run_id);
            if (! $run || (int) $run->uid !== $uid) {
                continue;
            }
            $items[] = [
                'id'           => (int) $pending->id,
                'run_id'       => (int) $pending->run_id,
                'message_uuid' => (string) $pending->message_uuid,
                'tool_name'    => (string) $pending->tool_name,
                'tool_args'    => (array) ($pending->tool_args ?? []),
                'status'       => (string) $pending->status,
            ];
        }

        return $items;
    }

    /**
     * 确认写执行前绑定会话所属用户到 MCP 上下文.
     *
     * 写确认接口不经过 stream 的 merge，直接 ToolExecutor 会得到空 mcp_user，
     * 导致 report_create 等以空 uid/user_id=0 落库并被判定写入失败.
     * 仅 merge 当前 request，不 instance 固化 McpRequest，避免 LaravelS 跨请求串用户.
     *
     * @throws \RuntimeException 用户无效时 fail-closed，禁止空身份写入
     */
    private function bindMcpUserForWrite(int $uid): void
    {
        if ($uid <= 0) {
            throw new \RuntimeException('写操作缺少有效用户，无法执行');
        }

        /** @var null|Admin $user */
        $user = Admin::query()->find($uid);
        if (! $user) {
            throw new \RuntimeException('写操作所属用户不存在，无法执行');
        }

        $request = request();
        app(McpUserContextResolver::class)->merge($request, $user);
        if (app()->bound(McpRequest::class)) {
            app(McpRequest::class)->resetRequestCache();
        }
    }
}
