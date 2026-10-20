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
 * 联系人业务
 */
final class LiaisonEnum extends CustomEnum
{
    /**
     * 新增联系人.
     */
    public const OPERATE_CREATE = 6;

    /**
     * 联系人.
     */
    public const LIAISON_VIEWER = 7;

    /**
     * 查看客户联系人.
     */
    public const CUSTOMER_VIEWER_LIAISON = 117;

    /**
     * 负责客户联系人.
     */
    public const CUSTOMER_CHARGE_LIAISON = 127;

    /**
     * 公海客户联系人.
     */
    public const CUSTOMER_HEIGHT_SEAS_LIAISON = 137;

    /**
     * 公司客户联系人.
     */
    public const CUSTOMER_COMPANY_LIAISON = 147;

    public const LIAISON_VIEWER_LIST_DEFAULT_FIELD = [
        'liaison_name', 'liaison_job', 'liaison_tel', 'eid', 'work_customer',
    ];

    public const LIAISON_VIEWER_SEARCH_DEFAULT_FIELD = [
        'liaison_name', 'liaison_job', 'liaison_tel',
    ];

    public const LIAISON_TYPE = [
        self::LIAISON_VIEWER,
        self::CUSTOMER_VIEWER_LIAISON,
        self::CUSTOMER_CHARGE_LIAISON,
        self::CUSTOMER_HEIGHT_SEAS_LIAISON,
        self::CUSTOMER_COMPANY_LIAISON,
    ];

    public const LIAISON_NOT_ALLOW_DELETE_FIELD = [
        'liaison_name', 'liaison_tel', 'eid',
    ];


    public const LIAISON_LIST_FIELD = [
        [
            'field' => 'creator',
            'name'  => '创建人',
        ]
    ];
    public const LIAISON_SEARCH_FIELD = [
        [
            'field'      => 'customer_name',
            'name'       => '客户名称',
            'type'       => 'text',
            'input_type' => 'input',
        ],
        [
            'field'      => 'creator',
            'name'       => '创建人',
            'input_type' => 'personnel',
        ]
    ];
}
