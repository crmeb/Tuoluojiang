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

namespace App\Http\Service\Warehouse;

use App\Constants\AttachEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Dao\Customer\ProductAttrValueDao;
use App\Http\Dao\Warehouse\WarehouseInboundDao;
use App\Http\Dao\Warehouse\WarehouseInboundDetailDao;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\ProductAssistService;
use App\Http\Service\Customer\ProductAttrValueService;

/**
 * 入库单服务，负责入库单创建与确认入库.
 */
class WarehouseInboundService extends WarehouseBillService
{
    /**
     * 外部单据类型与合同类型的映射，用于入库单自动回填商品来源信息.
     */
    private const SOURCE_LINK_TYPES = [
        WarehouseEnum::SOURCE_SALES_ORDER    => CustomEnum::CONTRACT,
        WarehouseEnum::SOURCE_PURCHASE_ORDER => CustomEnum::CONTRACT,
        WarehouseEnum::SOURCE_RETURN_ORDER   => CustomEnum::CONTRACT,
    ];

    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseInboundDao $dao, WarehouseInboundDetailDao $detailDao)
    {
        $this->dao       = $dao;
        $this->detailDao = $detailDao;
    }

    /**
     * 创建仓储业务单据.
     */
    public function createBill(array $data, int $uid): int
    {
        $attachIds = $this->normalizeAttachIds((array) ($data['attach_ids'] ?? $data['images'] ?? []));
        $inboundType = $this->normalizeInboundType((int) ($data['inbound_type'] ?? WarehouseEnum::INBOUND_TYPE_PURCHASE));
        $data['source_type'] = $this->normalizeSourceType($data['source_type'] ?? null, $inboundType);
        $sourceInfo = $this->resolveSourceInfo($data);
        $data['source_id'] = $sourceInfo['source_id'];
        $data['source_no'] = $sourceInfo['source_no'];
        app(WarehouseService::class)->assertEnabled((int) $data['warehouse_id']);
        app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['warehouse_id'], $uid, WarehouseEnum::ACTION_INBOUND);
        return $this->transaction(function () use ($data, $uid, $attachIds, $inboundType) {
            $sourceDetails = (array) ($data['details'] ?? []);
            if (! $sourceDetails && $inboundType !== WarehouseEnum::INBOUND_TYPE_OTHER) {
                $sourceDetails = $this->sourceProducts($data);
            }
            $details = $this->normalizeInboundDetails($sourceDetails, $inboundType);
            $this->assertNoCheckingConflict((int) $data['warehouse_id'], $details);
            $bill = $this->dao->create([
                'bill_no'      => $this->billNo('RK'),
                'warehouse_id' => (int) $data['warehouse_id'],
                'inbound_type' => $inboundType,
                'status'       => WarehouseEnum::INBOUND_STATUS_PENDING,
                'source_type'  => $data['source_type'] ?? WarehouseEnum::SOURCE_MANUAL,
                'source_id'    => (int) ($data['source_id'] ?? 0),
                'source_no'    => $data['source_no'] ?? '',
                'total_amount' => $this->sumAmount($details),
                'images'       => $attachIds,
                'remark'       => $data['remark'] ?? '',
                'creator'      => $uid,
            ]);
            $this->syncAttachRelation($attachIds, (int) $bill->id, AttachEnum::RELATION_TYPE_WAREHOUSE_INBOUND);
            $this->saveDetails((int) $bill->id, 'inbound_id', $details);
            return (int) $bill->id;
        });
    }

    /**
     * 确认入库并按库存属性增加良品或残次品库存.
     */
    public function confirm(int $id, int $uid): void
    {
        $this->transaction(function () use ($id, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('入库单不存在');
            }
            // 调拨来源入库单只推进调拨流程，避免普通入库逻辑重复增加库存。
            if ((int) $bill->source_type === WarehouseEnum::SOURCE_TRANSFER && (int) $bill->source_id) {
                app(WarehouseTransferService::class)->transferIn((int) $bill->source_id, $uid);
                return;
            }
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
            if ((int) $bill->status === WarehouseEnum::INBOUND_STATUS_COMPLETED) {
                return;
            }
            if ((int) $bill->status !== WarehouseEnum::INBOUND_STATUS_PENDING) {
                throw $this->exception('当前状态不允许确认入库');
            }
            $details = $this->detailDao->select(['inbound_id' => $id]);
            $this->assertNoCheckingConflict((int) $bill->warehouse_id, $details->toArray());
            foreach ($details as $detail) {
                $stockQuality = $this->normalizeStockQuality($detail->stock_quality ?: WarehouseEnum::STOCK_QUALITY_GOOD);
                $stockQuantity = (int) $detail->quantity + (int) ($detail->gift_quantity ?? 0);
                if ($stockQuantity <= 0) {
                    continue;
                }
                app(WarehouseStockService::class)->change([
                    'warehouse_id' => (int) $bill->warehouse_id,
                    'product_id'   => (int) $detail->product_id,
                    'sku_id'       => (int) $detail->sku_id,
                ], $stockQuality, $stockQuantity, WarehouseEnum::FLOW_INBOUND, [
                    'source_type' => WarehouseEnum::SOURCE_INBOUND,
                    'source_id'   => $id,
                    'source_no'   => $bill->bill_no,
                ], $uid, (string) $bill->remark);
            }
            $bill->status      = WarehouseEnum::INBOUND_STATUS_COMPLETED;
            $bill->operator    = $uid;
            $bill->operated_at = now();
            $bill->save();
        });
    }

    /**
     * 当前单据对应的库管动作权限.
     */
    protected function permissionAction(): string
    {
        return WarehouseEnum::ACTION_INBOUND;
    }

    /**
     * 入库列表额外加载来源订单与创建人.
     */
    protected function billListWith(): array
    {
        return array_merge(parent::billListWith(), ['order', 'orderByNo', 'creatorInfo']);
    }

    /**
     * 入库详情额外加载来源订单与创建人.
     */
    protected function billDetailWith(): array
    {
        return array_merge(parent::billDetailWith(), ['order', 'orderByNo', 'creatorInfo', 'operatorInfo']);
    }

    /**
     * 入库列表补充订单付款状态.
     */
    protected function formatBillList($list): array
    {
        return $this->formatOrderBillList($list);
    }

    /**
     * 入库详情补充订单付款状态.
     */
    protected function formatBillDetail(array $info): array
    {
        return $this->formatOrderBillDetail($info);
    }

    /**
     * 获取来源订单商品，供入库单自动回填.
     */
    public function sourceProducts(array $data): array
    {
        $inboundType = $this->normalizeInboundType((int) ($data['inbound_type'] ?? WarehouseEnum::INBOUND_TYPE_PURCHASE));
        if ($inboundType === WarehouseEnum::INBOUND_TYPE_OTHER) {
            return [];
        }

        $sourceInfo = $this->resolveSourceInfo($data);
        $sourceId = $sourceInfo['source_id'];
        if (! $sourceId) {
            throw $this->exception('请选择关联订单或添加商品明细');
        }

        $sourceType = $this->normalizeSourceType($data['source_type'] ?? null, $inboundType);
        $linkType = self::SOURCE_LINK_TYPES[$sourceType] ?? CustomEnum::CONTRACT;
        $products = app(ProductAssistService::class)->select([
            'link_type' => $linkType,
            'link_id'   => $sourceId,
        ])?->toArray();
        if (! $products) {
            throw $this->exception('关联订单未找到商品信息，请手动添加');
        }

        $skuList = app(ProductAttrValueService::class)->select([
            'unique' => array_column($products, 'unique'),
        ], with: ['product'])?->toArray();
        $skuMap = array_column($skuList ?: [], null, 'unique');
        $rows = [];
        foreach ($products as $product) {
            $sku = $skuMap[$product['unique'] ?? ''] ?? null;
            if (! $sku) {
                continue;
            }
            $quantity = $this->normalizeIntegerQuantity($product['count'] ?? 0, '关联订单商品数量错误');
            if ($quantity <= 0) {
                continue;
            }
            $unitPrice = (float) ($product['price'] ?? $sku['price'] ?? 0);
            $rows[] = [
                'product_id'     => (int) $sku['product_id'],
                'sku_id'         => (int) $sku['id'],
                'quantity'       => $quantity,
                'stock_quality'  => WarehouseEnum::STOCK_QUALITY_GOOD,
                'unit_price'     => $unitPrice,
                'gift_quantity'  => 0,
                'amount'         => bcmul((string) $unitPrice, (string) $quantity, 2),
                'remark'         => $product['remark'] ?? '',
                'product'        => $sku['product'] ?? null,
                'sku'            => $sku,
            ];
        }

        if (! $rows) {
            throw $this->exception('关联订单商品未匹配到可入库规格，请手动添加');
        }
        return $rows;
    }

    /**
     * 标准化入库商品明细并计算金额.
     */
    private function normalizeInboundDetails(array $details, int $inboundType): array
    {
        if (! $details) {
            throw $this->exception('请添加商品明细');
        }
        $rows = [];
        $requiresFinance = $inboundType !== WarehouseEnum::INBOUND_TYPE_OTHER;
        foreach ($details as $detail) {
            $skuId = (int) ($detail['sku_id'] ?? 0);
            $quantity = $this->getDetailInteger($detail, ['quantity', 'inbound_quantity', 'stock_quantity', 'num']);
            if (! $skuId || $quantity <= 0) {
                throw $this->exception('商品明细数量错误');
            }
            $sku = app(ProductAttrValueDao::class)->get($skuId, with: ['product'])?->toArray();
            if (! $sku) {
                throw $this->exception('商品规格不存在');
            }
            if ($requiresFinance && ! $this->hasAnyDetailField($detail, ['unit_price', 'price', 'sales_price', 'sell_price'])) {
                throw $this->exception('请填写入库单价');
            }
            $unitPrice = $requiresFinance ? $this->getDetailNumber($detail, ['unit_price', 'price', 'sales_price', 'sell_price']) : 0;
            $giftQuantity = $requiresFinance ? $this->getDetailInteger($detail, ['gift_quantity', 'gift_num']) : 0;
            if ($unitPrice < 0 || $giftQuantity < 0) {
                throw $this->exception('入库单价或赠送数量错误');
            }

            $rows[] = [
                'product_id'    => (int) $sku['product_id'],
                'sku_id'        => $skuId,
                'stock_quality' => $this->normalizeStockQuality($detail['stock_quality'] ?? WarehouseEnum::STOCK_QUALITY_GOOD),
                'unit_price'    => $unitPrice,
                'quantity'      => $quantity,
                'gift_quantity' => $giftQuantity,
                'amount'        => $requiresFinance ? bcmul((string) $unitPrice, (string) $quantity, 2) : '0.00',
                'remark'        => $detail['remark'] ?? '',
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        return $rows;
    }

    /**
     * 校验并归一化入库类型.
     */
    private function normalizeInboundType(int $inboundType): int
    {
        if (! in_array($inboundType, WarehouseEnum::INBOUND_TYPES, true)) {
            throw $this->exception('入库类型错误');
        }
        return $inboundType;
    }

    /**
     * 根据入库类型补齐默认来源类型.
     */
    private function normalizeSourceType(mixed $sourceType, int $inboundType): int
    {
        $sourceType = WarehouseEnum::sourceTypeValue($sourceType === null || $sourceType === '' ? null : (int) $sourceType);
        if ($sourceType !== WarehouseEnum::SOURCE_MANUAL) {
            return $sourceType;
        }
        return match ($inboundType) {
            WarehouseEnum::INBOUND_TYPE_PURCHASE => WarehouseEnum::SOURCE_PURCHASE_ORDER,
            WarehouseEnum::INBOUND_TYPE_RETURN   => WarehouseEnum::SOURCE_RETURN_ORDER,
            default                              => WarehouseEnum::SOURCE_MANUAL,
        };
    }

    /**
     * 根据来源单号或来源 ID 反查关联订单信息.
     */
    private function resolveSourceInfo(array $data): array
    {
        $sourceId = (int) ($data['source_id'] ?? 0);
        $sourceNo = trim((string) ($data['source_no'] ?? ''));
        if ($sourceId > 0 && $sourceNo === '') {
            $order = app(OrderService::class)->get($sourceId, ['id', 'contract_no']);
            $sourceNo = (string) ($order->contract_no ?? '');
        }
        if ($sourceId <= 0 && $sourceNo !== '') {
            $order = app(OrderService::class)->get(['contract_no' => $sourceNo], ['id', 'contract_no']);
            $sourceId = (int) ($order->id ?? 0);
        }
        return [
            'source_id' => $sourceId,
            'source_no' => $sourceNo,
        ];
    }

    /**
     * 归一化库存属性枚举值.
     */
    private function normalizeStockQuality(mixed $stockQuality): int
    {
        if (is_string($stockQuality)) {
            $stockQuality = is_numeric($stockQuality) ? (int) $stockQuality : $stockQuality;
        }
        return match ($stockQuality) {
            WarehouseEnum::STOCK_QUALITY_GOOD => WarehouseEnum::STOCK_QUALITY_GOOD,
            WarehouseEnum::STOCK_QUALITY_DEFECT => WarehouseEnum::STOCK_QUALITY_DEFECT,
            default => throw $this->exception('库存属性错误'),
        };
    }

    /**
     * 判断明细是否包含任意候选字段.
     */
    private function hasAnyDetailField(array $detail, array $fields): bool
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $detail)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 从多个候选字段中提取数值型明细值.
     */
    private function getDetailNumber(array $detail, array $fields): float
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $detail)) {
                return (float) $detail[$field];
            }
        }
        return 0;
    }

    /**
     * 从多个候选字段中提取整数型库存数量.
     */
    private function getDetailInteger(array $detail, array $fields): int
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $detail)) {
                return $this->normalizeIntegerQuantity($detail[$field]);
            }
        }
        return 0;
    }

    /**
     * 汇总入库明细金额.
     */
    private function sumAmount(array $details): string
    {
        $totalAmount = '0.00';
        foreach ($details as $detail) {
            $totalAmount = bcadd($totalAmount, (string) ($detail['amount'] ?? '0.00'), 2);
        }
        return $totalAmount;
    }

    /**
     * 作废单据并回滚库存.
     */
    public function cancel(int $id, int $uid): void
    {
        $this->transaction(function () use ($id, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('入库单不存在');
            }
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
            if ((int) $bill->status === WarehouseEnum::INBOUND_STATUS_CANCELLED) {
                return;
            }
            if ((int) $bill->status !== WarehouseEnum::INBOUND_STATUS_PENDING) {
                throw $this->exception('当前状态不允许作废');
            }
            $bill->status      = WarehouseEnum::INBOUND_STATUS_CANCELLED;
            $bill->operator    = $uid;
            $bill->operated_at = now();
            $bill->save();
        });
    }
}
