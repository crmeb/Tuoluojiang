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

use App\Http\Service\Crud\SystemCrudQuestionnaireService;
use Tuoluojiang\Open\exceptions\AdminException;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

/**
 * 后台授权登录
 * Class AuthAdmin.
 */
class AuthAdmin implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    /**
     * 前置事件.
     */
    public function before(Request $request)
    {
        try {
            $admin = auth('admin')->userOrFail();
        } catch (TokenExpiredException) {
            $admin = $this->resolveQuestionnaireUser($request);
        } catch (\Exception) {
            $admin = $this->resolveQuestionnaireUser($request);
        }

        if (isset($admin)) {
            $request->macro('admin', $admin);
            $request->macro('userInfo', function (?string $key = null) use ($admin) {
                $admin = $admin->toArray();
                if ($key) {
                    return $admin[$key] ?? null;
                }
                return $admin;
            });
            $request->macro('uuId', function () use ($admin) {
                return $admin->uid;
            });
        }
    }

    /**
     * 后置事件.
     * @param mixed $response
     * @return mixed|void
     */
    public function after($response)
    {
        $this->request = null;
    }

    /**
     * 兼容调查问卷匿名访问；普通登录失效统一交给前端 refresh_token 续期.
     */
    private function resolveQuestionnaireUser(Request $request)
    {
        $userInfo = app()->get(SystemCrudQuestionnaireService::class)->checkUniqueisLogin($request);
        if (is_bool($userInfo)) {
            throw new AdminException('Login expired, please login again', 410003);
        }
        return $userInfo;
    }
}
