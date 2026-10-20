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

use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

/**
 * 语言包切换
 * Class LangUage.
 */
class LangUage extends BaseMiddleware implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    /**
     * @return mixed|void
     */
    public function before(Request $request)
    {
        $lang = $request->cookies->get('language') ?: $request->header('laravel_lang');
        if ($lang && in_array($lang, array_keys(config('app.locales')))) {
            App::setLocale($lang);
        } else {
            App::setLocale(config('app.locale'));
        }
    }

    public function after($response)
    {
        // TODO: Implement after() method.
    }
}
