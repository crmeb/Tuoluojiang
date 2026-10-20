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
use App\Http\Service\Warehouse\WarehouseConvertService;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 正残品转换管理控制器.
 */
#[Prefix('ent/warehouse/convert')]
#[Resource('/', false, except: ['create', 'edit', 'update', 'destroy'], names: [
    'index' => '正次转换列表',
    'store' => '新增正次转换',
    'show'  => '正次转换详情',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WarehouseConvertController extends AuthController
{
    /**
     * 注入正残品转换服务.
     */
    public function __construct(WarehouseConvertService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取正残品转换列表.
     */
    public function index(): mixed
    {
        return $this->success($this->service->getBillList($this->request->getMore([['warehouse_id', ''], ['status', ''], ['bill_no', '', 'bill_no_like'], ['product_keyword', ''], ['convert_type', ''], ['time', '']]), auth('admin')->id()));
    }

    /**
     * 新增正残品转换单.
     */
    public function store(WarehouseBillRequest $request): mixed
    {
        $request->scene('convert')->check();
        return $this->success('common.insert.succ', ['id' => $this->service->createBill($request->postMore($this->fields()), auth('admin')->id())]);
    }

    /**
     * 编辑正残品转换单.
     */
    #[Put('{id}', '编辑正次转换')]
    public function update(WarehouseBillRequest $request, $id): mixed
    {
        $request->scene('convert')->check();
        $this->service->updateBill((int) $id, $request->postMore($this->fields()), auth('admin')->id());
        return $this->success('common.update.succ');
    }

    /**
     * 获取正残品转换详情.
     * @param mixed $id
     */
    public function show($id): mixed
    {
        return $this->success($this->service->detail((int) $id, auth('admin')->id()));
    }

    /**
     * 获取正次转换保存字段.
     */
    private function fields(): array
    {
        return [['warehouse_id', 0], ['convert_type', 1], ['remark', ''], ['details', []]];
    }
}
