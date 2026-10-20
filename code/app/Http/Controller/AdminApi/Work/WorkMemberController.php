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
use App\Http\Service\Work\WorkMemberService;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 企业微信成员管理.
 */
#[Prefix('ent/work')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkMemberController extends AuthController
{
    public function __construct(WorkMemberService $services)
    {
        parent::__construct();
        $this->service = $services;
    }
}
