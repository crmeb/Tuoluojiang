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

namespace Tuoluojiang\Open\utils;

/**
 * 消息类型
 * Class MessageType.
 */
class MessageType
{
    // 系统消息类型
    public const TYPE_SYSTEM = 0;

    // 短信消息类型
    public const TYPE_SMS = 1;

    // 企业微信消息类型
    public const TYPE_WORK = 2;

    // 钉钉消息类型
    public const TYPE_DING = 3;

    // 其他消息类型
    public const TYPE_OTHER = 4;

    public const TYPE_WEWORK = 5;

    // 邀请完善信息
    public const PERFECT_USER_INFO = 'user_perfect';

    // 日报填写提醒
    public const DAILY_REMIND_TYPE = 'daily_remind';

    // 日报查看提醒
    public const DAILY_SHOW_REMIND_TYPE = 'daily_show_remind';

    // 日报修改提醒
    public const DAILY_UPDATE_REMIND_TYPE = 'daily_update_remind';

    // 考核目标发布
    public const ASSESS_PUBLISH_TYPE = 'assess_publish';

    // 制定考核目标提醒
    public const ASSESS_TARGET_TYPE = 'assess_target';

    // 开启考核任务提醒
    public const ASSESS_START_TYPE = 'assess_start';

    // 考核自我评价提醒
    public const ASSESS_SELF_TYPE = 'assess_selt';

    // 考核上级评价提醒
    public const ASSESS_EXAMINE_TYPE = 'assess_examine';

    // 考核异常提醒
    public const ASSESS_ABNORMAK_TYPE = 'assess_abnormal';

    // 绩效考核结束
    public const ASSESS_END_TYPE = 'assess_end';

    // 绩效考核结果
    public const ASSESS_RESULT_END_TYPE = 'assess_result_remind';

    // 绩效申诉请求
    public const ASSESS_APPEAL_TYPE = 'assess_appeal';

    // 绩效申诉结果
    public const ASSESS_APPEAL_RESULT_TYPE = 'assess_appeal_result';

    // 上级评价结果
    public const ASSESS_EVALUATE_RESULT_TYPE = 'assess_evaluate_result';

    // 【业务类型】审批提醒
    public const BUSINESS_APPROVAL_TYPE = 'business_approval';

    // 【业务类型】撤回提醒
    public const BUSINESS_RECALL_TYPE = 'business_recall';

    // 申请人【业务类型】审批通过提醒
    public const BUSINESS_ADOPT_APPLY_TYPE = 'business_adopt_apply';

    // 抄送人【业务类型】审批通过提醒
    public const BUSINESS_ADOPT_CC_TYPE = 'business_adopt_cc';

    // 【业务类型】未通过审批醒
    public const BUSINESS_FAIL_TYPE = 'business_fail';

    // 合同急需续费提醒
    public const CONTRACT_URGENT_RENEW_TYPE = 'contract_urgent_renew';

    // 合同续费今日到期提醒
    public const CONTRACT_DAY_REMIND_TYPE = 'contract_day_remind';

    // 合同续费过期提醒
    public const CONTRACT_OVERDUE_REMIND_TYPE = 'contract_overdue_remind';

    // 合同回款提醒
    public const CONTRACT_RETURN_MONEY_TYPE = 'contract_return_money';

    // 合同续费提醒
    public const CONTRACT_RENEW_TYPE = 'contract_renew';

    // 合同支出提醒
    public const CONTRACT_EXPEND_TYPE = 'contract_expend';

    // 合同异常
    public const CONTRACT_ABNORMAL_TYPE = 'contract_abnormal';

    // 合同即将到期提醒
    public const CONTRACT_SOON_OVERDUE_REMIND = 'contract_soon_overdue_remind';

    // 合同今日到期提醒
    public const CONTRACT_OVERDUE_DAY_REMIND = 'contract_overdue_day_remind';

    // 开具发票申请
    public const CONTRACT_INVOICE_TYPE = 'contract_invoice';

    // 财务审核已通过提醒
    public const FINANCE_VERIFY_SUCCESS_TYPE = 'finance_verify_success';

    // 财务审核未通过提醒
    public const FINANCE_VERIFY_FAIL_TYPE = 'finance_verify_fail';

    // 财务发票审核已通过提醒
    public const FINANCE_INVOICE_VERIFY_SUCCESS_TYPE = 'finance_invoice_verify_success';

    // 财务发票审核未通过提醒
    public const FINANCE_INVOICE_VERIFY_FAIL_TYPE = 'finance_invoice_verify_fail';

    // 发票已开具提醒
    public const FINANCE_INVOICE_OPEN_TYPE = 'finance_invoice_open';

    // 发票未开具提醒
    public const FINANCE_INVOICE_CLOSE_TYPE = 'finance_invoice_close';

    // 待办任务提醒
    public const DEALT_PRESON_WORK_TYPE = 'dealt_person_work';

    // 待办任务提醒客户
    public const DEALT_CLIENT_WORK_TYPE = 'dealt_client_work';

    // 待办任务提醒回款
    public const DEALT_MONEY_WORK_TYPE = 'dealt_money_work';

    // 平台审核通过提醒
    public const ENTERPRISE_VERIFY_TYPE = 'enterprise_verify';

    // 平台审核未通过提醒
    public const ENTERPRISE_VEERIFY_FAIL_TYPE = 'enterprise_verify_fail';

    // 企业邀请提醒
    public const ENTERPRISE_INVITATION_TYPE = 'enterprise_invitation';

    // 人员申请加入提醒
    public const ENTERPRISE_PERSONNEL_APPLY_TYPE = 'enterprise_personnel_apply';

    // 人员加入提醒
    public const ENTERPRISE_PERSONNEL_JSON_TYPE = 'enterprise_personnel_join';

    // 人员拒绝加入提醒
    public const ENTERPRISE_PERSONNEL_REFUSE_TYPE = 'enterprise_personnel_refuse';

    // 文件删除提醒
    public const CLOUD_FILE_DELETE_TYPE = 'cloud_file_delete';

    // 文件创建提醒
    public const CLOUD_FILE_CREATE_TYPE = 'cloud_file_create';

    // 文件浏览提醒
    public const CLOUD_FILE_READ_TYPE = 'cloud_file_read';

    // 系统操作提醒
    public const SYSTEM_CRUD_TYPE = 'system_crud_type';
}
