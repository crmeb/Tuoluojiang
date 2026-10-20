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
 * 商机.
 */
final class OddsEnum extends CustomEnum
{
    /**
     * 新增商机.
     */
    public const OPERATE_CREATE = 1;

    /**
     * 修改商机.
     */
    public const OPERATE_UPDATE = 2;

    /**
     * 转移.
     */
    public const OPERATE_SHIFT = 6;

    public const ODDS_LIST_FIELD = [
        [
            'field'      => 'odds_no',
            'name'       => '商机编号',
            'input_type' => 'input',
        ],
        [
            'field'      => 'work_customer',
            'name'       => '企微客户',
            'input_type' => 'text',
        ],
        [
            'field'      => 'salesman',
            'name'       => '负责人',
            'input_type' => 'personnel',
        ],
        [
            'field' => 'last_follow_time',
            'name'  => '最后跟进时间',
            'type'  => 'text',
        ],
        [
            'field' => 'total_amount',
            'name'  => '商机金额',
            'type'  => 'text',
        ],
        [
            'field' => 'creator',
            'name'  => '创建人',
        ],
        [
            'field' => 'created_at',
            'name'  => '创建时间',
        ],
        [
            'field' => 'status',
            'name'  => '商机状态',
        ],
    ];

    public const ODDS_SEARCH_FIELD = [
        [
            'field'      => 'odds_no',
            'name'       => '商机编号',
            'input_type' => 'input',
        ],
        [
            'field'      => 'salesman',
            'name'       => '负责人',
            'input_type' => 'personnel',
        ],
        [
            'field'      => 'scope_frame',
            'name'       => '管理范围',
            'input_type' => 'scope_frame',
        ],
        [
            'field'      => 'customer_name',
            'name'       => '客户名称',
            'input_type' => 'input',
        ],
        [
            'field'      => 'product_name',
            'name'       => '产品名称',
            'input_type' => 'input',
        ],
        [
            'field'      => 'creator',
            'name'       => '创建人',
            'input_type' => 'personnel',
        ],
        [
            'field'      => 'created_at',
            'name'       => '创建时间',
            'input_type' => 'date',
        ],
        [
            'field'      => 'status',
            'name'       => '商机状态',
            'input_type' => 'radio',
            'type'       => 'radio',
            'dict_ident' => 'odds_status',
        ],
        [
            'field'      => 'follow',
            'name'       => '跟进状态',
            'input_type' => 'select',
            'dict'       => [
                [
                    'value' => 1,
                    'label' => '已跟进',
                    'name'  => '已跟进',
                ],
                [
                    'value' => 2,
                    'label' => '未跟进',
                    'name'  => '未跟进',
                ],
            ],
        ],
    ];

    /**
     * 我负责商机列表字段.
     */
    public const ODDS_CHARGE_LIST_FIELD = [
        [
            'field' => 'salesman',
            'name'  => '负责人',
            'type'  => 'salesman',
        ],
    ];

    /**
     * 我负责商机列表默认字段.
     */
    public const ODDS_CHARGE_LIST_DEFAULT_FIELD = [
        'customer_name', 'customer_way', 'liaison_tel', 'customer_label', 'last_follow_time', 'customer_status',
        'customer_followed', 'created_at',
    ];

    /**
     * 我负责商机列表搜索字段.
     */
    public const ODDS_VIEWER_SEARCH_FIELD = [
        [
            'field'      => 'salesman',
            'name'       => '负责人',
            'input_type' => 'personnel',
        ],
        [
            'field'      => 'scope_frame',
            'name'       => '管理范围',
            'input_type' => 'scope_frame',
        ],
    ];

    /**
     * 我负责商机列表搜索字段.
     */
    public const ODDS_CHARGE_SEARCH_FIELD = [
        [
            'field'      => 'salesman',
            'name'       => '负责人',
            'input_type' => 'personnel',
        ],
        [
            'field'      => 'scope_frame',
            'name'       => '管理范围',
            'input_type' => 'scope_frame',
        ],
    ];

    /**
     * 商机池商机列表默认搜索字段.
     */
    public const ODDS_CHARGE_SEARCH_DEFAULT_FIELD = [
        'customer_name', 'customer_repeat_check', 'customer_no', 'customer_label', 'liaison', 'liaison_tel',
        'customer_way', 'created_at', 'contract_name', 'contract_no',
    ];

    /**
     * 商机池商机列表默认搜索字段.
     */
    public const ODDS_VIEWER_LIST_FIELD = self::ODDS_CHARGE_LIST_FIELD;

    /**
     * 商机默认表头字段.
     */
    public const ODDS_VIEWER_LIST_DEFAULT_FIELD = [
        'name', 'total_amount', 'status', 'types', 'description', 'eid', 'work_customer', 'followed', 'salesman', 'last_follow_time', 'created_at',
    ];

    public const ODDS_VIEWER_SEARCH_DEFAULT_FIELD = [
        'name', 'odds_no', 'customer_name', 'types',
    ];

    public const ODDS_COMPANY_LIST_FIELD = self::ODDS_CHARGE_LIST_FIELD;

    public const ODDS_COMPANY_LIST_DEFAULT_FIELD = [
        'customer_name', 'customer_way', 'liaison_tel', 'customer_label', 'last_follow_time', 'customer_status',
        'customer_followed', 'salesman', 'created_at',
    ];

    public const ODDS_COMPANY_SEARCH_FIELD = self::ODDS_VIEWER_SEARCH_FIELD;

    /**
     *  商机-公司-搜索字段.
     */
    public const ODDS_COMPANY_SEARCH_DEFAULT_FIELD = [
        'customer_name', 'customer_repeat_check', 'salesman', 'customer_no', 'customer_label', 'liaison', 'liaison_tel',
        'customer_way', 'created_at', 'contract_name', 'contract_no',
    ];

    /**
     * 不允许删除的字段.
     */
    public const ODDS_NOT_ALLOW_DELETE_FIELD = [
        'odds_customer', 'status', 'followed', 'odds_no', 'products',
    ];
}
