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

use Tuoluojiang\Open\exceptions\WebOfficeException;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class WebOfficeAuthUser implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    public function before(Request $request)
    {
        $token  = $request->header('x-wps-weboffice-token');
        $fileId = $request->header('x-weboffice-file-id');
        if (! $token) {
            throw new WebOfficeException(__('缺少身份验证token'));
        }
        app('tymon.jwt.provider.auth')->setProvider(Auth::createUserProvider('admin'));
        try {
            $userInfo = JWTAuth::setToken($token)->authenticate();
        } catch (TokenExpiredException $e) {
            throw new WebOfficeException(__('Login expired, please login again'), 40002);
        } catch (JWTException|TokenInvalidException|UnauthorizedHttpException $e) {
            throw new WebOfficeException(__($e->getMessage()), 40001);
        }

        if (! isset($userInfo) || ! $userInfo) {
            throw new WebOfficeException(__('user does not exist'), 40002);
        }

        $request->macro('uuId', function () use ($userInfo) {
            return $userInfo->uid;
        });
        $request->macro('userInfo', function (?string $key = null) use ($userInfo) {
            $userInfo = $userInfo->toArray();
            if ($key) {
                return $userInfo[$key] ?? null;
            }
            return $userInfo;
        });
        $request->macro('fileId', function () use ($fileId) {
            return $fileId;
        });
    }

    public function after($response) {}
}
