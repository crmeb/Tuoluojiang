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

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Config\RelationFieldRegistry;

/**
 * 写操作的中文展示元数据：工具中文名、字段标签与值回译管道.
 *
 * 调用链：AgentExecutor 写确认预览 / AiConversationMessageService 写成功回显 → 本类 → FormService 自定义表单、RelationFieldRegistry.
 * 标签优先取租户自定义表单 key_name，通用键与参数键名逐级兜底；关联字段（ID→名称）回译
 * 统一委托 RelationFieldRegistry（「字段→实体」唯一事实源）；读取失败不抛错，保证确认与回显链路不中断.
 */
final class WriteFieldLabels
{
    /** 写工具 → 中文操作名；未知工具返回空串由调用方兜底. */
    private const TOOL_LABELS = [
        'customer_create'             => '新增客户',
        'contact_create'              => '新增联系人',
        'lead_create'                 => '新增线索',
        'opportunity_create'          => '新增商机',
        'order_create'                => '创建订单',
        'schedule_create'             => '创建日程',
        'schedule_batch_create'       => '批量创建日程',
        'schedule_complete'           => '完成日程',
        'report_create'               => '提交工作汇报',
        'follow_up_create'   => '新增客户跟进',
        'follow_up_create'       => '新增线索跟进',
        'follow_up_create' => '新增商机跟进',
        'follow_up_update'   => '更新客户跟进',
        'follow_up_update'       => '更新线索跟进',
        'follow_up_update' => '更新商机跟进',
        'report_update'               => '更新工作汇报',
        'schedule_update'             => '更新日程',
        'assess_self_eval'            => '提交绩效自评',
        'payment_create'              => '新增回款',
        'renewal_create'              => '新增续费',
        'expense_create'              => '新增支出',
        'invoice_apply'               => '提交开票申请',
        'invoice_void_apply'          => '提交作废发票',
        'approve_create'              => '发起审批',
    ];

    /** 写工具 → 参数所属的自定义表单类型（决定去哪张表单配置取字段标签）. */
    private const TOOL_CUSTOM_TYPES = [
        'customer_create'    => [CustomEnum::CUSTOMER, CustomEnum::LIAISON],
        'contact_create'     => [CustomEnum::LIAISON],
        'lead_create'        => [CustomEnum::CLUE],
        'opportunity_create' => [CustomEnum::ODDS],
        'order_create'       => [CustomEnum::CONTRACT],
    ];

    /** 通用字段标签兜底：自定义表单未覆盖的键（日程/审批/回款等静态字段）使用. */
    private const BASE_LABELS = [
        'title'         => '标题',
        'content'       => '内容',
        'start_time'    => '开始时间',
        'end_time'      => '结束时间',
        'date'          => '日期',
        'period'        => '周期',
        'focus'         => '范围',
        'amount'        => '金额',
        'remark'        => '备注',
        'reason'        => '事由',
        'types'         => '类型',
        'name'          => '名称',
        'customer_name' => '客户名称',
        'liaison_name'  => '联系人姓名',
        'phone'         => '电话',
        'mobile'        => '手机',
        'email'         => '邮箱',
        'address'       => '地址',
        'total_price'   => '总金额',
        'time'          => '时间',
        'cid'           => '订单',
        'eid'           => '客户',
        'customer_id'   => '客户',
        'contract_id'   => '订单',
        'link_id'       => '关联业务',
    ];

    /** 工具专属字段标签：覆盖通用兜底（如跟进类工具的 entity_name 按对象类型称呼）. */
    private const TOOL_FIELD_LABELS = [
        'follow_up_create'     => ['entity_name' => '客户名称'],
        'follow_up_update'     => ['entity_name' => '客户名称'],
        'follow_up_create'         => ['entity_name' => '线索名称'],
        'follow_up_update'         => ['entity_name' => '线索名称'],
        'follow_up_create'  => ['entity_name' => '商机名称'],
        'follow_up_update'  => ['entity_name' => '商机名称'],
    ];

    /**
     * 判断字段值是否已被回译管道覆盖（字典命中或已注册关联解析器）.
     *
     * 供展示层对「纯数字值未翻译」做漏网观测：新增内置关联字段时若漏配，
     * 确认环节告警而不是等用户在界面看到数字才发现.
     *
     * @param array<string, array{label: string, options: array<string, string>}> $meta fieldMeta 结果
     */
    public static function isTranslated(array $meta, string $key, string $value): bool
    {
        if (isset($meta[$key]['options'][$value])) {
            return true;
        }

        return RelationFieldRegistry::isRelationField($key);
    }

