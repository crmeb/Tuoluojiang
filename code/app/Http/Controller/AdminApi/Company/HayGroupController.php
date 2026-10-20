<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Controller\AdminApi\Company;

use App\Http\Contract\Company\HayGroupInterface;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Requests\enterprise\trait\HayGroupRequest;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 海氏评估表.
 */
#[Prefix('ent/company/evaluate')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '海氏评估表列表',
    'store'   => '海氏评估表保存',
    'update'  => '海氏评估表修改',
    'destroy' => '海氏评估表删除',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class HayGroupController extends AuthController
{
    public function __construct(HayGroupInterface $services)
    {
        parent::__construct();
        $this->service = $services;
        $this->middleware([AuthAdmin::class, AuthEnterprise::class]);
    }

    /**
     * 展示数据.
     * @throws BindingResolutionException
     */
    public function index(): mixed
    {
        $where = $this->request->getMore([
            ['name', ''],
            ['uid', auth('admin')->id()],
        ]);
        return $this->success($this->service->getList($where));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     */
    public function store(HayGroupRequest $request): mixed
    {
        $res = $this->service->save($request->postMore($this->getRequestFields()));
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws BindingResolutionException
     */
    public function update($id, HayGroupRequest $request): mixed
    {
        $res = $this->service->update((int) $id, $request->postMore($this->getRequestFields()));
        return $res ? $this->success('common.update.succ') : $this->fail('common.update.fail');
    }

    /**
     * 删除.
     * @param mixed $id
     */
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }

        $this->service->delete((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 数据列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('data/{group_id}', '评估表数据列表')]
    public function dataList($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }

        return $this->success($this->service->getDataList((int) $id));
    }

    /**
     * 历史记录.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('history/{group_id}', '评估表历史记录')]
    public function history($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }

        return $this->success($this->service->getHistoryList((int) $id));
    }

    /**
     * 设置Request类名.
     */
    protected function getRequestClassName(): string
    {
        return HayGroupRequest::class;
    }

    /**
     * 设置请求参数获取字段.
     * @return mixed
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['list', []],
        ];
    }
}
