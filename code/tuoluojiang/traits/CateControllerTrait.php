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

/**
 * Trait CateControllerTrait.
 */
trait CateControllerTrait
{
    /**
     * Request字段获取.
     */
    protected function getRequestFields(): array
    {
        return [
            ['path', []],
            ['pid', 0],
            ['cate_name', ''],
            ['pic', ''],
            ['sort', 0],
            ['is_show', 1],
        ];
    }

    /**
     * 搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['cate_name', ''],
            ['type', ''],
            ['pid', 0],
            ['is_show', ''],
        ];
    }
}
