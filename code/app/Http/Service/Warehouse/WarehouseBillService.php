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
use App\Http\Service\Attach\AttachService;
use App\Http\Dao\BaseDao;
use App\Http\Service\BaseService;

/**
 * 仓储单据基础服务，封装单号、明细、凭证和仓库权限公共逻辑.
 */
abstract class WarehouseBillService extends BaseService
{
    protected BaseDao $detailDao;

    /**
     * 获取仓储单据列表.
     */
    public function getBillList(array $where, int $uid): array
    {
        $this->assertListWarehousePermission($where, $uid);
        $where = app(WarehousePermissionService::class)->applyWarehouseScope($where, $uid, $this->permissionAction() === WarehouseEnum::ACTION_STOCK, $this->permissionAction());
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, ['*'], $page, $limit, ['id' => 'desc'], $this->billListWith());
        return $this->listData($this->formatBillList($list), $this->dao->count($where));
    }

    /**
     * 获取单据或业务详情.
     */
    public function detail(int $id, int $uid = 0): array
    {
        $info = $this->dao->get($id, with: $this->billDetailWith())?->toArray();
        if (! $info) {
            throw $this->exception('单据不存在');
        }
        if ($uid) {
            $this->assertWarehousePermission((int) ($info['warehouse_id'] ?? 0), $uid, $this->permissionAction());
        }
        return $this->formatBillDetail($info);
    }

    /**
     * 生成仓储单据编号.
     */
    protected function billNo(string $prefix): string
    {
        return $prefix . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 归一化单据凭证附件 ID.
     */
    protected function normalizeAttachIds(array $images): array
    {
        if (count($images) > 10) {
            throw $this->exception('凭证附件最多上传10个');
        }
        $ids = [];
        foreach ($images as $image) {
            $id = is_array($image) ? (int) ($image['id'] ?? $image['attach_id'] ?? 0) : (int) $image;
            if ($id <= 0) {
                throw $this->exception('凭证附件格式错误');
            }
            $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));
        if ($ids && app(AttachService::class)->count(['id' => $ids]) !== count($ids)) {
            throw $this->exception('凭证附件不存在');
        }
        return $ids;
    }

    /**
     * 标准化单据商品明细.
     */
    protected function normalizeDetails(array $details): array
    {
        if (! $details) {
            throw $this->exception('请添加商品明细');
        }
        $rows = [];
        foreach ($details as $detail) {
            $skuId = (int) ($detail['sku_id'] ?? 0);
            $quantity = $this->normalizeIntegerQuantity($detail['quantity'] ?? 0);
            if (! $skuId || $quantity <= 0) {
                throw $this->exception('商品明细数量错误');
            }
            $sku = app(ProductAttrValueDao::class)->get($skuId, with: ['product'])?->toArray();
            if (! $sku) {
                throw $this->exception('商品规格不存在');
            }
            $rows[] = [
                'product_id' => (int) $sku['product_id'],
                'sku_id'     => $skuId,
                'quantity'   => $quantity,
                'remark'     => $detail['remark'] ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        return $rows;
    }

    /**
     * 校验商品是否存在进行中的盘点冲突.
     */
    protected function assertNoCheckingConflict(int $warehouseId, array $details, int $ignoreCheckId = 0): void
    {
        $skuIds = collect($details)->pluck('sku_id')->filter()->unique()->values()->all();
        if (! $warehouseId || ! $skuIds) {
            return;
        }

        $checkIds = app(WarehouseCheckDao::class)->column([
            'warehouse_id' => $warehouseId,
            'status'       => WarehouseEnum::CHECK_STATUS_DOING,
        ], 'id');
        if (! $checkIds) {
            return;
        }
        if ($ignoreCheckId) {
            $checkIds = array_values(array_diff(array_map('intval', $checkIds), [$ignoreCheckId]));
        }
        if (! $checkIds) {
            return;
        }

        $exists = app(WarehouseCheckDetailDao::class)->getModel(false)
            ->whereIn('check_id', $checkIds)
            ->whereIn('sku_id', $skuIds)
            ->exists();
        if ($exists) {
            throw $this->exception('商品正在盘点中，请完成盘点后再操作库存');
        }
    }

    /**
     * 保存单据明细数据.
     */
    protected function saveDetails(int $billId, string $foreignKey, array $details): void
    {
        $this->detailDao->delete([$foreignKey => $billId]);
        foreach ($details as &$detail) {
            $detail[$foreignKey] = $billId;
        }
        $this->detailDao->getModel(false)->insert($details);
    }

    /**
     * 标准化整数库存数量，避免小数库存被数据库隐式截断.
     */
    protected function normalizeIntegerQuantity(mixed $value, string $message = '商品明细数量错误'): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (! is_numeric($value)) {
            throw $this->exception($message);
        }
        $value = trim((string) $value);
        if (! preg_match('/^-?\d+(?:\.0+)?$/', $value)) {
            throw $this->exception($message);
        }
        return (int) $value;
    }

    /**
     * 校验当前用户是否可操作指定仓库.
     */
    protected function assertWarehousePermission(int $warehouseId, int $uid, string $action = ''): void
    {
        app(WarehousePermissionService::class)->assertCanUseWarehouse($warehouseId, $uid, $action ?: $this->permissionAction());
    }

    /**
     * 列表查询传入仓库时先按当前单据动作校验，避免无权限仓库被静默过滤.
     */
    protected function assertListWarehousePermission(array $where, int $uid): void
    {
        $warehouseIds = $where['warehouse_id'] ?? $where['warehouse_ids'] ?? [];
        $warehouseIds = array_filter(array_map('intval', (array) $warehouseIds));
        foreach (array_unique($warehouseIds) as $warehouseId) {
            $this->assertWarehousePermission((int) $warehouseId, $uid);
        }
    }

    /**
     * 仓储单据列表关联.
     */
    protected function billListWith(): array
    {
        return ['warehouse', 'details.product', 'details.sku', 'files'];
    }

    /**
     * 仓储单据详情关联.
     */
    protected function billDetailWith(): array
    {
        return ['warehouse', 'details.product', 'details.sku', 'files'];
    }

    /**
     * 格式化仓储单据列表.
     */
    protected function formatBillList($list)
    {
        return $list;
    }

    /**
     * 格式化仓储单据详情.
     */
    protected function formatBillDetail(array $info): array
    {
        return $info;
    }

    /**
     * 格式化包含来源订单的列表.
     */
    protected function formatOrderBillList($list): array
    {
        return $list->map(function ($item) {
            return $this->formatOrderBillDetail($item->toArray());
        })->all();
    }

    /**
     * 格式化包含来源订单的详情.
     */
    protected function formatOrderBillDetail(array $row): array
    {
        $sourceNo = trim((string) ($row['source_no'] ?? ''));
        if ($sourceNo !== '') {
            $order = ($row['order_by_no'] ?? null) ?: ($row['order'] ?? null);
        } elseif ($this->isOrderSource((int) ($row['source_type'] ?? 0))) {
            $order = $row['order'] ?? null;
        } else {
            $order = null;
        }
        $paymentStatus = $order ? (bccomp((string) ($order['surplus'] ?? '0'), '0', 2) === 0 ? 1 : 0) : null;
        if ($order) {
            $order['payment_status'] = $paymentStatus;
        }
        $row['order'] = $order;
        unset($row['order_by_no']);
        $row['payment_status'] = $paymentStatus;
        return $row;
    }

    /**
     * 判断单据来源是否为可关联付款状态的订单来源.
     */
    protected function isOrderSource(int $sourceType): bool
    {
        return in_array($sourceType, [
            WarehouseEnum::SOURCE_PURCHASE_ORDER,
            WarehouseEnum::SOURCE_RETURN_ORDER,
            WarehouseEnum::SOURCE_ORDER,
            WarehouseEnum::SOURCE_CONTRACT,
            WarehouseEnum::SOURCE_SALES_ORDER,
        ], true);
    }

    /**
     * 当前单据对应的库管动作权限.
     */
    protected function permissionAction(): string
    {
        return '';
    }

    /**
     * 保存单据凭证附件关联.
     */
    protected function syncAttachRelation(array $attachIds, int $relationId, string $relationType): void
    {
        $relationTypeValue = AttachEnum::RELATION_TYPE[$relationType];
        app(AttachService::class)->update([
            'relation_id'   => $relationId,
            'relation_type' => $relationTypeValue,
        ], [
            'relation_id'   => 0,
            'relation_type' => AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_DEFAULT],
        ]);
        if ($attachIds) {
            app(AttachService::class)->update(['id' => $attachIds], [
                'relation_id'   => $relationId,
                'relation_type' => $relationTypeValue,
            ]);
        }
    }
}
