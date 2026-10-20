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
use App\Http\Requests\Warehouse\WarehouseTransferRequest;
use App\Http\Service\Warehouse\WarehouseTransferService;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 仓库调拨管理控制器.
 */
#[Prefix('ent/warehouse/transfer')]
#[Resource('/', false, except: ['create', 'edit', 'update', 'destroy'], names: [
    'index' => '调拨列表',
    'store' => '新增调拨',
    'show'  => '调拨详情',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WarehouseTransferController extends AuthController
{
    /**
     * 注入仓库调拨服务.
     */
    public function __construct(WarehouseTransferService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取调拨单列表.
     */
    public function index(): mixed
    {
        return $this->success($this->service->getTransferList($this->request->getMore([['warehouse_ids', []], ['warehouse_type', ''], ['status', ''], ['bill_no', '', 'bill_no_like'], ['product_keyword', ''], ['time', '']]), auth('admin')->id()));
    }

    /**
     * 新增调拨单.
     */
    public function store(WarehouseTransferRequest $request): mixed
    {
        $request->scene('save')->check();
        return $this->success('common.insert.succ', ['id' => $this->service->createBill($request->postMore($this->fields()), auth('admin')->id())]);
    }

    /**
     * 执行调拨出库.
     */
    #[Post('out/{id}', '调拨出库')]
    public function out($id): mixed
    {
        $this->service->transferOut((int) $id, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 执行调拨入库.
     */
    #[Post('in/{id}', '调拨入库')]
    public function in($id): mixed
    {
        $this->service->transferIn((int) $id, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 作废调拨单并释放占用库存.
     */
    #[Post('cancel/{id}', '调拨作废')]
    public function cancel($id): mixed
    {
        $this->service->cancel((int) $id, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 重新提交已作废调拨单.
     */
    #[Post('resubmit/{id}', '调拨重新提交')]
    public function resubmit(WarehouseTransferRequest $request, $id): mixed
    {
        $request->scene('save')->check();
        $this->service->resubmit((int) $id, $request->postMore($this->fields()), auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 获取调拨单详情.
     */
    public function show($id): mixed
    {
        return $this->success($this->service->detail((int) $id, auth('admin')->id()));
    }

    /**
     * 获取调拨单保存字段.
     */
    private function fields(): array
    {
        return [['out_warehouse_id', 0], ['in_warehouse_id', 0], ['transfer_type', 2], ['images', []], ['attach_ids', []], ['remark', ''], ['details', []]];
    }
}
