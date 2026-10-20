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
use App\Http\Requests\Warehouse\WarehouseManagerRequest;
use App\Http\Service\Warehouse\WarehouseManagerService;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 库管人员管理控制器.
 */
#[Prefix('ent/warehouse/manager')]
#[Resource('/', false, except: ['create', 'edit'], names: [
    'index'   => '库管列表',
    'store'   => '新增库管',
    'show'    => '库管详情',
    'update'  => '编辑库管',
    'destroy' => '删除库管',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WarehouseManagerController extends AuthController
{
    /**
     * 注入库管人员服务.
     */
    public function __construct(WarehouseManagerService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取库管列表.
     */
    public function index(): mixed
    {
        return $this->success($this->service->getManagerList($this->request->getMore([
            ['name', '', 'name_like'],
            ['status', ''],
        ])));
    }

    /**
     * 获取库管详情.
     */
    public function show($id): mixed
    {
        return $this->success($this->service->detail((int) $id));
    }

    /**
     * 新增库管.
     */
    public function store(WarehouseManagerRequest $request): mixed
    {
        $request->scene('save')->check();
        return $this->success('common.insert.succ', ['id' => $this->service->saveManager($request->postMore($this->fields()), auth('admin')->id())]);
    }

    /**
     * 编辑库管.
     */
    public function update($id, WarehouseManagerRequest $request): mixed
    {
        $request->scene('update')->check($this->request->all());
        $this->service->saveManager($request->postMore($this->fields()), auth('admin')->id(), (int) $id);
        return $this->success('common.update.succ');
    }

    /**
     * 启用或停用库管.
     */
    #[Put('status/{id}', '启停库管')]
    public function status($id): mixed
    {
        $this->service->update((int) $id, ['status' => (int) $this->request->post('status', 1)]);
        return $this->success('common.operation.succ');
    }

    /**
     * 删除库管.
     */
    public function destroy($id): mixed
    {
        $this->service->delete((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 获取库管保存字段.
     */
    private function fields(): array
    {
        return [
            ['name', ''],
            ['manager_type', 0],
            ['can_view_other', 0],
            ['status', 1],
            ['sort', 0],
            ['remark', ''],
            ['user_ids', []],
            ['warehouse_ids', []],
            ['warehouse_permissions', []],
        ];
    }
}
