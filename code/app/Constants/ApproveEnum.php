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

use MyCLabs\Enum\Enum;

/**
 * 审批相关.
 */
final class ApproveEnum extends Enum
{
    /**
     * 申请状态：撤回.
     */
    public const APPROVE_RECALL = -1;

    /**
     * 申请状态：待审批.
     */
    public const APPROVE_PENDING = 0;

    /**
     * 申请状态：已通过.
     */
    public const APPROVE_PASSED = 1;

    /**
     * 申请状态：已拒绝.
     */
    public const APPROVE_REJECTED = 2;

    /**
     * 申请催办间隔.
     */
    public const APPROVE_URGE_INTERVAL = 300;

    /**
     * 数据关联通知.
     */
    public const LINK_NOTICE = [
        NoticeEnum::BUSINESS_APPROVAL_TYPE,
        NoticeEnum::BUSINESS_RECALL_TYPE,
        NoticeEnum::BUSINESS_ADOPT_APPLY_TYPE,
        NoticeEnum::BUSINESS_ADOPT_CC_TYPE,
        NoticeEnum::BUSINESS_FAIL_TYPE,
    ];

    /**
     * 无数据关联通知.
     */
    public const Not_Link_Notice = [
    ];

    /**
     * 人事-请假.
     */
    public const PERSONNEL_HOLIDAY = 1;

    /**
     * 人事-补卡.
     */
    public const PERSONNEL_SIGN = 2;

    /**
     * 人事-加班.
     */
    public const PERSONNEL_OVERTIME = 3;

    /**
     * 人事-外出.
     */
    public const PERSONNEL_OUT = 4;

    /**
     * 人事-出差.
     */
    public const PERSONNEL_TRIP = 5;

    /**
     * 客户-订单回款.
     */
    public const CUSTOMER_CONTRACT_PAYMENT = 6;

    /**
     * 客户-订单续费.
     */
    public const CUSTOMER_CONTRACT_RENEWAL = 7;

    /**
     * 客户-订单支出.
     */
    public const CUSTOMER_CONTRACT_EXPENSES = 8;

    /**
     * 客户-开具发票.
     */
    public const CUSTOMER_INVOICE_ISSUANCE = 9;

    /**
     * 客户-发票冲红.
     */
    public const CUSTOMER_INVOICE_CANCELLATION = 10;

    /**
     * 客户-合同签约.
     */
    public const CUSTOMER_CONTRACT_SIGN = 12;

    /**
     * 撤销申请.
     */
    public const APPROVE_REVOKE = 11;

    /**
     * 客户相关审批.
     */
    public const CUSTOMER_TYPES = [
        self::CUSTOMER_CONTRACT_PAYMENT,
        self::CUSTOMER_CONTRACT_RENEWAL,
        self::CUSTOMER_CONTRACT_EXPENSES,
        self::CUSTOMER_INVOICE_ISSUANCE,
        self::CUSTOMER_INVOICE_CANCELLATION,
        self::APPROVE_REVOKE,
        self::CUSTOMER_CONTRACT_SIGN,
    ];

    /**
     * 订单付款表单.
     */
    public const CUSTOMER_FORM_PAYMENT = [
        'contractList'     => 'cid',
        'incomeCategories' => 'bill_cate_id',
        'payType'          => 'type_id',
        'collectionAmount' => 'num',
        'payTime'          => 'date',
        'paymentVoucher'   => 'attach',
        'remark'           => 'mark',
    ];

    /**
     * 订单续费表单.
     */
    public const CUSTOMER_FORM_RENEWAL = [
        'contractList'     => 'cid',
        'incomeCategories' => 'bill_cate_id',
        'renewalType'      => 'cate_id',
        'renewalAmount'    => 'num',
        'renewalEndTime'   => 'end_date',
        'payType'          => 'type_id',
        'payTime'          => 'date',
        'paymentVoucher'   => 'attach',
        'remark'           => 'mark',
    ];

    /**
     * 订单支出表单.
     */
    public const CUSTOMER_FORM_EXPENDITURE = [
        'contractList'          => 'cid',
        'expenditureCategories' => 'bill_cate_id',
        'payType'               => 'type_id',
        'expenditureAmount'     => 'num',
        'payTime'               => 'date',
        'paymentVoucher'        => 'attach',
        'remark'                => 'mark',
    ];

    /**
     * 申请开票表单.
     */
    public const CUSTOMER_FORM_ISSUEINVOICE = [
        'customerList'    => 'eid',
        'contractList'    => 'cid',
        'billId'          => 'bill_id',
        'billAmount'      => 'price',
        'desireDate'      => 'bill_date',
        'invoicingMethod' => 'collect_type',
        'invoicingEmail'  => 'collect_email',
        'liaisonMan'      => 'collect_name',
        'telephone'       => 'collect_tel',
        'mailingAddress'  => 'mail_address',
        'invoiceType'     => 'types',
        'invoiceAmount'   => 'amount',
        'invoiceHeader'   => 'title',
        'dutyParagraph'   => 'ident',
        'remark'          => 'mark',
    ];

    /**
     * 发票冲红表单.
     */
    public const CUSTOMER_FORM_VOIDEDINVOICE = [
        'invoiceList' => 'invoice_id',
        'remark'      => 'mark',
    ];

    /**
     * 事假.
     */
    public const ABSENCE_LEAVE = '10381ba76488e31e75f73b46250317f2';

    /**
     * 年假.
     */
    public const ANNUAL_LEAVE = 'ae28fb7aa4488d019e5413119ecabfdf';

    /**
     * 调休假.
     */
    public const COMPENSATORY_LEAVE = '61f0ebb4cbcc453a3f47bd4c5bf8cafd';

    /**
     * 产假.
     */
    public const MATERNITY_LEAVE = 'b6b6606b766073f12a8e351088497bd1';

    /**
     * 陪产假.
     */
    public const PATERNITY_LEAVE = 'edf101bedc8f7a493644077b0c800325';

    /**
     * 病假.
     */
    public const SICK_LEAVE = 'f957b8c3ca7bf1a780f48c18e2f3b4d5';

    /**
     * 丧假.
     */
    public const BEREAVEMENT_LEAVE = '8a6ac7dc2ca5c589f1a002bebfefc17e';

    /**
     * 婚假.
     */
    public const MARRIAGE_LEAVE = '93d88e89dbec2a11886f799c8996c7bc';

    /**
     * 请假类型标识.
     */
    public const LEAVE_TYPE_UNIQUE = [
        self::ABSENCE_LEAVE,
        self::ANNUAL_LEAVE,
        self::COMPENSATORY_LEAVE,
        self::MATERNITY_LEAVE,
        self::PATERNITY_LEAVE,
        self::SICK_LEAVE,
        self::BEREAVEMENT_LEAVE,
        self::MARRIAGE_LEAVE,
    ];
}
