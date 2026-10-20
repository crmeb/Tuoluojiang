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

namespace App\Http\Service\Config;

use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\LeadService;
use App\Http\Service\Customer\OpportunityService;
use App\Http\Service\Warehouse\WarehouseService;

/**
 * 自定义表单内置关联字段注册表：「字段 → 实体」知识的唯一事实源.
 *
 * 背景：关联字段（值存目标记录 ID，如订单的 contract_customer）是系统内置的
 * 闭合集合，与 FormService::getFormDataList 规则集合中的选项规则同源。此前该
 * 知识分散维护（Mcp schema 提示 / AI 展示回译 / 表单渲染各一份），新增内置
 * 关联字段时容易漏配；现收敛到本注册表，三处一律从派生接口取用.
 *
 * 新增内置关联字段时只需在 FIELDS 与 ENTITY_RESOLVERS 各登记一条.
 */
final class RelationFieldRegistry
{
    /**
     * 关联字段注册表：key => 实体标识与（可选的）MCP schema 提示文案.
     *
     * hint 为空表示该字段未开放给模型填写（如 eid 由系统补参），无需 schema 提示.
     */
    private const FIELDS = [
        'contract_customer' => [
            'entity' => 'customer',
            'hint'   => '必须传客户 ID（整数），不是客户名称；先用 customer_list / customer_detail 按名称解析出 id 再填入',
        ],
        'odds_customer' => [
            'entity' => 'customer',
            'hint'   => '必须传客户 ID（整数），不是客户名称；先用 customer_list / customer_detail 按名称解析出 id 再填入',
        ],
        'oid' => [
            'entity' => 'opportunity',
            'hint'   => '必须传商机 ID（整数），不是商机名称；先用 opportunity_list 按名称解析出 id 再填入',
        ],
        'outbound_warehouse_id' => [
            'entity' => 'warehouse',
            'hint'   => '必须传仓库 ID（整数），不是仓库名称；先用 warehouse_list 按名称解析出 id 再填入',
        ],
        'clue_id' => [
            'entity' => 'lead',
        ],
        // 联系人挂靠的客户 ID；create 工具已排除该参数（由系统补参），登记作防御
        'eid' => [
            'entity' => 'customer',
        ],
    ];

    /** 实体标识 → [查询服务类, 名称字段]；用于把 ID 回译成展示名称. */
    private const ENTITY_RESOLVERS = [
        'customer'    => [CustomerService::class, 'customer_name'],
        'opportunity' => [OpportunityService::class, 'name'],
        'lead'        => [LeadService::class, 'name'],
        'warehouse'   => [WarehouseService::class, 'name'],
    ];

    /** 判断是否为已注册的内置关联字段. */
    public static function isRelationField(string $key): bool
    {
        return isset(self::FIELDS[$key]);
    }

    /**
     * 查关联字段的 MCP schema 提示文案；未注册或未配置提示时返回空串.
     */
    public static function schemaHint(string $key): string
    {
        return (string) (self::FIELDS[$key]['hint'] ?? '');
    }

    /**
     * 把关联字段值（记录 ID）回译成实体展示名称.
     *
     * ID 非法、记录不存在或查询异常时返回 null，由调用方回退展示原值.
     */
    public static function displayLabel(string $key, string $value): ?string
    {
        $entity = self::FIELDS[$key]['entity'] ?? '';
        $resolver = self::ENTITY_RESOLVERS[$entity] ?? null;
        if ($resolver === null) {
            return null;
        }

        $value = trim($value);
        // 仅接受纯数字 ID（含字符串 "522"）；拒绝带小数/杂讯，避免误解析.
        if ($value === '' || ! preg_match('/^\d+$/', $value)) {
            return null;
        }
        $id = (int) $value;
        if ($id <= 0) {
            return null;
        }

        try {
            /** @var string $label */
            $label = app($resolver[0])->value($id, $resolver[1]);
        } catch (\Throwable) {
            return null;
        }

        return $label === null ? null : (string) $label;
    }
}
