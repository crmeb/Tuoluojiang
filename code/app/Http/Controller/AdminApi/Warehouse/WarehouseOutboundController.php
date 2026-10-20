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

use App\Constants\Warehouse\WarehouseEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Warehouse\WarehouseBillRequest;
use App\Http\Service\Warehouse\WarehouseOutboundService;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 出库单管理控制器.
 */
#[Prefix('ent/warehouse/outbound')]
#[Resource('/', false, except: ['create', 'edit', 'update', 'destroy'], names: [
    'index' => '出库列表',
    'store' => '新增出库',
    'show'  => '出库详情',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WarehouseOutboundController extends AuthController
{
    /**
     * 注入出库单服务.
     */
    public function __construct(WarehouseOutboundService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取出库单列表.
     */
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['warehouse_id', ''],
            ['source_no', '', 'source_no_like'],
            ['product_keyword', ''],
            ['status', ''],
            ['outbound_type', ''],
            ['bill_no', '', 'bill_no_like'],
            ['created_at', '', 'time'],
        ]);
        if (! $this->hasWarehouseId($where['warehouse_id'])) {
            return $this->fail('请选择仓库');
        }
        return $this->success($this->service->getBillList($where, auth('admin')->id()));
    }

    /**
     * 新增出库单.
     */
    public function store(WarehouseBillRequest $request): mixed
    {
        $request->scene('outbound')->check();
        return $this->success('common.insert.succ', ['id' => $this->service->createBill($request->postMore($this->fields()), auth('admin')->id())]);
    }

    /**
     * 编辑出库单.
     */
    #[Put('{id}', '编辑出库')]
    public function update(WarehouseBillRequest $request, $id): mixed
    {
        $request->scene('outbound')->check();
        $this->service->updateBill((int) $id, $request->postMore($this->fields()), auth('admin')->id());
        return $this->success('common.update.succ');
    }

    /**
     * 获取出库单详情.
     * @param mixed $id
     */
    public function show($id): mixed
    {
        return $this->success($this->service->detail((int) $id, auth('admin')->id()));
    }

    /**
     * 拣货出库.
     */
    #[Post('pick/{id}', '拣货出库')]
    public function pick($id): mixed
    {
        $this->service->pick((int) $id, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 作废出库单并释放占用库存.
     */
    #[Post('cancel/{id}', '出库作废')]
    public function cancel($id): mixed
    {
        $this->service->cancel((int) $id, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 获取出库单保存字段.
     */
    private function fields(): array
    {
        return [['warehouse_id', 0], ['outbound_type', WarehouseEnum::OUTBOUND_TYPE_SALES], ['source_type', WarehouseEnum::SOURCE_MANUAL], ['source_id', 0], ['source_no', ''], ['images', []], ['attach_ids', []], ['remark', ''], ['details', []]];
    }

    /**
     * 列表查询必须指定仓库，避免跨仓库枚举单据数据.
     */
    private function hasWarehouseId(mixed $warehouseId): bool
    {
        return (bool) array_filter((array) $warehouseId, static fn ($id) => (int) $id > 0);
    }
}
