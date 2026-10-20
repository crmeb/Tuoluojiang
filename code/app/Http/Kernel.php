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

namespace App\Http;

// ============================================================================
// 自定义应用中间件（App\Http\Middleware\*）
// ============================================================================
use App\Http\Middleware\AuthAdmin;              // 后台管理员认证
use App\Http\Middleware\AuthEnterprise;          // 企业微信认证
use App\Http\Middleware\Authenticate;          // 通用认证
use App\Http\Middleware\CheckCrudInfo;          // CRUD 检查
use App\Http\Middleware\ConvertEmptyStringsToNull; // 模块上下文中间件
use App\Http\Middleware\EncryptCookies; // 模块开关中间件
use App\Http\Middleware\FilterStrings; // 空字符串转 null
use App\Http\Middleware\LangUage;         // Cookie 加密
use App\Http\Middleware\LogEnterprise;          // 字符串过滤
use App\Http\Middleware\ModuleContextMiddleware;              // 多语言处理
use App\Http\Middleware\ModuleSwitchMiddleware;         // 企业日志
use App\Http\Middleware\PreventRequestsDuringMaintenance; // 维护模式拦截
use App\Http\Middleware\RedirectIfAuthenticated; // 已认证重定向
use App\Http\Middleware\SlowRequestLogger; // 慢请求日志
use App\Http\Middleware\TrustProxies;          // 信任代理
use App\Http\Middleware\McpAuthMiddleware;              // MCP认证
use App\Http\Middleware\AiGuestAuth;
// ============================================================================
// Laravel 框架核心中间件
// ============================================================================
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize; // Basic 认证
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;        // 授权检查
use Illuminate\Auth\Middleware\RequirePassword; // 邮箱验证
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;  // 密码确认
use Illuminate\Foundation\Http\Kernel as HttpKernel; // Cookie 队列
use Illuminate\Foundation\Http\Middleware\ValidatePostSize; // HTTP 内核基类
use Illuminate\Http\Middleware\HandleCors; // POST 大小验证
use Illuminate\Http\Middleware\SetCacheHeaders;  // 缓存头设置
use Illuminate\Routing\Middleware\SubstituteBindings; // 路由模型绑定
use Illuminate\Routing\Middleware\ThrottleRequests; // 请求限流
use Illuminate\Routing\Middleware\ValidateSignature; // 签名验证
use Illuminate\Session\Middleware\StartSession;  // Session 启动
use Illuminate\View\Middleware\ShareErrorsFromSession; // 视图错误共享

class Kernel extends HttpKernel
{
    /**
     * 全局中间件栈.
     *
     * 这些中间件在每个请求时都会执行
     *
     * @var array
     */
    protected $middleware = [
        TrustProxies::class,                    // 信任代理（防止 IP 伪造）
        HandleCors::class,                      // 处理跨域请求
        PreventRequestsDuringMaintenance::class, // 维护模式拦截
        ValidatePostSize::class,                // 验证 POST 数据大小
        FilterStrings::class,                   // 字符串过滤（XSS 防护）
        ConvertEmptyStringsToNull::class,       // 空字符串转 null
        LangUage::class,                        // 多语言处理
    ];

    /**
     * 中间件组.
     *
     * @var array
     */
    protected $middlewareGroups = [
        // Web 路由组 - 需要 Session 和 Cookie 的页面
        'web' => [
            EncryptCookies::class,           // Cookie 加密
            AddQueuedCookiesToResponse::class, // 添加 Cookie 到响应
            StartSession::class,             // 启动 Session
            ShareErrorsFromSession::class,   // 共享错误到视图
            SubstituteBindings::class,        // 路由模型绑定
        ],

        // API 路由组 - 无状态的 API 接口
        'api' => [
            SubstituteBindings::class,        // 路由模型绑定
            SlowRequestLogger::class,         // 慢接口观测（默认关闭）
            'module.context',                 // 数据权限中间件 - 自动过滤模块数据权限
        ],
    ];

    /**
     * 路由中间件别名.
     *
     * 这些中间件可以分配到路由组或单独使用
     *
     * @var array
     */
    protected $routeMiddleware = [
        // ============================================================================
        // Laravel 框架中间件
        // ============================================================================
        'auth'             => Authenticate::class,              // 通用认证
        'auth.basic'       => AuthenticateWithBasicAuth::class, // Basic 认证
        'cache.headers'    => SetCacheHeaders::class,          // 缓存头设置
        'can'              => Authorize::class,                 // 权限检查
        'guest'            => RedirectIfAuthenticated::class,   // 已认证重定向
        'password.confirm' => RequirePassword::class,          // 密码确认
        'signed'           => ValidateSignature::class,         // URL 签名验证
        'throttle'         => ThrottleRequests::class,         // 请求限流
        'verified'         => EnsureEmailIsVerified::class,     // 邮箱验证

        // ============================================================================
        // 自定义业务中间件
        // ============================================================================
        'auth.admin'     => AuthAdmin::class,                // 后台管理员认证
        'ent.log'        => LogEnterprise::class,            // 企业微信日志
        'ent.auth'       => AuthEnterprise::class,           // 企业微信认证
        'ent.crud'       => CheckCrudInfo::class,            // CRUD 检查
        'module.context' => ModuleContextMiddleware::class, // 模块上下文
        'module.switch'  => ModuleSwitchMiddleware::class, // 模块开关
        'mcp.auth'       => McpAuthMiddleware::class,      // MCP认证
        'ai.guest'       => AiGuestAuth::class,            // AI外部访客
    ];
}
