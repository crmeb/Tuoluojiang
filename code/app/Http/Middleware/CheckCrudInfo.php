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

namespace App\Http\Middleware;

use App\Http\Service\Crud\SystemCrudService;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;

/**
 * 验证实体中间件.
 */
class CheckCrudInfo implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    /**
     * 业务中间件.
     * @param Request $request
     * @throws BindingResolutionException
     */
    public function before($request)
    {
        $name = $request->route()->parameter('name');
        if ($name) {
            $request->crudInfo = app()->get(SystemCrudService::class)->getCrudInfoCache($name);
        }
    }

    public function after($response) {}
}
