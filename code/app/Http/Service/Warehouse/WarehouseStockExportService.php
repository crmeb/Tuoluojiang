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
use App\Http\Dao\Customer\ProductCategoryDao;
use App\Http\Dao\Warehouse\WarehouseSkuStockDao;
use Tuoluojiang\Open\services\export\BaseExport;
use Illuminate\Support\Facades\Schema;

/**
 * 仓库库存导出服务，负责生成库存导出数据.
 */
class WarehouseStockExportService extends BaseExport
{
    public string $moduleName = '库存';

    /**
     * 注入数据访问对象.
     */
    public function __construct(protected array $where, protected int $uid, int $recordId)
    {
        parent::__construct($recordId);
    }

    /**
     * 设置库存导出表头.
     */
    public function setHeadings(): array
    {
        return ['仓库名称', '商品名称', '商品编码', '条码', '商品分类', '规格', '品牌', '剩余库存', '占用库存', '在途库存'];
    }

    /**
     * 设置库存导出数据回调.
     */
    public function setDataCallback(): callable
    {
        return function () {
            $where = app(WarehousePermissionService::class)->applyWarehouseScope($this->where, $this->uid, true, WarehouseEnum::ACTION_STOCK);
            $categoryMap = app(ProductCategoryDao::class)->select([], ['id', 'name'])->pluck('name', 'id')->all();
            $query = app(WarehouseSkuStockDao::class)->search($where)->with(['warehouse', 'product', 'sku'])->orderByDesc('id');

            foreach ($query->cursor() as $item) {
                $product = $item->product;
                $sku = $item->sku;
                $warehouse = $item->warehouse;

                yield [
                    $warehouse->name ?? '',
                    $product->name ?? '',
                    $product->number ?? '',
                    $sku->bar_code ?? '',
                    $categoryMap[$product->pid ?? 0] ?? '',
                    $sku->sku ?? '',
                    $this->brandName($product),
                    $this->formatStock(max(0, (int) $item->good_stock - (int) $item->occupy_stock)),
                    $this->formatStock((int) $item->occupy_stock),
                    $this->formatStock((int) $item->transit_stock),
                ];
            }
        };
    }

    /**
     * 格式化商品品牌名称.
     */
    private function brandName($product): string
    {
        if (! $product) {
            return '';
        }
        foreach (['brand_name', 'brand'] as $column) {
            if (Schema::hasColumn('customer_product', $column) && isset($product->{$column})) {
                return (string) $product->{$column};
            }
        }
        if (Schema::hasColumn('customer_product', 'brand_id') && isset($product->brand_id)) {
            return (string) $product->brand_id;
        }
        return '';
    }

    /**
     * 格式化导出库存数量.
     */
    private function formatStock(int $value): string
    {
        return (string) $value;
    }
}
