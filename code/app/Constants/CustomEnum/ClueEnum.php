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
 * 线索.
 */
final class ClueEnum extends CustomEnum
{
    /**
     * 新增线索.
     */
    public const OPERATE_CREATE = 1;

    /**
     * 修改线索.
     */
    public const OPERATE_UPDATE = 2;

    /**
     * 领取线索.
     */
    public const OPERATE_RECEIVE = 3;

    /**
     * 退回线索池.
     */
    public const OPERATE_BACK = 4;

    /**
     * 转客户.
     */
    public const OPERATE_CONVERT = 5;

    /**
     * 转移.
     */
    public const OPERATE_SHIFT = 6;

    public const CLUE_LIST_FIELD = [
        [
            'field'      => 'work_customer',
            'name'       => '企微客户',
            'type'       => 'singleMember',
            'input_type' => 'member',
        ],
        [
            'field' => 'created_at',
            'name'  => '创建时间',
        ],
    ];

    public const CLUE_SEARCH_FIELD = [
        [
            'field'      => 'work_customer',
            'name'       => '企微客户',
            'input_type' => 'select',
            'dict'       => [
                [
                    'value' => 1,
                    'label' => '是',
                    'name'  => '是',
                ],
                [
                    'value' => 2,
                    'label' => '否',
                    'name'  => '否',
                ],
            ],
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
        [
            'field'      => 'created_at',
            'name'       => '创建时间',
            'input_type' => 'date',
        ],
        [
            'field'      => 'repeat',
            'name'       => '线索查重',
            'input_type' => 'select',
            'dict'       => [
                [
                    'value' => 'name',
                    'label' => '线索名称',
                ],
                [
                    'value' => 'phone',
                    'label' => '联系电话',
                ],
            ],
        ],
    ];

    public const CLUE_HEIGHT_SEARCH_FIELD = [
        [
            'field'      => 'salesman',
            'name'       => '负责人',
            'input_type' => 'personnel',
        ],
    ];

    public const CLUE_HEIGHT_SEAS_SEARCH_FIELD = [
        [
            'field'      => 'before_salesman',
            'name'       => '前负责人',
            'input_type' => 'personnel',
        ],
    ];

    /**
     * 线索池线索列表隐藏字段.
     */
    public const CLUE_SEAS_LIST_FIELD = [
        [
            'field' => 'before_salesman',
            'name'  => '前负责人',
        ],
        [
            'field' => 'return_num',
            'name'  => '退回次数',
        ],
        [
            'field' => 'return_reason',
            'name'  => '退回原因',
        ],
    ];

    /**
     * 线索池线索列表默认字段.
     */
    public const CLUE_SEAS_LIST_DEFAULT_FIELD = [
        "name",
        "source",
        "customer_label",
        "phone",
        "status",
        "createtime",
        "area_cascade",
        "mark",
        "return_num",
        "return_reason",
        "address"
    ];

    /**
     * 线索池线索列表搜索字段.
     */
    public const CLUE_SEAS_SEARCH_FIELD = [
        [
            'field'      => 'before_salesman',
            'name'       => '前负责人',
            'input_type' => 'personnel',
        ],
        [
            'field'      => 'scope_frame',
            'name'       => '管理范围',
            'input_type' => 'scope_frame',
        ],
    ];

    /**
     * 线索池线索列表默认搜索字段.
     */
    public const CLUE_SEAS_SEARCH_DEFAULT_FIELD = [
        'customer_name', 'customer_repeat_check', 'customer_no', 'customer_label', 'before_salesman', 'liaison', 'liaison_tel',
        'customer_way', 'created_at', 'contract_name', 'contract_no',
    ];

    /**
     * 我负责线索列表字段.
     */
    public const CLUE_CHARGE_LIST_FIELD = [
        [
            'field' => 'salesman',
            'name'  => '负责人',
            'type'  => 'salesman',
        ],
    ];

    /**
     * 我负责线索列表默认字段.
     */
    public const CLUE_CHARGE_LIST_DEFAULT_FIELD = [
        'customer_name', 'customer_way', 'liaison_tel', 'customer_label', 'last_follow_time', 'customer_status',
        'customer_followed', 'created_at',
    ];

    /**
     * 我负责线索列表搜索字段.
     */
    public const CLUE_VIEWER_SEARCH_FIELD = [
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
     * 我负责线索列表搜索字段.
     */
    public const CLUE_CHARGE_SEARCH_FIELD = [
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
     * 线索池线索列表默认搜索字段.
     */
    public const CLUE_CHARGE_SEARCH_DEFAULT_FIELD = [
        'customer_name', 'customer_repeat_check', 'customer_no', 'customer_label', 'liaison', 'liaison_tel',
        'customer_way', 'created_at', 'contract_name', 'contract_no',
    ];

    /**
     * 线索池线索列表默认搜索字段.
     */
    public const CLUE_VIEWER_LIST_FIELD = self::CLUE_CHARGE_LIST_FIELD;

    public const CLUE_VIEWER_LIST_DEFAULT_FIELD = [
        "name",
        "source",
        "work_customer",
        "phone",
        "customer_label",
        "status",
        "mark",
        "followed",
        "salesman",
        "created_at"
    ];

    public const CLUE_VIEWER_SEARCH_DEFAULT_FIELD = [
        'name', 'source', 'customer_label',
    ];

    public const CLUE_COMPANY_LIST_FIELD = self::CLUE_CHARGE_LIST_FIELD;

    public const CLUE_COMPANY_LIST_DEFAULT_FIELD = [
        'customer_name', 'customer_way', 'liaison_tel', 'customer_label', 'last_follow_time', 'customer_status',
        'customer_followed', 'salesman', 'created_at',
    ];

    public const CLUE_COMPANY_SEARCH_FIELD = self::CLUE_VIEWER_SEARCH_FIELD;

    /**
     *  线索-公司-搜索字段.
     */
    public const CLUE_COMPANY_SEARCH_DEFAULT_FIELD = [
        'customer_name', 'customer_repeat_check', 'salesman', 'customer_no', 'customer_label', 'liaison', 'liaison_tel',
        'customer_way', 'created_at', 'contract_name', 'contract_no',
    ];

    /**
     * 不允许删除的字段.
     */
    public const CLUE_NOT_ALLOW_DELETE_FIELD = [
        'name', 'source', 'pool', 'phone', 'status', 'followed', 'area_cascade', 'customer_label', 'createtime', 'work_customer',
    ];
}
