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

namespace App\Constants\Crud;

use MyCLabs\Enum\Enum;

/**
 * 低代码：自动修改触发器.
 */
final class CrudUpdateEnum extends Enum
{
    // 字段值
    public const UPDATE_TYPE_FIELD = 'field_value';

    // 固定值
    public const UPDATE_TYPE_VALUE = 'value';

    // 置空
    public const UPDATE_TYPE_NULL_VALUE = 'null_value';

    //公式计算
    public const UPDATE_TYPE_FORMULA_VALUE = 'formula_value';

    //覆盖
    public const UPDATE_TYPE_COVER_VALUE = 'cover_value';
    public const UPDATE_TYPE_SKIP_VALUE  = 'skip_value';
}
