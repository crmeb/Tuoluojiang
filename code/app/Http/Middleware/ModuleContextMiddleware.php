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
use App\Http\Context\DataPermissionContext;
use App\Http\Service\System\ModulePermissionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 模块上下文中间件
 * 从路由识别模块，获取数据权限上下文.
 */
class ModuleContextMiddleware
{
    /**
     * 路由模块映射
     * key: 路由路径前缀
     * value: ModuleEnum 中的模块名.
     */
    protected static array $routeModuleMap = [
        'api/ent/client'      => ModuleEnum::CUSTOMER,
        'api/ent/customer'    => ModuleEnum::CUSTOMER,
        'api/ent/attendance'  => ModuleEnum::ATTENDANCE,
        'api/ent/assess'      => ModuleEnum::ASSESS,
        'api/ent/daily'       => ModuleEnum::REPORT,
        'api/ent/schedule'    => ModuleEnum::SCHEDULE,
        'api/ent/work_report' => ModuleEnum::REPORT,
        'api/ent/program'     => ModuleEnum::PROGRAM,
        'api/ent/program_task' => ModuleEnum::PROGRAM,
    ];

    public function __construct() {}

    /**
     * 处理请求
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $path   = $request->path();
        $module = $this->identifyModule($path);

        if ($module && ($userId = auth('admin')->id())) {
            app()->get(ModulePermissionService::class)->hydrateDataPermissionContext($userId, $module);
        }

        try {
            return $next($request);
        } finally {
            DataPermissionContext::clear();
        }
    }

    /**
     * 从路由路径识别模块.
     */
    protected function identifyModule(string $path): ?string
    {
        foreach (self::$routeModuleMap as $routePrefix => $module) {
            if (str_starts_with($path, $routePrefix)) {
                return $module;
            }
        }

        return null;
    }
}
