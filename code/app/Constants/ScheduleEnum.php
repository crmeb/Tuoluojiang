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

namespace App\Constants;

use Tuoluojiang\Open\utils\MessageType;

/**
 * 日程.
 */
final class ScheduleEnum
{
    /**
     * 按天重复.
     */
    public const REPEAT_DAY = 1;

    /**
     * 按周重复.
     */
    public const REPEAT_WEEK = 2;

    /**
     * 按月重复.
     */
    public const REPEAT_MONTH = 3;

    /**
     * 按年重复.
     */
    public const REPEAT_YEAR = 4;

    /**
     * 不重复.
     */
    public const REPEAT_NOT = 0;

    /**
     * 操作方式：仅当前.
     */
    public const CHANGE_NOW = 0;

    /**
     * 操作方式：当前及以后.
     */
    public const CHANGE_AFTER = 1;

    /**
     * 操作方式：全部.
     */
    public const CHANGE_ALL = 2;

    /**
     * 个人提醒.
     */
    public const TYPE_PERSONAL = 1;

    /**
     * 客户跟进.
     */
    public const TYPE_CLIENT_TRACK = 2;

    /**
     * 付款提醒.
     */
    public const TYPE_CLIENT_RENEW = 3;

    /**
     * 订单回款.
     */
    public const TYPE_CLIENT_RETURN = 4;

    /**
     * 汇报待办.
     */
    public const TYPE_REPORT_RENEW = 5;

    /**
     * 绩效考核.
     */
    public const TYPE_ASSESS = 6;

    /**
     * 线索跟进.
     */
    public const TYPE_CLUE_TRACK = 7;

    /**
     * 商机跟进.
     */
    public const TYPE_ODDS_TRACK = 8;

    /**
     * 客户跟进关联通知.
     */
    public const SCHEDULE_FOLLOW_NOTICE = [
        MessageType::DEALT_PRESON_WORK_TYPE,
    ];
}
