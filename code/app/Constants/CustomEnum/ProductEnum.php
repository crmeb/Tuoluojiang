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

namespace App\Constants\CustomEnum;

/**
 * 线索.
 */
final class ProductEnum extends CustomEnum
{
    /**
     * 我查看客户.
     */
    public const PRODUCT_VIEWER = 1;

    /**
     * 我负责客户.
     */
    public const PRODUCT_CHARGE = 2;

    /**
     * 公海池客户.
     */
    public const PRODUCT_HEIGHT_SEAS = 3;

    /**
     * 公司客户.
     */
    public const PRODUCT_COMPANY = 4;

    /**
     * 对外客户.
     */
    public const PRODUCT_OPEN = 5;

    public const PRODUCT_LIST_FIELD = [
        [
            'field' => 'created_at',
            'name'  => '创建时间',
        ],
    ];

    public const PRODUCT_SEARCH_FIELD = [
        [
            'field'      => 'created_at',
            'name'       => '创建时间',
            'input_type' => 'date',
        ],
    ];

    /**
     * 我负责线索列表默认字段.
     */
    public const PRODUCT_CHARGE_LIST_DEFAULT_FIELD = [
        'customer_name', 'customer_way', 'liaison_tel', 'customer_label', 'last_follow_time', 'customer_status',
        'customer_followed', 'created_at',
    ];

    /**
     * 我负责线索列表搜索字段.
     */
    public const PRODUCT_VIEWER_SEARCH_FIELD = [];

    /**
     * 我负责线索列表搜索字段.
     */
    public const PRODUCT_CHARGE_SEARCH_FIELD = [
        [
            'field'      => 'salesman',
            'name'       => '负责人',
            'input_type' => 'personnel',
        ],
    ];

    /**
     * 线索池线索列表默认搜索字段.
     */
    public const PRODUCT_CHARGE_SEARCH_DEFAULT_FIELD = [
        'customer_name', 'customer_repeat_check', 'customer_no', 'customer_label', 'liaison', 'liaison_tel',
        'customer_way', 'created_at', 'contract_name', 'contract_no',
    ];

    /**
     * 线索池线索列表默认搜索字段.
     */
    public const PRODUCT_VIEWER_LIST_DEFAULT_FIELD = [
        'uid', 'name', 'types', 'unit_name', 'number', 'description', 'spec_type', 'is_show', 'path', 'created_at',
    ];

    public const PRODUCT_VIEWER_SEARCH_DEFAULT_FIELD = [
        'name', 'path', 'created_at',
    ];

    public const PRODUCT_COMPANY_LIST_DEFAULT_FIELD = [
        'customer_name', 'customer_way', 'liaison_tel', 'customer_label', 'last_follow_time', 'customer_status',
        'customer_followed', 'salesman', 'created_at',
    ];

    public const PRODUCT_COMPANY_SEARCH_FIELD = self::PRODUCT_VIEWER_SEARCH_FIELD;

    /**
     *  线索-公司-搜索字段.
     */
    public const PRODUCT_COMPANY_SEARCH_DEFAULT_FIELD = [
        'customer_name', 'customer_repeat_check', 'salesman', 'customer_no', 'customer_label', 'liaison', 'liaison_tel',
        'customer_way', 'created_at', 'contract_name', 'contract_no',
    ];

    /**
     * 不允许删除的字段.
     */
    public const PRODUCT_NOT_ALLOW_DELETE_FIELD = [
        'name', 'pid', 'path', 'is_show', 'unit_name', 'types', 'number',
    ];

    public const PRODUCT_TYPE = [
        self::PRODUCT_VIEWER,
        self::PRODUCT_CHARGE,
        self::PRODUCT_HEIGHT_SEAS,
        self::PRODUCT_COMPANY,
    ];
}
