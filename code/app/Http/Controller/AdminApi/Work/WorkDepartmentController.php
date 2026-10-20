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
use App\Http\Service\Work\WorkDepartmentService;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('ent/work')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkDepartmentController extends AuthController
{
    public function __construct(WorkDepartmentService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 同步部门信息.
     * @return mixed
     */
    #[Get('department/sync', '同步部门信息')]
    public function syncWorkDepartment()
    {
        $this->service->authDepartment();
        return $this->success('同步成功');
    }
}
