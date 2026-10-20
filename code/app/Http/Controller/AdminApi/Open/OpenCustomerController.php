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

namespace App\Http\Controller\AdminApi\Open;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthOpenApi;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\CustomerService;
use App\Http\Service\Config\FormService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

#[Prefix('open/customer')]
#[Resource('/', false, except: ['show', 'create', 'index', 'edit'], names: [
    'store'   => '保存客户接口',
    'update'  => '更新客户接口',
    'destroy' => '删除客户接口',
], parameters: ['' => 'id'])]
#[Middleware([AuthOpenApi::class])]
class OpenCustomerController extends AuthController
{
    public function __construct(CustomerService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 保存客户.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function store(FormService $service, AdminService $adminService): mixed
    {
        $data = $this->request->postMore($service->getRequestFields(CustomEnum::CUSTOMER));
        $uid  = (int) $this->request->post('uid', 0);
        if ($uid && ! $adminService->exists(['id' => $uid, 'status' => 1])) {
            return $this->fail('业务员不存在');
        }
        $res = $this->service->saveCustomer($data, $uid, ViewSearchEnum::VIEW_CUSTOMER, 1);
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改客户.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function update($id, FormService $service): mixed
    {
        if (! $id) {
            return $this->fail($this->message['update']['empty']);
        }
        $data = $this->request->postMore($service->getRequestFields(CustomEnum::CUSTOMER));
        $this->service->updateCustomer($data, (int) $id, 1);
        return $this->success(__('common.update.succ'));
    }

    /**
     * 删除客户.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attr', ['attr' => '客户ID']));
        }
        $uid = (int) $this->request->post('uid', 0);
        $this->service->deleteCustomer((int) $id, $uid);
        return $this->success('common.delete.succ');
    }
}
