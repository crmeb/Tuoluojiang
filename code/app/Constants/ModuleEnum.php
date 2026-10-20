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

/**
 * 内置模块枚举 - 用于数据权限按模块配置.
 */
final class ModuleEnum
{
    /**
     * 客户管理.
     */
    public const CUSTOMER = 'customer';

    /**
     * 考勤管理.
     */
    public const ATTENDANCE = 'attendance';

    /**
     * 绩效管理.
     */
    public const ASSESS = 'assess';

    /**
     * 日程管理.
     */
    public const SCHEDULE = 'schedule';

    /**
     * 工作汇报.
     */
    public const REPORT = 'report';

    /**
     * 项目管理.
     */
    public const PROGRAM = 'program';

    /**
     * 子模块配置映射.
     * key: 子模块标识
     * value: ['config_key' => 配置key, 'name' => 模块名称, 'route_prefix' => 路由前缀].
     */
    public const SUB_MODULE_CONFIG = [
        'lead' => [
            'config_key'   => 'lead_module_switch',
            'name'         => '线索模块',
            'route_prefix' => 'client/clues',
        ],
        'customer' => [
            'config_key'   => 'customer_module_switch',
            'name'         => '客户模块',
            'route_prefix' => 'client/customer',
        ],
        'liaison' => [
            'config_key'   => 'liaison_module_switch',
            'name'         => '联系人模块',
            'route_prefix' => ['client/liaisons', 'client/liaison'],
        ],
        'opportunity' => [
            'config_key'   => 'opportunity_module_switch',
            'name'         => '商机模块',
            'route_prefix' => 'client/odds',
        ],
        'contract' => [
            'config_key'   => 'contract_module_switch',
            'name'         => '合同模块',
            'route_prefix' => 'client/contract_doc',
        ],
        'order' => [
            'config_key'   => 'order_module_switch',
            'name'         => '订单模块',
            'route_prefix' => ['client/contracts', 'client/contract'],
        ],
    ];

    /**
     * 获取所有内置模块.
     * @return array<string, string> [module_key => module_name]
     */
    public static function all(): array
    {
        return [
            self::CUSTOMER   => '客户管理',
            self::ATTENDANCE => '考勤管理',
            self::ASSESS     => '绩效管理',
            self::SCHEDULE   => '日程管理',
            self::REPORT     => '工作汇报',
            self::PROGRAM    => '项目管理',
        ];
    }

    /**
     * 判断是否为有效的内置模块.
     */
    public static function isValid(string $module): bool
    {
        return in_array($module, array_keys(self::all()), true);
    }

