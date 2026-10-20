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

namespace App\Http\Controller\AdminApi\Work;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Work\WorkClientService;
use App\Jobs\Work\WorkClientSaveJob;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('ent/work')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkClientController extends AuthController
{
    /**
     * 客户信息.
     */
    public function __construct(WorkClientService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 同步客户信息.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('client/sync', '同步客户信息')]
    public function syncWorkClient()
    {
        WorkClientSaveJob::dispatch(1);
        return $this->success('已加入消息队列，正在异步同步中请稍后查看');
    }
}
