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

use App\Constants\CustomEnum\LiaisonEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthOpenApi;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\LiaisonService;
use App\Http\Service\Config\FormService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

#[Prefix('open/liaison')]
#[Resource('/', false, except: ['show', 'create', 'index', 'edit'], names: [
    'store'   => '保存联系人接口',
    'update'  => '更新联系人接口',
    'destroy' => '删除联系人接口',
], parameters: ['' => 'id'])]
#[Middleware([AuthOpenApi::class])]
class OpenLiaisonController extends AuthController
{
    public function __construct(LiaisonService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 保存联系人.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function store(FormService $service, AdminService $adminService): mixed
    {
        $eid = (int) $this->request->post('eid', 0);
        if ($eid < 1) {
            return $this->fail('请选择关联客户');
        }

        $data = $this->request->postMore($service->getRequestFields(LiaisonEnum::LIAISON));
        $uid  = (int) $this->request->post('uid', 0);
        if ($uid && ! $adminService->exists(['id' => $uid, 'status' => 1])) {
            return $this->fail('业务员不存在');
        }
        $res = $this->service->saveLiaison($data, $eid, $uid);
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改联系人.
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
        $data = $this->request->postMore($service->getRequestFields(LiaisonEnum::LIAISON));
        $this->service->updateLiaison($data, (int) $id);
        return $this->success(__('common.update.succ'));
    }

    /**
     * 删除联系人.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $this->service->deleteLiaison((int) $id, 0);
        return $this->success('common.delete.succ');
    }
}
