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

namespace Tuoluojiang\Open\utils;

use Illuminate\Contracts\Auth\UserProvider;
use Tymon\JWTAuth\Providers\Auth\Illuminate;

/**
 * Class JWTIlluminate.
 */
class JWTIlluminate extends Illuminate
{
    /**
     * Set the user provider used by the guard.
     *
     * @return $this
     */
    public function setProvider(UserProvider $provider)
    {
        $this->auth->setProvider($provider);
        unset($provider);
        return $this;
    }
}
