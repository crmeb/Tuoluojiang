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
use App\Http\Requests\Warehouse\WarehouseBillRequest;
use App\Http\Service\Warehouse\WarehouseCheckService;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 库存盘点管理控制器.
 */
#[Prefix('ent/warehouse/check')]
#[Resource('/', false, except: ['create', 'edit', 'update', 'destroy'], names: [
    'index' => '盘点列表',
    'store' => '新增盘点',
    'show'  => '盘点详情',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WarehouseCheckController extends AuthController
{
    /**
     * 注入库存盘点服务.
     */
    public function __construct(WarehouseCheckService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取盘点单列表.
     */
    public function index(): mixed
    {
        return $this->success($this->service->getBillList($this->request->getMore([['warehouse_id', ''], ['status', ''], ['bill_no', '', 'bill_no_like'], ['product_keyword', ''], ['time', '']]), auth('admin')->id()));
    }

    /**
     * 新增盘点单.
     */
    public function store(WarehouseBillRequest $request): mixed
    {
        $request->scene('check')->check();
        return $this->success('common.insert.succ', ['id' => $this->service->saveBill($request->postMore([['warehouse_id', 0], ['images', []], ['attach_ids', []], ['remark', ''], ['is_draft', true], ['details', []]]), auth('admin')->id())]);
    }

    /**
     * 继续保存盘点明细.
     */
    #[Put('continue/{id}', '继续盘点')]
    public function continue(WarehouseBillRequest $request, $id): mixed
    {
        $request->scene('continue')->check($this->request->all());
        $this->service->saveBill($request->postMore([['warehouse_id', 0], ['images', []], ['attach_ids', []], ['remark', ''], ['is_draft', true], ['details', []]]), auth('admin')->id(), (int) $id);
        return $this->success('common.operation.succ');
    }

    /**
     * 保存盘点备注.
     */
    #[Put('remark/{id}', '盘点备注')]
    public function remark($id): mixed
    {
        $this->service->remark((int) $id, (string) $this->request->post('remark', ''), auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 作废盘点单.
     */
    #[Put('cancel/{id}', '盘点作废')]
    public function cancel($id): mixed
    {
        $this->service->cancel((int) $id, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 获取盘点单详情.
     */
    public function show($id): mixed
    {
        return $this->success($this->service->detail((int) $id, auth('admin')->id()));
    }

}
