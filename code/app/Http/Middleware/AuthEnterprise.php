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

use App\Http\Service\Company\CompanyService;
use App\Http\Service\System\RolesService;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企业
 * Class AuthEnterprise.
 */
class AuthEnterprise implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    /**
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function before(Request $request)
    {
        $entInfo = app()->get(CompanyService::class)->getEntInfo(1);

        $request->macro('entId', function () {
            return 1;
        });
        $request->macro('isEnt', function () {
            return true;
        });
        $request->macro('entInfo', function (?string $key = null) use ($entInfo) {
            if ($key) {
                return $entInfo[$key] ?? null;
            }
            return $entInfo;
        });
        $uri = $request->route()->uri();
        app()->get(RolesService::class)->checkAuth($uri, $request->userInfo(), $request->entInfo() ?: [], $request->method());
    }

    public function after($response)
    {
        // TODO: Implement after() method.
    }
}
