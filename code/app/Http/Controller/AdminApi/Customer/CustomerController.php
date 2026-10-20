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
use App\Http\Requests\Customer\CustomerRequest;
use App\Http\Requests\Customer\LiaisonRequest;
use App\Http\Service\Config\FormService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Customer\LiaisonService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\PaymentService;
use Tuoluojiang\Open\traits\SearchTrait;
use Tuoluojiang\Open\utils\Date;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 客户管理
 * Class CustomerController.
 */
#[Prefix('ent/client/customer')]
#[Resource('/', false, except: ['show', 'index'], names: [
    'create'  => '客户新增表单',
    'store'   => '新增客户',
    'edit'    => '客户修改表单',
    'update'  => '修改客户',
    'destroy' => '删除客户',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CustomerController extends AuthController
{
    use SearchTrait;

    public function __construct(CustomerService $services)
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
    #[Post('list', '客户列表')]
    public function index(): mixed
    {
        $types                = $this->request->post('types', ViewSearchEnum::VIEW_CUSTOMER);
        $where                = $this->request->postMore($this->service->searchField($types));
        $where['view_search'] = (int) $this->request->post('view_search', 1);
        if ($types == ViewSearchEnum::VIEW_CUSTOMER_SEAS) {
            $where['view_search'] = 7;
        }
        if ((int) $this->request->post('is_select', 0)) {
            $uniField = ['customer_name', 'customer_tel', 'area_cascade'];
        } else {
            $uniField = [];
        }
        return $this->success($this->service->getListByType($where, auth('admin')->id(), $uniField));
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
        $linkId       = (int) $this->request->get('link_id', 0);
        $customerForm = $service->getFormDataWithType(CustomEnum::CUSTOMER, associationId: $linkId, linkId: $linkId);
        $liaisonForm  = $service->getFormDataWithType(CustomEnum::LIAISON, associationId: $linkId, hidden: ['eid']);
        return $this->success(array_merge($customerForm, $liaisonForm));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function store(CustomerRequest $request, LiaisonRequest $liaisonRequest, LiaisonService $liaisonService): mixed
    {
        $data       = $request->validated();
        $liaison    = $liaisonRequest->setExcludeFields(['eid'])->validated();
        $customType = $this->request->post('types', ViewSearchEnum::VIEW_CUSTOMER);
        $res        = $this->service->saveCustomer($data, auth('admin')->id(), $customType, (int) $this->request->post('link_id', 0));
        $liaisonService->saveLiaison($liaison, $res->id, auth('admin')->id());
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 获取客户自定义表单是否启用订单分类.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('category_enabled', '获取客户自定义表单是否启用订单分类')]
    public function getCategoryEnabled(): mixed
    {
        return $this->success(['enabled' => app(OrderService::class)->getCategoryEnabled()]);
    }

    /**
     * 修改.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function update(CustomerRequest $request, $id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        $data = $request->setExcludeId((int) $id)->validated();
        $this->service->updateData($data, (int) $id, auth('admin')->id(), ViewSearchEnum::VIEW_CUSTOMER);
        return $this->success(__('common.update.succ'));
    }

    /**
     * 获取客户基础信息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('base/{id}', '获取客户基础信息')]
    public function baseInfo($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs', ['attr' => 'id']));
        }
        return $this->success($this->service->baseInfo((int) $id, auth('admin')->id()));
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
        return $this->success($this->service->detail((int) $id, auth('admin')->id(), ViewSearchEnum::VIEW_CUSTOMER));
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
        $this->service->deleteCustomer((int) $id, auth('admin')->id());
        return $this->success('common.delete.succ');
    }

    /**
     * 列表统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('list_statistics', '客户统计')]
    public function listStatistics(): mixed
    {
        $types = (int) $this->request->get('types', 1);
        if ($types == 3) {
            $this->request->merge([
                'uid' => 0,
            ]);
        } else {
            $scope_frame = $this->request->get('scope_frame', '');
            if (! $scope_frame) {
                switch ($types) {
                    case 1:
                        $this->request->merge([
                            'scope_frame' => 'all',
                        ]);
                        break;
                    case 2:
                        $this->request->merge([
                            'scope_frame' => 'self',
                        ]);
                        break;
                }
            }
            $this->withScopeFrame(module: ModuleEnum::CUSTOMER);
        }
        return $this->success($this->service->getListStatistics($types, auth('admin')->id(), (array) $this->request->get('uid', [])));
    }

    /**
     * 下拉数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('select', '客户下拉数据')]
    public function select(): mixed
    {
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
        ]);
        return $this->success($this->service->getSelectList($where, auth('admin')->id()));
    }

    /**
     * 流失.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('lost', '客户流失')]
    public function lost(): mixed
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);

        if (empty($data) || ! is_array($data)) {
            return $this->fail('common.empty.attrs');
        }

        $this->service->lost($data, auth('admin')->id());
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 退回.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('return', '客户退回')]
    public function return(): mixed
    {
        [$data, $reason] = $this->request->postMore([
            ['data', []],
            ['reason', ''],
        ], true);

        if (empty($data) || ! is_array($data)) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->returnHighSeas($data, $reason, auth('admin')->id());
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 修改关注状态
     * @throws BindingResolutionException
     */
    #[Post('subscribe/{id}/{status}', '修改关注状态')]
    public function subscribe($id, $status, CustomerSubscribeInterface $clientSubscribeService): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $clientSubscribeService->subscribe(auth('admin')->id(), (int) $id, (int) $status, CustomEnum::CUSTOMER);
        return $this->success($status ? '已关注' : '已取消关注');
    }

    /**
     * 取消流失.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('cancel_lost/{id}', '取消流失')]
    public function cancelLost($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->cancelLost((int) $id, auth('admin')->id());
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 合并客户数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('merge', '合并客户数据')]
    public function toMerge(): mixed
    {
        [$ids, $mainId] = $this->request->postMore([
            ['ids', []],
            ['main_id', 0],
        ], true);
        $this->service->toMergeCustomer((int) $mainId, (array) $ids, auth('admin')->id());
        return $this->success('合并成功');
    }

    /**
     * 负责人.
     */
    #[Get('salesman', '客户负责人')]
    public function salesman(): mixed
    {
        return $this->success($this->service->getSalesman(auth('admin')->id()));
    }

    /**
     * 领取.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('claim', '客户领取')]
    public function claim(): mixed
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);
        if (empty($data) || ! is_array($data)) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->claim($data, auth('admin')->id());
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 批量设置标签.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('label', '客户批量设置标签')]
    public function label(): mixed
    {
        [$data, $label] = $this->request->postMore([
            ['data', []],
            ['label', []],
        ], true);
        $this->service->label((array) $data, (array) $label);
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 客户转移.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('shift', '客户转移')]
    public function shift(): mixed
    {
        [$data, $toUid, $invoice, $contract] = $this->request->postMore([
            ['data', []],
            ['to_uid', 0],
            ['invoice', 0],
            ['contract', 0],
        ], true);
        if (! $data) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->shift((array) $data, (int) $toUid, (int) $invoice, (int) $contract, auth('admin')->id());
        return $this->success(__('common.operation.succ'));
    }

    /**
     * 业绩统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('statistics', '客户业绩统计')]
    public function statistics(): mixed
    {
        $this->withScopeFrame(normal: false, module: ModuleEnum::CUSTOMER);
        [$time, $userIds, $categoryIds] = $this->request->postMore([
            ['time', ''],
            ['uid', []],
            ['category_id', []],
        ], true);
        $data = $this->service->getStatistics($time, (array) $userIds, (array) $categoryIds);
        return $this->success($data);
    }

    /**
     * 合同订单类型分析统计.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('contract_rank', '合同订单类型分析统计')]
    public function contractRank(OrderService $contractService): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::CUSTOMER, normal: false);
        [$time, $categoryIds, $categoryId, $userIds] = $this->request->postMore([
            ['time', ''],
            ['category_id', []],
            ['category', 0],
            ['uid', []],
        ], true);

        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        $data                     = $contractService->getCategoryRank($searchTime, (array) $userIds, (array) $categoryIds, (int) $categoryId);
        return $this->success($data);
    }

    /**
     * 产品分类业绩统计.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('product_rank', '产品分类业绩统计')]
    public function productRank(OrderService $contractService): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::CUSTOMER);
        [$time, $categoryIds, $categoryId, $userIds] = $this->request->postMore([
            ['time', ''],
            ['category_id', []],
            ['category', 0],
            ['uid', []],
        ], true);

        [$searchTime, $ratioTime] = Date::ringRatioTime($time);
        $data                     = $contractService->getProductCategoryRank($searchTime, (array) $userIds, (array) $categoryIds, (int) $categoryId);
        return $this->success($data);
    }

    /**
     * 产品业绩排行列表（按规格维度，带分页）.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('product_rank_list', '产品业绩排行列表')]
    public function productRankList(OrderService $contractService): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::CUSTOMER);
        [$time, $categoryIds, $userIds] = $this->request->postMore([
            ['time', ''],
            ['category_id', []],
            ['uid', []],
        ], true);

        [$searchTime] = Date::ringRatioTime($time);
        $data         = $contractService->getProductRankList($searchTime, (array) $userIds, (array) $categoryIds);
        return $this->success($data);
    }

    /**
     * 负责人业绩排行榜.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('ranking', '业务员业绩排行榜')]
    public function ranking(): mixed
    {
        $this->withScopeFrame(normal: false, module: ModuleEnum::CUSTOMER);
        [$time, $categoryIds, $userIds] = $this->request->postMore([
            ['time', ''],
            ['category_id', []],
            ['uid', []],
        ], true);

        $data = $this->service->getRanking($time, (array) $userIds, (array) $categoryIds);
        return $this->success($data);
    }

    /**
     * 业务员业绩排行榜.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('frame_ranking', '部门业绩排行榜')]
    public function frameRanking(): mixed
    {
        $this->withScopeFrame(normal: false, module: ModuleEnum::CUSTOMER);
        [$time, $categoryIds, $userIds] = $this->request->postMore([
            ['time', ''],
            ['category_id', []],
            ['uid', []],
        ], true);

        $data = $this->service->getFrameRanking($time, (array) $userIds, (array) $categoryIds);
        return $this->success($data);
    }

    /**
     * 业绩统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('trend_statistics', '业绩趋势统计')]
    public function trendStatistics(PaymentService $billService): mixed
    {
        $this->withScopeFrame(normal: false, module: ModuleEnum::CUSTOMER);
        [$time, $categoryIds, $userIds] = $this->request->postMore([
            ['time', ''],
            ['category_id', []],
            ['uid', []],
        ], true);
        $data = $billService->getTrendStatistics($time, (array) $userIds, (array) $categoryIds);
        return $this->success($data);
    }

    /**
     * 导入.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('import', '客户导入')]
    public function import(): mixed
    {
        $this->withScopeFrame(module: ModuleEnum::CUSTOMER);
        [$data, $uids] = $this->request->postMore([
            ['data', []],
            ['uid', []],
        ], true);
        $this->service->batchImport((array) $data, $uids, auth('admin')->id());
        return $this->success('common.operation.succ');
    }

    /**
     * 协作者保存.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('member/{id}', '客户协作者保存')]
    public function member($id)
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);
        $this->service->saveMember((int) $id, $data, auth('admin')->id());
        return $this->success('common.operation.succ');
    }
}
