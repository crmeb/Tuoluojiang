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

namespace App\Constants;

use Tuoluojiang\Open\utils\MessageType;

final class CommonEnum
{
    /**
     * 已处理.
     */
    public const STATUS_DELETE = -1;

    public const STATUS_NOMAL = 1;

    public const ORIGIN_UNI = 'uni';

    public const ORIGIN_WEB = 'web';

    public const ORIGIN_SAAS = 'saas';

    public const ORIGIN_OWN = 'own';

    public const PACKAGE_FULL = 'full';

    public const PACKAGE_PATCH = 'patch';

    /**
     * 企业邀请提醒.
     */
    public const ENTERPRISE_INVITATION_NOTICE = [
        MessageType::ENTERPRISE_INVITATION_TYPE,
        MessageType::ENTERPRISE_PERSONNEL_APPLY_TYPE,
    ];

    /**
     * 功能模块：绩效.
     */
    public const MODULE_ASSESS = 'assess';

    /**
     * 功能模块：审批.
     */
    public const MODULE_APPROVE = 'approve';

    /**
     * 功能模块：客户.
     */
    public const MODULE_CUSTOMER = 'customer';

    /**
     * 功能模块：汇报.
     */
    public const MODULE_REPORT = 'daily';

    /**
     * 排序方式：正序.
     */
    public const SORT_ASC = 'asc';

    /**
     * 排序方式：倒序.
     */
    public const SORT_DESC = 'desc';
}
