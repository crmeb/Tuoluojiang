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

namespace App\Http\Controller\AdminApi\Customer;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Customer\LiaisonRequest;
use App\Http\Service\Config\FormService;
use App\Http\Service\Customer\LiaisonService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 联系人管理
 * Class LiaisonController.
 */
#[Prefix('ent/client/liaisons')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '联系人列表',
    'create'  => '联系人新增表单',
    'store'   => '保存联系人',
    'edit'    => '联系人修改表单',
    'update'  => '修改联系人',
    'destroy' => '删除联系人',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class LiaisonController extends AuthController
{
    public function __construct(LiaisonService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 列表数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function index(): mixed
    {
        $where                = $this->request->getMore($this->service->getSearchField());
        $where['view_search'] = (int) $this->request->get('view_search', 2);
        $where['types']       = ViewSearchEnum::VIEW_LIAISON;
        return $this->success($this->service->getListByType($where, uid: auth('admin')->id()));
    }

    /**
     * 创建表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function create(FormService $service): mixed
    {
        $linkId = (int) $this->request->get('link_id', 0);
        return $this->success($service->getFormDataWithType(CustomEnum::LIAISON, associationId: $linkId));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function store(LiaisonRequest $request): mixed
    {
        $data = $request->validated();
        $eid  = (int) $this->request->post('eid', 0);
        if ($eid < 1) {
            return $this->fail('请选择关联客户!');
        }
        $res = $this->service->saveLiaison($data, $eid, auth('admin')->id());
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function update(LiaisonRequest $request, $id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        $data = $request->setExcludeId((int) $id)->validated();
        $this->service->updateData($data, (int) $id, auth('admin')->id(), ViewSearchEnum::VIEW_LIAISON);
        return $this->success(__('common.update.succ'));
    }

    /**
     * 删除.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->deleteLiaison((int) $id, auth('admin')->id());
        return $this->success('common.delete.succ');
    }

    /**
     * 修改表单.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function edit($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        return $this->success($this->service->detail((int) $id, auth('admin')->id(), ViewSearchEnum::VIEW_LIAISON));
    }
}
