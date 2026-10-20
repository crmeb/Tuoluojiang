<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * 我的待办相关常量.
 */
final class TodoEnum
{
    /**
     * 待办日程.
     */
    public const TYPE_SCHEDULE = 'schedule';

    /**
     * 待自评绩效.
     */
    public const TYPE_ASSESS_SELF = 'assess_self';

    /**
     * 待上级评价绩效.
     */
    public const TYPE_ASSESS_CHECK = 'assess_check';

    /**
     * 待申诉处理绩效.
     */
    public const TYPE_ASSESS_APPEAL = 'assess_appeal';

    /**
     * 待跟进客户.
     */
    public const TYPE_CUSTOMER = 'customer';

    /**
     * 签约中合同.
     */
    public const TYPE_CONTRACT = 'contract';

    /**
     * 待开票.
     */
    public const TYPE_INVOICE = 'invoice';

    /**
     * 待处理项目任务.
     */
    public const TYPE_TASK = 'task';

    /**
     * 未读企业动态.
     */
    public const TYPE_NOTICE = 'notice';

    /**
     * 提交付批.
     */
    public const TYPE_APPROVE_SUBMIT = 'approve_submit';

    /**
     * 待我审批.
     */
    public const TYPE_APPROVE_PENDING = 'approve_pending';

    /**
     * 全部待办.
     */
    public const TYPE_ALL = 'all';

    /**
     * 类型标签映射.
     */
    public const TYPE_LABELS = [
        self::TYPE_SCHEDULE        => '待办日程',
        self::TYPE_ASSESS_SELF     => '待自评绩效',
        self::TYPE_ASSESS_CHECK    => '待上级评价',
        self::TYPE_ASSESS_APPEAL   => '待申诉处理',
        self::TYPE_CUSTOMER        => '待跟进客户',
        self::TYPE_CONTRACT        => '签约中合同',
        self::TYPE_INVOICE         => '待开票',
        self::TYPE_TASK            => '待处理任务',
        self::TYPE_NOTICE          => '未读企业动态',
        self::TYPE_APPROVE_SUBMIT  => '提交待批',
        self::TYPE_APPROVE_PENDING => '待我审批',
        self::TYPE_ALL             => '全部待办',
    ];

    /**
     * 绩效待评类型.
     */
    public const ASSESS_TYPES = [
        self::TYPE_ASSESS_SELF,
        self::TYPE_ASSESS_CHECK,
        self::TYPE_ASSESS_APPEAL,
    ];

    /**
     * 所有待办类型.
     */
    public const ALL_TYPES = [
        self::TYPE_SCHEDULE,
        self::TYPE_ASSESS_SELF,
        self::TYPE_ASSESS_CHECK,
        self::TYPE_ASSESS_APPEAL,
        self::TYPE_CUSTOMER,
        self::TYPE_CONTRACT,
        self::TYPE_INVOICE,
        self::TYPE_TASK,
        self::TYPE_NOTICE,
        self::TYPE_APPROVE_SUBMIT,
        self::TYPE_APPROVE_PENDING,
    ];
}
