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

namespace App\Constants\System;

use MyCLabs\Enum\Enum;

/**
 * 系统：配置分类枚举.
 */
final class CategoryEnum extends Enum
{
    /**
     * 系统配置.
     */
    public const SYSTEM_CONFIG = [
        'label' => '系统配置',
        'key'   => 'system_config',
    ];

    /**
     * 系统配置.
     */
    public const WORK_CONFIG = [
        'label' => '企业微信配置',
        'key'   => 'work_config',
    ];

    /**
     * 存储配置.
     */
    public const STORAGE_CONFIG = [
        'label' => '存储配置',
        'key'   => 'storage_config',
    ];

    /**
     * 一号通配置.
     */
    public const YIHT_CONFIG = [
        'label' => '一号通配置',
        'key'   => 'yiht_config',
    ];

    /**
     * Unipush配置.
     */
    public const PUSH_CONFIG = [
        'label' => 'App通知配置',
        'key'   => 'push_config',
    ];

    /**
     * 防火墙配置.
     */
    public const FIREWALL_CONFIG = [
        'label' => '防火墙配置',
        'key'   => 'firewall_config',
    ];

    /**
     * 文件上传配置.
     */
    public const UPLOAD_CONFIG = [
        'label' => '文件上传配置',
        'key'   => 'upload_config',
    ];

    /**
     * 客户跟进配置.
     */
    public const CUSTOMER_FOLLOW_CONFIG = [
        'label' => '客户跟进配置',
        'key'   => 'customer_follow_config',
    ];

    /**
     * 客户公海配置.
     */
    public const CUSTOMER_SEA_CONFIG = [
        'label' => '客户公海配置',
        'key'   => 'customer_sea_config',
    ];

    /**
     * 线索池配置.
     */
    public const CLUE_POOL_CONFIG = [
        'label' => '线索池配置',
        'key'   => 'clue_pool_config',
    ];

    /**
     * 商机跟进配置.
     */
    public const ODDS_FOLLOW_CONFIG = [
        'label' => '商机跟进配置',
        'key'   => 'odds_follow_config',
    ];

    /**
     * 客户审批配置.
     */
    public const CUSTOMER_APPROVE_CONFIG = [
        'label' => '客户审批配置',
        'key'   => 'customer_approve_config',
    ];

    /**
     * 绩效配置.
     */
    public const ASSESS_CONFIG = [
        'label' => '绩效配置',
        'key'   => 'assess_config',
    ];

    /**
     * 云文件配置.
     */
    public const WPS_CONFIG = [
        'label' => '云文件配置',
        'key'   => 'wps_config',
    ];

    /**
     * 其他配置.
     */
    public const OTHER_CONFIG = [
        'label' => '其他配置',
        'key'   => 'other_config',
    ];

    /**
     * 客户模块配置.
     */
    public const CUSTOMER_MODULE_CONFIG = [
        'label' => '客户模块配置',
        'key'   => 'customer_module_config',
    ];

    /**
     * 关联出库配置.
     */
    public const CUSTOMER_OUTBOUND_CONFIG = [
        'label' => '关联出库配置',
        'key'   => 'customer_outbound_config',
    ];
}
