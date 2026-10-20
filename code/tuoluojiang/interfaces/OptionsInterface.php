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
 * 参数设置接口
 * Interface OptionsInterface.
 */
interface OptionsInterface
{
    /**
     * 对象转数组.
     */
    public function toArray(): array;

    /**
     * 获取参数.
     * @return mixed
     */
    public function get(string $key);

    /**
     * 设置参数.
     * @param mixed $value
     * @return mixed
     */
    public function set(string $key, $value);
}
