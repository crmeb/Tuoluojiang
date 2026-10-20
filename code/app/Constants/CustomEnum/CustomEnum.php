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

namespace App\Constants\CustomEnum;

/**
 * 自定义表单.
 */
class CustomEnum
{
    public const LIST_SELECT = 'list_select';

    public const SEARCH_SELECT = 'search_select';

    /**
     * 修改.
     */
    public const OPERATE_CHANGE = 10;

    /**
     * 客户.
     */
    public const CUSTOMER = 1;

    /**
     * 订单.
     */
    public const CONTRACT = 2;

    /**
     * 联系人.
     */
    public const LIAISON = 3;

    /**
     * 线索.
     */
    public const CLUE = 4;

    /**
     * 商机.
     */
    public const ODDS = 5;

    /**
     * 产品.
     */
    public const PRODUCT = 6;

    /**
     * 合同签约.
     */
    public const DOC = 7;

    /**
     * 列表场景.
     */
    public const SCENE_LIST = 1;

    /**
     * 详情场景.
     */
    public const SCENE_INFO = 2;

    /**
     * 修改场景.
     */
    public const SCENE_EDIT = 3;

    /**
     * 变更类型,关联跟进.
     */
    public const LINK_FOLLOW = 0;

    public const PRODUCT_PARAMS = ['unique', 'price', 'count', 'discount', 'total_price', 'ot_price', 'sku', 'remark'];
}
