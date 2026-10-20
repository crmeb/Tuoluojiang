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

use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Dao\Customer\ProductAttrValueDao;
use App\Http\Dao\Warehouse\WarehouseInboundDao;
use App\Http\Dao\Warehouse\WarehouseOutboundDao;
use App\Http\Dao\Warehouse\WarehouseSkuStockDao;
use App\Http\Dao\Warehouse\WarehouseStockFlowDao;
use App\Http\Service\ImportExport\RecordService as ImportRecordService;
use App\Http\Service\BaseService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 仓库库存服务，负责库存列表、台账、上下限和库存变更.
 */
class WarehouseStockService extends BaseService
{
    /**
     * 注入数据访问对象.
     */
    public function __construct(WarehouseSkuStockDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取仓库库存列表.
     */
    public function getStockList(array $where, int $uid): array
    {
        $where          = app(WarehousePermissionService::class)->applyWarehouseScope($where, $uid, true, WarehouseEnum::ACTION_STOCK);
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, ['*'], $page, $limit, ['id' => 'desc'], [
            'warehouse', 'product', 'product.category', 'sku',
        ]);
        return $this->listData($list, $this->dao->count($where));
    }

    /**
     * 获取库存数量统计.
     */
    public function getStockStatistics(array $where, int $uid): array
    {
        unset($where['stock_status']);
        $where = app(WarehousePermissionService::class)->applyWarehouseScope($where, $uid, true, WarehouseEnum::ACTION_STOCK);

        return [
            'all'      => (int) $this->dao->count($where),
            'low'      => (int) $this->dao->count($where + ['stock_status' => WarehouseEnum::STOCK_STATUS_LOW]),
            'high'     => (int) $this->dao->count($where + ['stock_status' => WarehouseEnum::STOCK_STATUS_HIGH]),
            'sold_out' => (int) $this->dao->count($where + ['stock_status' => WarehouseEnum::STOCK_STATUS_SOLD_OUT]),
        ];
    }

    /**
     * 获取库存上下限列表.
     */
    public function getLimitList(array $where, int $uid): array
    {
        return $this->getStockList($where, $uid);
    }

    /**
     * 创建库存导出记录并启动导出.
     */
    public function export(array $where, int $uid): void
    {
        $record = app(ImportRecordService::class)->create([
            'uid'    => $uid,
            'types'  => 0,
            'module' => 'warehouse_stock',
            'name'   => '',
        ]);
        new WarehouseStockExportService($where, $uid, (int) $record->id);
    }

    /**
     * 获取库存台账流水.
     */
    public function ledger(array $where, int $uid): array
    {
        if ((int) ($where['flow_type'] ?? 0) === WarehouseEnum::FLOW_OCCUPY) {
            $where['occupy_pending_source'] = true;
        }
        $where          = app(WarehousePermissionService::class)->applyWarehouseScope($where, $uid, true, WarehouseEnum::ACTION_STOCK);
        [$page, $limit] = $this->getPageValue();
        $dao            = app(WarehouseStockFlowDao::class);
        $list           = $dao->getList($where, ['*'], $page, $limit, ['id' => 'desc'], ['operatorInfo']);
        $list           = $this->loadLedgerSourceStatus($list);
        return $this->listData($list, $dao->count($where));
    }

    /**
     * 获取销售出库与退货入库记录.
     */
    public function records(array $where, int $uid): array
    {
        $recordType = (string) ($where['record_type'] ?? '');
        unset($where['record_type']);
        if ((int) ($where['source_id'] ?? 0) <= 0) {
            throw $this->exception('请选择关联订单');
        }
        [$page, $limit] = $this->getPageValue();
        $count          = DB::query()->fromSub($this->recordUnionQuery($where, $recordType), 'records')->count();
        $pageRows       = DB::query()
            ->fromSub($this->recordUnionQuery($where, $recordType), 'records')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->when($page && $limit, fn (Builder $query) => $query->forPage($page, $limit))
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->listData($this->loadRecordRows($pageRows), $count);
    }

