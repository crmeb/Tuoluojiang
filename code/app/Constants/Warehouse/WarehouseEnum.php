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

namespace App\Constants\Warehouse;

/**
 * 仓储模块通用枚举。
 */
final class WarehouseEnum
{
    /**
     * 仓库停用。
     */
    public const STATUS_DISABLE = 0;

    /**
     * 仓库启用。
     */
    public const STATUS_ENABLE  = 1;

    /**
     * 普通库管。
     */
    public const MANAGER_NORMAL = 0;

    /**
     * 超级库管。
     */
    public const MANAGER_SUPER  = 1;

    /**
     * 库存管理权限标识。
     */
    public const ACTION_STOCK    = 'stock';

    /**
     * 库存设置权限标识。
     */
    public const ACTION_STOCK_SETTING = 'stock_setting';

    /**
     * 入库权限标识。
     */
    public const ACTION_INBOUND  = 'inbound';

    /**
     * 出库权限标识。
     */
    public const ACTION_OUTBOUND = 'outbound';

    /**
     * 盘点权限标识。
     */
    public const ACTION_CHECK    = 'check';

    /**
     * 调拨权限标识。
     */
    public const ACTION_TRANSFER = 'transfer';

    /**
     * 正残品转换权限标识。
     */
    public const ACTION_CONVERT  = 'convert';

    /**
     * 库管可操作的业务动作集合。
     */
    public const PERMISSION_ACTIONS = [
        self::ACTION_STOCK,
        self::ACTION_STOCK_SETTING,
        self::ACTION_INBOUND,
        self::ACTION_OUTBOUND,
        self::ACTION_CHECK,
        self::ACTION_TRANSFER,
        self::ACTION_CONVERT,
    ];

    /**
     * 正品库存。
     */
    public const STOCK_QUALITY_GOOD    = 1;

    /**
     * 次品库存。
     */
    public const STOCK_QUALITY_DEFECT  = 2;

    /**
     * 占用库存。
     */
    public const STOCK_QUALITY_OCCUPY  = 3;

    /**
     * 在途库存。
     */
    public const STOCK_QUALITY_TRANSIT = 4;

    /**
     * 可参与库存属性筛选的值。
     */
    public const STOCK_QUALITY_OPTIONS = [
        self::STOCK_QUALITY_GOOD,
        self::STOCK_QUALITY_DEFECT,
    ];

    /**
     * 所有库存类型集合。
     */
    public const STOCK_QUALITY_ALL = [
        self::STOCK_QUALITY_GOOD,
        self::STOCK_QUALITY_DEFECT,
        self::STOCK_QUALITY_OCCUPY,
        self::STOCK_QUALITY_TRANSIT,
    ];

    /**
     * 采购入库。
     */
    public const INBOUND_TYPE_PURCHASE = 1;

    /**
     * 退货入库。
     */
    public const INBOUND_TYPE_RETURN   = 2;

    /**
     * 其他入库。
     */
    public const INBOUND_TYPE_OTHER    = 3;

    /**
     * 单据可用的入库类型集合。
     */
    public const INBOUND_TYPES = [
        self::INBOUND_TYPE_PURCHASE,
        self::INBOUND_TYPE_RETURN,
        self::INBOUND_TYPE_OTHER,
    ];

    /**
     * 销售出库。
     */
    public const OUTBOUND_TYPE_SALES = 1;

    /**
     * 过期退货。
     */
    public const OUTBOUND_TYPE_EXPIRED_RETURN = 2;

    /**
     * 试用出库。
     */
    public const OUTBOUND_TYPE_TRIAL = 3;

    /**
     * 其他出库。
     */
    public const OUTBOUND_TYPE_OTHER = 4;

    /**
     * 单据可用的出库类型集合。
     */
    public const OUTBOUND_TYPES = [
        self::OUTBOUND_TYPE_SALES,
        self::OUTBOUND_TYPE_EXPIRED_RETURN,
        self::OUTBOUND_TYPE_TRIAL,
        self::OUTBOUND_TYPE_OTHER,
    ];

