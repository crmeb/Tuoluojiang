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
use App\Http\Requests\Customer\OpportunityRequest;
use App\Http\Service\Config\FormService;
use App\Http\Service\Customer\OpportunityService;
use App\Http\Service\Customer\SubscribeService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 商机管理
 * Class OpportunityController.
 */
#[Prefix('ent/client/odds')]
#[Resource('/', false, except: ['index', 'show'], names: [
    'create'  => '商机新增表单',
    'store'   => '新增商机',
    'edit'    => '商机修改表单',
    'update'  => '修改商机',
    'destroy' => '删除商机',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log', 'module.switch'])]
class OpportunityController extends AuthController
{
    public function __construct(OpportunityService $services)
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
    #[Post('list', '商机列表')]
    public function index(): mixed
    {
        $where                = $this->request->postMore($this->service->searchField());
        $where['view_search'] = (int) $this->request->post('view_search', 2);
        $where['odds_types']  = $this->request->post('types', '');
        $where['types']       = ViewSearchEnum::VIEW_ODDS;
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
        return $this->success($service->getFormDataWithType(CustomEnum::ODDS, associationId: (int) $this->request->get('eid', 0)));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function store(OpportunityRequest $request): mixed
    {
        $data = $request->validated();
        $res  = $this->service->saveOdds($data, auth('admin')->id());
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
    public function update(OpportunityRequest $request, $id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        $data = $request->setExcludeId((int) $id)->validated();
        $this->service->updateData($data, (int) $id, auth('admin')->id(), ViewSearchEnum::VIEW_ODDS);
        return $this->success(__('common.update.succ'));
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
        return $this->success($this->service->detail((int) $id, auth('admin')->id(), ViewSearchEnum::VIEW_ODDS));
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
        $this->service->deleteOdds((int) $id);
        return $this->success('common.delete.succ');
    }

    /**
     * 修改关注状态
     * @throws BindingResolutionException
     */
    #[Post('subscribe/{id}/{status}', '修改商机关注状态')]
    public function subscribe(SubscribeService $subscribeService, $id, $status): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $subscribeService->subscribe(auth('admin')->id(), (int) $id, (int) $status, CustomEnum::ODDS);
        return $this->success($status ? '已关注' : '已取消关注');
    }

    /**
     * 修改状态
     * @throws BindingResolutionException
     */
    #[Post('status/{id}/{status}', '修改商机状态')]
    public function updateStatus($id, $status): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->updateStatus(auth('admin')->id(), (int) $id, (int) $status);
        return $this->success('操作成功');
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('select', '商机下拉列表')]
    public function select(): mixed
    {
        [$eid] = $this->request->getMore([
            ['data', []],
        ], true);

        if (! $eid) {
            return $this->fail('common.empty.attrs');
        }
        return $this->success($this->service->getSelectList((array) $eid, auth('admin')->id()));
    }

    /**
     * 商机转移.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('shift', '商机转移')]
    public function shift(): mixed
    {
        [$ids, $toUid, $contract,$invoice] = $this->request->postMore([
            ['data', []],
            ['to_uid', 0],
            ['contract', 0],
            ['invoice', 0],
        ], true);
        if (! $ids) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->shift($ids, auth('admin')->id(), (int) $toUid, (int) $contract, (int) $invoice);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 导入.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('import', '商机导入')]
    public function import(): mixed
    {
        [$data, $uids] = $this->request->postMore([
            ['data', []],
            ['uid', []],
        ], true);
        $this->service->batchImport((array) $data, $uids);
        return $this->success('common.operation.succ');
    }
}
