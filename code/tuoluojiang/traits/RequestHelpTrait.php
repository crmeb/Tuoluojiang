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

namespace Tuoluojiang\Open\traits;

use Tuoluojiang\Open\utils\Arr;

/**
 * Trait RequestHelpTrait.
 */
trait RequestHelpTrait
{
    /**
     * 获取POST请求的数据.
     */
    public function postMore(array $params, ?bool $suffix = null): array
    {
        return Arr::more($this->request(), $params, $suffix);
    }

    /**
     * 获取GET请求的数据.
     */
    public function getMore(array $params, ?bool $suffix = null): array
    {
        return Arr::more($this->request(), $params, $suffix, 'get');
    }

    /**
     * @return mixed
     */
    abstract protected function request();
}
