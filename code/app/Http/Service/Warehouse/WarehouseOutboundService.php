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
use App\Constants\System\ConfigEnum;
use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Dao\Customer\ProductAttrValueDao;
use App\Http\Dao\Warehouse\WarehouseOutboundDao;
use App\Http\Dao\Warehouse\WarehouseOutboundDetailDao;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\ProductAssistService;
use App\Http\Service\Customer\ProductAttrValueService;

/**
 * 出库单服务，负责出库单创建、拣货和作废.
 */
class WarehouseOutboundService extends WarehouseBillService
{
    /**
     * 外部单据类型与商品关联类型映射，用于出库单自动回填商品来源信息.
     */
    private const SOURCE_LINK_TYPES = [
        WarehouseEnum::SOURCE_SALES_ORDER => CustomEnum::CONTRACT,
        WarehouseEnum::SOURCE_ORDER       => CustomEnum::CONTRACT,
        WarehouseEnum::SOURCE_CONTRACT    => CustomEnum::CONTRACT,
    ];

    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseOutboundDao $dao, WarehouseOutboundDetailDao $detailDao)
    {
        $this->dao       = $dao;
        $this->detailDao = $detailDao;
    }

    /**
     * 创建仓储业务单据.
     */
    public function createBill(array $data, int $uid, bool $checkPermission = true): int
    {
        $attachIds = $this->normalizeAttachIds((array) ($data['attach_ids'] ?? $data['images'] ?? []));
        $outboundType = $this->normalizeOutboundType((int) ($data['outbound_type'] ?? WarehouseEnum::OUTBOUND_TYPE_SALES));
        $data['source_type'] = $this->normalizeSourceType($data['source_type'] ?? null, $outboundType);
        $sourceInfo = $this->resolveSourceInfo($data);
        $data['source_id'] = $sourceInfo['source_id'];
        $data['source_no'] = $sourceInfo['source_no'];
        app(WarehouseService::class)->assertEnabled((int) $data['warehouse_id']);
        if ($checkPermission) {
            app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['warehouse_id'], $uid, WarehouseEnum::ACTION_OUTBOUND);
        }
        return $this->transaction(function () use ($data, $uid, $attachIds, $outboundType) {
            $sourceDetails = (array) ($data['details'] ?? []);
            if (! $sourceDetails && $outboundType === WarehouseEnum::OUTBOUND_TYPE_SALES) {
                $sourceDetails = $this->sourceProducts($data);
            }
            $details = $this->normalizeOutboundDetails($sourceDetails);
            $this->assertNoCheckingConflict((int) $data['warehouse_id'], $details);
            $bill = $this->dao->create([
                'bill_no'       => $this->billNo('CK'),
                'warehouse_id'  => (int) $data['warehouse_id'],
                'outbound_type' => $outboundType,
                'status'        => WarehouseEnum::OUTBOUND_STATUS_PENDING,
                'source_type'   => $data['source_type'] ?? WarehouseEnum::SOURCE_MANUAL,
                'source_id'     => (int) ($data['source_id'] ?? 0),
                'source_no'     => $data['source_no'] ?? '',
                'images'        => $attachIds,
                'remark'        => $data['remark'] ?? '',
                'creator'       => $uid,
            ]);
            $this->syncAttachRelation($attachIds, (int) $bill->id, AttachEnum::RELATION_TYPE_WAREHOUSE_OUTBOUND);
            $this->occupyDetails((int) $data['warehouse_id'], (int) $bill->id, (string) $bill->bill_no, $details, $uid, '出库占用');
            $this->saveDetails((int) $bill->id, 'outbound_id', $details);
            return (int) $bill->id;
        });
    }

    /**
     * 编辑待出库单并重新占用库存.
     */
    public function updateBill(int $id, array $data, int $uid): void
    {
        $attachIds = $this->normalizeAttachIds((array) ($data['attach_ids'] ?? $data['images'] ?? []));
        $outboundType = $this->normalizeOutboundType((int) ($data['outbound_type'] ?? WarehouseEnum::OUTBOUND_TYPE_SALES));
        $data['source_type'] = $this->normalizeSourceType($data['source_type'] ?? null, $outboundType);
        $sourceInfo = $this->resolveSourceInfo($data);
        $data['source_id'] = $sourceInfo['source_id'];
        $data['source_no'] = $sourceInfo['source_no'];
        app(WarehouseService::class)->assertEnabled((int) $data['warehouse_id']);
        app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['warehouse_id'], $uid, WarehouseEnum::ACTION_OUTBOUND);

        $this->transaction(function () use ($id, $data, $uid, $attachIds, $outboundType) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('出库单不存在');
            }
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
            if ((int) $bill->status !== WarehouseEnum::OUTBOUND_STATUS_PENDING) {
                throw $this->exception('当前状态不允许编辑');
            }

            $sourceDetails = (array) ($data['details'] ?? []);
            if (! $sourceDetails && $outboundType === WarehouseEnum::OUTBOUND_TYPE_SALES) {
                $sourceDetails = $this->sourceProducts($data);
            }
            $details = $this->normalizeOutboundDetails($sourceDetails);
            $this->assertNoCheckingConflict((int) $data['warehouse_id'], $details);

            $this->releaseOccupiedDetails($bill, $uid, '编辑出库释放占用');
            $bill->warehouse_id  = (int) $data['warehouse_id'];
            $bill->outbound_type = $outboundType;
            $bill->source_type   = $data['source_type'] ?? WarehouseEnum::SOURCE_MANUAL;
            $bill->source_id     = (int) ($data['source_id'] ?? 0);
            $bill->source_no     = $data['source_no'] ?? '';
            $bill->images        = $attachIds;
            $bill->remark        = $data['remark'] ?? '';
            $bill->save();
            $this->syncAttachRelation($attachIds, (int) $bill->id, AttachEnum::RELATION_TYPE_WAREHOUSE_OUTBOUND);
            $this->occupyDetails((int) $data['warehouse_id'], (int) $bill->id, (string) $bill->bill_no, $details, $uid, '编辑出库占用');
            $this->saveDetails((int) $bill->id, 'outbound_id', $details);
        });
    }

    /**
     * 拣货出库并释放占用库存.
     */
    public function pick(int $id, int $uid): void
    {
        $this->transaction(function () use ($id, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('出库单不存在');
            }
            // 调拨来源出库单只推进调拨流程，避免普通出库逻辑重复扣减库存。
            if ((int) $bill->source_type === WarehouseEnum::SOURCE_TRANSFER && (int) $bill->source_id) {
                app(WarehouseTransferService::class)->transferOut((int) $bill->source_id, $uid);
                return;
            }
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
            if ((int) $bill->status === WarehouseEnum::OUTBOUND_STATUS_COMPLETED) {
                return;
            }
            if ((int) $bill->status !== WarehouseEnum::OUTBOUND_STATUS_PENDING) {
                throw $this->exception('当前状态不允许出库');
            }
            $this->assertOutboundRule($bill);
            $details = $this->detailDao->select(['outbound_id' => $id]);
            $this->assertNoCheckingConflict((int) $bill->warehouse_id, $details->toArray());
            foreach ($details as $detail) {
                $item = ['warehouse_id' => (int) $bill->warehouse_id, 'product_id' => (int) $detail->product_id, 'sku_id' => (int) $detail->sku_id];
                app(WarehouseStockService::class)->change($item, WarehouseEnum::STOCK_QUALITY_GOOD, -((int) $detail->quantity), WarehouseEnum::FLOW_OUTBOUND, ['source_type' => WarehouseEnum::SOURCE_OUTBOUND, 'source_id' => $id, 'source_no' => $bill->bill_no], $uid, (string) $bill->remark);
                app(WarehouseStockService::class)->change($item, WarehouseEnum::STOCK_QUALITY_OCCUPY, -((int) $detail->quantity), WarehouseEnum::FLOW_RELEASE_OCCUPY, ['source_type' => WarehouseEnum::SOURCE_OUTBOUND, 'source_id' => $id, 'source_no' => $bill->bill_no], $uid, '出库释放占用');
            }
            $bill->status      = WarehouseEnum::OUTBOUND_STATUS_COMPLETED;
            $bill->operator    = $uid;
            $bill->operated_at = now();
            $bill->save();
        });
    }

    /**
     * 校验销售来源出库规则.
     */
    private function assertOutboundRule($bill): void
    {
        if (! isset(self::SOURCE_LINK_TYPES[(int) $bill->source_type]) || ! (int) $bill->source_id) {
            return;
        }

        $order = app(OrderService::class)->get((int) $bill->source_id, ['id', 'received', 'surplus']);
        $order && $this->assertOrderCanOutbound($order);
    }

    public function assertOrderCanOutbound(mixed $order): void
    {
        $rule = (int) sys_config(ConfigEnum::ORDER_DELIVERY_RULE['key'], 2);
        if ($rule === 0 && bccomp((string) $order->received, '0', 2) <= 0) {
            throw $this->exception('订单未回款，不允许出库');
        }
        if ($rule === 1 && bccomp((string) $order->surplus, '0', 2) > 0) {
            throw $this->exception('订单未结清，不允许出库');
        }
    }

    /**
     * 作废单据并回滚占用或状态.
     */
    public function cancel(int $id, int $uid): void
    {
        $this->transaction(function () use ($id, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('出库单不存在');
            }
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
            if ((int) $bill->status === WarehouseEnum::OUTBOUND_STATUS_CANCELLED) {
                return;
            }
            if ((int) $bill->status !== WarehouseEnum::OUTBOUND_STATUS_PENDING) {
                throw $this->exception('当前状态不允许作废');
            }
            foreach ($this->detailDao->select(['outbound_id' => $id]) as $detail) {
                app(WarehouseStockService::class)->change(['warehouse_id' => (int) $bill->warehouse_id, 'product_id' => (int) $detail->product_id, 'sku_id' => (int) $detail->sku_id], WarehouseEnum::STOCK_QUALITY_OCCUPY, -((int) $detail->quantity), WarehouseEnum::FLOW_RELEASE_OCCUPY, ['source_type' => WarehouseEnum::SOURCE_OUTBOUND, 'source_id' => $id, 'source_no' => $bill->bill_no], $uid, '作废释放占用');
            }
            $bill->status      = WarehouseEnum::OUTBOUND_STATUS_CANCELLED;
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
        return WarehouseEnum::ACTION_OUTBOUND;
    }

    /**
     * 出库列表额外加载来源订单与创建人.
     */
    protected function billListWith(): array
    {
        return array_merge(parent::billListWith(), ['order', 'orderByNo', 'creatorInfo']);
    }

    /**
     * 出库详情额外加载来源订单与创建人.
     */
    protected function billDetailWith(): array
    {
        return array_merge(parent::billDetailWith(), ['order', 'orderByNo', 'creatorInfo', 'operatorInfo']);
    }

    /**
     * 出库列表补充订单付款状态.
     */
    protected function formatBillList($list): array
    {
        return $this->formatOrderBillList($list);
    }

    /**
     * 出库详情补充订单付款状态.
     */
    protected function formatBillDetail(array $info): array
    {
        return $this->formatOrderBillDetail($info);
    }

    /**
     * 获取来源订单商品，供销售出库自动回填.
     */
    public function sourceProducts(array $data): array
    {
        $sourceInfo = $this->resolveSourceInfo($data);
        $sourceId = $sourceInfo['source_id'];
        if (! $sourceId) {
            throw $this->exception('请选择关联订单或添加商品明细');
        }

        $sourceType = $this->normalizeSourceType($data['source_type'] ?? null, (int) ($data['outbound_type'] ?? WarehouseEnum::OUTBOUND_TYPE_SALES));
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
            $rows[] = [
                'product_id'    => (int) $sku['product_id'],
                'sku_id'        => (int) $sku['id'],
                'quantity'      => $quantity,
                'stock_quality' => WarehouseEnum::STOCK_QUALITY_GOOD,
                'remark'        => $product['remark'] ?? '',
                'product'       => $sku['product'] ?? null,
                'sku'           => $sku,
            ];
        }

        if (! $rows) {
            throw $this->exception('关联订单商品未匹配到可出库规格，请手动添加');
        }
        return $rows;
    }

    /**
     * 标准化出库商品明细.
     */
    private function normalizeOutboundDetails(array $details): array
    {
        if (! $details) {
            throw $this->exception('请添加商品明细');
        }
        $rows = [];
        foreach ($details as $detail) {
            $skuId = (int) ($detail['sku_id'] ?? 0);
            $quantity = $this->getDetailInteger($detail, ['quantity', 'outbound_quantity', 'stock_quantity', 'num']);
            if (! $skuId || $quantity <= 0) {
                throw $this->exception('商品明细数量错误');
            }
            $sku = app(ProductAttrValueDao::class)->get($skuId, with: ['product'])?->toArray();
            if (! $sku) {
                throw $this->exception('商品规格不存在');
            }
            $rows[] = [
                'product_id'    => (int) $sku['product_id'],
                'sku_id'        => $skuId,
                'stock_quality' => $this->normalizeStockQuality($detail['stock_quality'] ?? WarehouseEnum::STOCK_QUALITY_GOOD),
                'quantity'      => $quantity,
                'remark'        => $detail['remark'] ?? '',
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        return $rows;
    }

    /**
     * 校验并归一化出库类型.
     */
    private function normalizeOutboundType(int $outboundType): int
    {
        if (! in_array($outboundType, WarehouseEnum::OUTBOUND_TYPES, true)) {
            throw $this->exception('出库类型错误');
        }
        return $outboundType;
    }

    /**
     * 根据出库类型补齐默认来源类型.
     */
    private function normalizeSourceType(mixed $sourceType, int $outboundType): int
    {
        $sourceType = WarehouseEnum::sourceTypeValue($sourceType === null || $sourceType === '' ? null : (int) $sourceType);
        if ($sourceType !== WarehouseEnum::SOURCE_MANUAL) {
            return $sourceType;
        }
        return $outboundType === WarehouseEnum::OUTBOUND_TYPE_SALES ? WarehouseEnum::SOURCE_SALES_ORDER : WarehouseEnum::SOURCE_MANUAL;
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
            WarehouseEnum::STOCK_QUALITY_DEFECT => throw $this->exception('出库暂只支持正品库存'),
            default => throw $this->exception('库存属性错误'),
        };
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
     * 占用出库商品明细库存.
     */
    private function occupyDetails(int $warehouseId, int $billId, string $billNo, array $details, int $uid, string $remark): void
    {
        foreach ($details as $detail) {
            app(WarehouseStockService::class)->occupy([
                'warehouse_id' => $warehouseId,
                'product_id'   => (int) $detail['product_id'],
                'sku_id'       => (int) $detail['sku_id'],
            ], (int) $detail['quantity'], ['source_type' => WarehouseEnum::SOURCE_OUTBOUND, 'source_id' => $billId, 'source_no' => $billNo], $uid, $remark);
        }
    }

    /**
     * 释放出库单已占用库存.
     */
    private function releaseOccupiedDetails($bill, int $uid, string $remark): void
    {
        foreach ($this->detailDao->select(['outbound_id' => (int) $bill->id]) as $detail) {
            app(WarehouseStockService::class)->change(['warehouse_id' => (int) $bill->warehouse_id, 'product_id' => (int) $detail->product_id, 'sku_id' => (int) $detail->sku_id], WarehouseEnum::STOCK_QUALITY_OCCUPY, -((int) $detail->quantity), WarehouseEnum::FLOW_RELEASE_OCCUPY, ['source_type' => WarehouseEnum::SOURCE_OUTBOUND, 'source_id' => (int) $bill->id, 'source_no' => $bill->bill_no], $uid, $remark);
        }
    }
}