    /**
     * 获取模块的权限字段配置
     * key: 模块名
     * value: ['table' => 表名, 'field' => 主uid字段, 'fields' => [多uid字段数组], 'joins' => [关联表配置]].
     *
     * 关联表配置格式:
     * 'joins' => [
     *     '关联表名' => [
     *         'type' => 'left|right|inner',
     *         'on' => ['主表字段', '=', '关联表字段'],
     *         'master' => '关联表映射到的主表别名'
     *     ]
     * ]
     */
    public static function getModuleFieldConfig(): array
    {
        return [
            self::CUSTOMER => [
                'table'       => 'customer',
                'field'       => 'uid',
                'fields'      => ['uid'],
                'json_fields' => ['member'],
                'joins'       => [
                    'customer_clue' => [
                        'type'          => 'direct',
                        'direct_fields' => ['uid', 'creator_uid', 'before_uid'],
                        'master'        => 'customer',
                    ],
                    'contract' => [
                        'type'   => 'left',
                        'on'     => ['customer.id', '=', 'contract.eid'],
                        'master' => 'customer',
                    ],
                    'customer_odds' => [
                        'type'          => 'left',
                        'on'            => ['customer.id', '=', 'customer_odds.eid'],
                        'direct_fields' => ['uid', 'creator_uid', 'before_uid'],
                        'master'        => 'customer',
                    ],
                    'customer_liaison' => [
                        'type'          => 'left',
                        'on'            => ['customer.id', '=', 'customer_liaison.eid'],
                        'direct_fields' => ['uid'],
                        'master'        => 'customer',
                    ],
                    'contract_doc' => [
                        'type'   => 'left',
                        'on'     => ['customer.id', '=', 'contract_doc.eid'],
                        'master' => 'customer',
                    ],
                    'client_bill' => [
                        'type'   => 'left',
                        'on'     => ['customer.id', '=', 'client_bill.eid'],
                        'master' => 'customer',
                    ],
                    'client_invoice' => [
                        'type'   => 'left',
                        'on'     => ['customer.id', '=', 'client_invoice.eid'],
                        'master' => 'customer',
                    ],
                    'customer_record' => [
                        'type'          => 'left',
                        'on'            => ['customer.id', '=', 'customer_record.eid'],
                        'direct_fields' => ['uid', 'creator_uid'],
                        'master'        => 'customer',
                    ],
                ],
            ],
            self::ATTENDANCE => [
                'table'  => 'attendance_statistics',
                'field'  => 'uid',
                'fields' => ['uid'],
                'joins'  => [
                    'attendance_clock_record' => [
                        'type'   => 'inner',
                        'on'     => ['attendance_statistics.uid', '=', 'attendance_clock_record.uid'],
                        'master' => 'attendance_statistics',
                    ],
                    'attendance_arrange' => [
                        'type'   => 'inner',
                        'on'     => ['attendance_statistics.uid', '=', 'attendance_arrange.uid'],
                        'master' => 'attendance_statistics',
                    ],
                    'attendance_arrange_record' => [
                        'type'   => 'inner',
                        'on'     => ['attendance_statistics.uid', '=', 'attendance_arrange_record.uid'],
                        'master' => 'attendance_statistics',
                    ],
                    'attendance_apply_record' => [
                        'type'   => 'inner',
                        'on'     => ['attendance_statistics.uid', '=', 'attendance_apply_record.uid'],
                        'master' => 'attendance_statistics',
                    ],
                    'attendance_statistics_leave' => [
                        'type'   => 'inner',
                        'on'     => ['attendance_statistics.uid', '=', 'attendance_statistics_leave.uid'],
                        'master' => 'attendance_statistics',
                    ],
                    'attendance_short_remind' => [
                        'type'   => 'inner',
                        'on'     => ['attendance_statistics.uid', '=', 'attendance_short_remind.uid'],
                        'master' => 'attendance_statistics',
                    ],
                ],
            ],
            self::ASSESS => [
                'table'  => 'assess',
                'field'  => 'uid',
                'fields' => ['test_uid', 'check_uid'],
                'joins'  => [
                    'assess_reply' => [
                        'type'   => 'inner',
                        'on'     => ['assess.id', '=', 'assess_reply.assessid'],
                        'master' => 'assess',
                    ],
                    'assess_user_score' => [
                        'type'   => 'inner',
                        'on'     => ['assess.id', '=', 'assess_user_score.assessid'],
                        'master' => 'assess',
                    ],
                    'user_assess' => [
                        'type'   => 'inner',
                        'on'     => ['assess.test_uid', '=', 'user_assess.test_uid'],
                        'master' => 'assess',
                    ],
                ],
            ],
            self::SCHEDULE => [
                'table'  => 'schedule_task',
                'field'  => 'uid',
                'fields' => ['uid'],
                'joins'  => [],
            ],
            self::REPORT => [
                'table'  => 'enterprise_user_daily',
                'field'  => 'user_id',
                'fields' => ['user_id'],
                'joins'  => [
                    'enterprise_user_daily_reply' => [
                        'type'   => 'inner',
                        'on'     => ['enterprise_user_daily.daily_id', '=', 'enterprise_user_daily_reply.daily_id'],
                        'master' => 'enterprise_user_daily',
                    ],
                ],
            ],
            self::PROGRAM => [
                'table'  => 'program_member',
                'field'  => 'uid',
                'fields' => ['uid'],
                'joins'  => [
                    'program' => [
                        'type'   => 'inner',
                        'on'     => ['program_member.program_id', '=', 'program.id'],
                        'master' => 'program_member',
                    ],
                    'program_task' => [
                        'type'   => 'inner',
                        'on'     => ['program_member.program_id', '=', 'program_task.program_id'],
                        'master' => 'program_member',
                    ],
                    'program_task_member' => [
                        'type'   => 'inner',
                        'on'     => ['program_member.uid', '=', 'program_task_member.uid'],
                        'master' => 'program_member',
                    ],
                    'program_version' => [
                        'type'   => 'inner',
                        'on'     => ['program_member.program_id', '=', 'program_version.program_id'],
                        'master' => 'program_member',
                    ],
                ],
            ],
        ];
    }

    /**
     * 根据表名获取模块配置.
     */
    public static function getModuleByTable(string $table): ?string
    {
        foreach (self::getModuleFieldConfig() as $module => $config) {
            if ($config['table'] === $table) {
                return $module;
            }
        }
        return null;
    }

    /**
     * 根据表名获取模块的字段配置（支持多字段）.
     */
    public static function getModuleFields(string $table): array
    {
        $module = self::getModuleByTable($table);
        if (! $module) {
            return [];
        }

        $config = self::getModuleFieldConfig()[$module];
        return $config['fields'] ?? [$config['field']];
    }

    /**
     * 根据表名获取模块的关联表配置.
     */
    public static function getModuleJoins(string $table): array
    {
        $module = self::getModuleByTable($table);
        if (! $module) {
            return [];
        }

        return self::getModuleFieldConfig()[$module]['joins'] ?? [];
    }

    /**
     * 判断表是否是主表.
     */
    public static function isMainTable(string $table): bool
    {
        return self::getModuleByTable($table) !== null;
    }

    /**
     * 判断表是否是关联表.
     */
    public static function isJoinTable(string $table, string $module): bool
    {
        $joins = self::getModuleJoins(self::getModuleFieldConfig()[$module]['table']);
        return isset($joins[$table]);
    }

    /**
     * 根据路由前缀获取子模块配置.
     */
    public static function getSubModuleByRoute(string $routePrefix): ?array
    {
        $routePrefix = $routePrefix . '/';
        foreach (self::SUB_MODULE_CONFIG as $key => $config) {
            if (is_array($config['route_prefix'])) {
                foreach ($config['route_prefix'] as $val) {
                    if (str_starts_with($routePrefix, $val . '/')) {
                        return array_merge(['key' => $key], $config);
                    }
                }
            } elseif (str_starts_with($routePrefix, $config['route_prefix'] . '/')) {
                return array_merge(['key' => $key], $config);
            }
        }
        return null;
    }
}
