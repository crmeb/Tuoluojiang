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

namespace App\Http\Middleware;

use App\Http\Services\system\auth\SystemAdminServices;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\services\CoreBusinessService;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;

/**
 * Class CheckRuleAdmin.
 */
class CheckRuleAdmin implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    /**
     * 核心业务服务
     */
    protected CoreBusinessService $coreService;

    public function __construct(CoreBusinessService $coreService)
    {
        $this->coreService = $coreService;
    }

    /**
     * 前置.
     * @return mixed|void
     */
    public function before(Request $request)
    {
        $uri = $request->route()->uri();
        $adminInfo = $request->adminInfo();

        if ($adminInfo && isset($adminInfo['level']) && $adminInfo['level']) {
            // 使用核心服务进行权限验证
            if (!$this->coreService->validateAdminPermission($uri, $adminInfo, $request->method())) {
                abort(403, '权限验证失败');
            }

            /** @var SystemAdminServices $service */
            $service = app()->get(SystemAdminServices::class);
            $service->checkAuth($uri, $adminInfo, $request->method());
        }
    }

    /**
     * 后置.
     * @return mixed|void
     */
    public function after($response)
    {
        // TODO: Implement after() method.
    }
}
