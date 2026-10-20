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
use App\Http\Dao\Warehouse\WarehouseConvertDao;
use App\Http\Dao\Warehouse\WarehouseConvertDetailDao;

/**
 * 正残品转换服务，负责良品与残次品库存互转.
 */
class WarehouseConvertService extends WarehouseBillService
{
    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseConvertDao $dao, WarehouseConvertDetailDao $detailDao)
    {
        $this->dao       = $dao;
        $this->detailDao = $detailDao;
    }

    /**
     * 创建仓储业务单据.
     */
    public function createBill(array $data, int $uid): int
    {
        app(WarehouseService::class)->assertEnabled((int) $data['warehouse_id']);
        app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['warehouse_id'], $uid, WarehouseEnum::ACTION_CONVERT);
        return $this->transaction(function () use ($data, $uid) {
            $details = $this->normalizeDetails((array) $data['details']);
            $this->assertNoCheckingConflict((int) $data['warehouse_id'], $details);
            $bill = $this->dao->create([
                'bill_no'      => $this->billNo('ZH'),
                'warehouse_id' => (int) $data['warehouse_id'],
                'convert_type' => (int) $data['convert_type'],
                'status'       => WarehouseEnum::BILL_STATUS_COMPLETED,
                'images'       => [],
                'remark'       => $data['remark'] ?? '',
                'creator'      => $uid,
                'operator'     => $uid,
                'operated_at'  => now(),
            ]);
            $this->changeConvertStock($bill, $details, $uid);
            $this->saveConvertDetails((int) $bill->id, $details);
            return (int) $bill->id;
        });
    }

    /**
     * 编辑正次转换单并重算库存转换.
     */
    public function updateBill(int $id, array $data, int $uid): void
    {
        app(WarehouseService::class)->assertEnabled((int) $data['warehouse_id']);
        app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $data['warehouse_id'], $uid, WarehouseEnum::ACTION_CONVERT);
        $this->transaction(function () use ($id, $data, $uid) {
            $bill = $this->dao->getModel(false)->where('id', $id)->lockForUpdate()->first();
            if (! $bill) {
                throw $this->exception('正次转换单不存在');
            }
            $this->assertWarehousePermission((int) $bill->warehouse_id, $uid);
            if ((int) $bill->status !== WarehouseEnum::BILL_STATUS_COMPLETED) {
                throw $this->exception('当前状态不允许编辑');
            }

            $oldDetails = $this->detailDao->getModel(false)->where('convert_id', $id)->lockForUpdate()->get()->toArray();
            $details    = $this->normalizeDetails((array) $data['details']);
            $this->assertNoCheckingConflict((int) $bill->warehouse_id, $oldDetails);
            $this->assertNoCheckingConflict((int) $data['warehouse_id'], $details);
            $this->changeConvertStock($bill, $oldDetails, $uid, -1, '编辑正次转换回滚');

            $bill->warehouse_id = (int) $data['warehouse_id'];
            $bill->convert_type = (int) $data['convert_type'];
            $bill->images       = [];
            $bill->remark       = $data['remark'] ?? '';
            $bill->operator     = $uid;
            $bill->operated_at  = now();
            $bill->save();

            $this->syncAttachRelation([], (int) $bill->id, AttachEnum::RELATION_TYPE_WAREHOUSE_CONVERT);
            $this->changeConvertStock($bill, $details, $uid);
            $this->saveConvertDetails((int) $bill->id, $details);
        });
    }

    /**
     * 当前单据对应的库管动作权限.
     */
    protected function permissionAction(): string
    {
        return WarehouseEnum::ACTION_CONVERT;
    }

    /**
     * 正次转换列表额外加载创建人.
     */
    protected function billListWith(): array
    {
        return ['warehouse', 'details.product', 'details.sku', 'creatorInfo'];
    }

    /**
     * 正次转换详情额外加载创建人.
     */
    protected function billDetailWith(): array
    {
        return ['warehouse', 'details.product', 'details.sku', 'details.files', 'creatorInfo'];
    }

    /**
     * 标准化正次转换商品明细.
     */
    protected function normalizeDetails(array $details): array
    {
        $details = array_values($details);
        $rows    = parent::normalizeDetails($details);
        foreach ($details as $index => $detail) {
            $rows[$index]['images'] = $this->normalizeAttachIds((array) ($detail['images'] ?? []));
        }
        return $rows;
    }

    /**
     * 保存正次转换明细和商品凭证附件关联.
     */
    private function saveConvertDetails(int $billId, array $details): void
    {
        $oldIds = $this->detailDao->getModel(false)->where('convert_id', $billId)->pluck('id')->all();
        foreach ($oldIds as $oldId) {
            $this->syncAttachRelation([], (int) $oldId, AttachEnum::RELATION_TYPE_WAREHOUSE_CONVERT_DETAIL);
        }
        $this->detailDao->delete(['convert_id' => $billId]);
        foreach ($details as $detail) {
            $attachIds            = (array) ($detail['images'] ?? []);
            $detail['convert_id'] = $billId;
            $row                  = $this->detailDao->create($detail);
            $this->syncAttachRelation($attachIds, (int) $row->id, AttachEnum::RELATION_TYPE_WAREHOUSE_CONVERT_DETAIL);
        }
    }

    /**
     * 执行或回滚正次转换库存.
     * @param mixed $bill
     */
    private function changeConvertStock($bill, array $details, int $uid, int $direction = 1, string $remark = ''): void
    {
        [$from, $to]  = $this->convertQualities((int) $bill->convert_type);
        $stockService = app(WarehouseStockService::class);
        foreach ($details as $detail) {
            $quantity = (int) $detail['quantity'];
            $item     = ['warehouse_id' => (int) $bill->warehouse_id, 'product_id' => (int) $detail['product_id'], 'sku_id' => (int) $detail['sku_id']];
            $source   = ['source_type' => WarehouseEnum::SOURCE_CONVERT, 'source_id' => (int) $bill->id, 'source_no' => $bill->bill_no];
            $stockService->change($item, $from, -$quantity * $direction, WarehouseEnum::FLOW_CONVERT, $source, $uid, $remark ?: (string) $bill->remark);
            $stockService->change($item, $to, $quantity * $direction, WarehouseEnum::FLOW_CONVERT, $source, $uid, $remark ?: (string) $bill->remark);
        }
    }

    /**
     * 获取正次转换的出入库存属性.
     */
    private function convertQualities(int $convertType): array
    {
        $from = $convertType === WarehouseEnum::CONVERT_DEFECT_TO_GOOD ? WarehouseEnum::STOCK_QUALITY_DEFECT : WarehouseEnum::STOCK_QUALITY_GOOD;
        $to   = $from === WarehouseEnum::STOCK_QUALITY_GOOD ? WarehouseEnum::STOCK_QUALITY_DEFECT : WarehouseEnum::STOCK_QUALITY_GOOD;
        return [$from, $to];
    }
}
