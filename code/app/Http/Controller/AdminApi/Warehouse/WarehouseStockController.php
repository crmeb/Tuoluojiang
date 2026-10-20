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

namespace App\Http\Controller\AdminApi\Warehouse;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Warehouse\WarehouseStockLimitRequest;
use App\Http\Service\Warehouse\WarehouseStockService;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 仓库库存管理控制器.
 */
#[Prefix('ent/warehouse/stock')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WarehouseStockController extends AuthController
{
    /**
     * 注入仓库库存服务.
     */
    public function __construct(WarehouseStockService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取 SKU 仓库库存列表.
     */
    #[Get('list', 'SKU仓库库存列表')]
    public function list(): mixed
    {
        return $this->success($this->service->getStockList($this->request->getMore([
            ['warehouse_id', ''],
            ['product_id', ''],
            ['sku_id', ''],
            ['category_id', ''],
            ['brand', ''],
            ['keyword', ''],
            ['stock_status', ''],
            ['has_occupy', ''],
            ['has_transit', ''],
        ]), auth('admin')->id()));
    }

    /**
     * 获取库存数量统计.
     */
    #[Get('statistics', '库存数量统计')]
    public function statistics(): mixed
    {
        return $this->success($this->service->getStockStatistics($this->request->getMore([
            ['warehouse_id', ''],
            ['product_id', ''],
            ['sku_id', ''],
            ['category_id', ''],
            ['brand', ''],
            ['keyword', ''],
            ['stock_status', ''],
            ['has_occupy', ''],
            ['has_transit', ''],
        ]), auth('admin')->id()));
    }

    /**
     * 获取库存台账流水.
     */
    #[Get('ledger', '库存台账')]
    public function ledger(): mixed
    {
        return $this->success($this->service->ledger($this->request->getMore([
            ['warehouse_id', ''],
            ['product_id', ''],
            ['sku_id', ''],
            ['flow_type', ''],
            ['stock_quality', ''],
            ['source_no', '', 'source_no_like'],
            ['created_at', ''],
        ]), auth('admin')->id()));
    }

    /**
     * 获取销售出库与退货入库记录.
     */
    #[Get('records', '出入库记录')]
    public function records(): mixed
    {
        return $this->success($this->service->records($this->request->getMore([
            ['source_id', 0],
            ['warehouse_id', ''],
            ['source_no', '', 'source_no_like'],
            ['product_keyword', ''],
            ['status', ''],
            ['record_type', ''],
            ['created_at', ''],
        ]), auth('admin')->id()));
    }

    /**
     * 获取库存上下限列表.
     */
    #[Get('limit/list', '库存上下限列表')]
    public function limitList(): mixed
    {
        return $this->success($this->service->getLimitList($this->request->getMore([
            ['warehouse_id', ''],
            ['product_id', ''],
            ['sku_id', ''],
            ['category_id', ''],
            ['brand', ''],
            ['keyword', ''],
            ['stock_status', ''],
        ]), auth('admin')->id()));
    }

    /**
     * 设置 SKU 库存上下限.
     */
    #[Put('limit', '设置库存上下限')]
    public function limit(WarehouseStockLimitRequest $request): mixed
    {
        $request->scene('save')->check($this->request->all());
        $this->service->setLimit($request->postMore([
            ['id', 0],
            ['low_limit', ''],
            ['high_limit', ''],
        ]), auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 批量设置 SKU 库存上下限.
     */
    #[Put('limit/batch', '批量设置库存上下限')]
    public function batchLimit(WarehouseStockLimitRequest $request): mixed
    {
        $request->scene('batch_save')->check($this->request->all());
        $this->service->batchSetLimit($request->postMore([
            ['ids', []],
            ['low_limit', ''],
            ['high_limit', ''],
        ]), auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 发起库存导出任务.
     */
    #[Post('export', '库存导出')]
    public function export(): mixed
    {
        $this->service->export($this->request->postMore([
            ['warehouse_id', ''],
            ['product_id', ''],
            ['sku_id', ''],
            ['category_id', ''],
            ['brand', ''],
            ['keyword', ''],
            ['stock_status', ''],
            ['has_occupy', ''],
            ['has_transit', ''],
        ]), auth('admin')->id());
        return $this->success('数据导出中，请稍后在记录中查看...');
    }
}