    /** 查写工具的中文操作名；未收录的工具返回空串. */
    public static function operationLabel(string $toolName): string
    {
        return self::TOOL_LABELS[$toolName] ?? '';
    }

    /**
     * 汇总写工具字段的展示元数据：中文标签与字典选项的「值→文字」映射.
     *
     * 优先级：租户自定义表单 > 通用兜底；select/字典类字段附带 options，供把模型提交的
     * 选项值（如客户来源 3）回译成选项文字。表单或字典读取失败时不抛错，保留兜底展示.
     *
     * @return array<string, array{label: string, options: array<string, string>}> key → 标签与选项映射
     */
    public static function fieldMeta(string $toolName): array
    {
        $meta = [];
        foreach (self::BASE_LABELS as $key => $label) {
            $meta[$key] = ['label' => $label, 'options' => []];
        }
        // 工具专属标签覆盖通用兜底（自定义表单随后仍可再覆盖）
        foreach (self::TOOL_FIELD_LABELS[$toolName] ?? [] as $key => $label) {
            $meta[$key] = ['label' => $label, 'options' => []];
        }
        foreach (self::TOOL_CUSTOM_TYPES[$toolName] ?? [] as $customType) {
            try {
                $fields = app(FormService::class)->getFormDataList((int) $customType) ?? [];
            } catch (\Throwable) {
                // 表单配置读取失败时保留兜底标签，不阻断确认/回显链路.
                continue;
            }
            foreach ($fields as $field) {
                $key  = (string) ($field['key'] ?? '');
                $name = trim((string) ($field['key_name'] ?? ''));
                if ($key === '' || $name === '') {
                    continue;
                }
                $meta[$key] = [
                    'label'   => $name,
                    'options' => self::dictOptions((string) ($field['dict_ident'] ?? '')),
                ];
            }
        }

        return $meta;
    }

    /** 查写工具字段键到中文标签的映射（fieldMeta 的标签视图）. */
    public static function forTool(string $toolName): array
    {
        $labels = [];
        foreach (self::fieldMeta($toolName) as $key => $meta) {
            $labels[$key] = $meta['label'];
        }

        return $labels;
    }

    /**
     * 把字段值翻译成人类可读文字；无匹配时原样返回.
     *
     * 回译顺序：字典选项文字（如客户来源 3 → 线上推广）→ 关联实体名称（如
     * contract_customer 3 → 客户名称）→ 原值兜底；关联解析统一委托 RelationFieldRegistry.
     *
     * @param array<string, array{label: string, options: array<string, string>}> $meta fieldMeta 结果
     */
    public static function displayValue(array $meta, string $key, string $value): string
    {
        $options = $meta[$key]['options'] ?? [];
        if ($options !== [] && isset($options[$value])) {
            return $options[$value];
        }

        if (RelationFieldRegistry::isRelationField($key)) {
            $label = RelationFieldRegistry::displayLabel($key, $value);
            if ($label !== null && $label !== '') {
                return $label;
            }
        }

        return $value;
    }

    /** 字典标识 → value→文字 映射；非字典字段返回空映射. */
    private static function dictOptions(string $dictIdent): array
    {
        if ($dictIdent === '' || $dictIdent === 'area_cascade') {
            return [];
        }
        try {
            $items = app(DictDataService::class)->getTreeData(
                ['type_name' => $dictIdent, 'status' => 1],
                ['name as label', 'name as text', 'value', 'pid']
            );
        } catch (\Throwable) {
            return [];
        }

        return self::flattenOptions(is_array($items) ? $items : [], []);
    }

    /** 递归展平字典树，收集全部层级的 value→label. */
    private static function flattenOptions(array $nodes, array $map): array
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            $value = $node['value'] ?? null;
            $label = trim((string) ($node['label'] ?? $node['text'] ?? ''));
            if ($value !== null && $value !== '' && $label !== '') {
                $map[(string) $value] = $label;
            }
            $children = $node['children'] ?? [];
            if (is_array($children) && $children !== []) {
                $map = self::flattenOptions($children, $map);
            }
        }

        return $map;
    }
}
