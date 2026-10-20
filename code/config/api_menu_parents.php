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
return [
    'DELETE|api/ent/approve/apply/{param}' => [
        'component' => 'business/record/index',
        'menu_name' => '审批记录',
    ],
    'DELETE|api/ent/approve/config/{param}' => [
        'component' => 'business/examine/index',
        'menu_name' => '审批设置',
    ],
    'DELETE|api/ent/approve/holiday_type/{param}' => [
        'component' => 'business/holidayType/index',
        'menu_name' => '假期类型',
    ],
    'DELETE|api/ent/approve/reply/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/attendance/cycle/{param}' => [
        'component' => 'hr/attendance/setting/addConent',
        'menu_name' => '新增考勤设置',
    ],
    'DELETE|api/ent/attendance/group/{param}' => [
        'component' => 'hr/attendance/setting/team',
        'menu_name' => '考勤组设置',
    ],
    'DELETE|api/ent/attendance/shift/{param}' => [
        'component' => 'hr/attendance/setting/shift',
        'menu_name' => '班次设置',
    ],
    'DELETE|api/ent/bill/{param}' => [
        'component' => 'fd/enterprise/list/index',
        'menu_name' => '收支记账',
    ],
    'DELETE|api/ent/bill_cate/{param}' => [
        'component' => 'fd/setup/cate/income',
        'menu_name' => '收入分类',
    ],
    'DELETE|api/ent/chat/applications/{param}' => [
        'component' => 'chat',
        'menu_name' => 'AI',
    ],
    'DELETE|api/ent/chat/mcp/{param}' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'DELETE|api/ent/chat/models/{param}' => [
        'component' => 'chat/model',
        'menu_name' => '模型设置',
    ],
    'DELETE|api/ent/client/bill/finance/{param}' => [
        'component' => 'fd/examine/pending',
        'menu_name' => '待处理',
    ],
    'DELETE|api/ent/client/bill/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/client/clues/{param}' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'DELETE|api/ent/client/contract_doc/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/client/contracts/{param}' => [
        'component' => 'customer/contract/index',
        'menu_name' => '订单列表',
    ],
    'DELETE|api/ent/client/customer/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'DELETE|api/ent/client/file/delete/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/client/follow/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/client/invoice/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/client/invoice_category/{param}' => [
        'component' => 'customer/setup/work',
        'menu_name' => '业务设置',
    ],
    'DELETE|api/ent/client/labels/{param}' => [
        'component' => 'customer/setup/label',
        'menu_name' => '客户标签',
    ],
    'DELETE|api/ent/client/liaisons/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/client/odds/{param}' => [
        'component' => 'customer/opportunityManagement/index',
        'menu_name' => '商机列表',
    ],
    'DELETE|api/ent/client/product/cate/{param}' => [
        'component' => 'customer/product/category',
        'menu_name' => '产品分类',
    ],
    'DELETE|api/ent/client/products/{param}' => [
        'component' => 'customer/product/index',
        'menu_name' => '产品管理',
    ],
    'DELETE|api/ent/client/remind/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/client/resources/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/client/targets' => [
        'component' => 'customer/kpi/index',
        'menu_name' => '目标管理',
    ],
    'DELETE|api/ent/cloud/file/{param}/batch_delete' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'DELETE|api/ent/cloud/file/{param}/delete/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'DELETE|api/ent/cloud/space/delete/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'DELETE|api/ent/cloud/space/force_delete/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'DELETE|api/ent/cloud/space/force_deletes' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'DELETE|api/ent/company/card/batch' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'DELETE|api/ent/company/card/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'DELETE|api/ent/company/evaluate/{param}' => [
        'component' => 'hr/tool/haishAssessment/index',
        'menu_name' => '海氏量表',
    ],
    'DELETE|api/ent/company/promotion/data/{param}' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'DELETE|api/ent/company/promotions/{param}' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'DELETE|api/ent/company/salary/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'DELETE|api/ent/config/dict_data/{param}' => [
        'component' => 'customer/setup/dictionary/management',
        'menu_name' => '数据管理',
    ],
    'DELETE|api/ent/config/dict_type/{param}' => [
        'component' => 'customer/setup/dictionary/index',
        'menu_name' => '字典配置',
    ],
    'DELETE|api/ent/config/form/cate/{param}' => [
        'component' => 'customer/setup/customForm/index',
        'menu_name' => '自定义表单',
    ],
    'DELETE|api/ent/config/frame/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'DELETE|api/ent/config/quick/{param}' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'DELETE|api/ent/config/quickCate/{param}' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'DELETE|api/ent/config/storage/{param}' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'DELETE|api/ent/crud/approve/{param}' => [
        'component' => 'develop/approve/index',
        'menu_name' => '流程管理',
    ],
    'DELETE|api/ent/crud/cate/del/{param}' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'DELETE|api/ent/crud/curl/{param}' => [
        'component' => 'develop/dataManagement/index',
        'menu_name' => '外部对接',
    ],
    'DELETE|api/ent/crud/dashboard/{param}' => [
        'component' => 'system/dashboard-design/list/index',
        'menu_name' => '图表列表',
    ],
    'DELETE|api/ent/crud/database/del/{param}' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'DELETE|api/ent/crud/event/del/{param}' => [
        'component' => 'develop/event/index',
        'menu_name' => '触发器管理',
    ],
    'DELETE|api/ent/crud/field/del/{param}' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'DELETE|api/ent/crud/module/{param}/batchdelete' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'DELETE|api/ent/crud/module/{param}/cancel_share/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'DELETE|api/ent/crud/module/{param}/comment/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'DELETE|api/ent/crud/module/{param}/delete/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'DELETE|api/ent/crud/module/{param}/questionnaire/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'DELETE|api/ent/crud/module/{param}/share/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'DELETE|api/ent/daily/reply/{param}/{param}' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'DELETE|api/ent/daily/{param}' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'DELETE|api/ent/education/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'DELETE|api/ent/enterprise/folder/{param}/destroy' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'DELETE|api/ent/folder/delete' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'DELETE|api/ent/folder/destroy' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'DELETE|api/ent/jobs/{param}' => [
        'component' => 'hr/enterprise/job/jobAdministration',
        'menu_name' => '职位管理',
    ],
    'DELETE|api/ent/notice/category/{param}' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'DELETE|api/ent/notice/list/{param}' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'DELETE|api/ent/openapi/key/{param}' => [
        'component' => 'develop/foreign/index',
        'menu_name' => '授权密钥',
    ],
    'DELETE|api/ent/pay_type/{param}' => [
        'component' => 'fd/setup/type/index',
        'menu_name' => '支付方式',
    ],
    'DELETE|api/ent/program/{param}' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'DELETE|api/ent/program_file/{param}' => [
        'component' => 'program/programList/taskDetails',
        'menu_name' => '任务详情',
    ],
    'DELETE|api/ent/program_task/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/rank/{param}' => [
        'component' => 'hr/enterprise/job/rankManagement',
        'menu_name' => '职级管理',
    ],
    'DELETE|api/ent/rank_cate/{param}' => [
        'component' => 'hr/enterprise/job/rankManagement',
        'menu_name' => '职级管理',
    ],
    'DELETE|api/ent/rank_level/relation/{param}' => [
        'component' => 'hr/enterprise/job/positionSystemChart',
        'menu_name' => '职级体系图',
    ],
    'DELETE|api/ent/rank_level/{param}' => [
        'component' => 'hr/enterprise/job/positionSystemChart',
        'menu_name' => '职级体系图',
    ],
    'DELETE|api/ent/schedule/delete/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/schedule/reply/del/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/schedule/type/delete/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/storage/cate/{param}' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'DELETE|api/ent/storage/list/{param}' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'DELETE|api/ent/system/data/delete/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'DELETE|api/ent/system/menus/{param}' => [
        'component' => 'setting/system/menus/index',
        'menu_name' => '菜单管理',
    ],
    'DELETE|api/ent/system/roles/{param}' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'DELETE|api/ent/task_comment/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'DELETE|api/ent/user/education/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'DELETE|api/ent/user/ent/apply/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'DELETE|api/ent/user/work/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'DELETE|api/ent/work/mass_messaging/{param}' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'DELETE|api/ent/work/mass_messaging_temp/{param}' => [
        'component' => 'customer/weChatMass/mass',
        'menu_name' => '群发素材',
    ],
    'DELETE|api/ent/work/mass_messaging_temp_group/{param}' => [
        'component' => 'customer/weChatMass/mass',
        'menu_name' => '群发素材',
    ],
    'DELETE|api/ent/work/reply_temp/{param}' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'DELETE|api/ent/work/reply_temp_group/{param}' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'DELETE|api/ent/work/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/approve/apply' => [
        'component' => 'user/examine/mine',
        'menu_name' => '我的申请',
    ],
    'GET|api/ent/approve/apply/export' => [
        'component' => 'business/record/index',
        'menu_name' => '审批记录',
    ],
    'GET|api/ent/approve/apply/form/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/approve/apply/urge/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/approve/apply/verify/{param}/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/approve/apply/{param}/edit' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/approve/config' => [
        'component' => 'business/examine/index',
        'menu_name' => '审批设置',
    ],
    'GET|api/ent/approve/config/search/{param}' => [
        'component' => 'user/examine/mine',
        'menu_name' => '我的申请',
    ],
    'GET|api/ent/approve/config/{param}' => [
        'component' => 'business/examine/index',
        'menu_name' => '审批设置',
    ],
    'GET|api/ent/approve/config/{param}/edit' => [
        'component' => 'business/examine/index',
        'menu_name' => '审批设置',
    ],
    'GET|api/ent/approve/holiday_type/info/{param}' => [
        'component' => 'business/holidayType/index',
        'menu_name' => '假期类型',
    ],
    'GET|api/ent/approve/holiday_type/list' => [
        'component' => 'business/holidayType/index',
        'menu_name' => '假期类型',
    ],
    'GET|api/ent/approve/holiday_type/select' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/assess/abnormal' => [
        'component' => 'hr/assess/staff/mentStaff',
        'menu_name' => '考核记录',
    ],
    'GET|api/ent/assess/del_form/{param}' => [
        'component' => 'user/assessment/department',
        'menu_name' => '部门考核',
    ],
    'GET|api/ent/assess/del_record' => [
        'component' => 'hr/assess/staff/deleteRecord',
        'menu_name' => '删除记录',
    ],
    'GET|api/ent/assess/explain/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/assess/index' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/assess/info/{param}' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/assess/is_abnormal' => [
        'component' => 'hr/assess/staff/mentStaff',
        'menu_name' => '考核记录',
    ],
    'GET|api/ent/assess/list' => [
        'component' => 'hr/assess/staff/mentStaff',
        'menu_name' => '考核记录',
    ],
    'GET|api/ent/assess/plan/period' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/assess/plan/user_list' => [
        'component' => 'hr/assess/config/mentPlan',
        'menu_name' => '考核计划',
    ],
    'GET|api/ent/assess/plan/{param}/edit' => [
        'component' => 'hr/assess/config/mentPlan',
        'menu_name' => '考核计划',
    ],
    'GET|api/ent/assess/score' => [
        'component' => 'hr/assess/config/mentScore',
        'menu_name' => '考核评分',
    ],
    'GET|api/ent/assess/score/{param}' => [
        'component' => 'user/assessment/my',
        'menu_name' => '我的考核',
    ],
    'GET|api/ent/assess/show/{param}' => [
        'component' => 'user/assessment/department',
        'menu_name' => '部门考核',
    ],
    'GET|api/ent/assess/target_cate' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/assess/verify' => [
        'component' => 'hr/assess/config/mentProcess',
        'menu_name' => '考核流程',
    ],
    'GET|api/ent/attendance/abnormal_date' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/attendance/arrange' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'GET|api/ent/attendance/arrange/info/{param}' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'GET|api/ent/attendance/attendance_statistics' => [
        'component' => 'user/statistic/single',
        'menu_name' => '个人统计',
    ],
    'GET|api/ent/attendance/calendar/{param}' => [
        'component' => 'hr/attendance/setting/calendarsetUp',
        'menu_name' => '日历配置',
    ],
    'GET|api/ent/attendance/clock_record' => [
        'component' => 'hr/attendance/statistics/clock',
        'menu_name' => '打卡记录',
    ],
    'GET|api/ent/attendance/clock_record/{param}' => [
        'component' => 'hr/attendance/statistics/clock',
        'menu_name' => '打卡记录',
    ],
    'GET|api/ent/attendance/cycle/info/{param}/{param}' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'GET|api/ent/attendance/cycle/list/{param}' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'GET|api/ent/attendance/daily_statistics' => [
        'component' => 'hr/attendance/statistics/daily',
        'menu_name' => '每日统计',
    ],
    'GET|api/ent/attendance/group' => [
        'component' => 'hr/attendance/setting/team',
        'menu_name' => '考勤组设置',
    ],
    'GET|api/ent/attendance/group/info/{param}' => [
        'component' => 'hr/attendance/setting/addConent',
        'menu_name' => '新增考勤设置',
    ],
    'GET|api/ent/attendance/group/member' => [
        'component' => 'hr/attendance/setting/addConent',
        'menu_name' => '新增考勤设置',
    ],
    'GET|api/ent/attendance/group/select' => [
        'component' => 'hr/attendance/statistics/daily',
        'menu_name' => '每日统计',
    ],
    'GET|api/ent/attendance/group/unattended_member' => [
        'component' => 'hr/attendance/setting/team',
        'menu_name' => '考勤组设置',
    ],
    'GET|api/ent/attendance/group/white' => [
        'component' => 'hr/attendance/setting/white',
        'menu_name' => '白名单设置',
    ],
    'GET|api/ent/attendance/individual_statistics' => [
        'component' => 'user/statistic/single',
        'menu_name' => '个人统计',
    ],
    'GET|api/ent/attendance/monthly_statistics' => [
        'component' => 'hr/attendance/statistics/monthly',
        'menu_name' => '月度统计',
    ],
    'GET|api/ent/attendance/shift' => [
        'component' => 'hr/attendance/setting/shift',
        'menu_name' => '班次设置',
    ],
    'GET|api/ent/attendance/shift/info/{param}' => [
        'component' => 'hr/attendance/setting/shift',
        'menu_name' => '班次设置',
    ],
    'GET|api/ent/attendance/shift/select' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'GET|api/ent/attendance/statistics/{param}' => [
        'component' => 'hr/attendance/statistics/daily',
        'menu_name' => '每日统计',
    ],
    'GET|api/ent/attendance/work_clock_record' => [
        'component' => 'hr/attendance/statistics/daily',
        'menu_name' => '每日统计',
    ],
    'GET|api/ent/bill/create' => [
        'component' => 'fd/enterprise/list/index',
        'menu_name' => '收支记账',
    ],
    'GET|api/ent/bill/record/{param}' => [
        'component' => 'fd/enterprise/list/index',
        'menu_name' => '收支记账',
    ],
    'GET|api/ent/bill/{param}/edit' => [
        'component' => 'fd/enterprise/list/index',
        'menu_name' => '收支记账',
    ],
    'GET|api/ent/bill_cate' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/bill_cate/create?pid={param}' => [
        'component' => 'fd/setup/cate/income',
        'menu_name' => '收入分类',
    ],
    'GET|api/ent/bill_cate/{param}/edit' => [
        'component' => 'fd/setup/cate/income',
        'menu_name' => '收入分类',
    ],
    'GET|api/ent/chat/applications' => [
        'component' => 'chat',
        'menu_name' => 'AI',
    ],
    'GET|api/ent/chat/applications/databes/list' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'GET|api/ent/chat/applications/{param}' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'GET|api/ent/chat/applications/{param}/edit' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'GET|api/ent/chat/mcp' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'GET|api/ent/chat/models' => [
        'component' => 'chat/model',
        'menu_name' => '模型设置',
    ],
    'GET|api/ent/chat/models/list' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'GET|api/ent/chat/models/options' => [
        'component' => 'chat/model',
        'menu_name' => '模型设置',
    ],
    'GET|api/ent/chat/models/select' => [
        'component' => 'chat/model',
        'menu_name' => '模型设置',
    ],
    'GET|api/ent/chat/models/{param}/edit' => [
        'component' => 'chat/model',
        'menu_name' => '模型设置',
    ],
    'GET|api/ent/client/bill' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/bill/contract_statistics/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/bill/customer_statistics/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/bill/list' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/bill/un_invoiced_list' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/clues/create' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'GET|api/ent/client/clues/{param}/edit' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'GET|api/ent/client/config/list' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contract_category' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contract_doc' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contract_doc/cancel/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contract_doc/orders/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contract_doc/signatory/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contract_doc/task/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contract_doc/{param}/edit' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contracts/bill_cate/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contracts/create' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contracts/select' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/contracts/{param}/edit' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/customer/base/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/customer/category_enabled' => [
        'component' => 'customer/turnover/index',
        'menu_name' => '业绩统计',
    ],
    'GET|api/ent/client/customer/create' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'GET|api/ent/client/customer/salesman' => [
        'component' => 'customer/invoice/index',
        'menu_name' => '发票',
    ],
    'GET|api/ent/client/customer/select' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'GET|api/ent/client/customer/{param}/edit' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/data/select' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/data/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/file/index' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/follow' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/invoice' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/invoice/bill/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/invoice/info/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/invoice/list' => [
        'component' => 'fd/invoice/pending',
        'menu_name' => '待开发票',
    ],
    'GET|api/ent/client/invoice/price_statistics' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/invoice/record/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/invoice/uri/{param}' => [
        'component' => 'fd/invoice/pending',
        'menu_name' => '待开发票',
    ],
    'GET|api/ent/client/invoice_category' => [
        'component' => 'customer/setup/work',
        'menu_name' => '业务设置',
    ],
    'GET|api/ent/client/labels' => [
        'component' => 'customer/setup/label',
        'menu_name' => '客户标签',
    ],
    'GET|api/ent/client/labels/auth_work_client_label' => [
        'component' => 'customer/setup/label',
        'menu_name' => '客户标签',
    ],
    'GET|api/ent/client/liaisons' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/liaisons/create' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/liaisons/{param}/edit' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/odds/create' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/odds/{param}/edit' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/product/cate' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/product/cate/create' => [
        'component' => 'customer/product/category',
        'menu_name' => '产品分类',
    ],
    'GET|api/ent/client/product/cate/{param}' => [
        'component' => 'customer/product/category',
        'menu_name' => '产品分类',
    ],
    'GET|api/ent/client/product/cate/{param}/edit' => [
        'component' => 'customer/product/category',
        'menu_name' => '产品分类',
    ],
    'GET|api/ent/client/products/attrs' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/products/create' => [
        'component' => 'customer/product/addProduct',
        'menu_name' => '添加产品',
    ],
    'GET|api/ent/client/products/info/{param}' => [
        'component' => 'customer/product/index',
        'menu_name' => '产品管理',
    ],
    'GET|api/ent/client/products/{param}/edit' => [
        'component' => 'customer/product/addProduct',
        'menu_name' => '添加产品',
    ],
    'GET|api/ent/client/record' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/remind' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/remind/info/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/resources' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/client/targets' => [
        'component' => 'customer/kpi/index',
        'menu_name' => '目标管理',
    ],
    'GET|api/ent/client/targets/census' => [
        'component' => 'customer/targetStatistics/index',
        'menu_name' => '目标统计',
    ],
    'GET|api/ent/client/targets/rate' => [
        'component' => 'customer/targetStatistics/index',
        'menu_name' => '目标统计',
    ],
    'GET|api/ent/cloud/file/{param}/info/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/cloud/file/{param}/list' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/cloud/file/{param}/rules/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/cloud/space/dir' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/cloud/space/lately' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/cloud/space/list' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/cloud/space/recycle' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/cloud/space/rules/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/common/auth' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/common/city' => [
        'component' => 'setting/enterprise/info/basic',
        'menu_name' => '企业信息',
    ],
    'GET|api/ent/common/download_url' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/common/site' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'GET|api/ent/common/upload_key' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/common/version' => [
        'component' => 'setting/auth/auth/index',
        'menu_name' => '商业授权',
    ],
    'GET|api/ent/common/weather' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/company/card/change' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/company/card/formal/{param}' => [
        'component' => 'hr/archives/unemployed',
        'menu_name' => '未入职员工',
    ],
    'GET|api/ent/company/card/import/temp' => [
        'component' => 'hr/archives/unemployed',
        'menu_name' => '未入职员工',
    ],
    'GET|api/ent/company/card/info/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/company/card/interview' => [
        'component' => 'hr/archives/unemployed',
        'menu_name' => '未入职员工',
    ],
    'GET|api/ent/company/card/perfect/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/company/evaluate' => [
        'component' => 'hr/tool/haishAssessment/index',
        'menu_name' => '海氏量表',
    ],
    'GET|api/ent/company/evaluate/data/{param}' => [
        'component' => 'hr/tool/haishAssessment/index',
        'menu_name' => '海氏量表',
    ],
    'GET|api/ent/company/evaluate/history/{param}' => [
        'component' => 'hr/tool/haishAssessment/index',
        'menu_name' => '海氏量表',
    ],
    'GET|api/ent/company/info' => [
        'component' => 'hr/enterprise/job/jobAdministration',
        'menu_name' => '职位管理',
    ],
    'GET|api/ent/company/job_analysis' => [
        'component' => 'user/duty/analyse',
        'menu_name' => '工作分析表',
    ],
    'GET|api/ent/company/job_analysis/info/{param}' => [
        'component' => 'user/duty/analyse',
        'menu_name' => '工作分析表',
    ],
    'GET|api/ent/company/job_analysis/mine' => [
        'component' => 'user/duty/analyse',
        'menu_name' => '工作分析表',
    ],
    'GET|api/ent/company/message' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/company/message/list' => [
        'component' => 'setting/enterprise/news/record',
        'menu_name' => '推送记录',
    ],
    'GET|api/ent/company/promotion/data' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'GET|api/ent/company/promotions' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'GET|api/ent/company/promotions/{param}' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'GET|api/ent/company/quantity/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/company/salary' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/company/salary/last/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/company/salary/{param}/edit' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/company/train/{param}' => [
        'component' => 'hr/training/companyProfile',
        'menu_name' => '公司介绍',
    ],
    'GET|api/ent/config/cate' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'GET|api/ent/config/client_rule/approve/{param}' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/config/client_rule/cate' => [
        'component' => 'customer/setup/ruleSettings/index',
        'menu_name' => '规则设置',
    ],
    'GET|api/ent/config/client_rule/{param}' => [
        'component' => 'customer/setup/ruleSettings/index',
        'menu_name' => '规则设置',
    ],
    'GET|api/ent/config/data/firewall' => [
        'component' => 'setting/system/firewall',
        'menu_name' => '防火墙',
    ],
    'GET|api/ent/config/data/updateConfig' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'GET|api/ent/config/dict_data' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/config/dict_data/create' => [
        'component' => 'customer/setup/dictionary/management',
        'menu_name' => '数据管理',
    ],
    'GET|api/ent/config/dict_data/{param}' => [
        'component' => 'customer/setup/dictionary/management',
        'menu_name' => '数据管理',
    ],
    'GET|api/ent/config/dict_data/{param}/edit' => [
        'component' => 'customer/setup/dictionary/management',
        'menu_name' => '数据管理',
    ],
    'GET|api/ent/config/dict_type' => [
        'component' => 'customer/setup/dictionary/index',
        'menu_name' => '字典配置',
    ],
    'GET|api/ent/config/dict_type/create' => [
        'component' => 'customer/setup/dictionary/index',
        'menu_name' => '字典配置',
    ],
    'GET|api/ent/config/dict_type/info/{param}' => [
        'component' => 'customer/setup/dictionary/management',
        'menu_name' => '数据管理',
    ],
    'GET|api/ent/config/dict_type/{param}' => [
        'component' => 'customer/setup/dictionary/index',
        'menu_name' => '字典配置',
    ],
    'GET|api/ent/config/dict_type/{param}/edit' => [
        'component' => 'customer/setup/dictionary/index',
        'menu_name' => '字典配置',
    ],
    'GET|api/ent/config/form/cate' => [
        'component' => 'customer/setup/customForm/index',
        'menu_name' => '自定义表单',
    ],
    'GET|api/ent/config/form/cate/{param}' => [
        'component' => 'customer/setup/customForm/index',
        'menu_name' => '自定义表单',
    ],
    'GET|api/ent/config/form/data/fields/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/config/frame' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/config/frame/create' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/config/frame/scope' => [
        'component' => 'user/daily/department',
        'menu_name' => '部门汇报',
    ],
    'GET|api/ent/config/frame/{param}/edit' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/config/quick' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'GET|api/ent/config/quick/create' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'GET|api/ent/config/quick/{param}' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'GET|api/ent/config/quick/{param}/edit' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'GET|api/ent/config/quickCate' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'GET|api/ent/config/quickCate/create' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'GET|api/ent/config/quickCate/{param}/edit' => [
        'component' => 'setting/system/quick/index',
        'menu_name' => '快捷入口',
    ],
    'GET|api/ent/config/storage/create/{param}' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'GET|api/ent/config/storage/domain/{param}' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'GET|api/ent/config/storage/form/{param}' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'GET|api/ent/config/storage/index' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'GET|api/ent/config/storage/method' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'GET|api/ent/config/storage/sync/{param}' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'GET|api/ent/crud/approve' => [
        'component' => 'develop/approve/index',
        'menu_name' => '流程管理',
    ],
    'GET|api/ent/crud/approve/{param}' => [
        'component' => 'develop/approve/index',
        'menu_name' => '流程管理',
    ],
    'GET|api/ent/crud/cate/find/{param}' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'GET|api/ent/crud/cate/list' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'GET|api/ent/crud/crud_dict/list/{param}' => [
        'component' => 'develop/dictionary/optionSetting',
        'menu_name' => '字典设置',
    ],
    'GET|api/ent/crud/curl' => [
        'component' => 'develop/dataManagement/index',
        'menu_name' => '外部对接',
    ],
    'GET|api/ent/crud/curl/{param}/edit' => [
        'component' => 'develop/dataManagement/index',
        'menu_name' => '外部对接',
    ],
    'GET|api/ent/crud/dashboard' => [
        'component' => 'system/dashboard-design/list/index',
        'menu_name' => '图表列表',
    ],
    'GET|api/ent/crud/dashboard/design/{param}' => [
        'component' => 'develop/dashboard/index',
        'menu_name' => '项目组统计',
    ],
    'GET|api/ent/crud/database/info/{param}' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'GET|api/ent/crud/database/list' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'GET|api/ent/crud/database/tree' => [
        'component' => 'develop/dictionary/index',
        'menu_name' => '数据字典',
    ],
    'GET|api/ent/crud/event/action' => [
        'component' => 'develop/event/index',
        'menu_name' => '触发器管理',
    ],
    'GET|api/ent/crud/event/list' => [
        'component' => 'develop/event/index',
        'menu_name' => '触发器管理',
    ],
    'GET|api/ent/crud/event/list/{param}' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'GET|api/ent/crud/event/log' => [
        'component' => 'develop/event/log',
        'menu_name' => '触发日志',
    ],
    'GET|api/ent/crud/event/type' => [
        'component' => 'develop/event/index',
        'menu_name' => '触发器管理',
    ],
    'GET|api/ent/crud/field/info/{param}' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'GET|api/ent/crud/field/list/{param}' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'GET|api/ent/crud/field/type' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'GET|api/ent/crud/import/temp' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'GET|api/ent/crud/module/{param}/comment/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'GET|api/ent/crud/module/{param}/create' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'GET|api/ent/crud/module/{param}/crud/info/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'GET|api/ent/crud/module/{param}/find/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'GET|api/ent/crud/module/{param}/frame/tree' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'GET|api/ent/crud/module/{param}/log/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'GET|api/ent/crud/module/{param}/questionnaire' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'GET|api/ent/crud/module/{param}/senior/list' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'GET|api/ent/crud/module/{param}/share/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'GET|api/ent/daily' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'GET|api/ent/daily/export' => [
        'component' => 'hr/report/index',
        'menu_name' => '汇报管理',
    ],
    'GET|api/ent/daily/no_submit_list' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'GET|api/ent/daily/report_list' => [
        'component' => 'user/daily/department',
        'menu_name' => '部门汇报',
    ],
    'GET|api/ent/daily/report_member' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'GET|api/ent/daily/schedule_record/{param}' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'GET|api/ent/daily/statistics' => [
        'component' => 'user/daily/department',
        'menu_name' => '部门汇报',
    ],
    'GET|api/ent/daily/submit_list' => [
        'component' => 'user/daily/department',
        'menu_name' => '部门汇报',
    ],
    'GET|api/ent/daily/submit_statistics' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'GET|api/ent/daily/users' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'GET|api/ent/daily/{param}/edit' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'GET|api/ent/education' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/education/create' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/education/{param}/edit' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/enterprise/folder/{param}/match' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/folder/detail/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/folder/dir_lst' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/folder/total' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'GET|api/ent/jobs' => [
        'component' => 'hr/enterprise/job/jobAdministration',
        'menu_name' => '职位管理',
    ],
    'GET|api/ent/jobs/create' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/jobs/select' => [
        'component' => 'user/duty/analyse',
        'menu_name' => '工作分析表',
    ],
    'GET|api/ent/jobs/subordinate' => [
        'component' => 'user/duty/explain',
        'menu_name' => '岗位职责',
    ],
    'GET|api/ent/jobs/subordinate/{param}' => [
        'component' => 'user/duty/explain',
        'menu_name' => '岗位职责',
    ],
    'GET|api/ent/jobs/{param}/edit' => [
        'component' => 'user/duty/explain',
        'menu_name' => '岗位职责',
    ],
    'GET|api/ent/notice/category' => [
        'component' => 'user/notice/index',
        'menu_name' => '企业动态',
    ],
    'GET|api/ent/notice/category/create' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'GET|api/ent/notice/category/{param}/edit' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'GET|api/ent/notice/detail/{param}' => [
        'component' => 'user/notice/index',
        'menu_name' => '企业动态',
    ],
    'GET|api/ent/notice/index_list' => [
        'component' => 'user/notice/index',
        'menu_name' => '企业动态',
    ],
    'GET|api/ent/notice/list' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/notice/list/{param}' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'GET|api/ent/notice/list/{param}/edit' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'GET|api/ent/openapi/docs' => [
        'component' => 'develop/foreign/document',
        'menu_name' => '接口文档',
    ],
    'GET|api/ent/openapi/key' => [
        'component' => 'develop/foreign/index',
        'menu_name' => '授权密钥',
    ],
    'GET|api/ent/openapi/key/{param}' => [
        'component' => 'develop/foreign/index',
        'menu_name' => '授权密钥',
    ],
    'GET|api/ent/openapi/key/{param}/edit' => [
        'component' => 'develop/foreign/index',
        'menu_name' => '授权密钥',
    ],
    'GET|api/ent/openapi/role' => [
        'component' => 'develop/foreign/index',
        'menu_name' => '授权密钥',
    ],
    'GET|api/ent/pay_type' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/pay_type/create' => [
        'component' => 'fd/setup/type/index',
        'menu_name' => '支付方式',
    ],
    'GET|api/ent/pay_type/{param}/edit' => [
        'component' => 'fd/setup/type/index',
        'menu_name' => '支付方式',
    ],
    'GET|api/ent/program' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'GET|api/ent/program/info/{param}' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'GET|api/ent/program/members' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/program/select' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/program_dynamic/program' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'GET|api/ent/program_dynamic/task' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/program_file/index' => [
        'component' => 'program/programList/taskDetails',
        'menu_name' => '任务详情',
    ],
    'GET|api/ent/program_task' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'GET|api/ent/program_task/info/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/program_task/select' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/program_version' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/program_version/select' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/rank' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/rank/create' => [
        'component' => 'hr/enterprise/job/rankManagement',
        'menu_name' => '职级管理',
    ],
    'GET|api/ent/rank/{param}/edit' => [
        'component' => 'hr/enterprise/job/rankManagement',
        'menu_name' => '职级管理',
    ],
    'GET|api/ent/rank_cate' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/rank_cate/create' => [
        'component' => 'hr/enterprise/job/rankManagement',
        'menu_name' => '职级管理',
    ],
    'GET|api/ent/rank_cate/{param}/edit' => [
        'component' => 'hr/enterprise/job/rankManagement',
        'menu_name' => '职级管理',
    ],
    'GET|api/ent/rank_level' => [
        'component' => 'hr/enterprise/job/positionSystemChart',
        'menu_name' => '职级体系图',
    ],
    'GET|api/ent/rank_level/rank/{param}' => [
        'component' => 'hr/enterprise/job/positionSystemChart',
        'menu_name' => '职级体系图',
    ],
    'GET|api/ent/schedule/info/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/schedule/replys' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/schedule/type/create' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/schedule/type/edit/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/schedule/types' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/storage/cate' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'GET|api/ent/storage/cate/create' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'GET|api/ent/storage/cate/{param}/edit' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'GET|api/ent/storage/record' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'GET|api/ent/storage/record/census' => [
        'component' => 'administration/material/chart/index',
        'menu_name' => '物资概览',
    ],
    'GET|api/ent/storage/record/repair/{param}' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'GET|api/ent/storage/record/user' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'GET|api/ent/storage/record/users' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'GET|api/ent/system/data/record' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'GET|api/ent/system/data/template/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'GET|api/ent/system/log' => [
        'component' => 'setting/system/log/index',
        'menu_name' => '操作日志',
    ],
    'GET|api/ent/system/menus' => [
        'component' => 'setting/system/menus/index',
        'menu_name' => '菜单管理',
    ],
    'GET|api/ent/system/menus/{param}' => [
        'component' => 'setting/system/menus/index',
        'menu_name' => '菜单管理',
    ],
    'GET|api/ent/system/message/cate' => [
        'component' => 'setting/enterprise/news/index',
        'menu_name' => '消息设置',
    ],
    'GET|api/ent/system/message/find/{param}' => [
        'component' => 'setting/enterprise/news/index',
        'menu_name' => '消息设置',
    ],
    'GET|api/ent/system/message/list' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/system/roles' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/system/roles/create' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'GET|api/ent/system/roles/role/{param}' => [
        'component' => 'setting/auth/group/index',
        'menu_name' => '用户权限',
    ],
    'GET|api/ent/system/roles/user/{param}' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'GET|api/ent/system/roles/{param}' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'GET|api/ent/system/roles/{param}/edit' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'GET|api/ent/system/treaty' => [
        'component' => 'setting/system/agreement',
        'menu_name' => '协议设置',
    ],
    'GET|api/ent/system/treaty/{param}/edit' => [
        'component' => 'setting/system/agreement',
        'menu_name' => '协议设置',
    ],
    'GET|api/ent/task_comment' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/todo/list' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/todo/overview' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'GET|api/ent/user/add_book/list' => [
        'component' => 'user/ent/index',
        'menu_name' => '企业通讯录',
    ],
    'GET|api/ent/user/add_book/tree' => [
        'component' => 'user/ent/index',
        'menu_name' => '企业通讯录',
    ],
    'GET|api/ent/user/assess/remind/{param}' => [
        'component' => 'hr/assess/staff/mentStaff',
        'menu_name' => '考核记录',
    ],
    'GET|api/ent/user/card/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/user/education' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/user/education/{param}/edit' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/user/ent/getApply' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/user/info' => [
        'component' => 'hr/assess/config/mentScore',
        'menu_name' => '考核评分',
    ],
    'GET|api/ent/user/list' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/user/resume' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/user/schedule' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/user/work' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/user/work/count' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/user/work/menus' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/user/work/statistics/{param}' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/user/work/statistics_type' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'GET|api/ent/user/work/{param}/edit' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/work' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/work/client/sync' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'GET|api/ent/work/config' => [
        'component' => 'setting/wecom/index',
        'menu_name' => '企微设置',
    ],
    'GET|api/ent/work/config/rsa' => [
        'component' => 'setting/wecom/index',
        'menu_name' => '企微设置',
    ],
    'GET|api/ent/work/corp/config' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'GET|api/ent/work/create' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'GET|api/ent/work/mass_messaging' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'GET|api/ent/work/mass_messaging/remind/{param}' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'GET|api/ent/work/mass_messaging/result/{param}' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'GET|api/ent/work/mass_messaging/status/{param}' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'GET|api/ent/work/mass_messaging/{param}/edit' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'GET|api/ent/work/mass_messaging_temp' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'GET|api/ent/work/mass_messaging_temp/{param}/edit' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'GET|api/ent/work/mass_messaging_temp_group' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'GET|api/ent/work/mass_messaging_temp_group/create' => [
        'component' => 'customer/weChatMass/mass',
        'menu_name' => '群发素材',
    ],
    'GET|api/ent/work/mass_messaging_temp_group/{param}/edit' => [
        'component' => 'customer/weChatMass/mass',
        'menu_name' => '群发素材',
    ],
    'GET|api/ent/work/reply_temp' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'GET|api/ent/work/reply_temp/import/temp' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'GET|api/ent/work/reply_temp/{param}/edit' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'GET|api/ent/work/reply_temp_group' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'GET|api/ent/work/reply_temp_group/create' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'GET|api/ent/work/reply_temp_group/{param}/edit' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'GET|api/ent/work/{param}/edit' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'POST|api/ent/approve/apply/form/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/approve/apply/revoke/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/approve/apply/sign/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/approve/apply/transfer/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/approve/config' => [
        'component' => 'business/examine/index',
        'menu_name' => '审批设置',
    ],
    'POST|api/ent/approve/holiday_type' => [
        'component' => 'business/holidayType/index',
        'menu_name' => '假期类型',
    ],
    'POST|api/ent/approve/reply' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/assess/census' => [
        'component' => 'user/assessment/my',
        'menu_name' => '我的考核',
    ],
    'POST|api/ent/assess/census_bar' => [
        'component' => 'hr/assess/staff/mentStatistics',
        'menu_name' => '考核统计',
    ],
    'POST|api/ent/assess/create' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/assess/score' => [
        'component' => 'hr/assess/config/mentScore',
        'menu_name' => '考核评分',
    ],
    'POST|api/ent/assess/target' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/assess/to_eval/{param}' => [
        'component' => 'hr/assess/staff/mentStaff',
        'menu_name' => '考核记录',
    ],
    'POST|api/ent/assess/update/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/attendance/arrange' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'POST|api/ent/attendance/clock/import_record' => [
        'component' => 'hr/attendance/statistics/daily',
        'menu_name' => '每日统计',
    ],
    'POST|api/ent/attendance/clock/import_third' => [
        'component' => 'hr/attendance/statistics/daily',
        'menu_name' => '每日统计',
    ],
    'POST|api/ent/attendance/cycle' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'POST|api/ent/attendance/group' => [
        'component' => 'hr/attendance/setting/addConent',
        'menu_name' => '新增考勤设置',
    ],
    'POST|api/ent/attendance/group/repeat_check' => [
        'component' => 'hr/attendance/setting/addConent',
        'menu_name' => '新增考勤设置',
    ],
    'POST|api/ent/attendance/group/white' => [
        'component' => 'hr/attendance/setting/white',
        'menu_name' => '白名单设置',
    ],
    'POST|api/ent/attendance/shift' => [
        'component' => 'hr/attendance/setting/shift',
        'menu_name' => '班次设置',
    ],
    'POST|api/ent/bill/chart' => [
        'component' => 'fd/enterprise/chart/index',
        'menu_name' => '收支统计',
    ],
    'POST|api/ent/bill/import' => [
        'component' => 'fd/enterprise/list/index',
        'menu_name' => '收支记账',
    ],
    'POST|api/ent/bill/list' => [
        'component' => 'fd/enterprise/list/index',
        'menu_name' => '收支记账',
    ],
    'POST|api/ent/bill/rank_analysis' => [
        'component' => 'fd/enterprise/chart/index',
        'menu_name' => '收支统计',
    ],
    'POST|api/ent/chat/applications' => [
        'component' => 'chat',
        'menu_name' => 'AI',
    ],
    'POST|api/ent/chat/applications/database/tooltip' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'POST|api/ent/chat/history/crud_dialog' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'POST|api/ent/chat/mcp' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'POST|api/ent/chat/models' => [
        'component' => 'chat/model',
        'menu_name' => '模型设置',
    ],
    'POST|api/ent/client/bill' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/bill/status/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/clues' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/clues/claim' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'POST|api/ent/client/clues/list' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'POST|api/ent/client/clues/return' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'POST|api/ent/client/clues/shift' => [
        'component' => 'customer/invoice/index',
        'menu_name' => '发票',
    ],
    'POST|api/ent/client/clues/subscribe/{param}/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/config/save' => [
        'component' => 'customer/setup/work',
        'menu_name' => '业务设置',
    ],
    'POST|api/ent/client/contract_doc' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/contract_doc/link_order/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/contract_doc/process' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/contract_doc/sign/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/contracts' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/contracts/import' => [
        'component' => 'customer/contract/index',
        'menu_name' => '订单列表',
    ],
    'POST|api/ent/client/contracts/list' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/contracts/shift' => [
        'component' => 'customer/invoice/index',
        'menu_name' => '发票',
    ],
    'POST|api/ent/client/contracts/subscribe/{param}/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/customer' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/client/customer/cancel_lost/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/client/customer/claim' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/client/customer/contract_rank' => [
        'component' => 'customer/turnover/index',
        'menu_name' => '业绩统计',
    ],
    'POST|api/ent/client/customer/frame_ranking' => [
        'component' => 'customer/turnover/index',
        'menu_name' => '业绩统计',
    ],
    'POST|api/ent/client/customer/label' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/client/customer/list' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/customer/lost' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/client/customer/member/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/customer/merge' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/client/customer/product_rank' => [
        'component' => 'customer/turnover/index',
        'menu_name' => '业绩统计',
    ],
    'POST|api/ent/client/customer/product_rank_list' => [
        'component' => 'customer/turnover/index',
        'menu_name' => '业绩统计',
    ],
    'POST|api/ent/client/customer/ranking' => [
        'component' => 'customer/turnover/index',
        'menu_name' => '业绩统计',
    ],
    'POST|api/ent/client/customer/return' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/client/customer/shift' => [
        'component' => 'customer/invoice/index',
        'menu_name' => '发票',
    ],
    'POST|api/ent/client/customer/statistics' => [
        'component' => 'customer/turnover/index',
        'menu_name' => '业绩统计',
    ],
    'POST|api/ent/client/customer/subscribe/{param}/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/customer/trend_statistics' => [
        'component' => 'customer/turnover/index',
        'menu_name' => '业绩统计',
    ],
    'POST|api/ent/client/follow' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/invoice/shift' => [
        'component' => 'customer/invoice/index',
        'menu_name' => '发票',
    ],
    'POST|api/ent/client/invoice/status/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/invoice_category' => [
        'component' => 'customer/setup/work',
        'menu_name' => '业务设置',
    ],
    'POST|api/ent/client/labels' => [
        'component' => 'customer/setup/work',
        'menu_name' => '业务设置',
    ],
    'POST|api/ent/client/labels/save_labels' => [
        'component' => 'customer/setup/label',
        'menu_name' => '客户标签',
    ],
    'POST|api/ent/client/labels/sort_labels' => [
        'component' => 'customer/setup/label',
        'menu_name' => '客户标签',
    ],
    'POST|api/ent/client/liaisons' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/odds' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/odds/list' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/odds/shift' => [
        'component' => 'customer/invoice/index',
        'menu_name' => '发票',
    ],
    'POST|api/ent/client/odds/subscribe/{param}/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/products' => [
        'component' => 'customer/product/addProduct',
        'menu_name' => '添加产品',
    ],
    'POST|api/ent/client/products/list' => [
        'component' => 'customer/product/index',
        'menu_name' => '产品管理',
    ],
    'POST|api/ent/client/remind' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/client/resources' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/cloud/file/{param}/batch_move' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/cloud/file/{param}/copy/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/cloud/file/{param}/create' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/cloud/file/{param}/folder' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/cloud/file/{param}/move/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/cloud/file/{param}/rename/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/cloud/file/{param}/temp_download' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/cloud/space/create' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/cloud/space/transfer/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/company/card' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'POST|api/ent/company/card/entry/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'POST|api/ent/company/card/import' => [
        'component' => 'hr/archives/unemployed',
        'menu_name' => '未入职员工',
    ],
    'POST|api/ent/company/card/quit/{param}' => [
        'component' => 'hr/archives/unemployed',
        'menu_name' => '未入职员工',
    ],
    'POST|api/ent/company/card/save/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'POST|api/ent/company/evaluate' => [
        'component' => 'hr/tool/haishAssessment/index',
        'menu_name' => '海氏量表',
    ],
    'POST|api/ent/company/promotion/data' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'POST|api/ent/company/promotion/data/sort/{param}' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'POST|api/ent/company/promotion/data/standard/{param}' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'POST|api/ent/company/promotions' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'POST|api/ent/company/salary' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'POST|api/ent/config/dict_data/tree' => [
        'component' => 'develop/dictionary/optionSetting',
        'menu_name' => '字典设置',
    ],
    'POST|api/ent/config/form/cate/{param}' => [
        'component' => 'customer/setup/customForm/index',
        'menu_name' => '自定义表单',
    ],
    'POST|api/ent/config/form/data/{param}' => [
        'component' => 'customer/setup/customForm/index',
        'menu_name' => '自定义表单',
    ],
    'POST|api/ent/config/frame' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'POST|api/ent/config/storage/config' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'POST|api/ent/crud/batch/field/save' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'POST|api/ent/crud/cate/one_save/{param}' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'POST|api/ent/crud/crud_dict/batch' => [
        'component' => 'develop/dictionary/optionSetting',
        'menu_name' => '字典设置',
    ],
    'POST|api/ent/crud/curl' => [
        'component' => 'develop/dataManagement/index',
        'menu_name' => '外部对接',
    ],
    'POST|api/ent/crud/dashboard' => [
        'component' => 'system/dashboard-design/list/index',
        'menu_name' => '图表列表',
    ],
    'POST|api/ent/crud/database/copy/{param}' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'POST|api/ent/crud/database/create' => [
        'component' => 'develop/crud/index',
        'menu_name' => '应用管理',
    ],
    'POST|api/ent/crud/event/save' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'POST|api/ent/crud/field/save' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'POST|api/ent/crud/module/{param}/comment/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/crud/module/{param}/crud' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/crud/module/{param}/import' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/crud/module/{param}/list' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/crud/module/{param}/questionnaire' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/crud/module/{param}/save' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/crud/module/{param}/senior/save' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/crud/module/{param}/share' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/crud/module/{param}/transfer' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/crud/test_send' => [
        'component' => 'develop/dataManagement/index',
        'menu_name' => '外部对接',
    ],
    'POST|api/ent/crud/view/save/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'POST|api/ent/daily' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'POST|api/ent/daily/reply' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'POST|api/ent/enterprise/folder/{param}/view/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/enterprise/folder_space/rename/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/folder/copy/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/folder/create' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/folder/make' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/folder/move' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/folder/move/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/folder/recover' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'POST|api/ent/jobs' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'POST|api/ent/notice/list' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'POST|api/ent/openapi/key' => [
        'component' => 'develop/foreign/index',
        'menu_name' => '授权密钥',
    ],
    'POST|api/ent/program' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'POST|api/ent/program_task' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'POST|api/ent/program_task/batch' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'POST|api/ent/program_task/batch_del' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'POST|api/ent/program_task/sort' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'POST|api/ent/program_task/subordinate' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'POST|api/ent/program_version/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/rank_level' => [
        'component' => 'hr/enterprise/job/positionSystemChart',
        'menu_name' => '职级体系图',
    ],
    'POST|api/ent/schedule/count' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/schedule/index' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/schedule/reply/save' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/schedule/store' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/storage/list' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'POST|api/ent/storage/list/cate' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'POST|api/ent/storage/record' => [
        'component' => 'administration/material/fixed/manage',
        'menu_name' => '物资管理',
    ],
    'POST|api/ent/system/data/export/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/system/data/import/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'POST|api/ent/system/roles' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'POST|api/ent/system/roles/add_user' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'POST|api/ent/system/roles/del_user' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'POST|api/ent/system/roles/pwd' => [
        'component' => 'setting/auth/group/index',
        'menu_name' => '用户权限',
    ],
    'POST|api/ent/system/roles/show_user' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'POST|api/ent/system/roles/user' => [
        'component' => 'setting/auth/group/index',
        'menu_name' => '用户权限',
    ],
    'POST|api/ent/task_comment' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'POST|api/ent/user/batch/create' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'POST|api/ent/user/work/menus' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'POST|api/ent/user/work/statistics_type' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'POST|api/ent/work/config/save' => [
        'component' => 'setting/wecom/index',
        'menu_name' => '企微设置',
    ],
    'POST|api/ent/work/mass_messaging' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'POST|api/ent/work/mass_messaging/customer_count' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'POST|api/ent/work/mass_messaging/group_chat' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'POST|api/ent/work/mass_messaging_temp' => [
        'component' => 'customer/weChatMass/mass',
        'menu_name' => '群发素材',
    ],
    'POST|api/ent/work/media/upload-by-url' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'POST|api/ent/work/reply_temp' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'POST|api/ent/work/reply_temp/import' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
    'POST|api/ent/work/reply_temp/url_metadata' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'PUT|api/ent/approve/config/{param}' => [
        'component' => 'business/examine/index',
        'menu_name' => '审批设置',
    ],
    'PUT|api/ent/approve/holiday_type/{param}' => [
        'component' => 'business/holidayType/index',
        'menu_name' => '假期类型',
    ],
    'PUT|api/ent/assess/eval' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'PUT|api/ent/assess/examine_eval/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/assess/plan/{param}' => [
        'component' => 'hr/assess/config/mentPlan',
        'menu_name' => '考核计划',
    ],
    'PUT|api/ent/assess/self_eval/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/assess/superior_eval/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/assess/template/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/attendance/arrange/{param}' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'PUT|api/ent/attendance/calendar/{param}' => [
        'component' => 'hr/attendance/setting/calendarsetUp',
        'menu_name' => '日历配置',
    ],
    'PUT|api/ent/attendance/cycle/{param}' => [
        'component' => 'hr/attendance/setting/schedul',
        'menu_name' => '排班管理',
    ],
    'PUT|api/ent/attendance/group/{param}' => [
        'component' => 'hr/attendance/setting/addConent',
        'menu_name' => '新增考勤设置',
    ],
    'PUT|api/ent/attendance/shift/{param}' => [
        'component' => 'hr/attendance/setting/shift',
        'menu_name' => '班次设置',
    ],
    'PUT|api/ent/attendance/statistics/{param}' => [
        'component' => 'hr/attendance/statistics/daily',
        'menu_name' => '每日统计',
    ],
    'PUT|api/ent/chat/applications/{param}' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'PUT|api/ent/chat/mcp/{param}' => [
        'component' => 'chat/setting',
        'menu_name' => '应用设置',
    ],
    'PUT|api/ent/chat/models/{param}' => [
        'component' => 'chat/model',
        'menu_name' => '模型设置',
    ],
    'PUT|api/ent/client/bill/finance/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/bill/mark/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/bill/withdraw/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/bill/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/clues/to_customer/{param}' => [
        'component' => 'customer/clue/index',
        'menu_name' => '线索列表',
    ],
    'PUT|api/ent/client/clues/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/contract_doc/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/contracts/abnormal/{param}/{param}' => [
        'component' => 'customer/contract/index',
        'menu_name' => '订单列表',
    ],
    'PUT|api/ent/client/contracts/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/customer/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'PUT|api/ent/client/file/real_name/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/follow/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/invoice/bill/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/invoice/invalid_apply/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/invoice/invalid_review/{param}' => [
        'component' => 'fd/examine/pending',
        'menu_name' => '待处理',
    ],
    'PUT|api/ent/client/invoice/mark/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/invoice/status/{param}' => [
        'component' => 'fd/invoice/pending',
        'menu_name' => '待开发票',
    ],
    'PUT|api/ent/client/invoice/withdraw/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/invoice_category/{param}' => [
        'component' => 'customer/setup/work',
        'menu_name' => '业务设置',
    ],
    'PUT|api/ent/client/labels/{param}' => [
        'component' => 'customer/setup/work',
        'menu_name' => '业务设置',
    ],
    'PUT|api/ent/client/liaisons/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/odds/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/products/{param}' => [
        'component' => 'customer/product/addProduct',
        'menu_name' => '添加产品',
    ],
    'PUT|api/ent/client/remind/abjure/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/remind/mark/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/remind/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/resources/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/client/targets' => [
        'component' => 'customer/kpi/index',
        'menu_name' => '目标管理',
    ],
    'PUT|api/ent/cloud/file/{param}/rules/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'PUT|api/ent/cloud/space/batch_recovery' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'PUT|api/ent/cloud/space/recovery/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'PUT|api/ent/cloud/space/update/{param}' => [
        'component' => 'user/cloudfile/index',
        'menu_name' => '企业空间',
    ],
    'PUT|api/ent/company/card/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'PUT|api/ent/company/evaluate/{param}' => [
        'component' => 'hr/tool/haishAssessment/index',
        'menu_name' => '海氏量表',
    ],
    'PUT|api/ent/company/info' => [
        'component' => 'setting/enterprise/info/basic',
        'menu_name' => '企业信息',
    ],
    'PUT|api/ent/company/job_analysis/{param}' => [
        'component' => 'user/duty/analyse',
        'menu_name' => '工作分析表',
    ],
    'PUT|api/ent/company/message/batch/{param}' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'PUT|api/ent/company/promotion/data/{param}' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'PUT|api/ent/company/promotions/{param}' => [
        'component' => 'hr/enterprise/promotion',
        'menu_name' => '晋升说明',
    ],
    'PUT|api/ent/company/salary/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'PUT|api/ent/company/train/{param}' => [
        'component' => 'hr/training/companyProfile',
        'menu_name' => '公司介绍',
    ],
    'PUT|api/ent/config/client_rule/{param}' => [
        'component' => 'customer/setup/ruleSettings/index',
        'menu_name' => '规则设置',
    ],
    'PUT|api/ent/config/data/all/{param}' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'PUT|api/ent/config/data/firewall' => [
        'component' => 'setting/system/firewall',
        'menu_name' => '防火墙',
    ],
    'PUT|api/ent/config/form/cate/{param}' => [
        'component' => 'customer/setup/customForm/index',
        'menu_name' => '自定义表单',
    ],
    'PUT|api/ent/config/form/data/convert/{param}' => [
        'component' => 'customer/setup/customForm/index',
        'menu_name' => '自定义表单',
    ],
    'PUT|api/ent/config/form/data/fields/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/config/form/data/move/{param}' => [
        'component' => 'customer/setup/customForm/index',
        'menu_name' => '自定义表单',
    ],
    'PUT|api/ent/config/frame/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'PUT|api/ent/config/storage/save_basic' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'PUT|api/ent/config/storage/save_type/{param}' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'PUT|api/ent/config/storage/status/{param}' => [
        'component' => 'setting/enterprise/setup/index',
        'menu_name' => '基础设置',
    ],
    'PUT|api/ent/crud/curl/{param}' => [
        'component' => 'develop/dataManagement/index',
        'menu_name' => '外部对接',
    ],
    'PUT|api/ent/crud/dashboard/{param}' => [
        'component' => 'system/dashboard-design/list/index',
        'menu_name' => '图表列表',
    ],
    'PUT|api/ent/crud/database/update/{param}' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'PUT|api/ent/crud/event/status/{param}' => [
        'component' => 'develop/event/index',
        'menu_name' => '触发器管理',
    ],
    'PUT|api/ent/crud/field/main/{param}' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'PUT|api/ent/crud/field/update/{param}' => [
        'component' => 'develop/crud/design',
        'menu_name' => '实体设计',
    ],
    'PUT|api/ent/crud/module/{param}/comment/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'PUT|api/ent/crud/module/{param}/questionnaire/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'PUT|api/ent/crud/module/{param}/share/{param}' => [
        'component' => 'customer/list/public',
        'menu_name' => '公海池',
    ],
    'PUT|api/ent/crud/module/{param}/update/{param}' => [
        'component' => 'develop/module/index',
        'menu_name' => '工资结构',
    ],
    'PUT|api/ent/daily/{param}' => [
        'component' => 'user/daily/my',
        'menu_name' => '我的汇报',
    ],
    'PUT|api/ent/enterprise/payType' => [
        'component' => 'fd/setup/type/index',
        'menu_name' => '支付方式',
    ],
    'PUT|api/ent/jobs/show/{param}/{param}' => [
        'component' => 'hr/enterprise/job/jobAdministration',
        'menu_name' => '职位管理',
    ],
    'PUT|api/ent/jobs/subordinate/{param}' => [
        'component' => 'user/duty/explain',
        'menu_name' => '岗位职责',
    ],
    'PUT|api/ent/jobs/{param}' => [
        'component' => 'hr/enterprise/job/jobAdministration',
        'menu_name' => '职位管理',
    ],
    'PUT|api/ent/notice/list/{param}' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'PUT|api/ent/notice/top/{param}' => [
        'component' => 'administration/notice/index',
        'menu_name' => '企业动态',
    ],
    'PUT|api/ent/openapi/key/{param}' => [
        'component' => 'develop/foreign/index',
        'menu_name' => '授权密钥',
    ],
    'PUT|api/ent/program/{param}' => [
        'component' => 'program/programList/index',
        'menu_name' => '我的项目',
    ],
    'PUT|api/ent/program_file/real_name/{param}' => [
        'component' => 'program/programList/taskDetails',
        'menu_name' => '任务详情',
    ],
    'PUT|api/ent/program_task/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/rank_level/batch/{param}' => [
        'component' => 'hr/enterprise/job/positionSystemChart',
        'menu_name' => '职级体系图',
    ],
    'PUT|api/ent/rank_level/relation/{param}' => [
        'component' => 'hr/enterprise/job/positionSystemChart',
        'menu_name' => '职级体系图',
    ],
    'PUT|api/ent/rank_level/{param}' => [
        'component' => 'hr/enterprise/job/positionSystemChart',
        'menu_name' => '职级体系图',
    ],
    'PUT|api/ent/schedule/status/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/schedule/update/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/system/message/batch_update' => [
        'component' => 'setting/enterprise/news/index',
        'menu_name' => '消息设置',
    ],
    'PUT|api/ent/system/message/status/{param}/{param}' => [
        'component' => 'setting/enterprise/news/index',
        'menu_name' => '消息设置',
    ],
    'PUT|api/ent/system/message/subscribe/{param}' => [
        'component' => 'setting/enterprise/news/index',
        'menu_name' => '消息设置',
    ],
    'PUT|api/ent/system/message/sync' => [
        'component' => 'setting/enterprise/news/index',
        'menu_name' => '消息设置',
    ],
    'PUT|api/ent/system/message/update/{param}' => [
        'component' => 'setting/enterprise/news/index',
        'menu_name' => '消息设置',
    ],
    'PUT|api/ent/system/roles/{param}' => [
        'component' => 'setting/auth/admin/index',
        'menu_name' => '角色权限',
    ],
    'PUT|api/ent/system/treaty/{param}' => [
        'component' => 'setting/system/agreement',
        'menu_name' => '协议设置',
    ],
    'PUT|api/ent/task_comment/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/user/agree/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'PUT|api/ent/user/assess/mark/{param}' => [
        'component' => 'user/calendar/index',
        'menu_name' => '我的日程',
    ],
    'PUT|api/ent/user/card/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'PUT|api/ent/user/refuse/{param}' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'PUT|api/ent/user/resume_save' => [
        'component' => 'hr/enterprise/group/index',
        'menu_name' => '组织架构',
    ],
    'PUT|api/ent/user/savePassword' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'PUT|api/ent/user/user/join' => [
        'component' => 'user/workbench/index',
        'menu_name' => '工作台',
    ],
    'PUT|api/ent/work/mass_messaging/{param}' => [
        'component' => 'customer/weChatMass/clientGroupChat',
        'menu_name' => '客户群聊',
    ],
    'PUT|api/ent/work/mass_messaging_temp/{param}' => [
        'component' => 'customer/weChatMass/mass',
        'menu_name' => '群发素材',
    ],
    'PUT|api/ent/work/reply_temp/{param}' => [
        'component' => 'customer/quickReply/index',
        'menu_name' => '快捷回复',
    ],
];