    /**
     * 库存属性中文名称映射。
     */
    public const STOCK_QUALITY_LABELS = [
        self::STOCK_QUALITY_GOOD    => '正品',
        self::STOCK_QUALITY_DEFECT  => '次品',
        self::STOCK_QUALITY_OCCUPY  => '占用',
        self::STOCK_QUALITY_TRANSIT => '在途',
    ];

    /**
     * 单据草稿。
     */
    public const BILL_STATUS_DRAFT     = 0;

    /**
     * 单据待处理。
     */
    public const BILL_STATUS_PENDING   = 1;

    /**
     * 单据已完成。
     */
    public const BILL_STATUS_COMPLETED = 2;

    /**
     * 单据已作废。
     */
    public const BILL_STATUS_CANCELLED = 9;

    /**
     * 入库单草稿。
     */
    public const INBOUND_STATUS_DRAFT = self::BILL_STATUS_DRAFT;

    /**
     * 入库单待入库。
     */
    public const INBOUND_STATUS_PENDING = self::BILL_STATUS_PENDING;

    /**
     * 入库单已完成。
     */
    public const INBOUND_STATUS_COMPLETED = self::BILL_STATUS_COMPLETED;

    /**
     * 入库单已作废。
     */
    public const INBOUND_STATUS_CANCELLED = self::BILL_STATUS_CANCELLED;

    /**
     * 出库单草稿。
     */
    public const OUTBOUND_STATUS_DRAFT = self::BILL_STATUS_DRAFT;

    /**
     * 出库单待出库。
     */
    public const OUTBOUND_STATUS_PENDING = self::BILL_STATUS_PENDING;

    /**
     * 出库单已完成。
     */
    public const OUTBOUND_STATUS_COMPLETED = self::BILL_STATUS_COMPLETED;

    /**
     * 出库单已作废。
     */
    public const OUTBOUND_STATUS_CANCELLED = self::BILL_STATUS_CANCELLED;

    /**
     * 盘点进行中。
     */
    public const CHECK_STATUS_DOING    = 1;

    /**
     * 盘点已完成。
     */
    public const CHECK_STATUS_DONE     = 2;

    /**
     * 盘点已作废。
     */
    public const CHECK_STATUS_CANCEL   = 9;

    /**
     * 直调直达。
     */
    public const TRANSFER_DIRECT   = 1;

    /**
     * 物流调拨。
     */
    public const TRANSFER_LOGISTIC = 2;

    /**
     * 调拨待出库。
     */
    public const TRANSFER_PENDING_OUT = 1;

    /**
     * 调拨待入库。
     */
    public const TRANSFER_PENDING_IN  = 2;

    /**
     * 调拨完成。
     */
    public const TRANSFER_DONE        = 3;

    /**
     * 调拨已作废。
     */
    public const TRANSFER_CANCELLED   = 9;

    /**
     * 良品转次品。
     */
    public const CONVERT_GOOD_TO_DEFECT = 1;

    /**
     * 次品转良品。
     */
    public const CONVERT_DEFECT_TO_GOOD = 2;

    /**
     * 库存正常。
     */
    public const STOCK_STATUS_NORMAL   = 0;

    /**
     * 库存偏低。
     */
    public const STOCK_STATUS_LOW      = 1;

    /**
     * 库存偏高。
     */
    public const STOCK_STATUS_HIGH     = 2;

    /**
     * 库存已售罄。
     */
    public const STOCK_STATUS_SOLD_OUT = 3;

    /**
     * 入库流水。
     */
    public const FLOW_INBOUND         = 1;

    /**
     * 出库流水。
     */
    public const FLOW_OUTBOUND        = 2;

    /**
     * 占用库存流水。
     */
    public const FLOW_OCCUPY          = 3;

    /**
     * 释放占用流水。
     */
    public const FLOW_RELEASE_OCCUPY  = 4;

    /**
     * 在途流水。
     */
    public const FLOW_TRANSIT         = 5;

