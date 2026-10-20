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
use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Dao\Customer\ProductAttrValueDao;
use App\Http\Dao\Warehouse\WarehouseInboundDao;
use App\Http\Dao\Warehouse\WarehouseInboundDetailDao;
use App\Http\Dao\Warehouse\WarehouseOutboundDao;
use App\Http\Dao\Warehouse\WarehouseOutboundDetailDao;
use App\Http\Dao\Warehouse\WarehouseStockFlowDao;
use App\Http\Dao\Warehouse\WarehouseTransferDao;
use App\Http\Dao\Warehouse\WarehouseTransferDetailDao;

/**
 * 仓库调拨服务，负责调拨单创建、出库、入库和作废.
 */
class WarehouseTransferService extends WarehouseBillService
{
    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseTransferDao $dao, WarehouseTransferDetailDao $detailDao)
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
        if ((int) $data['out_warehouse_id'] === (int) $data['in_warehouse_id']) {
            throw $this->exception('调出仓库和调入仓库不能相同');
        }
        app(WarehouseService::class)->assertEnabled((int) $data['out_warehouse_id']);
        app(WarehouseService::class)->assertEnabled((int) $data['in_warehouse_id']);
        app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['out_warehouse_id'], $uid, WarehouseEnum::ACTION_TRANSFER);
        app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['in_warehouse_id'], $uid, WarehouseEnum::ACTION_TRANSFER);
        return $this->transaction(function () use ($data, $uid, $attachIds) {
            $details = $this->normalizeTransferDetails((array) $data['details']);
            $this->assertNoCheckingConflict((int) $data['out_warehouse_id'], $details);
            $this->assertNoCheckingConflict((int) $data['in_warehouse_id'], $details);
            $bill = $this->dao->create([
                'bill_no'          => $this->billNo('DB'),
                'out_warehouse_id' => (int) $data['out_warehouse_id'],
                'in_warehouse_id'  => (int) $data['in_warehouse_id'],
                'transfer_type'    => (int) ($data['transfer_type'] ?? WarehouseEnum::TRANSFER_LOGISTIC),
                'status'           => WarehouseEnum::TRANSFER_PENDING_OUT,
                'images'           => $attachIds,
                'remark'           => $data['remark'] ?? '',
                'creator'          => $uid,
            ]);
            $this->syncAttachRelation($attachIds, (int) $bill->id, AttachEnum::RELATION_TYPE_WAREHOUSE_TRANSFER);
            if ((int) $bill->transfer_type === WarehouseEnum::TRANSFER_LOGISTIC) {
                $this->reserveTransferDetails((int) $data['out_warehouse_id'], (int) $bill->id, (string) $bill->bill_no, $details, $uid, '调拨占用');
            }
            $this->saveDetails((int) $bill->id, 'transfer_id', $details);
            $this->syncTransferOutboundBill($bill, $details, WarehouseEnum::OUTBOUND_STATUS_PENDING, $uid);
            if ((int) $bill->transfer_type === WarehouseEnum::TRANSFER_DIRECT) {
                $this->transferOut((int) $bill->id, $uid);
                $this->transferIn((int) $bill->id, $uid);
            }
            return (int) $bill->id;
        });
    }

    /**
     * 获取调拨单列表.
     */
    public function getTransferList(array $where, int $uid): array
    {
        $hasWarehouseIds = isset($where['warehouse_ids']) && $where['warehouse_ids'] !== '';
        $where = app(WarehousePermissionService::class)->applyWarehouseScope($where, $uid, false, WarehouseEnum::ACTION_TRANSFER);
        if (($where['warehouse_type'] ?? '') !== '' && $hasWarehouseIds && isset($where['warehouse_ids'])) {
            $where['warehouse_type'] = [
                'type' => $where['warehouse_type'],
                'ids'  => (array) $where['warehouse_ids'],
            ];
            unset($where['warehouse_ids']);
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, ['*'], $page, $limit, ['id' => 'desc'], [
            'outWarehouse', 'inWarehouse', 'details.product', 'details.sku', 'files', 'creatorInfo',
        ]);
        return $this->listData($list, $this->dao->count($where));
    }

    /**
     * 获取单据或业务详情.
     */
    public function detail(int $id, int $uid = 0): array
    {
        $info = $this->dao->get($id, with: ['outWarehouse', 'inWarehouse', 'details.product', 'details.sku', 'files', 'creatorInfo'])?->toArray();
        if (! $info) {
            throw $this->exception('调拨单不存在');
        }
        if ($uid) {
            $this->assertTransferViewPermission((int) $info['out_warehouse_id'], (int) $info['in_warehouse_id'], $uid);
        }
        return $info;
    }

    /**
     * 执行调拨出库并转入在途库存.
     */
    public function transferOut(int $id, int $uid): void
    {
        $this->transaction(function () use ($id, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('调拨单不存在');
            }
            $this->assertTransferPermission((int) $bill->out_warehouse_id, (int) $bill->in_warehouse_id, $uid);
            if ((int) $bill->status === WarehouseEnum::TRANSFER_PENDING_IN || (int) $bill->status === WarehouseEnum::TRANSFER_DONE) {
                return;
            }
            if ((int) $bill->status !== WarehouseEnum::TRANSFER_PENDING_OUT) {
                throw $this->exception('当前状态不允许调拨出库');
            }
            $details = $this->detailDao->select(['transfer_id' => $id]);
            $this->assertNoCheckingConflict((int) $bill->out_warehouse_id, $details->toArray());
            $this->assertNoCheckingConflict((int) $bill->in_warehouse_id, $details->toArray());
            $isLogistic = (int) $bill->transfer_type === WarehouseEnum::TRANSFER_LOGISTIC;
            $stockService = app(WarehouseStockService::class);
            foreach ($details as $detail) {
                $stockQuality = $this->normalizeStockQuality($detail->stock_quality ?: WarehouseEnum::STOCK_QUALITY_GOOD);
                $outItem = ['warehouse_id' => (int) $bill->out_warehouse_id, 'target_warehouse_id' => (int) $bill->in_warehouse_id, 'product_id' => (int) $detail->product_id, 'sku_id' => (int) $detail->sku_id];
                if (! $isLogistic) {
                    $this->assertTransferOutStockAvailable($stockService, (int) $bill->out_warehouse_id, (int) $detail->sku_id, $stockQuality, (int) $detail->quantity);
                }
                $stockService->change($outItem, $stockQuality, -((int) $detail->quantity), WarehouseEnum::FLOW_TRANSFER_OUT, ['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => $id, 'source_no' => $bill->bill_no], $uid, (string) $bill->remark);
                if ($isLogistic) {
                    $this->releaseTransferOccupyStock($stockService, $outItem, (int) $detail->quantity, $id, (string) $bill->bill_no, $uid, '调拨出库释放占用');
                    $stockService->change(['warehouse_id' => (int) $bill->in_warehouse_id, 'target_warehouse_id' => (int) $bill->out_warehouse_id, 'product_id' => (int) $detail->product_id, 'sku_id' => (int) $detail->sku_id], WarehouseEnum::STOCK_QUALITY_TRANSIT, (int) $detail->quantity, WarehouseEnum::FLOW_TRANSIT, ['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => $id, 'source_no' => $bill->bill_no], $uid, '调拨在途');
                }
            }
            $bill->status   = WarehouseEnum::TRANSFER_PENDING_IN;
            $bill->operator = $uid;
            $bill->out_at   = now();
            $this->syncTransferOutboundBill($bill, $details->all(), WarehouseEnum::OUTBOUND_STATUS_COMPLETED, $uid);
            $this->syncTransferInboundBill($bill, $details->all(), WarehouseEnum::INBOUND_STATUS_PENDING, $uid);
            $bill->save();
        });
    }

    /**
     * 执行调拨入库并释放在途库存.
     */
    public function transferIn(int $id, int $uid): void
    {
        $this->transaction(function () use ($id, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('调拨单不存在');
            }
            $this->assertTransferPermission((int) $bill->out_warehouse_id, (int) $bill->in_warehouse_id, $uid);
            if ((int) $bill->status === WarehouseEnum::TRANSFER_DONE) {
                return;
            }
            if ((int) $bill->status !== WarehouseEnum::TRANSFER_PENDING_IN) {
                throw $this->exception('当前状态不允许调拨入库');
            }
            $details = $this->detailDao->select(['transfer_id' => $id]);
            $this->assertNoCheckingConflict((int) $bill->in_warehouse_id, $details->toArray());
            $isLogistic = (int) $bill->transfer_type === WarehouseEnum::TRANSFER_LOGISTIC;
            foreach ($details as $detail) {
                $stockQuality = $this->normalizeStockQuality($detail->stock_quality ?: WarehouseEnum::STOCK_QUALITY_GOOD);
                $item = ['warehouse_id' => (int) $bill->in_warehouse_id, 'target_warehouse_id' => (int) $bill->out_warehouse_id, 'product_id' => (int) $detail->product_id, 'sku_id' => (int) $detail->sku_id];
                if ($isLogistic) {
                    app(WarehouseStockService::class)->change($item, WarehouseEnum::STOCK_QUALITY_TRANSIT, -((int) $detail->quantity), WarehouseEnum::FLOW_RELEASE_TRANSIT, ['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => $id, 'source_no' => $bill->bill_no], $uid, '调拨入库释放在途');
                }
                app(WarehouseStockService::class)->change($item, $stockQuality, (int) $detail->quantity, WarehouseEnum::FLOW_TRANSFER_IN, ['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => $id, 'source_no' => $bill->bill_no], $uid, (string) $bill->remark);
            }
            $bill->status = WarehouseEnum::TRANSFER_DONE;
            $bill->in_at  = now();
            $this->syncTransferInboundBill($bill, $details->all(), WarehouseEnum::INBOUND_STATUS_COMPLETED, $uid);
            $bill->save();
        });
    }

    /**
     * 作废单据并回滚占用或状态.
     */
    public function cancel(int $id, int $uid): void
    {
        $this->transaction(function () use ($id, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('调拨单不存在');
            }
            $this->assertTransferPermission((int) $bill->out_warehouse_id, (int) $bill->in_warehouse_id, $uid);
            if ((int) $bill->status === WarehouseEnum::TRANSFER_CANCELLED) {
                return;
            }
            if ((int) $bill->status !== WarehouseEnum::TRANSFER_PENDING_OUT) {
                throw $this->exception('当前状态不允许作废');
            }
            if ((int) $bill->transfer_type === WarehouseEnum::TRANSFER_LOGISTIC) {
                $this->releasePendingOutOccupyDetails($bill, $uid, '调拨作废释放占用');
            }
            $bill->status = WarehouseEnum::TRANSFER_CANCELLED;
            $bill->save();
        });
    }

    /**
     * 重新编辑并提交作废的调拨单.
     */
    public function resubmit(int $id, array $data, int $uid): void
    {
        $this->transaction(function () use ($id, $data, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('调拨单不存在');
            }
            $this->assertTransferPermission((int) $bill->out_warehouse_id, (int) $bill->in_warehouse_id, $uid);
            if ((int) $bill->status !== WarehouseEnum::TRANSFER_CANCELLED) {
                throw $this->exception('当前状态不允许重新提交');
            }
            $attachIds = $this->normalizeAttachIds((array) ($data['attach_ids'] ?? $data['images'] ?? []));
            if ((int) $data['out_warehouse_id'] === (int) $data['in_warehouse_id']) {
                throw $this->exception('调出仓库和调入仓库不能相同');
            }
            app(WarehouseService::class)->assertEnabled((int) $data['out_warehouse_id']);
            app(WarehouseService::class)->assertEnabled((int) $data['in_warehouse_id']);
            app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['out_warehouse_id'], $uid, WarehouseEnum::ACTION_TRANSFER);
            app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['in_warehouse_id'], $uid, WarehouseEnum::ACTION_TRANSFER);
            $details = $this->normalizeTransferDetails((array) $data['details']);
            $this->assertNoCheckingConflict((int) $data['out_warehouse_id'], $details);
            $this->assertNoCheckingConflict((int) $data['in_warehouse_id'], $details);

            $bill->out_warehouse_id = (int) $data['out_warehouse_id'];
            $bill->in_warehouse_id  = (int) $data['in_warehouse_id'];
            $bill->transfer_type    = (int) ($data['transfer_type'] ?? WarehouseEnum::TRANSFER_LOGISTIC);
            $bill->status           = WarehouseEnum::TRANSFER_PENDING_OUT;
            $bill->images           = $attachIds;
            $bill->remark           = $data['remark'] ?? '';
            $bill->save();
            $this->syncAttachRelation($attachIds, (int) $bill->id, AttachEnum::RELATION_TYPE_WAREHOUSE_TRANSFER);
            $this->saveDetails((int) $bill->id, 'transfer_id', $details);
            $this->syncTransferOutboundBill($bill, $details, WarehouseEnum::OUTBOUND_STATUS_PENDING, $uid, true);
            if ((int) $bill->transfer_type === WarehouseEnum::TRANSFER_LOGISTIC) {
                $this->reserveTransferDetails((int) $bill->out_warehouse_id, $id, (string) $bill->bill_no, $details, $uid, '调拨重新提交占用');
                return;
            }
            $this->transferOut((int) $bill->id, $uid);
            $this->transferIn((int) $bill->id, $uid);
        });
    }

    /**
     * 判断仓库是否存在调拨历史单据.
     */
    public function hasWarehouseBill(int $warehouseId): bool
    {
        return $this->dao->getModel(false)->where('out_warehouse_id', $warehouseId)->orWhere('in_warehouse_id', $warehouseId)->exists();
    }

    /**
     * 校验调出和调入仓库操作权限.
     */
    private function assertTransferPermission(int $outWarehouseId, int $inWarehouseId, int $uid): void
    {
        $permission = app(WarehousePermissionService::class);
        $permission->assertCanUseWarehouse($outWarehouseId, $uid, WarehouseEnum::ACTION_TRANSFER);
        $permission->assertCanUseWarehouse($inWarehouseId, $uid, WarehouseEnum::ACTION_TRANSFER);
    }

    /**
     * 校验调拨单查看权限.
     */
    private function assertTransferViewPermission(int $outWarehouseId, int $inWarehouseId, int $uid): void
    {
        $ids = app(WarehousePermissionService::class)->accessibleWarehouseIds($uid, false, WarehouseEnum::ACTION_TRANSFER);
        if (! $ids) {
            return;
        }
        if (! in_array($outWarehouseId, $ids, true) && ! in_array($inWarehouseId, $ids, true)) {
            throw $this->exception('无权查看该调拨单');
        }
    }

    /**
     * 当前单据对应的库管动作权限.
     */
    protected function permissionAction(): string
    {
        return WarehouseEnum::ACTION_TRANSFER;
    }

    /**
     * 标准化调拨商品明细.
     */
    private function normalizeTransferDetails(array $details): array
    {
        if (! $details) {
            throw $this->exception('请添加商品明细');
        }
        $rows = [];
        foreach ($details as $detail) {
            $skuId = (int) ($detail['sku_id'] ?? 0);
            $quantity = $this->getDetailInteger($detail, ['quantity', 'transfer_quantity', 'stock_quantity', 'num']);
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
     * 为物流调拨待出库明细计入占用库存.
     */
    private function reserveTransferDetails(int $warehouseId, int $billId, string $billNo, array $details, int $uid, string $remark): void
    {
        $stockService = app(WarehouseStockService::class);
        foreach ($details as $detail) {
            $stockQuality = $this->normalizeStockQuality($detail['stock_quality'] ?? WarehouseEnum::STOCK_QUALITY_GOOD);
            $item = [
                'warehouse_id' => $warehouseId,
                'product_id'   => (int) $detail['product_id'],
                'sku_id'       => (int) $detail['sku_id'],
            ];
            $stock = $stockService->stockRow($warehouseId, (int) $detail['sku_id'], true);
            if ($stockQuality === WarehouseEnum::STOCK_QUALITY_GOOD) {
                $this->assertGoodStockAvailable($stock, (int) $detail['quantity']);
                $stockService->change($item, WarehouseEnum::STOCK_QUALITY_OCCUPY, (int) $detail['quantity'], WarehouseEnum::FLOW_OCCUPY, ['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => $billId, 'source_no' => $billNo], $uid, $remark);
                continue;
            }
            $this->assertDefectStockAvailable($stock, (int) $detail['quantity']);
            $stockService->change($item, WarehouseEnum::STOCK_QUALITY_OCCUPY, (int) $detail['quantity'], WarehouseEnum::FLOW_OCCUPY, ['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => $billId, 'source_no' => $billNo], $uid, $remark);
        }
    }

    /**
     * 校验直接调拨出库库存可用量.
     */
    private function assertTransferOutStockAvailable(WarehouseStockService $stockService, int $warehouseId, int $skuId, int $stockQuality, int $quantity): void
    {
        $stock = $stockService->stockRow($warehouseId, $skuId, true);
        if ($stockQuality === WarehouseEnum::STOCK_QUALITY_GOOD) {
            $this->assertGoodStockAvailable($stock, $quantity);
            return;
        }
        $this->assertDefectStockAvailable($stock, $quantity);
    }

    /**
     * 校验良品可用库存.
     * @param mixed $stock
     */
    private function assertGoodStockAvailable($stock, int $quantity): void
    {
        if (((int) $stock->good_stock - (int) $stock->occupy_stock) < $quantity) {
            throw $this->exception('可用库存不足');
        }
    }

    /**
     * 校验次品库存.
     * @param mixed $stock
     */
    private function assertDefectStockAvailable($stock, int $quantity): void
    {
        if ((int) $stock->defect_stock < $quantity) {
            throw $this->exception('次品库存不足');
        }
    }

    /**
     * 释放物流调拨单待出库占用库存.
     */
    private function releasePendingOutOccupyDetails($bill, int $uid, string $remark): void
    {
        $stockService = app(WarehouseStockService::class);
        foreach ($this->detailDao->select(['transfer_id' => (int) $bill->id]) as $detail) {
            $this->releaseTransferOccupyStock($stockService, ['warehouse_id' => (int) $bill->out_warehouse_id, 'product_id' => (int) $detail->product_id, 'sku_id' => (int) $detail->sku_id], (int) $detail->quantity, (int) $bill->id, (string) $bill->bill_no, $uid, $remark);
        }
    }

    /**
     * 释放当前调拨单实际占用过的库存，兼容历史待出库单未生成占用流水的情况.
     */
    private function releaseTransferOccupyStock(WarehouseStockService $stockService, array $item, int $quantity, int $transferId, string $billNo, int $uid, string $remark): void
    {
        $occupied = (int) app(WarehouseStockFlowDao::class)->getModel(false)
            ->where('warehouse_id', (int) $item['warehouse_id'])
            ->where('sku_id', (int) $item['sku_id'])
            ->where('stock_quality', WarehouseEnum::STOCK_QUALITY_OCCUPY)
            ->where('source_type', WarehouseEnum::sourceTypeValue(WarehouseEnum::SOURCE_TRANSFER))
            ->where('source_id', $transferId)
            ->sum('change_num');
        if ($occupied <= 0) {
            return;
        }

        $stock = $stockService->stockRow((int) $item['warehouse_id'], (int) $item['sku_id'], true);
        $releaseNum = min($quantity, $occupied, (int) $stock->occupy_stock);
        if ($releaseNum <= 0) {
            return;
        }

        $stockService->change($item, WarehouseEnum::STOCK_QUALITY_OCCUPY, -$releaseNum, WarehouseEnum::FLOW_RELEASE_OCCUPY, ['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => $transferId, 'source_no' => $billNo], $uid, $remark);
    }

    /**
     * 同步调拨关联出库单，库存变更仍由调拨出库流程统一处理.
     */
    private function syncTransferOutboundBill($bill, array $details, int $status, int $uid, bool $syncDetails = false): void
    {
        $dao = app(WarehouseOutboundDao::class);
        $outbound = $dao->get(['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => (int) $bill->id]);
        if ($outbound) {
            $outbound->warehouse_id  = (int) $bill->out_warehouse_id;
            $outbound->outbound_type = WarehouseEnum::OUTBOUND_TYPE_OTHER;
            $outbound->status        = $status;
            $outbound->source_no     = (string) $bill->bill_no;
            $outbound->remark        = (string) $bill->remark;
            $outbound->operator      = $status === WarehouseEnum::OUTBOUND_STATUS_COMPLETED ? $uid : (int) ($outbound->operator ?? 0);
            $outbound->operated_at   = $status === WarehouseEnum::OUTBOUND_STATUS_COMPLETED ? now() : ($outbound->operated_at ?? null);
            $outbound->save();
            if ($syncDetails) {
                app(WarehouseOutboundDetailDao::class)->delete(['outbound_id' => (int) $outbound->id]);
                app(WarehouseOutboundDetailDao::class)->getModel(false)->insert($this->transferOutboundDetails((int) $outbound->id, $details));
            }
            return;
        }

        $outbound = $dao->create([
            'bill_no'       => $this->billNo('CK'),
            'warehouse_id'  => (int) $bill->out_warehouse_id,
            'outbound_type' => WarehouseEnum::OUTBOUND_TYPE_OTHER,
            'status'        => $status,
            'source_type'   => WarehouseEnum::SOURCE_TRANSFER,
            'source_id'     => (int) $bill->id,
            'source_no'     => (string) $bill->bill_no,
            'images'        => (array) ($bill->images ?? []),
            'remark'        => (string) $bill->remark,
            'creator'       => $uid,
            'operator'      => $status === WarehouseEnum::OUTBOUND_STATUS_COMPLETED ? $uid : 0,
            'operated_at'   => $status === WarehouseEnum::OUTBOUND_STATUS_COMPLETED ? now() : null,
        ]);
        app(WarehouseOutboundDetailDao::class)->getModel(false)->insert($this->transferOutboundDetails((int) $outbound->id, $details));
    }

    /**
     * 同步调拨关联入库单，库存变更仍由调拨入库流程统一处理.
     */
    private function syncTransferInboundBill($bill, array $details, int $status, int $uid): void
    {
        $dao = app(WarehouseInboundDao::class);
        $inbound = $dao->get(['source_type' => WarehouseEnum::SOURCE_TRANSFER, 'source_id' => (int) $bill->id]);
        if ($inbound) {
            $inbound->warehouse_id  = (int) $bill->in_warehouse_id;
            $inbound->inbound_type  = WarehouseEnum::INBOUND_TYPE_OTHER;
            $inbound->status        = $status;
            $inbound->source_no     = (string) $bill->bill_no;
            $inbound->remark        = (string) $bill->remark;
            $inbound->operator      = $status === WarehouseEnum::INBOUND_STATUS_COMPLETED ? $uid : (int) ($inbound->operator ?? 0);
            $inbound->operated_at   = $status === WarehouseEnum::INBOUND_STATUS_COMPLETED ? now() : ($inbound->operated_at ?? null);
            $inbound->save();
            return;
        }

        $inbound = $dao->create([
            'bill_no'      => $this->billNo('RK'),
            'warehouse_id' => (int) $bill->in_warehouse_id,
            'inbound_type' => WarehouseEnum::INBOUND_TYPE_OTHER,
            'status'       => $status,
            'source_type'  => WarehouseEnum::SOURCE_TRANSFER,
            'source_id'    => (int) $bill->id,
            'source_no'    => (string) $bill->bill_no,
            'total_amount' => '0.00',
            'images'       => (array) ($bill->images ?? []),
            'remark'       => (string) $bill->remark,
            'creator'      => $uid,
            'operator'     => $status === WarehouseEnum::INBOUND_STATUS_COMPLETED ? $uid : 0,
            'operated_at'  => $status === WarehouseEnum::INBOUND_STATUS_COMPLETED ? now() : null,
        ]);
        app(WarehouseInboundDetailDao::class)->getModel(false)->insert($this->transferInboundDetails((int) $inbound->id, $details));
    }

    /**
     * 将调拨明细转换为出库单明细.
     */
    private function transferOutboundDetails(int $outboundId, array $details): array
    {
        return array_map(fn ($detail): array => [
            'outbound_id'   => $outboundId,
            'product_id'    => (int) $this->detailValue($detail, 'product_id'),
            'sku_id'        => (int) $this->detailValue($detail, 'sku_id'),
            'stock_quality' => $this->normalizeStockQuality($this->detailValue($detail, 'stock_quality') ?: WarehouseEnum::STOCK_QUALITY_GOOD),
            'quantity'      => (int) $this->detailValue($detail, 'quantity'),
            'remark'        => (string) ($this->detailValue($detail, 'remark') ?? ''),
            'created_at'    => now(),
            'updated_at'    => now(),
        ], $details);
    }

    /**
     * 将调拨明细转换为入库单明细.
     */
    private function transferInboundDetails(int $inboundId, array $details): array
    {
        return array_map(fn ($detail): array => [
            'inbound_id'    => $inboundId,
            'product_id'    => (int) $this->detailValue($detail, 'product_id'),
            'sku_id'        => (int) $this->detailValue($detail, 'sku_id'),
            'stock_quality' => $this->normalizeStockQuality($this->detailValue($detail, 'stock_quality') ?: WarehouseEnum::STOCK_QUALITY_GOOD),
            'unit_price'    => 0,
            'quantity'      => (int) $this->detailValue($detail, 'quantity'),
            'gift_quantity' => 0,
            'amount'        => '0.00',
            'remark'        => (string) ($this->detailValue($detail, 'remark') ?? ''),
            'created_at'    => now(),
            'updated_at'    => now(),
        ], $details);
    }

    /**
     * 兼容数组明细和模型对象明细取值.
     */
    private function detailValue($detail, string $key): mixed
    {
        return is_array($detail) ? ($detail[$key] ?? null) : ($detail->{$key} ?? null);
    }
}
