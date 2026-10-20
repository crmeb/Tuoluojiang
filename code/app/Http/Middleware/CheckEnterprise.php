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

use Tuoluojiang\Open\exceptions\EntException;
use Tuoluojiang\Open\interfaces\ApiMiddlewareInterface;
use Tuoluojiang\Open\traits\MiddlewareTrait;
use Illuminate\Http\Request;

/**
 * 检测企业是否存在
 * Class CheckEnterprise.
 */
class CheckEnterprise implements ApiMiddlewareInterface
{
    use MiddlewareTrait;

    /**
     * 前置事件.
     * @return mixed
     */
    public function before(Request $request)
    {
        if (! $request->hasMacro('isEnt') || ! $request->hasMacro('entInfo')) {
            throw new EntException('您没有权限操作', 410005);
        }
        if (! $request->entInfo()) {
            throw new EntException('企业不存在', 410005);
        }
        if (! $request->entInfo('status')) {
            throw new EntException('企业已被禁用无法使用', 410005);
        }
    }

    /**
     * 后置中间件.
     * @return mixed
     */
    public function after($response)
    {
        // TODO: Implement after() method.
    }
}
