<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Constants\System;

use MyCLabs\Enum\Enum;

/**
 * 系统：配置项枚举.
 */
final class ViewSearchEnum extends Enum
{
    /**
     * 客户.
     */
    public const VIEW_CUSTOMER = 'customer';

    /**
     * 客户公海池.
     */
    public const VIEW_CUSTOMER_SEAS = 'customer_seas';

    /**
     * 订单.
     */
    public const VIEW_CONTRACT = 'contract';

    /**
     * 合同签约.
     */
    public const VIEW_CONTRACT_DOC = 'contract_doc';

    /**
     * 联系人.
     */
    public const VIEW_LIAISON = 'liaison';

    /**
     * 线索.
     */
    public const VIEW_CLUE = 'clue';

    /**
     * 线索池.
     */
    public const VIEW_CLUE_SEAS = 'clue_seas';

    /**
     * 商机.
     */
    public const VIEW_ODDS = 'odds';

    /**
     * 产品.
     */
    public const VIEW_PRODUCT = 'product';
}
