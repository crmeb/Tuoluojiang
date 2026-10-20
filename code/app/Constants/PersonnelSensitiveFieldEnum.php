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

namespace App\Constants;

/**
 * 人员敏感字段枚举
 * 定义敏感字段的权限级别和脱敏规则
 */
final class PersonnelSensitiveFieldEnum
{
    /**
     * 权限级别定义
     */

    /**
     * 仅 HR 可见
     */
    public const LEVEL_HR_ONLY = 'hr_only';

    /**
     * 仅 HR 在特定场景可见（如办理证件）
     */
    public const LEVEL_HR_CERTIFICATION = 'hr_certification';

    /**
     * 仅财务可见
     */
    public const LEVEL_FINANCE_ONLY = 'finance_only';

    /**
     * 直属上级或 HR 可见
     */
    public const LEVEL_SUPERIOR_OR_HR = 'superior_or_hr';

    /**
     * 上级及 HR 可见
     */
    public const LEVEL_MANAGEMENT = 'management';

    /**
     * 本人可见
     */
    public const LEVEL_SELF_ONLY = 'self_only';

    /**
     * 敏感字段定义
     */
    public const FIELDS = [
        // 薪资相关
        'salary' => [
            'level' => self::LEVEL_HR_ONLY,
            'mask' => '***',
            'description' => '薪资',
        ],
        'salary_base' => [
            'level' => self::LEVEL_HR_ONLY,
            'mask' => '***',
            'description' => '基本工资',
        ],
        'salary_bonus' => [
            'level' => self::LEVEL_HR_ONLY,
            'mask' => '***',
            'description' => '奖金',
        ],

        // 身份信息
        'id_card' => [
            'level' => self::LEVEL_HR_CERTIFICATION,
            'mask' => '***',
            'description' => '身份证号',
        ],
        'id_card_num' => [
            'level' => self::LEVEL_HR_CERTIFICATION,
            'mask' => '***',
            'description' => '身份证号',
        ],

        // 银行信息
        'bank_card' => [
            'level' => self::LEVEL_FINANCE_ONLY,
            'mask' => '***',
            'description' => '银行卡号',
        ],
        'bank_account' => [
            'level' => self::LEVEL_FINANCE_ONLY,
            'mask' => '***',
            'description' => '银行账号',
        ],
        'bank_name' => [
            'level' => self::LEVEL_FINANCE_ONLY,
            'mask' => '***',
            'description' => '开户行',
        ],

        // 联系方式
        'phone_private' => [
            'level' => self::LEVEL_SUPERIOR_OR_HR,
            'mask' => '***',
            'description' => '私人电话',
        ],
        'phone_emergency' => [
            'level' => self::LEVEL_SUPERIOR_OR_HR,
            'mask' => '***',
            'description' => '紧急联系人电话',
        ],
        'emergency_contact' => [
            'level' => self::LEVEL_SUPERIOR_OR_HR,
            'mask' => '***',
            'description' => '紧急联系人',
        ],
        'emergency_contact_phone' => [
            'level' => self::LEVEL_SUPERIOR_OR_HR,
            'mask' => '***',
            'description' => '紧急联系人电话',
        ],

        // 地址信息
        'address' => [
            'level' => self::LEVEL_HR_ONLY,
            'mask' => '***',
            'description' => '家庭住址',
        ],
        'home_address' => [
            'level' => self::LEVEL_HR_ONLY,
            'mask' => '***',
            'description' => '家庭住址',
        ],

        // 绩效相关
        'performance_score' => [
            'level' => self::LEVEL_MANAGEMENT,
            'mask' => '***',
            'description' => '绩效评分',
        ],
        'assess_score' => [
            'level' => self::LEVEL_MANAGEMENT,
            'mask' => '***',
            'description' => '考核分数',
        ],

        // 离职相关
        'resign_reason' => [
            'level' => self::LEVEL_HR_ONLY,
            'mask' => '***',
            'description' => '离职原因',
        ],
        'resign_comment' => [
            'level' => self::LEVEL_HR_ONLY,
            'mask' => '***',
            'description' => '离职备注',
        ],
    ];

    /**
     * 获取所有敏感字段名
     */
    public static function getSensitiveFields(): array
    {
        return array_keys(self::FIELDS);
    }

    /**
     * 检查字段是否为敏感字段
     */
    public static function isSensitive(string $field): bool
    {
        return isset(self::FIELDS[$field]);
    }

    /**
     * 获取字段的权限级别
     */
    public static function getFieldLevel(string $field): ?string
    {
        return self::FIELDS[$field]['level'] ?? null;
    }

    /**
     * 获取字段的脱敏值
     */
    public static function getMaskValue(string $field): string
    {
        return self::FIELDS[$field]['mask'] ?? '***';
    }

    /**
     * 获取字段描述
     */
    public static function getFieldDescription(string $field): string
    {
        return self::FIELDS[$field]['description'] ?? $field;
    }

    /**
     * 根据权限级别获取字段列表
     */
    public static function getFieldsByLevel(string $level): array
    {
        return array_filter(self::FIELDS, function ($config) use ($level) {
            return $config['level'] === $level;
        });
    }
}
