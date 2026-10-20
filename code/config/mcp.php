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

use App\Constants\ModuleEnum;

$toolModules = [
    'customer' => [
        'name'              => '客户',
        'description'       => '客户、线索、商机、订单、合同、发票、客户账目、回款、订单支出、销售业绩、联系人、跟进记录；员工报销/餐费报销不在本模块（走 approve）。'
            . '业务审批表单用 customer_biz_form_get(biz=payment|renewal|expense|contract_sign|invoice_issue|invoice_void)（内读 sys_config，禁止 approve_type_list；expense=订单支出非报销）。'
            . '跟进用 follow_up_create / follow_up_update（entity_type=customer|lead|opportunity），不要向用户索要记录 ID；'
            . '须具备对应负责人的客户数据权限，不改客户资料、金额、合同和发票。'
            . '业绩汇总用 bill_statistics，趋势用 finance_trend(dimension=performance)，'
            . '人员排行用 finance_rank_analysis(dimension=personnel)，部门排行用 finance_rank_analysis(dimension=frame)；'
            . '资金流水用 finance_list / finance_trend(dimension=finance)；'
            . '订单出库仓库用 warehouse_list，订单产品用 product_list；取数前先 who_am_i / my_data_scope。',
        'permission_module' => ModuleEnum::CUSTOMER,
        'permission_strategy' => 'module_data_scope',
        'tool_dirs'         => ['Customer', 'Lead', 'Opportunity', 'Order', 'Contract', 'Invoice', 'Bill', 'Finance', 'Contact', 'Record', 'Warehouse'],
        'seed_default'      => true,
        'dynamic_schema'    => true,
    ],
    'attendance' => [
        'name'              => '考勤',
        'description'       => '考勤打卡、排班、申请和统计。取数前先调用 who_am_i / my_data_scope 确认身份与数据范围。',
        'permission_module' => ModuleEnum::ATTENDANCE,
        'permission_strategy' => 'module_data_scope',
        'tool_dirs'         => ['Attendance'],
        'seed_default'      => true,
    ],
    'assess' => [
        'name'              => '绩效',
        'description'       => '绩效考核（不是销售业绩）：绩效列表、详情、统计、趋势与自评。取数前先调用 who_am_i / my_data_scope 确认身份与数据范围。',
        'permission_module' => ModuleEnum::ASSESS,
        'permission_strategy' => 'module_data_scope',
        'tool_dirs'         => ['Assess'],
        'seed_default'      => true,
    ],
    'report' => [
        'name'              => '汇报',
        'description'       => '工作汇报列表、详情、统计、创建与修改本人汇报内容（report_update 按日期、日报/周报/月报或原文定位，不要向用户索要 ID，不改类型和他人汇报）。取数前先调用 who_am_i / my_data_scope 确认身份与数据范围。',
        'permission_module' => ModuleEnum::REPORT,
        'permission_strategy' => 'module_data_scope',
        'tool_dirs'         => ['Report'],
        'seed_default'      => true,
    ],
    'schedule' => [
        'name'              => '日程',
        'description'       => '日程查询、新建日程、完成我的日程、修改本人创建的个人或自定义日程（schedule_update 按现有标题或日期定位，不要向用户索要 ID；不改付款/回款/跟进/绩效等待办）。取数前先调用 who_am_i / my_data_scope 确认身份与数据范围。',
        'permission_module' => ModuleEnum::SCHEDULE,
        'permission_strategy' => 'module_data_scope',
        'tool_dirs'         => ['Schedule'],
        'seed_default'      => true,
    ],
    'approve' => [
        'name'              => '审批',
        'description'       => '人事审批与自定义审批（含员工报销/餐费报销）：approve_type_list（人事传 types；报销用 name=报销）→ approve_form_get → approve_process_get → approve_create。'
            . '客户回款/开票/续费/订单支出/合同签约/作废发票不在本模块，须用 customer 模块业务表单工具（customer_biz_form_get 等）。操作前先调用 who_am_i。',
        'tool_dirs'         => ['Approve'],
        'permission_strategy' => 'business_guard',
        'seed_default'      => true,
        'dynamic_schema'    => true,
    ],
];

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Configuration
    |--------------------------------------------------------------------------
    */
    'server' => [
        'name'        => '陀螺匠MCP服务',
        'version'     => '2.0.0',
        'description' => '提供客户管理、人员管理、绩效管理、汇报管理、财务管理等服务的MCP接口',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'enabled'           => true,
        /** 默认关闭 query 传 Key；迁移期可设 MCP_ALLOW_QUERY_KEY=true 并观察弃用日志. */
        'allow_query_key'   => (bool) env('MCP_ALLOW_QUERY_KEY', false),
        'rate_limit'        => [
            'per_minute'          => (int) env('MCP_RATE_LIMIT_PER_MINUTE', 120),
            'auth_failures_per_minute' => (int) env('MCP_AUTH_FAILURES_PER_MINUTE', 5),
        ],
        'middleware'        => ['auth.admin'],
        'token_expires_in'  => (int) env('JWT_TTL', 10080) * 60,
        'guard'             => 'admin',
        'refresh_threshold' => env('JWT_REFRESH_THRESHOLD', 900),
        'header_auth' => [
            'enabled'         => true,
            'account_header'  => 'X-Account',
            'password_header' => 'X-Password',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled'          => true,
        'channel'          => 'mcp',
        'level'            => 'info',
        'log_success'      => (bool) env('MCP_LOG_AUTH_SUCCESS', false),
        'log_user_context' => (bool) env('MCP_LOG_USER_CONTEXT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'tools_meta_ttl' => (int) env('MCP_TOOLS_META_CACHE_TTL', 3600),
        'user_context_ttl' => (int) env('MCP_USER_CONTEXT_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'enabled' => (bool) env('MCP_METRICS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Streamable HTTP（P3 实验性端点 /mcp/stream）
    |--------------------------------------------------------------------------
    */
    'stream' => [
        'enabled' => (bool) env('MCP_STREAM_ENABLED', true),
        'allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MCP_ALLOWED_ORIGINS', ''))
        ))),
        'allow_missing_origin' => (bool) env('MCP_ALLOW_MISSING_ORIGIN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Protocol profile（P1：仅宣称 2024-11-05 / 2025-03-26，不混入 2026 draft）
    |--------------------------------------------------------------------------
    | session_mode=stateless：不发送 Mcp-Session-Id，每次请求靠 Key + 上下文认证。
    | session_mode=stateful 需配合 session_store=redis 才下发会话头；否则按 stateless 降级。
    | list_page_size：tools/list、resources/list 分页大小；list_compat_max：可翻页总上限。
    */
    'protocol' => [
        'supported_versions' => ['2024-11-05', '2025-03-26'],
        'unknown_version'    => env('MCP_UNKNOWN_PROTOCOL_VERSION', 'reject'),
        'session_mode'       => env('MCP_SESSION_MODE', 'stateless'),
        'session_store'      => env('MCP_SESSION_STORE', 'none'),
        'list_page_size'     => (int) env('MCP_LIST_PAGE_SIZE', 100),
        'list_compat_max'    => (int) env('MCP_LIST_COMPAT_MAX', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool argument validation（T-1 / laravel-ai 契约移植）
    |--------------------------------------------------------------------------
    | off：跳过；warn：校验失败只打日志仍执行；enforce：返回 INVALID_ARGUMENTS.
    */
    'arguments' => [
        'validation_mode' => env('MCP_ARGUMENTS_VALIDATION_MODE', 'enforce'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound External MCP URL policy（P0）
    |--------------------------------------------------------------------------
    | allow_private=false 时拒绝 localhost/私网字面主机；allow_hosts 可显式放行。
    */
    'outbound' => [
        'allowed_schemes' => ['https', 'http'],
        'allow_private'   => (bool) env('MCP_OUTBOUND_ALLOW_PRIVATE', false),
        'allow_hosts'     => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MCP_OUTBOUND_ALLOW_HOSTS', ''))
        ))),
    ],

    'context_instructions' => '取数前先确认身份（who_am_i）与数据范围（my_data_scope）。'
        . '「绩效/绩效考核/考核结果」与「业绩/销售业绩/回款」不同：绩效用 assess_list / assess_statistics / assess_trend / assess_detail；'
        . '业绩汇总用 bill_statistics（CustomerService::getStatistics），业绩趋势用 finance_trend(dimension=performance)，'
        . '人员排行用 finance_rank_analysis(dimension=personnel)，部门排行用 finance_rank_analysis(dimension=frame)，订单业绩用 order_statistics。'
        . '企业资金流水才用 finance_list / finance_trend(dimension=finance)。按员工/部门统计时先用 personnel_search 或 org_department_users 获取可见人员 ID。',

    /*
    |--------------------------------------------------------------------------
    | 业务能力目录（工具名称之外的 AI 业务语义）
    |--------------------------------------------------------------------------
    |
    | 这部分是 MCP 工具名称与用户说法之间的单一映射源。
    | ToolRegistry 会将命中工具的 aliases、口径和调用提示追加到 description。
    |
    */
    'business_capabilities' => [
        'assess_performance' => [
            'name'    => '绩效考核',
            'aliases' => ['绩效', '绩效考核', '考核结果', '考核分数', '绩效列表', '绩效统计', '绩效趋势', '考核统计'],
            'metric'  => '绩效考核任务、得分、状态、统计与趋势；不是销售回款业绩',
            'tools'   => ['assess_list', 'assess_detail', 'assess_statistics', 'assess_trend', 'assess_self_eval'],
        ],
        'sales_performance' => [
            'name'    => '销售业绩',
            'aliases' => ['业绩', '销售额', '营收', '销售排行', '业绩简报', '经营业绩', '业绩统计'],
            'metric'  => '对齐管理端业绩统计：回款/续费/新客户/新订单/未收；趋势与排行同页接口',
            'tools'   => ['bill_statistics', 'finance_rank_analysis', 'finance_trend', 'order_statistics', 'contract_statistics'],
        ],
        'received_payment' => [
            'name'    => '回款统计',
            'aliases' => ['回款', '收款', '到账', '已收款', '客户回款'],
            'metric'  => '已审核客户账目收入（PaymentService）；明细用 bill_list',
            'tools'   => ['bill_statistics', 'bill_list', 'finance_trend'],
        ],
        'personnel_performance_ratio' => [
            'name'    => '人员业绩占比',
            'aliases' => ['人员业绩占比', '业务员贡献度', '销售人员排行', '部门成员回款贡献', '团队业绩分布', '部门业绩排行'],
            'metric'  => '人员用 finance_rank_analysis(dimension=personnel)；部门用 dimension=frame',
            'tools'   => ['personnel_search', 'org_department_users', 'finance_rank_analysis'],
        ],
        'order_performance' => [
            'name'    => '订单业绩',
            'aliases' => ['订单统计', '签约金额', '订单金额', '成交订单', '未收订单'],
            'metric'  => '对齐管理端：新订单数、新订单金额、未收金额',
            'tools'   => ['order_list', 'order_statistics'],
        ],
        'contract_performance' => [
            'name'    => '合同统计',
            'aliases' => ['合同金额', '合同签约', '合同状态', '合同业绩'],
            'metric'  => '合同数量、状态和金额',
            'tools'   => ['contract_list', 'contract_statistics'],
        ],
        'attendance_summary' => [
            'name'    => '考勤统计',
            'aliases' => ['打卡统计', '考勤统计', '出勤情况', '考勤异常', '打卡记录', '考勤记录'],
            'metric'  => '考勤记录、出勤、缺勤和异常情况',
            'tools'   => ['attendance_statistics', 'attendance_summary', 'attendance_clock_list'],
        ],
        'approval_status' => [
            'name'    => '审批状态',
            'aliases' => ['待我审批', '我的审批', '审批进度', '请假进度'],
            'metric'  => '当前用户可见的审批记录和流程状态',
            'tools'   => ['approve_my_list', 'approve_pending_list', 'approve_process_get'],
        ],
        'work_summary' => [
            'name'    => '工作汇报',
            'aliases' => ['日报', '周报', '月报', '工作总结', '工作汇报', '整理日报', '整理周报', '整理月报'],
            'metric'  => '工作汇报列表、统计，以及按日/周/月从日程与 CRM 动态组装汇报素材',
            'tools'   => [
                'report_list', 'report_statistics', 'report_detail', 'report_update',
                'schedule_work_summary', 'crm_activity_summary',
            ],
        ],
        'crm_entity_create' => [
            'name'    => 'CRM 实体创建',
            'aliases' => [
                '创建客户', '添加客户', '新增客户', '新建客户', '录入客户',
                '创建订单', '新增订单', '新建订单', '生成订单',
                '创建线索', '新增线索', '创建商机', '新增商机', '创建联系人', '添加联系人',
            ],
            'metric'  => '客户/联系人/线索/商机/订单创建；复合写按 customer_create → order_create',
            'tools'   => [
                'customer_list', 'customer_detail', 'customer_create', 'contact_create',
                'lead_create', 'opportunity_create', 'opportunity_list',
                'order_create', 'warehouse_list', 'product_list',
            ],
        ],
        'customer_query' => [
            'name'    => '客户查询',
            'aliases' => ['客户列表', '查客户', '客户详情', '找客户'],
            'metric'  => '客户检索与详情',
            'tools'   => ['customer_list', 'customer_detail', 'contact_list'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Resources
    |--------------------------------------------------------------------------
    */
    'resources' => [
        [
            'uri'         => 'oa://user/me',
            'name'        => '当前用户',
            'description' => '当前 MCP 登录用户身份信息',
            'mimeType'    => 'application/json',
        ],
        [
            'uri'         => 'oa://user/data-scope',
            'name'        => '数据权限范围',
            'description' => '当前用户在各模块下的数据权限范围',
            'mimeType'    => 'application/json',
        ],
        [
            'uri'         => 'oa://org/tree',
            'name'        => '组织架构树',
            'description' => '基于数据权限过滤后的组织架构树',
            'mimeType'    => 'application/json',
        ],
        [
            'uri'         => 'oa://modules/permissions',
            'name'        => '模块权限摘要',
            'description' => '当前用户在各业务模块的权限配置',
            'mimeType'    => 'application/json',
        ],
    ],

    'resource_templates' => [],

    /*
    |--------------------------------------------------------------------------
    | Context Tools（跨模块人员/组织上下文，自动注入各业务模块端点）
    |--------------------------------------------------------------------------
    */
    'common_tools' => [
        'who_am_i',
        'my_data_scope',
        'check_data_permission',
    ],

    'context_tools' => [
        'org_tree',
        'org_department_list',
        'org_department_detail',
        'org_department_users',
        'personnel_list',
        'personnel_search',
        'personnel_detail',
        'my_subordinates',
        'personnel_stats_overview',
        'personnel_stats_department_distribution',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Module Configuration（MCP 模块端点唯一配置源）
    |--------------------------------------------------------------------------
    |
    | /mcp 保留全量工具兼容旧客户端；
    | /mcp/{module} 暴露 common_tools + context_tools + tool_dirs 工具。
    |
    */
    'tool_modules' => $toolModules,

    /*
    |--------------------------------------------------------------------------
    | Module Configuration（由 tool_modules 派生，供 /mcp/info 等旧接口）
    |--------------------------------------------------------------------------
    */
    'modules' => array_map(
        static fn (array $module, string $key): array => ['name' => $module['name'] ?? $key],
        $toolModules,
        array_keys($toolModules)
    ),
];
