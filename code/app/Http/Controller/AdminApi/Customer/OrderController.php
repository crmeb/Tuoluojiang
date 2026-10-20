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
use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Contract\Customer\CustomerSubscribeInterface;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Customer\OrderRequest;
use App\Http\Service\Config\FormService;
use App\Http\Service\Customer\OrderService;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 订单管理
 * Class OrderController.
 */
#[Prefix('ent/client/contracts')]
#[Resource('/', false, except: ['index', 'show'], names: [
    'create'  => '订单新增表单',
    'store'   => '新增订单保存',
    'edit'    => '订单修改表单',
    'update'  => '修改订单保存',
    'destroy' => '删除订单',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class OrderController extends AuthController
{
    use SearchTrait;

    public function __construct(OrderService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 列表数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('list', '合同订单列表')]
    public function index(): mixed
    {
        $where                = $this->request->postMore($this->service->searchField());
        $where['view_search'] = (int) $this->request->post('view_search', 2);
        $where['types']       = ViewSearchEnum::VIEW_CONTRACT;
        return $this->success($this->service->getListByType($where, auth('admin')->id()));
    }

    /**
     * 保存表单.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function create(FormService $service): mixed
    {
        $oddsId = $this->request->get('odds_id', 0);
        $eid    = $this->request->get('eid', 0);
        return $this->success($service->getFormDataWithType(CustomEnum::CONTRACT, associationId: (int) $eid, oddsId: (int) $oddsId));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function store(OrderRequest $request): mixed
    {
        $data = $request->validated();
        $res  = $this->service->saveContract($data, auth('admin')->id());
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function update(OrderRequest $request, int|string $id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        $data = $request->setExcludeId((int) $id)->validated();
        $this->service->updateContract($data, (int) $id, creatorUid: auth('admin')->id());
        return $this->success(__('common.update.succ'));
    }

    /**
     * 订单出库.
     * @throws BindingResolutionException
     */
    #[Post('outbound/{id}', '订单出库')]
    public function outbound($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        return $this->success('common.operation.succ', ['id' => $this->service->outbound((int) $id, auth('admin')->id())]);
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
        $edit = (bool) $this->request->get('edit', 0);
        return $this->success($this->service->detail((int) $id, auth('admin')->id(), ViewSearchEnum::VIEW_CONTRACT, $edit ? '' : $this->origin));
    }

    /**
     * 删除.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->deleteContract((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 列表统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('list_statistics', '合同订单列表统计')]
    public function listStatistics(): mixed
    {
        $types = $this->request->get('types', 5);
        return $this->success($this->service->getListStatistics((int) $types, $this->uuid));
    }

    /**
     * 修改关注状态
     * @throws BindingResolutionException
     */
    #[Post('subscribe/{id}/{status}', '修改合同订单关注状态')]
    public function subscribe($id, $status, CustomerSubscribeInterface $clientSubscribeService): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $clientSubscribeService->subscribe(auth('admin')->id(), (int) $id, (int) $status, CustomEnum::CONTRACT);
        return $this->success($status ? '已关注' : '已取消关注');
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('select', '合同订单下拉列表')]
    public function select(): mixed
    {
        [$eid] = $this->request->getMore([
            ['data', []],
        ], true);

        if (! $eid) {
            return $this->fail('common.empty.attrs');
        }
        return $this->success($this->service->getSelectList((array) $eid, $this->uuid));
    }

    /**
     * 异常状态
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('abnormal/{id}/{status}', '修改合同订单异常状态')]
    public function abnormal($id, $status): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->abnormal((int) $id, (int) $status, $this->uuid);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 合同订单转移.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('shift', '合同订单转移')]
    public function shift(): mixed
    {
        [$ids, $toUid, $invoice] = $this->request->postMore([
            ['data', []],
            ['to_uid', 0],
            ['invoice', 0],
        ], true);
        if (! $ids) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->shift($ids, (int) $toUid, (int) $invoice);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 导入.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('import', '合同订单导入')]
    public function import(): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::CUSTOMER);
        [$data, $uids] = $this->request->postMore([
            ['data', []],
            ['uid', []],
        ], true);
        $this->service->batchImport((array) $data, $uids);
        return $this->success('common.operation.succ');
    }
}
