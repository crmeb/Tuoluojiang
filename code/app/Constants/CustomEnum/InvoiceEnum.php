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

namespace App\Constants\CustomEnum;

use MyCLabs\Enum\Enum;

/**
 * 发票相关枚举.
 */
final class InvoiceEnum extends Enum
{
    /**
     * 发票状态：待审核.
     */
    public const STATUS_AUDIT = 0;

    /**
     * 发票状态：开票审核通过(待开票).
     */
    public const STATUS_APPROVED = 1;

    /**
     * 发票状态：开票审核拒绝.
     */
    public const STATUS_REJECT = 2;

    /**
     * 发票状态：撤回开票审核.
     */
    public const STATUS_REVOKE = 3;

    /**
     * 发票状态：发票已冲红.
     */
    public const STATUS_CANCEL = -1;

    /**
     * 发票状态：申请冲红中.
     */
    public const STATUS_APPLY_CANCEL = 4;

    /**
     * 发票状态：已开票.
     */
    public const STATUS_INVOICED = 5;
}
