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
use App\Http\Dao\Warehouse\WarehouseCheckDao;
use App\Http\Dao\Warehouse\WarehouseCheckDetailDao;
use App\Http\Dao\Warehouse\WarehouseInboundDao;
use App\Http\Dao\Warehouse\WarehouseInboundDetailDao;
use App\Http\Dao\Warehouse\WarehouseOutboundDao;
use App\Http\Dao\Warehouse\WarehouseOutboundDetailDao;

/**
 * 库存盘点服务，负责盘点创建、复盘保存和差异入出库处理.
 */
class WarehouseCheckService extends WarehouseBillService
{
    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseCheckDao $dao, WarehouseCheckDetailDao $detailDao)
    {
        $this->dao       = $dao;
        $this->detailDao = $detailDao;
    }

    /**
     * 创建仓储业务单据.
     */
    public function createBill(array $data, int $uid): int
    {
        return $this->saveBill($data, $uid);
    }

    /**
     * 保存盘点单，草稿保持盘点中，非草稿立即处理库存并完成盘点.
     */
    public function saveBill(array $data, int $uid, int $id = 0): int
    {
        $attachIds   = $this->normalizeAttachIds((array) ($data['attach_ids'] ?? $data['images'] ?? []));
        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        $isDraft     = filter_var($data['is_draft'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $isDraft     = $isDraft ?? true;

        app(WarehouseService::class)->assertEnabled($warehouseId);
        app(WarehousePermissionService::class)->assertCanUseWarehouse($warehouseId, $uid, WarehouseEnum::ACTION_CHECK);

        return $this->transaction(function () use ($data, $uid, $id, $attachIds, $warehouseId, $isDraft) {
            if ($id) {
                $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
                if (! $bill) {
                    throw $this->exception('盘点单不存在');
                }
                if ((int) $bill->status !== WarehouseEnum::CHECK_STATUS_DOING) {
                    throw $this->exception('盘点单状态异常');
                }
                $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
                $bill->warehouse_id = $warehouseId;
                $bill->images       = $attachIds;
                $bill->remark       = $data['remark'] ?? '';
                $bill->save();
            } else {
                $bill = $this->dao->create([
                    'bill_no'      => $this->billNo('PD'),
                    'warehouse_id' => $warehouseId,
                    'status'       => WarehouseEnum::CHECK_STATUS_DOING,
                    'images'       => $attachIds,
                    'remark'       => $data['remark'] ?? '',
                    'creator'      => $uid,
                ]);
            }

            $billId = (int) $bill->id;
            $this->syncAttachRelation($attachIds, $billId, AttachEnum::RELATION_TYPE_WAREHOUSE_CHECK);
            $this->saveCheckDetails($billId, $warehouseId, (array) $data['details'], $billId);

            if (! $isDraft) {
                $this->completeCheck($bill, $uid);
            }

            return $billId;
        });
    }

    /**
     * 保存盘点明细.
     */
    public function saveCheck(int $id, array $details, int $uid = 0): void
    {
        $bill = $this->dao->get($id);
        if (! $bill || (int) $bill->status !== WarehouseEnum::CHECK_STATUS_DOING) {
            throw $this->exception('盘点单状态异常');
        }
        if ($uid) {
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
        }
        $this->saveCheckDetails($id, (int) $bill->warehouse_id, $details, $id);
    }

    /**
     * 更新盘点备注.
     */
    public function remark(int $id, string $remark, int $uid = 0): void
    {
        if ($uid) {
            $bill = $this->dao->get($id);
            if (! $bill) {
                throw $this->exception('盘点单不存在');
            }
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
        }
        $this->dao->update($id, ['remark' => $remark]);
    }

    /**
     * 作废盘点中单据.
     */
    public function cancel(int $id, int $uid): void
    {
        $this->transaction(function () use ($id, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('盘点单不存在');
            }
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
            if ((int) $bill->status === WarehouseEnum::CHECK_STATUS_CANCEL) {
                return;
            }
            if ((int) $bill->status !== WarehouseEnum::CHECK_STATUS_DOING) {
                throw $this->exception('当前状态不允许作废');
            }
            $bill->status      = WarehouseEnum::CHECK_STATUS_CANCEL;
            $bill->operator    = $uid;
            $bill->operated_at = now();
            $bill->save();
        });
    }

    /**
     * 盘点列表额外加载创建人.
     */
    protected function billListWith(): array
    {
        return array_merge(parent::billListWith(), ['creatorInfo']);
    }

    /**
     * 盘点详情额外加载创建人.
     */
    protected function billDetailWith(): array
    {
        return array_merge(parent::billDetailWith(), ['creatorInfo']);
    }

    /**
     * 处理盘盈盘亏库存并完成盘点单.
     */
    private function completeCheck($bill, int $uid): void
    {
        if ((int) $bill->status === WarehouseEnum::CHECK_STATUS_DONE) {
            return;
        }
        if ((int) $bill->status !== WarehouseEnum::CHECK_STATUS_DOING) {
            throw $this->exception('当前状态不允许完成盘点');
        }

        $profitRows = [];
        $lossRows   = [];
        $billId     = (int) $bill->id;
        foreach ($this->detailDao->select(['check_id' => $billId]) as $detail) {
            $item = ['warehouse_id' => (int) $bill->warehouse_id, 'product_id' => (int) $detail->product_id, 'sku_id' => (int) $detail->sku_id];
            $goodDiff = (int) $detail->real_good_stock - (int) $detail->book_good_stock;
            if ($goodDiff !== 0) {
                app(WarehouseStockService::class)->change($item, WarehouseEnum::STOCK_QUALITY_GOOD, $goodDiff, $goodDiff > 0 ? WarehouseEnum::FLOW_CHECK_PROFIT : WarehouseEnum::FLOW_CHECK_LOSS, ['source_type' => WarehouseEnum::SOURCE_CHECK, 'source_id' => $billId, 'source_no' => $bill->bill_no], $uid, (string) $bill->remark);
                if ($goodDiff > 0) {
                    $this->appendCheckBillRow($profitRows, $detail, abs($goodDiff), '良品盘点差异', WarehouseEnum::STOCK_QUALITY_GOOD);
                } else {
                    $this->appendCheckBillRow($lossRows, $detail, abs($goodDiff), '良品盘点差异');
                }
            }
            $defectDiff = (int) $detail->real_defect_stock - (int) $detail->book_defect_stock;
            if ($defectDiff !== 0) {
                app(WarehouseStockService::class)->change($item, WarehouseEnum::STOCK_QUALITY_DEFECT, $defectDiff, $defectDiff > 0 ? WarehouseEnum::FLOW_CHECK_PROFIT : WarehouseEnum::FLOW_CHECK_LOSS, ['source_type' => WarehouseEnum::SOURCE_CHECK, 'source_id' => $billId, 'source_no' => $bill->bill_no], $uid, (string) $bill->remark);
                if ($defectDiff > 0) {
                    $this->appendCheckBillRow($profitRows, $detail, abs($defectDiff), '残次品盘点差异', WarehouseEnum::STOCK_QUALITY_DEFECT);
                } else {
                    $this->appendCheckBillRow($lossRows, $detail, abs($defectDiff), '残次品盘点差异');
                }
            }
        }
        $this->createCheckProfitInbound($bill, $profitRows, $uid);
        $this->createCheckLossOutbound($bill, $lossRows, $uid);
        $bill->status      = WarehouseEnum::CHECK_STATUS_DONE;
        $bill->operator    = $uid;
        $bill->operated_at = now();
        $bill->finished_at = now();
        $bill->save();
    }

    /**
     * 保存盘点明细并记录账面库存.
     */
    private function saveCheckDetails(int $billId, int $warehouseId, array $details, int $ignoreCheckId = 0): void
    {
        if (! $details) {
            throw $this->exception('请添加盘点商品');
        }
        $rows = [];
        foreach ($details as $detail) {
            $skuId = (int) ($detail['sku_id'] ?? 0);
            if (! $skuId) {
                throw $this->exception('商品规格错误');
            }
            $sku   = app(ProductAttrValueDao::class)->get($skuId, with: ['product'])?->toArray();
            if (! $sku) {
                throw $this->exception('商品规格不存在');
            }
            $stock = app(WarehouseStockService::class)->stockRow($warehouseId, $skuId, true);
            $realGoodStock = $this->normalizeIntegerQuantity($detail['real_good_stock'] ?? $stock->good_stock, '良品实盘库存格式错误');
            $realDefectStock = $this->normalizeIntegerQuantity($detail['real_defect_stock'] ?? $stock->defect_stock, '残次品实盘库存格式错误');
            if ($realGoodStock < 0 || $realDefectStock < 0) {
                throw $this->exception('实盘库存不能小于0');
            }
            $rows[] = [
                'check_id'          => $billId,
                'product_id'        => (int) $sku['product_id'],
                'sku_id'            => $skuId,
                'quantity'          => 0,
                'book_good_stock'   => (int) $stock->good_stock,
                'book_defect_stock' => (int) $stock->defect_stock,
                'real_good_stock'   => $realGoodStock,
                'real_defect_stock' => $realDefectStock,
                'remark'            => $detail['remark'] ?? '',
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }
        $this->assertNoCheckingConflict($warehouseId, $rows, $ignoreCheckId);
        $this->detailDao->delete(['check_id' => $billId]);
        $this->detailDao->getModel(false)->insert($rows);
    }

    /**
     * 汇总盘点差异生成入出库明细.
     */
    private function appendCheckBillRow(array &$rows, $detail, int $quantity, string $remark, ?int $stockQuality = null): void
    {
        if ($quantity <= 0) {
            return;
        }
        $key = (int) $detail->sku_id . ':' . $remark . ':' . (string) $stockQuality;
        if (! isset($rows[$key])) {
            $rows[$key] = [
                'product_id'    => (int) $detail->product_id,
                'sku_id'        => (int) $detail->sku_id,
                'quantity'      => 0,
                'remark'        => $remark,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
            if ($stockQuality) {
                $rows[$key]['stock_quality'] = $stockQuality;
            }
        }
        $rows[$key]['quantity'] += $quantity;
    }

    /**
     * 按盘盈差异生成入库单.
     */
    private function createCheckProfitInbound($bill, array $rows, int $uid): void
    {
        if (! $rows) {
            return;
        }
        $inbound = app(WarehouseInboundDao::class)->create([
            'bill_no'      => $this->billNo('PYRK'),
            'warehouse_id' => (int) $bill->warehouse_id,
            'inbound_type' => WarehouseEnum::INBOUND_TYPE_OTHER,
            'status'       => WarehouseEnum::INBOUND_STATUS_COMPLETED,
            'source_type'  => WarehouseEnum::SOURCE_CHECK,
            'source_id'    => (int) $bill->id,
            'source_no'    => (string) $bill->bill_no,
            'total_amount' => 0,
            'images'       => [],
            'remark'       => trim('盘赢入库 ' . (string) $bill->remark),
            'creator'      => $uid,
            'operator'     => $uid,
            'operated_at'  => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
        $this->insertGeneratedRows(app(WarehouseInboundDetailDao::class), (int) $inbound->id, 'inbound_id', $rows);
    }

    /**
     * 按盘亏差异生成出库单.
     */
    private function createCheckLossOutbound($bill, array $rows, int $uid): void
    {
        if (! $rows) {
            return;
        }
        $outbound = app(WarehouseOutboundDao::class)->create([
            'bill_no'      => $this->billNo('PKCK'),
            'warehouse_id' => (int) $bill->warehouse_id,
            'status'       => WarehouseEnum::OUTBOUND_STATUS_COMPLETED,
            'source_type'  => WarehouseEnum::SOURCE_CHECK,
            'source_id'    => (int) $bill->id,
            'images'       => [],
            'remark'       => trim('盘亏出库 ' . (string) $bill->remark),
            'creator'      => $uid,
            'operator'     => $uid,
            'operated_at'  => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
        $this->insertGeneratedRows(app(WarehouseOutboundDetailDao::class), (int) $outbound->id, 'outbound_id', $rows);
    }

    /**
     * 批量写入系统生成的单据明细.
     */
    private function insertGeneratedRows($dao, int $billId, string $foreignKey, array $rows): void
    {
        $rows = array_values($rows);
        foreach ($rows as &$row) {
            $row[$foreignKey] = $billId;
        }
        $dao->getModel(false)->insert($rows);
    }

    /**
     * 当前单据对应的库管动作权限.
     */
    protected function permissionAction(): string
    {
        return WarehouseEnum::ACTION_CHECK;
    }
}