    public function recordCount(int $sourceId): int
    {
        if ($sourceId <= 0) {
            return 0;
        }

        return (int) DB::query()
            ->fromSub($this->recordUnionQuery(['source_id' => $sourceId], ''), 'records')
            ->count();
    }

    /**
     * 设置 SKU 库存上下限.
     */
    public function setLimit(array $data, int $uid): void
    {
        $lowLimit  = $this->normalizeLimitValue($data['low_limit'] ?? null);
        $highLimit = $this->normalizeLimitValue($data['high_limit'] ?? null);
        $this->assertLimitRange($lowLimit, $highLimit);

        $this->transaction(function () use ($data, $highLimit, $lowLimit, $uid) {
            $stock = $this->stockRecord((int) $data['id'], true);
            app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $stock->warehouse_id, $uid, WarehouseEnum::ACTION_STOCK_SETTING);
            $this->saveLimitByStock($stock, $lowLimit, $highLimit);
        });
    }

    /**
     * 批量设置 SKU 库存上下限.
     *
     * @param array $data 包含 ids, low_limit, high_limit
     */
    public function batchSetLimit(array $data, int $uid): void
    {
        $lowLimit  = $this->normalizeLimitValue($data['low_limit'] ?? null);
        $highLimit = $this->normalizeLimitValue($data['high_limit'] ?? null);
        $this->assertLimitRange($lowLimit, $highLimit);

        $this->transaction(function () use ($data, $highLimit, $lowLimit, $uid) {
            foreach ($data['ids'] as $id) {
                $stock = $this->stockRecord((int) $id, true);
                app(WarehousePermissionService::class)->assertCanUseWarehouse((int) $stock->warehouse_id, $uid, WarehouseEnum::ACTION_STOCK_SETTING);
                $this->saveLimitByStock($stock, $lowLimit, $highLimit);
            }
        });
    }

    /**
     * 变更库存数量并记录库存流水.
     */
    public function change(array $item, int $quality, int $num, int $flowType, array $source, int $operator, string $remark = ''): void
    {
        if ($num === 0) {
            return;
        }
        $stock    = $this->stockRow((int) $item['warehouse_id'], (int) $item['sku_id'], true);
        $quality  = $this->normalizeQuality($quality);
        $flowType = $this->normalizeFlowType($flowType);
        $field    = match ($quality) {
            WarehouseEnum::STOCK_QUALITY_DEFECT  => 'defect_stock',
            WarehouseEnum::STOCK_QUALITY_OCCUPY  => 'occupy_stock',
            WarehouseEnum::STOCK_QUALITY_TRANSIT => 'transit_stock',
            default                              => 'good_stock',
        };
        $before = (int) $stock->{$field};
        $after  = $before + $num;
        if ($after < 0) {
            throw $this->exception('库存不足');
        }
        $stock->{$field} = $after;
        if (in_array($field, ['good_stock', 'occupy_stock'], true)) {
            $goodStock           = $field === 'good_stock' ? $after : (int) $stock->good_stock;
            $occupyStock         = $field === 'occupy_stock' ? $after : (int) $stock->occupy_stock;
            $stock->stock_status = $this->stockStatus($goodStock, $occupyStock, $stock->low_limit, $stock->high_limit);
        }
        $stock->save();
        app(WarehouseStockFlowDao::class)->create([
            'warehouse_id'        => (int) $item['warehouse_id'],
            'target_warehouse_id' => (int) ($item['target_warehouse_id'] ?? 0),
            'product_id'          => (int) $item['product_id'],
            'sku_id'              => (int) $item['sku_id'],
            'flow_type'           => $flowType,
            'stock_quality'       => $quality,
            'source_type'         => WarehouseEnum::sourceTypeValue(isset($source['source_type']) ? (int) $source['source_type'] : null),
            'source_id'           => (int) ($source['source_id'] ?? 0),
            'source_no'           => $source['source_no'] ?? '',
            'change_num'          => $num,
            'before_num'          => $before,
            'after_num'           => $after,
            'operator'            => $operator,
            'remark'              => $remark,
        ]);
    }

    /**
     * 占用可用库存.
     */
    public function occupy(array $item, int $num, array $source, int $operator, string $remark = ''): void
    {
        $stock = $this->stockRow((int) $item['warehouse_id'], (int) $item['sku_id'], true);
        if (((int) $stock->good_stock - (int) $stock->occupy_stock) < $num) {
            throw $this->exception('可用库存不足');
        }
        $this->change($item, WarehouseEnum::STOCK_QUALITY_OCCUPY, $num, WarehouseEnum::FLOW_OCCUPY, $source, $operator, $remark);
    }

    /**
     * 获取良品可用库存.
     */
    public function effectiveGoodStock(int $warehouseId, int $skuId): int
    {
        $stock = $this->stockRow($warehouseId, $skuId, true);
        return max(0, (int) $stock->good_stock - (int) $stock->occupy_stock);
    }

    /**
     * 获取或初始化 SKU 仓库库存记录.
     */
    public function stockRow(int $warehouseId, int $skuId, bool $lock = false)
    {
        $query = $this->dao->getModel(false)->where('warehouse_id', $warehouseId)->where('sku_id', $skuId);
        if ($lock) {
            $query->lockForUpdate();
        }
        $stock = $query->first();
        if ($stock) {
            return $stock;
        }
        $sku = $this->skuInfo($skuId);
        return $this->dao->create([
            'warehouse_id' => $warehouseId,
            'product_id'   => (int) $sku['product_id'],
            'sku_id'       => $skuId,
            'stock_status' => WarehouseEnum::STOCK_STATUS_SOLD_OUT,
        ]);
    }

    /**
     * 获取库存记录.
     */
    private function stockRecord(int $id, bool $lock = false)
    {
        $query = $this->dao->getModel(false)->where('id', $id);
        if ($lock) {
            $query->lockForUpdate();
        }
        $stock = $query->first();
        if (! $stock) {
            throw $this->exception('库存记录不存在');
        }
        return $stock;
    }

    /**
     * 按库存记录保存上下限.
     * @param mixed $stock
     * @param mixed $lowLimit
     * @param mixed $highLimit
     */
    private function saveLimitByStock($stock, $lowLimit, $highLimit): void
    {
        $stock->low_limit    = $lowLimit;
        $stock->high_limit   = $highLimit;
        $stock->stock_status = $this->stockStatus((int) $stock->good_stock, (int) $stock->occupy_stock, $lowLimit, $highLimit);
        $stock->save();
    }

    /**
     * 校验库存属性枚举值.
     */
    private function normalizeQuality(int $quality): int
    {
        if (in_array($quality, WarehouseEnum::STOCK_QUALITY_ALL, true)) {
            return $quality;
        }

        throw $this->exception('库存类型错误');
    }

    /**
     * 校验库存流水变动类型.
     */
    private function normalizeFlowType(int $flowType): int
    {
        if (in_array($flowType, WarehouseEnum::FLOW_TYPES, true)) {
            return $flowType;
        }

        throw $this->exception('库存变动类型错误');
    }

    /**
     * 获取并校验商品规格信息.
     */
    private function skuInfo(int $skuId): array
    {
        $sku = app(ProductAttrValueDao::class)->get($skuId, with: ['product'])?->toArray();
        if (! $sku) {
            throw $this->exception('商品规格不存在');
        }
        return $sku;
    }

    /**
     * 格式化出入库记录行.
     * @param mixed $rows
     */
    private function formatRecordRows($rows, string $recordType): array
    {
        $list = [];
        foreach ($rows as $row) {
            $row     = is_array($row) ? $row : $row->toArray();
            $order   = ($row['order_by_no'] ?? null) ?: ($row['order'] ?? null) ?: [];
            $details = (array) ($row['details'] ?? []);
            $list[]  = [
                'record_type'    => $recordType,
                'type_label'     => $recordType === 'inbound' ? '退货入库' : '销售出库',
                'bill_id'        => (int) ($row['id'] ?? 0),
                'bill_no'        => (string) ($row['bill_no'] ?? ''),
                'source_no'      => (string) ($row['source_no'] ?? ''),
                'record_no'      => (string) (($row['source_no'] ?? '') ?: ($row['bill_no'] ?? '')),
                'warehouse_id'   => (int) ($row['warehouse_id'] ?? ($row['warehouse']['id'] ?? 0)),
                'warehouse_name' => (string) ($row['warehouse']['name'] ?? ''),
                'total_amount'   => $this->recordAmount($row, $order, $recordType),
                'status'         => (int) ($row['status'] ?? 0),
                'status_label'   => $this->recordStatusLabel((int) ($row['status'] ?? 0), $recordType),
                'products'       => $this->recordProducts($details),
                'product_count'  => count($details),
                'created_at'     => (string) ($row['created_at'] ?? ''),
            ];
        }
        return $list;
    }

    private function recordUnionQuery(array $where, string $recordType): Builder
    {
        $queries = [];
        if ($recordType !== 'outbound') {
            $queries[] = $this->recordBaseQuery('warehouse_inbound', 'inbound', $where)
                ->where('inbound_type', WarehouseEnum::INBOUND_TYPE_RETURN);
        }
        if ($recordType !== 'inbound') {
            $queries[] = $this->recordBaseQuery('warehouse_outbound', 'outbound', $where)
                ->where('outbound_type', WarehouseEnum::OUTBOUND_TYPE_SALES);
        }

        $query = array_shift($queries) ?: $this->recordBaseQuery('warehouse_inbound', 'inbound', $where)->whereRaw('1 = 0');
        foreach ($queries as $unionQuery) {
            $query->unionAll($unionQuery);
        }
        return $query;
    }

    private function recordBaseQuery(string $table, string $recordType, array $where): Builder
    {
        $detailTable = $recordType === 'inbound' ? 'warehouse_inbound_detail' : 'warehouse_outbound_detail';
        $foreignKey  = $recordType === 'inbound' ? 'inbound_id' : 'outbound_id';
        $keyword     = trim((string) ($where['product_keyword'] ?? ''));

        return DB::table($table)
            ->selectRaw('? as record_type', [$recordType])
            ->addSelect('id', 'created_at')
            ->whereNull('deleted_at')
            ->where('source_id', (int) $where['source_id'])
            ->when(isset($where['warehouse_id']) && $where['warehouse_id'] !== '', fn (Builder $query) => $query->whereIn('warehouse_id', (array) $where['warehouse_id']))
            ->when(isset($where['warehouse_ids']) && $where['warehouse_ids'] !== '', fn (Builder $query) => $query->whereIn('warehouse_id', (array) $where['warehouse_ids']))
            ->when(isset($where['status']) && $where['status'] !== '', fn (Builder $query) => $query->where('status', $where['status']))
            ->when(isset($where['source_no_like']) && $where['source_no_like'] !== '', fn (Builder $query) => $query->where('source_no', 'like', '%' . $where['source_no_like'] . '%'))
            ->when(isset($where['created_at']) && $where['created_at'] !== '', fn (Builder $query) => $this->applyRecordTime($query, $where['created_at']))
            ->when($keyword !== '', function (Builder $query) use ($detailTable, $foreignKey, $table, $keyword) {
                $query->whereExists(function (Builder $query) use ($detailTable, $foreignKey, $table, $keyword) {
                    $query->selectRaw('1')
                        ->from($detailTable)
                        ->leftJoin('customer_product', 'customer_product.id', '=', $detailTable . '.product_id')
                        ->leftJoin('customer_product_attr_value', 'customer_product_attr_value.id', '=', $detailTable . '.sku_id')
                        ->whereColumn($detailTable . '.' . $foreignKey, $table . '.id')
                        ->where(function (Builder $query) use ($keyword) {
                            $query->where('customer_product.name', 'like', '%' . $keyword . '%')
                                ->orWhere('customer_product.number', 'like', '%' . $keyword . '%')
                                ->orWhere('customer_product_attr_value.sku', 'like', '%' . $keyword . '%')
                                ->orWhere('customer_product_attr_value.bar_code', 'like', '%' . $keyword . '%');
                        });
                });
            });
    }

    private function applyRecordTime(Builder $query, mixed $time): void
    {
        if (! is_array($time)) {
            $time = str_contains((string) $time, ',')
                ? explode(',', (string) $time, 2)
                : preg_split('/\s+-\s+/', (string) $time, 2);
        }
        $time = $time ?: [];
        $time = array_values(array_filter(array_map('trim', $time), static fn (string $value) => $value !== ''));
        if (count($time) >= 2) {
            $query->whereBetween('created_at', [$time[0], $time[1]]);
        }
    }

    private function loadRecordRows(array $pageRows): array
    {
        if (! $pageRows) {
            return [];
        }

        $ids = ['inbound' => [], 'outbound' => []];
        foreach ($pageRows as $row) {
            $ids[$row['record_type']][] = (int) $row['id'];
        }

        $with = ['warehouse', 'details.product', 'details.sku', 'order', 'orderByNo'];
        $map  = [];
        if ($ids['inbound']) {
            foreach ($this->formatRecordRows(app(WarehouseInboundDao::class)->getList(['id' => $ids['inbound']], ['*'], 0, 0, [], $with), 'inbound') as $row) {
                $map['inbound:' . $row['bill_id']] = $row;
            }
        }
        if ($ids['outbound']) {
            foreach ($this->formatRecordRows(app(WarehouseOutboundDao::class)->getList(['id' => $ids['outbound']], ['*'], 0, 0, [], $with), 'outbound') as $row) {
                $map['outbound:' . $row['bill_id']] = $row;
            }
        }

        return array_values(array_filter(array_map(static fn (array $row) => $map[$row['record_type'] . ':' . $row['id']] ?? null, $pageRows)));
    }

    private function loadLedgerSourceStatus($list)
    {
        if (! method_exists($list, 'filter')) {
            return $list;
        }

        $sourceRows = $list->filter(fn ($row): bool => $this->isLedgerSourceBillType((int) $row->source_type) && (int) $row->source_id > 0);
        if (method_exists($sourceRows, 'loadMissing')) {
            $sourceRows->loadMissing('sourceBill');
        }

        foreach ($list as $row) {
            $sourceType = (int) $row->source_type;
            $sourceBill = method_exists($row, 'relationLoaded') && $row->relationLoaded('sourceBill') ? $row->sourceBill : null;
            $status     = $sourceBill ? (int) $sourceBill->status : null;
            $row->setAttribute('source_status', $status);
            $row->setAttribute('source_status_label', $this->ledgerSourceStatusLabel($status, $sourceType));
        }

        return $list;
    }

    private function ledgerSourceStatusLabel(?int $status, int $sourceType): string
    {
        $recordType = match ($sourceType) {
            WarehouseEnum::SOURCE_INBOUND  => 'inbound',
            WarehouseEnum::SOURCE_OUTBOUND => 'outbound',
            default                        => '',
        };

        if ($status === null) {
            return '';
        }

        return $recordType === '' ? $this->recordStatusLabel($status, 'bill') : $this->recordStatusLabel($status, $recordType);
    }

    private function isLedgerSourceBillType(int $sourceType): bool
    {
        return in_array($sourceType, [
            WarehouseEnum::SOURCE_INBOUND,
            WarehouseEnum::SOURCE_OUTBOUND,
            WarehouseEnum::SOURCE_CHECK,
            WarehouseEnum::SOURCE_TRANSFER,
            WarehouseEnum::SOURCE_CONVERT,
        ], true);
    }

    private function recordAmount(array $row, array $order, string $recordType): string
    {
        $amount = $recordType === 'inbound' ? ($row['total_amount'] ?? 0) : ($order['contract_price'] ?? 0);
        return number_format((float) $amount, 2, '.', '');
    }

    private function recordStatusLabel(int $status, string $recordType): string
    {
        return match ($status) {
            WarehouseEnum::BILL_STATUS_DRAFT     => '草稿',
            WarehouseEnum::BILL_STATUS_PENDING   => match ($recordType) {
                'inbound'  => '待入库',
                'outbound' => '待出库',
                default    => '待处理',
            },
            WarehouseEnum::BILL_STATUS_COMPLETED => match ($recordType) {
                'inbound'  => '已入库',
                'outbound' => '已出库',
                default    => '已完成',
            },
            WarehouseEnum::BILL_STATUS_CANCELLED => '已作废',
            default                              => '',
        };
    }

    private function recordProducts(array $details): array
    {
        return array_map(static function (array $detail): array {
            $product = (array) ($detail['product'] ?? []);
            $sku     = (array) ($detail['sku'] ?? []);
            return [
                'product_id' => (int) ($detail['product_id'] ?? ($product['id'] ?? 0)),
                'sku_id'     => (int) ($detail['sku_id'] ?? ($sku['id'] ?? 0)),
                'name'       => (string) ($product['name'] ?? ''),
                'number'     => (string) ($product['number'] ?? ''),
                'sku'        => (string) ($sku['sku'] ?? ''),
                'bar_code'   => (string) ($sku['bar_code'] ?? ''),
                'image'      => (string) (($sku['image'] ?? '') ?: ($product['image'] ?? '')),
                'quantity'   => (int) ($detail['quantity'] ?? 0),
            ];
        }, $details);
    }

    /**
     * 根据可用库存和上下限计算库存状态.
     * @param mixed $lowLimit
     * @param mixed $highLimit
     */
    private function stockStatus(int $goodStock, int $occupyStock, ?int $lowLimit, ?int $highLimit): int
    {
        $effectiveStock = max(0, $goodStock - $occupyStock);
        if ($effectiveStock <= 0) {
            return WarehouseEnum::STOCK_STATUS_SOLD_OUT;
        }
        if ($lowLimit !== null && $effectiveStock < $lowLimit) {
            return WarehouseEnum::STOCK_STATUS_LOW;
        }
        if ($highLimit !== null && $effectiveStock > $highLimit) {
            return WarehouseEnum::STOCK_STATUS_HIGH;
        }
        return WarehouseEnum::STOCK_STATUS_NORMAL;
    }

    /**
     * 校验库存上下限关系.
     * @param mixed $lowLimit
     * @param mixed $highLimit
     */
    private function assertLimitRange($lowLimit, $highLimit): void
    {
        if ($highLimit !== null && $lowLimit !== null && $highLimit <= $lowLimit) {
            throw $this->exception('库存上限必须大于下限');
        }
    }

    /**
     * 标准化库存上下限空值.
     * @param mixed $value
     */
    private function normalizeLimitValue($value)
    {
        if (! $this->hasLimitValue($value)) {
            return null;
        }
        if (! is_numeric($value)) {
            throw $this->exception('库存上下限格式错误');
        }
        $value = trim((string) $value);
        if (! preg_match('/^\d+(?:\.0+)?$/', $value)) {
            throw $this->exception('库存上下限必须为整数');
        }
        return (int) $value;
    }

    /**
     * 判断库存上下限是否已设置.
     * @param mixed $value
     */
    private function hasLimitValue($value): bool
    {
        return $value !== null && $value !== '';
    }
}
