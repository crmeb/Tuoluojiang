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

namespace Tuoluojiang\Open\traits\model;

/**
 * path字段序列化
 * Trait PathAttrTrait.
 * @property array $attributes
 */
trait PathAttrTrait
{
    /**
     * 格式化path字段.
     * @param mixed $value
     * @return false|string[]
     */
    public function setPathAttribute($value)
    {
        $this->attributes['path'] = $value ? '/' . implode('/', $value) . '/' : '';
    }

    /**
     * 格式化path字段.
     * @param mixed $value
     * @return false|string[]
     */
    public function getPathAttribute($value)
    {
        return $value ? array_map('intval', array_merge(array_filter(explode('/', $value)))) : [];
    }
}
