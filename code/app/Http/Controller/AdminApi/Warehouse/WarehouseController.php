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
use App\Http\Requests\Warehouse\WarehouseRequest;
use App\Http\Service\Warehouse\WarehouseService;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;
use Spatie\RouteAttributes\Attributes\WhereNumber;

/**
 * 仓库基础资料管理控制器.
 */
#[Prefix('ent/warehouse/base')]
#[Resource('/', false, except: ['create', 'edit', 'show', 'update', 'destroy'], names: [
    'index'   => '仓库列表',
    'store'   => '新增仓库',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WarehouseController extends AuthController
{
    /**
     * 注入仓库基础资料服务.
     */
    public function __construct(WarehouseService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取仓库列表.
     */
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
            ['code', '', 'code_like'],
            ['status', ''],
        ]);
        return $this->success($this->service->getWarehouseList($where));
    }

    /**
     * 获取登录人员可查看的仓库列表（含权限）.
     */
    #[Get('accessible', '可查看仓库列表')]
    public function accessible(): mixed
    {
        return $this->success($this->service->getAccessibleWarehouseList(auth('admin')->id()));
    }

    /**
     * 获取仓库详情.
     */
    #[Get('{id}', '仓库详情')]
    #[WhereNumber('id')]
    public function show($id): mixed
    {
        return $this->success($this->service->get((int) $id)?->toArray());
    }

    /**
     * 新增仓库.
     */
    public function store(WarehouseRequest $request): mixed
    {
        $request->scene('save')->check();
        $data = $request->postMore($this->fields());
        return $this->success('common.insert.succ', ['id' => $this->service->saveWarehouse($data, auth('admin')->id())]);
    }

    /**
     * 编辑仓库.
     */
    #[Put('{id}', '编辑仓库')]
    #[WhereNumber('id')]
    public function update($id, WarehouseRequest $request): mixed
    {
        $request->scene('update')->check($this->request->all());
        $data = $request->postMore($this->fields());
        $this->service->saveWarehouse($data, auth('admin')->id(), (int) $id);
        return $this->success('common.update.succ');
    }

    /**
     * 启用或停用仓库.
     */
    #[Put('status/{id}', '启停仓库')]
    public function status($id): mixed
    {
        $this->service->update((int) $id, ['status' => (int) $this->request->post('status', 1)]);
        return $this->success('common.operation.succ');
    }

    /**
     * 删除仓库.
     */
    #[Delete('{id}', '删除仓库')]
    #[WhereNumber('id')]
    public function destroy($id): mixed
    {
        $this->service->deleteWarehouse((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 获取仓库保存字段.
     */
    private function fields(): array
    {
        return [
            ['name', ''],
            ['contact', ''],
            ['phone', ''],
            ['address', ''],
            ['status', 1],
            ['sort', 0],
            ['remark', ''],
        ];
    }
}
