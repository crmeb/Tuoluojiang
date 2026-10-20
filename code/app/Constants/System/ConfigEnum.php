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

namespace App\Constants\System;

use App\Constants\StorageEnum;
use MyCLabs\Enum\Enum;

/**
 * 系统：配置项枚举.
 */
final class ConfigEnum extends Enum
{
    public const WECHAT_WORK_USER_SWITCH = [
        'title'      => '企业微信通讯录开关',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'wechat_work_user_switch',
        'value'      => 0,
        'parameter'  => [
            '关闭',
            '开启',
        ],
        'category' => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_CLIENT_SWITCH = [
        'title'      => '企业微信客户开关',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'wechat_work_client_switch',
        'value'      => 0,
        'parameter'  => [
            '关闭',
            '开启',
        ],
        'category' => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_CLIENT_RADIO = [
        'title'      => '企业微信客户同步',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'wechat_work_client_radio',
        'value'      => 'clue',
        'parameter'  => [
            'clue'     => '线索',
            'customer' => '客户',
        ],
        'category' => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_SESSION_SWITCH = [
        'title'      => '企业微信会话存档开关',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'wechat_work_session_switch',
        'value'      => 0,
        'parameter'  => [
            '关闭',
            '开启',
        ],
        'category' => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_SESSION_SECRET = [
        'title'      => '企业微信会话存secret',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_session_secret',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_SESSION_PUBLIC_KEY_VERSION = [
        'title'      => '企业微信会话存档密钥版本',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_session_public_key_version',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_SESSION_PUBLIC_KEY = [
        'title'      => '企业微信会话存档公钥',
        'type'       => 'upload',
        'input_type' => 'input',
        'key'        => 'wechat_work_session_public_key',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_SESSION_PRIVATE_KEY = [
        'title'      => '企业微信会话存档密钥',
        'type'       => 'upload',
        'input_type' => 'input',
        'key'        => 'wechat_work_session_private_key',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_CORPID = [
        'title'      => '企业微信ID',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_corpid',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_TOKEN = [
        'title'      => '企业微信token',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_token',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_AES_KEY = [
        'title'      => '企业微信aes_key',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_aes_key',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_USER_SECRET = [
        'title'      => '企业微信客户联系密钥',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_user_secret',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_ADDRESS_SECRET = [
        'title'      => '企业微信通讯录密钥',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_address_secret',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_BUILD_AGENT_ID = [
        'title'      => '企业微信自建应用ID',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_build_agent_id',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_BUILD_SECRET = [
        'title'      => '企业微信自建应用secret',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wechat_work_build_secret',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const WECHAT_WORK_FORCED_BUILD = [
        'title'      => '是否强制绑定企业微信',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'wechat_work_forced_build',
        'value'      => 0,
        'parameter'  => [
            '关闭',
            '开启',
        ],
        'category' => CategoryEnum::WORK_CONFIG['key'],
    ];

    public const LOGIN_PASSWORD_LENGTH = [
        'title'      => '密码长度',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'login_password_length',
        'value'      => '8',
        'desc'       => '设置用户密码的最小长度，建议设置为 8-16 位，长度越长安全性越高。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const LOGIN_PASSWORD_TYPE = [
        'title'      => '密码类型',
        'type'       => 'checkbox',
        'input_type' => '',
        'key'        => 'login_password_type',
        'value'      => [0],
        'parameter'  => [
            '数字',
            '大写字母',
            '小写字母',
            '特殊符号',
        ],
        'desc'     => '选择用户密码的组成规则，可多选，勾选后密码必须包含对应类型字符，提升账号安全性。',
        'category' => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const LOGINT_TIME_OUT = [
        'title'      => '登录超时退出时间',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'login_time_out',
        'value'      => '24',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const LOGIN_ERROR_COUNT = [
        'title'      => '登录错误次数',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'login_error_count',
        'value'      => '15',
        'desc'       => '设置用户登录失败的最大尝试次数，超过次数后将锁定账号，防止暴力破解攻击。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const LOGIN_LOCK = [
        'title'      => '密码错误锁定时间',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'login_lock',
        'value'      => '1',
        'desc'       => '设置登录失败次数超限后账号的锁定时长（单位：小时），锁定期间无法登录，可有效防止暴力破解。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const SINGLE_LOGIN_SWITCH = [
        'title'      => '单点登录开关',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'single_login_switch',
        'value'      => 1,
        'parameter'  => [
            '关闭',
            '开启',
        ],
        'desc'     => '开启后，同一账号仅允许在一处设备登录，新登录将挤掉旧会话；关闭则支持多设备同时登录。',
        'category' => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const SITE_OPEN = [
        'title'      => '站点开启',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'site_open',
        'value'      => 1,
        'parameter'  => [
            '关闭',
            '开启',
        ],
        'desc'     => '选择是否开启系统站点，开启为正常对外服务，关闭则用户无法访问系统，仅管理员可登录维护。',
        'category' => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const SITE_URL = [
        'title'      => '网站地址',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'site_url',
        'value'      => '',
        'desc'       => '请填写系统完整网站地址（如 https://www.baidu.com），用于生成回调链接与资源访问路径。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const SITE_RECORD_NUMBER = [
        'title'      => '网站备案号',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'site_record_number',
        'value'      => '',
        'desc'       => '请填写工信部备案的网站备案号（如 陕ICP备xxxxxxxx号），用于合规展示与监管要求。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const GLOBAL_ATTACH_SIZE = [
        'title'      => '全局附件大小',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'global_attach_size',
        'value'      => '',
        'parameter'  => [],
        'desc'       => '设置系统内所有附件上传的最大允许大小（单位：MB），超出限制的文件将无法上传。',
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const SITE_NAME = [
        'title'      => '网站名称',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'site_name',
        'value'      => '',
        'desc'       => '请填写系统的官方名称，将显示在浏览器标签、页面标题及对外宣传中。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const SITE_TEL = [
        'title'      => '网站联系电话',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'site_tel',
        'value'      => '',
        'desc'       => '请填写系统对外的官方联系电话。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const ENTERPRISE_CULTURE = [
        'title'      => '企业文化语',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'enterprise_culture',
        'value'      => '高效团队铸就一流企业！！！',
        'desc'       => '请填写企业宣传标语或文化口号，将在系统首页置展示，传递企业理念。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const AI_STATUS = [
        'title'      => '开启Ai悬浮球',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'ai_status',
        'value'      => 1,
        'desc'       => '开启后，系统界面将显示 AI 悬浮球，用户可快速唤起 AI 助手；关闭则隐藏 AI 入口。',
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const AI_IMAGE = [
        'title'      => 'Ai悬浮球图',
        'type'       => 'upload',
        'input_type' => 'input',
        'key'        => 'ai_image',
        'value'      => 'https://crmebent.oss-cn-hangzhou.aliyuncs.com/attach/2025/04/a4697202504100927184844.jpg',
        'desc'       => '请上传 AI 悬浮球的自定义图标，建议为正方形透明底图，用于在界面中展示 AI 助手入口。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const UPLOAD_TYPE = [
        'title'      => '云存储类型',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'upload_type',
        'value'      => StorageEnum::UPLOAD_LOCAL,
        'parameter'  => [
            StorageEnum::UPLOAD_LOCAL => '本地存储',
            StorageEnum::UPLOAD_QINIU => '七牛云存储',
            StorageEnum::UPLOAD_ALI   => '阿里云存储',
            StorageEnum::UPLOAD_TX    => '腾讯云存储',
            StorageEnum::UPLOAD_JD    => '京东云存储',
            StorageEnum::UPLOAD_HW    => '华为云存储',
            StorageEnum::UPLOAD_TY    => '天翼云存储',
        ],
        'category' => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WPS_TYPE = [
        'title'      => '云文件预览类型',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'wps_type',
        'value'      => '1',
        'parameter'  => [
            '0' => 'WPS',
            '1' => 'PDF',
        ],
        'category' => CategoryEnum::WPS_CONFIG['key'],
    ];

    public const WPS_APPID = [
        'title'      => 'WPS AppId',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wps_appid',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WPS_CONFIG['key'],
    ];

    public const WPS_APPKEY = [
        'title'      => 'WPS AppKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'wps_appkey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::WPS_CONFIG['key'],
    ];

    public const ASSESS_COMPUTE_MODE = [
        'title'      => '评分方式',
        'type'       => 'radio',
        'input_type' => 'input',
        'key'        => 'assess_compute_mode',
        'value'      => 1,
        'parameter'  => ['加权评分', '加和评分'],
        'category'   => CategoryEnum::ASSESS_CONFIG['key'],
    ];

    public const ASSESS_SCORE_MARK = [
        'title'      => '评分说明',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'assess_score_mark',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::ASSESS_CONFIG['key'],
    ];

    public const SCHEDULE_SYNC = [
        'title'      => '同步日程',
        'type'       => 'radio',
        'input_type' => 'input',
        'key'        => 'schedule_sync',
        'desc'       => '开启后，系统将自动同步外部企业微信日程数据；关闭则不同步。',
        'value'      => 1,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const CUSTOMER_MODELS_ID = [
        'title'      => 'AI模型',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'customer_models_id',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_FOLLOW_CONFIG['key'],
    ];

    public const FOLLOW_UP_SWITCH = [
        'title'      => '客户跟进提醒',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'follow_up_switch',
        'value'      => 0,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_FOLLOW_CONFIG['key'],
    ];

    public const FOLLOW_UP_STATUS = [
        'title'      => '客户状态',
        'type'       => 'checkbox',
        'input_type' => '',
        'key'        => 'follow_up_status',
        'value'      => [],
        'parameter'  => ['未成交', '已成交'],
        'category'   => CategoryEnum::CUSTOMER_FOLLOW_CONFIG['key'],
    ];

    public const FOLLOW_UP_TRADED = [
        'title'      => '客户状态已成交提醒周期',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'follow_up_traded',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_FOLLOW_CONFIG['key'],
    ];

    public const FOLLOW_UP_UNSETTLED = [
        'title'      => '客户状态暂未成交提醒周期',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'follow_up_unsettled',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_FOLLOW_CONFIG['key'],
    ];

    public const RETURN_HIGH_SEAS_SWITCH = [
        'title'      => '自动退回公海规则',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'return_high_seas_switch',
        'value'      => 0,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_SEA_CONFIG['key'],
    ];

    public const UNSETTLED_CYCLE = [
        'title'      => '退回客户公海周期(暂未成交)',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'unsettled_cycle',
        'value'      => 30,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_SEA_CONFIG['key'],
    ];

    public const UNFOLLOWED_CYCLE = [
        'title'      => '未跟进退回公海(暂未成交)',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'unfollowed_cycle',
        'value'      => 30,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_SEA_CONFIG['key'],
    ];

    public const ADVANCE_CYCLE = [
        'title'      => '客户退回公海提醒提前',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'advance_cycle',
        'value'      => 5,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_SEA_CONFIG['key'],
    ];

    public const CLIENT_POLICY_SWITCH = [
        'title'      => '客户保单规则',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'client_policy_switch',
        'value'      => 0,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_SEA_CONFIG['key'],
    ];

    public const UNSETTLED_CLIENT_NUMBER = [
        'title'      => '暂未成交客户数量设置',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'unsettled_client_number',
        'value'      => 999,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_SEA_CONFIG['key'],
    ];

    public const CLUE_FOLLOW_SWITCH = [
        'title'      => '线索跟进提醒',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'clue_follow_switch',
        'value'      => 0,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CLUE_POOL_CONFIG['key'],
    ];

    public const CLUE_FOLLOW_DATE = [
        'title'      => '线索跟进提醒日期',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'clue_follow_date',
        'value'      => 3,
        'parameter'  => [],
        'category'   => CategoryEnum::CLUE_POOL_CONFIG['key'],
    ];

    public const RETURN_CLUE_SWITCH = [
        'title'      => '自动退回线索规则',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'return_clue_switch',
        'value'      => 0,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CLUE_POOL_CONFIG['key'],
    ];

    public const RETURN_CLUE_DATE = [
        'title'      => '退回线索周期(未转客户)',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'return_clue_date',
        'value'      => 7,
        'parameter'  => [],
        'category'   => CategoryEnum::CLUE_POOL_CONFIG['key'],
    ];

    public const RETURN_CLUE_CYCLE = [
        'title'      => '退回线索周期(未跟进)',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'return_clue_cycle',
        'value'      => 30,
        'parameter'  => [],
        'category'   => CategoryEnum::CLUE_POOL_CONFIG['key'],
    ];

    public const RETURN_CLUE_REMIND = [
        'title'      => '退回线索提醒',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'return_clue_remind',
        'value'      => 5,
        'parameter'  => [],
        'category'   => CategoryEnum::CLUE_POOL_CONFIG['key'],
    ];

    public const CLUE_POLICY_SWITCH = [
        'title'      => '线索保单规则',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'clue_policy_switch',
        'value'      => 0,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CLUE_POOL_CONFIG['key'],
    ];

    public const CLUE_POLICY_COUNT = [
        'title'      => '线索保单数量',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'clue_policy_count',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CLUE_POOL_CONFIG['key'],
    ];

    public const ODDS_FOLLOW_SWITCH = [
        'title'      => '商机跟进提醒',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'follow_up_switch',
        'value'      => 0,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::ODDS_FOLLOW_CONFIG['key'],
    ];

    public const ODDS_FOLLOW_STATUS = [
        'title'      => '商机状态',
        'type'       => 'checkbox',
        'input_type' => '',
        'key'        => 'follow_up_status',
        'value'      => [],
        'parameter'  => ['未成交', '已成交'],
        'category'   => CategoryEnum::ODDS_FOLLOW_CONFIG['key'],
    ];

    public const ODDS_FOLLOW_TRADED = [
        'title'      => '商机状态已成交提醒周期',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'follow_up_traded',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::ODDS_FOLLOW_CONFIG['key'],
    ];

    public const ODDS_FOLLOW_UNSETTLED = [
        'title'      => '商机状态暂未成交提醒周期',
        'type'       => 'text',
        'input_type' => 'number',
        'key'        => 'follow_up_unsettled',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::ODDS_FOLLOW_CONFIG['key'],
    ];

    public const CONTRACT_REFUND_SWITCH = [
        'title'      => '订单回款',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'contract_refund_switch',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_APPROVE_CONFIG['key'],
    ];

    public const CONTRACT_RENEW_SWITCH = [
        'title'      => '订单续费',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'contract_renew_switch',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_APPROVE_CONFIG['key'],
    ];

    public const CONTRACT_DISBURSE_SWITCH = [
        'title'      => '订单支出',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'contract_disburse_switch',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_APPROVE_CONFIG['key'],
    ];

    public const INVOICING_SWITCH = [
        'title'      => '开具发票',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'invoicing_switch',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_APPROVE_CONFIG['key'],
    ];

    public const VOID_INVOICE_SWITCH = [
        'title'      => '发票冲红',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'void_invoice_switch',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_APPROVE_CONFIG['key'],
    ];

    public const CONTRACT_SIGN_SWITCH = [
        'title'      => '合同签约',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'contract_sign_switch',
        'value'      => 0,
        'parameter'  => [],
        'category'   => CategoryEnum::CUSTOMER_APPROVE_CONFIG['key'],
    ];

    public const YIHAOTONG_APPID = [
        'title'      => '一号通AppId',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'yihaotong_appid',
        'value'      => '',
        'desc'       => '请登陆一号通平台，在一号通->授权管理->应用管理获取APPID',
        'parameter'  => [],
        'category'   => CategoryEnum::YIHT_CONFIG['key'],
    ];

    public const YIHAOTONG_APPSECRET = [
        'title'      => '一号通AppSecret',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'yihaotong_appsecret',
        'value'      => '',
        'desc'       => '请登陆一号通平台，在一号通->授权管理->应用管理获取AppSecret',
        'parameter'  => [],
        'category'   => CategoryEnum::YIHT_CONFIG['key'],
    ];

    public const UNI_PACKAGE_ID = [
        'title'      => '应用包名',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'uni_package_id',
        'desc'       => '请填写 App 的唯一包名（如 com.example.app），用于标识应用身份，需与客户端配置保持一致。',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::PUSH_CONFIG['key'],
    ];

    public const UNI_PUSH_APPID = [
        'title'      => '应用appId',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'uni_push_appid',
        'desc'       => '请填写 UniPush 平台分配的应用 appId，用于建立系统与推送服务的身份关联。',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::PUSH_CONFIG['key'],
    ];

    public const UNI_PUSH_APPKEY = [
        'title'      => '应用appKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'uni_push_appkey',
        'desc'       => '请填写 UniPush 平台分配的应用 appKey，严禁泄露，用于接口鉴权与推送权限验证。',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::PUSH_CONFIG['key'],
    ];

    public const UNI_PUSH_SECRET = [
        'title'      => '应用pushSecret',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'uni_push_secret',
        'desc'       => '请填写 UniPush 平台分配的应用 pushSecret，严禁泄露，用于推送消息的签名与安全校验。',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::PUSH_CONFIG['key'],
    ];

    public const UNI_PUSH_MASTER_SECRET = [
        'title'      => '应用MasterSecret',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'uni_push_master_secret',
        'desc'       => '请填写 UniPush 平台分配的应用 MasterSecret，严禁泄露，用于高权限推送接口调用。',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::PUSH_CONFIG['key'],
    ];

    public const TL_CODE = [
        'title'      => '授权密钥',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'tl_code',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::OTHER_CONFIG['key'],
    ];

    public const SYSTEM_CACHE_TTL = [
        'title'      => '缓存时间',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'system_cache_ttl',
        'value'      => 3600,
        'desc'       => '设置系统数据缓存的有效时长（单位：秒），如 3600 表示 1 小时，合理配置可提升系统访问速度。',
        'parameter'  => [],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const THUMB_BIG_WIDTH = [
        'title'      => '缩略大图（单位：px）：宽',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'thumb_big_width',
        'value'      => '800',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const THUMB_BIG_HEIGHT = [
        'title'      => '缩略大图（单位：px）：高',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'thumb_big_height',
        'value'      => '800',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const THUMB_MID_WIDTH = [
        'title'      => '缩略中图（单位：px）：宽',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'thumb_mid_width',
        'value'      => '300',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const THUMB_MID_HEIGHT = [
        'title'      => '缩略中图（单位：px）：高',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'thumb_mid_height',
        'value'      => '300',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const THUMB_SMALL_WIDTH = [
        'title'      => '缩略小图（单位：px）： 宽',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'thumb_small_width',
        'value'      => '150',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const THUMB_SMALL_HEIGHT = [
        'title'      => '缩略小图（单位：px）： 高',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'thumb_small_height',
        'value'      => '150',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const IMAGE_WATERMARK_STATUS = [
        'title'      => '是否开启水印',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'image_watermark_status',
        'value'      => '0',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_TYPE = [
        'title'      => '水印类型',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'watermark_type',
        'value'      => '1',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_IMAGE = [
        'title'      => '水印图片',
        'type'       => 'upload',
        'input_type' => 'input',
        'key'        => 'watermark_image',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_POSITION = [
        'title'      => '水印位置',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'watermark_position',
        'value'      => '5',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_OPACITY = [
        'title'      => '水印图片透明度',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'watermark_opacity',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_ROTATE = [
        'title'      => '水印图片倾斜度',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'watermark_rotate',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_TEXT = [
        'title'      => '水印文字',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'watermark_text',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_TEXT_SIZE = [
        'title'      => '水印文字大小（单位：px）',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'watermark_text_size',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_TEXT_COLOR = [
        'title'      => '水印字体颜色',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'watermark_text_color',
        'value'      => '#666666',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_TEXT_ANGLE = [
        'title'      => '水印字体旋转角度',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'watermark_text_angle',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_X = [
        'title'      => '水印横坐标偏移量（单位：px）',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'watermark_x',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const WATERMARK_Y = [
        'title'      => '水印纵坐标偏移量（单位：px）',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'watermark_y',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const ACCESSKEY = [
        'title'      => '阿里云云存储accessKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'accessKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const SECRETKEY = [
        'title'      => '阿里云存储secretKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'secretKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const QINIU_ACCESSKEY = [
        'title'      => '七牛云存储accessKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'qiniu_accessKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const QINIU_SECRETKEY = [
        'title'      => '七牛云存储secretKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'qiniu_secretKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const TENGXUN_ACCESSKEY = [
        'title'      => '腾讯云存储accessKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'tengxun_accessKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const TENGXUN_SECRETKEY = [
        'title'      => '腾讯云存储secretKey',
        'type'       => 'text',
        'input_type' => '',
        'key'        => 'tengxun_secretKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const JD_ACCESSKEY = [
        'title'      => '京东云accessKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'jd_accessKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const JD_SECRETKEY = [
        'title'      => '京东云secretKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'jd_secretKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const JD_STORAGE_REGION = [
        'title'      => '京东云StorageRegion',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'jd_storage_region',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const HW_ACCESSKEY = [
        'title'      => '华为云accessKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'hw_accessKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const HW_SECRETKEY = [
        'title'      => '华为云secretKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'hw_secretKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const HW_STORAGE_REGION = [
        'title'      => '华为云StorageRegion',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'hw_storage_region',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const TY_SECRETKEY = [
        'title'      => '天翼云secretKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'ty_secretKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const TY_ACCESSKEY = [
        'title'      => '天翼云accessKey',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'ty_accessKey',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const TENGXUN_APPID = [
        'title'      => '腾讯云APPID',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'tengxun_appid',
        'value'      => '',
        'parameter'  => [],
        'category'   => CategoryEnum::STORAGE_CONFIG['key'],
    ];

    public const REGISTRATION_OPEN = [
        'title'      => '开启用户注册',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'registration_open',
        'value'      => 0,
        'desc'       => '开启后，允许新用户自主注册账号；关闭则仅管理员可创建账号，提升系统安全性；APP应用上架需要开启此项。',
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const GLOBAL_WATERMARK_STATUS = [
        'title'      => '全局水印后台开关',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'global_watermark_status',
        'value'      => 1,
        'desc'       => '开启后，后台所有页面将自动添加水印，用于防止信息泄露与溯源追踪；关闭则不显示水印。',
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::SYSTEM_CONFIG['key'],
    ];

    public const FIREWALL_SWITCH = [
        'title'      => '防火墙开关',
        'input_type' => 'radio',
        'key'        => 'firewall_switch',
        'value'      => 0,
        'desc'       => '关闭:不验证请求数据;拦截:若非发请求则返回错误;过滤:过滤掉非法参数，程序继续执行',
        'parameter'  => [
            '0' => '关闭',
            '1' => '拦截',
            '2' => '过滤',
        ],
        'category' => CategoryEnum::FIREWALL_CONFIG['key'],
    ];

    public const FIREWALL_CONTENT = [
        'title'      => '防火墙规则',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'firewall_content',
        'value'      => ['\/\.\.\/', '\/\sand\s+.*=.*\/i', '\/\|\|.*?(?:ls|pwd|whoami|ll|ifconfig|ipconfig|&&|chmod|cd|mkdir|rmdir|cp|mv)\/i', '\/(onmouseover|onerror|onload|onclick)\=\/i', '\/<(iframe|script|body|img|layer|div|meta|style|base|object|input)\/i', '\/$_(GET|POST|COOKIE|FILES|SESSION|ENV|GLOBALS|SERVER)\/i', '\/(gopher|doc|php|glob|file|phar|zlib|ftp|ldap|dict|ogg|data)\:\/\/\/i', '\/$_(GET|POST|COOKIE|FILES|SESSION|ENV|GLOBALS|SERVER)\[\/i', '\/(?:define|eval|file_get_contents|include|require|require_once|shell_exec|phpinfo|system|passthru|preg_\w+|execute|echo|print|print_r|var_dump|(fp)open|alert|showmodaldialog)\(\/i', '\/group\s+by.+\(\/i', '\/into(\s+)(?:dump|out)file\s*\/i', '\/(?:etc\/\W*passwd)\/i', '\/(?:current_|user|database|schema|connection_id)\s*\(\/i', '\/(?:from\W+information_schema\W)\/i', '\/base64_decode\(\/i', '\/benchmark\((.*)\,(.*)\)\/i', '\/sleep\((\s*)(\d*)(\s*)\)\/i', '\/(having|updatexml|extractvalue)\/i', '\/(union[\s\S]*?select)\/i', '\/(select[\s\S]*?)(from|limit)\/i', '\/\bor\b.*=.*\/i', '\/\<\?\/'],
        'desc'       => '防火墙规则设置',
        'parameter'  => [],
        'category'   => CategoryEnum::FIREWALL_CONFIG['key'],
    ];

    public const UPLOAD_MIME = [
        'title'      => '允许上传文件类型',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'upload_mime',
        'value'      => [
            // 文档类型
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/rtf',
            'text/csv',
            // 图片类型
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/webp',
            'image/svg+xml',
            'image/tiff',
            // 音频类型
            'audio/mpeg',
            'audio/x-wav',
            'audio/x-ms-wma',
            // 视频类型
            'video/mp4',
            'video/x-msvideo',
            'video/x-ms-wmv',
            'video/quicktime',
            // 压缩包
            'application/zip',
            'application/x-rar-compressed',
            'application/x-tar',
            'application/x-gzip',
            'application/x-7z-compressed',
            // 其他
            'application/json',
            'text/html',
            'text/css',
            'application/javascript',
            'application/postscript',
            'application/x-shockwave-flash',
        ],
        'desc'      => '允许上传文件类型',
        'parameter' => [],
        'category'  => CategoryEnum::UPLOAD_CONFIG['key'],
    ];

    public const E_SIGNATURE = [
        'title'      => '电子签',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'e_signature',
        'value'      => 0,
        'desc'       => '是否开启电子签',
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::OTHER_CONFIG['key'],
    ];

    public const E_COMPANY_NAME = [
        'title'      => '电子签企业名称',
        'type'       => 'text',
        'input_type' => 'input',
        'key'        => 'e_company_name',
        'value'      => 0,
        'desc'       => '电子签企业名称',
        'parameter'  => [],
        'category'   => CategoryEnum::OTHER_CONFIG['key'],
    ];

    /**
     * 线索模块开关.
     */
    public const LEAD_MODULE_SWITCH = [
        'title'      => '线索模块',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'lead_module_switch',
        'value'      => 1,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_MODULE_CONFIG['key'],
    ];

    /**
     * 客户模块开关.
     */
    public const CUSTOMER_MODULE_SWITCH = [
        'title'      => '客户模块',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'customer_module_switch',
        'value'      => 1,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_MODULE_CONFIG['key'],
    ];

    /**
     * 联系人模块开关.
     */
    public const LIAISON_MODULE_SWITCH = [
        'title'      => '联系人模块',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'liaison_module_switch',
        'value'      => 1,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_MODULE_CONFIG['key'],
    ];

    /**
     * 商机模块开关.
     */
    public const OPPORTUNITY_MODULE_SWITCH = [
        'title'      => '商机模块',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'opportunity_module_switch',
        'value'      => 1,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_MODULE_CONFIG['key'],
    ];

    /**
     * 合同模块开关.
     */
    public const CONTRACT_MODULE_SWITCH = [
        'title'      => '合同模块',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'contract_module_switch',
        'value'      => 1,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_MODULE_CONFIG['key'],
    ];

    /**
     * 订单模块开关.
     */
    public const ORDER_MODULE_SWITCH = [
        'title'      => '订单模块',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'order_module_switch',
        'value'      => 1,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_MODULE_CONFIG['key'],
    ];

    /**
     * 订单自动生成出库单规则.
     */
    public const ORDER_AUTO_OUTBOUND_RULE = [
        'title'      => '自动生成出库单规则',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'order_auto_outbound_rule',
        'value'      => 0,
        'parameter'  => ['不自动生成出库单', '添加订单时自动生成出库单'],
        'category'   => CategoryEnum::CUSTOMER_OUTBOUND_CONFIG['key'],
    ];

    /**
     * 关联订单发货规则.
     */
    public const ORDER_DELIVERY_RULE = [
        'title'      => '关联订单发货规则',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'order_delivery_rule',
        'value'      => 2,
        'parameter'  => ['订单首次回款后，才能拣货出库', '订单结清后，才能拣货出库', '订单未回款时，仍支持拣货出库'],
        'category'   => CategoryEnum::CUSTOMER_OUTBOUND_CONFIG['key'],
    ];

    /**
     * 发票模块开关.
     */
    public const INVOICE_MODULE_SWITCH = [
        'title'      => '发票模块',
        'type'       => 'radio',
        'input_type' => '',
        'key'        => 'invoice_module_switch',
        'value'      => 1,
        'parameter'  => ['关闭', '开启'],
        'category'   => CategoryEnum::CUSTOMER_MODULE_CONFIG['key'],
    ];
}
