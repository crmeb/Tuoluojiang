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

namespace Tuoluojiang\Open\interfaces;

/**
 * api Request接口类
 * Interface ApiRequestInterface.
 */
interface ApiRequestInterface
{
    /**
     * 获取POST参数.
     */
    public function getMore(array $params, ?bool $suffix = null): array;

    /**
     * 获取GET参数.
     */
    public function postMore(array $params, ?bool $suffix = null): array;
}
