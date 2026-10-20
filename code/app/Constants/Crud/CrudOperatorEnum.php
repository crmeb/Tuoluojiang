<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Constants\Crud;

use MyCLabs\Enum\Enum;

/**
 * 低代码：条件判断
 * Class CrudOperatorEnum.
 * @email 136327134@qq.com
 * @date 2024/3/7
 */
final class CrudOperatorEnum extends Enum
{
    /**
     * 包含.
     */
    public const OPERATOR_IN = 'in';

    /**
     * 不包含.
     */
    public const OPERATOR_NOT_IN = 'not_in';

    /**
     * 等于.
     */
    public const OPERATOR_EQ = 'eq';

    /**
     * 大于.
     */
    public const OPERATOR_GT = 'gt';

    /**
     * 大于等于.
     */
    public const OPERATOR_GT_EQ = 'gt_eq';

    /**
     * 小于.
     */
    public const OPERATOR_LT = 'lt';

    /**
     * 小于等于.
     */
    public const OPERATOR_LT_EQ = 'lt_eq';

    /**
     * 不等于.
     */
    public const OPERATOR_NOT_EQ = 'not_eq';

    /**
     * 为空.
     */
    public const OPERATOR_IS_EMPTY = 'is_empty';

    /**
     * 不为空.
     */
    public const OPERATOR_NOT_EMPTY = 'not_empty';

    /**
     * 区间.
     */
    public const OPERATOR_BT = 'between';

    /**
     * N天前.
     */
    public const OPERATOR_N_DAY = 'n_day';

    /**
     * 最近N天.
     */
    public const OPERATOR_LAST_DAY = 'last_day';

    /**
     * 未来N天.
     */
    public const OPERATOR_NEXT_DAY = 'next_day';

    /**
     * 今天.
     */
    public const OPERATOR_TO_DAY = 'today';

    /**
     * 本周.
     */
    public const OPERATOR_WEEK = 'week';

    /**
     * 本月.
     */
    public const OPERATOR_MONTH = 'month';

    /**
     * 本季度.
     */
    public const OPERATOR_QUARTER = 'quarter';

    /**
     * 去年.
     */
    public const OPERATOR_LAST_YEAR = 'last_year';

    /**
     * 本年.
     */
    public const OPERATOR_YEAR = 'year';

    /**
     * 正则表达式.
     */
    public const OPERATOR_REGEX = 'regex';

    /**
     * 模糊查询.
     */
    public const OPERATOR_LIKE = 'like';
}
