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

namespace App\Http\Controller\AdminApi\Module;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Middleware\LogEnterprise;
use App\Http\Service\Crud\SystemCrudEventLogService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('ent/crud')]
#[Middleware([AuthAdmin::class, AuthEnterprise::class, LogEnterprise::class])]
class CrudEventLogController extends AuthController
{
    public function __construct(SystemCrudEventLogService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('event/log')]
    public function index()
    {
        $where = $this->request->getMore([
            ['name', ''],
            ['crud_id', ''],
        ]);

        return $this->success($this->service->getLogList($where));
    }
}
