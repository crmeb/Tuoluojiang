<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Controller\AdminApi\Company;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\User\UserCardPerfectService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 完善个人信息.
 */
#[Prefix('ent/user/perfect')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CompanyPerfectController extends AuthController
{
    public function __construct(UserCardPerfectService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 邀请记录列表.
     */
    #[Get('index', '邀请记录列表')]
    public function list(): mixed
    {
        $where = $this->request->getMore([
            ['user_id', auth('admin')->id()],
            ['status', [1, 2]],
            ['total', [0, 1, -1]],
        ]);
        $data = $this->service->getList($where, ['*'], 'status', [
            'enterprise' => fn ($query) => $query->select(['id', 'enterprise_name', 'logo']),
        ]);
        return $this->success($data);
    }

    /**
     * 同意操作.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('agree/{id}', '同意发送个人资料')]
    public function agree(AdminService $service, $id): mixed
    {
        if (! $id) {
            return $this->fail('缺少邀请记录ID');
        }
        $service->agreePerfect((int) $id, auth('admin')->id());
        return $this->success('操作成功');
    }

    /**
     * 拒绝操作.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Put('refuse/{id}', '拒绝发送个人资料')]
    public function refuse(AdminService $service, $id): mixed
    {
        if (! $id) {
            return $this->fail('缺少邀请记录ID');
        }
        $service->refusePerfect($id);
        return $this->success('操作成功');
    }
}