    /**
     * 释放在途流水。
     */
    public const FLOW_RELEASE_TRANSIT = 6;

    /**
     * 盘盈流水。
     */
    public const FLOW_CHECK_PROFIT    = 7;

    /**
     * 盘亏流水。
     */
    public const FLOW_CHECK_LOSS      = 8;

    /**
     * 调拨出库流水。
     */
    public const FLOW_TRANSFER_OUT    = 9;

    /**
     * 调拨入库流水。
     */
    public const FLOW_TRANSFER_IN     = 10;

    /**
     * 正残品转换流水。
     */
    public const FLOW_CONVERT         = 11;

    /**
     * 库存流水变动类型集合。
     */
    public const FLOW_TYPES = [
        self::FLOW_INBOUND,
        self::FLOW_OUTBOUND,
        self::FLOW_OCCUPY,
        self::FLOW_RELEASE_OCCUPY,
        self::FLOW_TRANSIT,
        self::FLOW_RELEASE_TRANSIT,
        self::FLOW_CHECK_PROFIT,
        self::FLOW_CHECK_LOSS,
        self::FLOW_TRANSFER_OUT,
        self::FLOW_TRANSFER_IN,
        self::FLOW_CONVERT,
    ];

    /**
     * 手工来源。
     */
    public const SOURCE_MANUAL = 0;

    /**
     * 入库单来源。
     */
    public const SOURCE_INBOUND = 1;

    /**
     * 出库单来源。
     */
    public const SOURCE_OUTBOUND = 2;

    /**
     * 盘点单来源。
     */
    public const SOURCE_CHECK = 3;

    /**
     * 调拨单来源。
     */
    public const SOURCE_TRANSFER = 4;

    /**
     * 正残品转换单来源。
     */
    public const SOURCE_CONVERT = 5;

    /**
     * 采购订单来源。
     */
    public const SOURCE_PURCHASE_ORDER = 6;

    /**
     * 退货订单来源。
     */
    public const SOURCE_RETURN_ORDER = 7;

    /**
     * 订单来源。
     */
    public const SOURCE_ORDER = 8;

    /**
     * 合同来源。
     */
    public const SOURCE_CONTRACT = 9;

    /**
     * 销售订单来源。
     */
    public const SOURCE_SALES_ORDER = 10;

    /**
     * 仓储来源类型集合。
     */
    public const SOURCE_TYPES = [
        self::SOURCE_MANUAL,
        self::SOURCE_INBOUND,
        self::SOURCE_OUTBOUND,
        self::SOURCE_CHECK,
        self::SOURCE_TRANSFER,
        self::SOURCE_CONVERT,
        self::SOURCE_PURCHASE_ORDER,
        self::SOURCE_RETURN_ORDER,
        self::SOURCE_ORDER,
        self::SOURCE_CONTRACT,
        self::SOURCE_SALES_ORDER,
    ];

    /**
     * 外部业务来源类型映射，用于订单库存配置。
     */
    public const SOURCE_BUSINESS_TYPES = [
        self::SOURCE_PURCHASE_ORDER => 'purchase_order',
        self::SOURCE_RETURN_ORDER   => 'return_order',
        self::SOURCE_ORDER          => 'order',
        self::SOURCE_CONTRACT       => 'contract',
        self::SOURCE_SALES_ORDER    => 'sales_order',
    ];

    /**
     * 校验仓储来源类型。
     */
    public static function sourceTypeValue(?int $sourceType): int
    {
        if ($sourceType === null || in_array($sourceType, self::SOURCE_TYPES, true)) {
            return $sourceType ?? self::SOURCE_MANUAL;
        }

        return self::SOURCE_MANUAL;
    }

    /**
     * 获取外部业务来源类型。
     */
    public static function businessSourceType(?int $sourceType): string
    {
        if ($sourceType === null) {
            return '';
        }

        return self::SOURCE_BUSINESS_TYPES[$sourceType] ?? '';
    }
}
