<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Middleware;

use App\Constants\ModuleEnum;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 模块开关中间件
 * 检查子模块是否被禁用
 * Class ModuleSwitchMiddleware.
 */
class ModuleSwitchMiddleware
{
    /**
     * 处理请求.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $path = $request->path();
        // 获取路由对应的子模块
        $subModule = ModuleEnum::getSubModuleByRoute(preg_replace('#^api/(ent|uni)/#', '', $path));
        if ($subModule) {
            $configKey = $subModule['config_key'];
            $switch    = (int) sys_config($configKey);
            if (! $switch) {
                // 模块被禁用，返回错误响应
                return response()->json([
                    'status' => 403,
                    'msg'    => $subModule['name'] . '已禁用,页面即将更新',
                    'data'   => [],
                ]);
            }
        }

        return $next($request);
    }
}
