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
use App\Http\Service\Warehouse\WarehouseInboundService;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 入库单管理控制器.
 */
#[Prefix('ent/warehouse/inbound')]
#[Resource('/', false, except: ['create', 'edit', 'update', 'destroy'], names: [
    'index' => '入库列表',
    'store' => '新增入库',
    'show'  => '入库详情',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WarehouseInboundController extends AuthController
{
    /**
     * 注入入库单服务.
     */
    public function __construct(WarehouseInboundService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取入库单列表.
     */
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['warehouse_id', ''],
            ['source_no', '', 'source_no_like'],
            ['product_keyword', ''],
            ['status', ''],
            ['bill_no', '', 'bill_no_like'],
            ['inbound_type', ''],
            ['created_at', '', 'time'],
        ]);
        if (! $this->hasWarehouseId($where['warehouse_id'])) {
            return $this->fail('请选择仓库');
        }
        return $this->success($this->service->getBillList($where, auth('admin')->id()));
    }

    /**
     * 新增入库单.
     */
    public function store(WarehouseBillRequest $request): mixed
    {
        $request->scene('inbound')->check();
        return $this->success('common.insert.succ', ['id' => $this->service->createBill($request->postMore($this->fields()), auth('admin')->id())]);
    }

    /**
     * 获取入库单详情.
     * @param mixed $id
     */
    public function show($id): mixed
    {
        return $this->success($this->service->detail((int) $id, auth('admin')->id()));
    }

    /**
     * 确认入库并写入库存流水.
     */
    #[Post('confirm/{id}', '入库确认')]
    public function confirm($id): mixed
    {
        $this->service->confirm((int) $id, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 作废入库单.
     */
    #[Post('cancel/{id}', '入库作废')]
    public function cancel($id): mixed
    {
        $this->service->cancel((int) $id, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 获取入库单保存字段.
     */
    private function fields(): array
    {
        return [['warehouse_id', 0], ['inbound_type', WarehouseEnum::INBOUND_TYPE_PURCHASE], ['source_type', WarehouseEnum::SOURCE_MANUAL], ['source_id', 0], ['source_no', ''], ['images', []], ['attach_ids', []], ['remark', ''], ['details', []]];
    }

    /**
     * 列表查询必须指定仓库，避免跨仓库枚举单据数据.
     */
    private function hasWarehouseId(mixed $warehouseId): bool
    {
        return (bool) array_filter((array) $warehouseId, static fn ($id) => (int) $id > 0);
    }
}
