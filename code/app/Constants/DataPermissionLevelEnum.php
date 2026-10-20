<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * 数据权限级别枚举 - 支持6级权限体系
 */
final class DataPermissionLevelEnum
{
    /**
     * 不允许访问
     */
    public const NONE = 0;

    /**
     * 仅本人数据
     */
    public const SELF = 1;

    /**
     * 直属下级数据
     */
    public const DIRECT_SUBORDINATE = 2;

    /**
     * 本部门数据
     */
    public const DEPARTMENT = 3;

    /**
     * 自定义部门数据
     */
    public const CUSTOM_DEPARTMENT = 4;

    /**
     * 全部数据
     */
    public const ALL = 5;

    /**
     * 级别 labels
     */
    public const LEVELS = [
        self::NONE => '不允许访问',
        self::SELF => '仅本人数据',
        self::DIRECT_SUBORDINATE => '直属下级数据',
        self::DEPARTMENT => '本部门数据',
        self::CUSTOM_DEPARTMENT => '自定义部门数据',
        self::ALL => '全部数据',
    ];

    /**
     * 获取级别标签
     */
    public static function getLabel(int $level): string
    {
        return self::LEVELS[$level] ?? '未知';
    }

    /**
     * 判断是否有效级别
     */
    public static function isValid(int $level): bool
    {
        return isset(self::LEVELS[$level]);
    }

    /**
     * 判断是否可访问（级别大于 NONE）
     */
    public static function isAccessible(int $level): bool
    {
        return $level > self::NONE;
    }

    /**
     * 比较两个权限级别，返回较高的级别
     * 用于多角色权限合并
     */
    public static function max(int $level1, int $level2): int
    {
        // NONE(0) < SELF(1) < DIRECT_SUBORDINATE(2) < DEPARTMENT(3) < CUSTOM_DEPARTMENT(4) < ALL(5)
        // 优先级顺序：NONE < SELF < DIRECT_SUBORDINATE < DEPARTMENT < CUSTOM_DEPARTMENT < ALL
        $priority = [
            self::NONE => 0,
            self::SELF => 1,
            self::DIRECT_SUBORDINATE => 2,
            self::DEPARTMENT => 3,
            self::CUSTOM_DEPARTMENT => 4,
            self::ALL => 5,
        ];

        return ($priority[$level1] ?? 0) >= ($priority[$level2] ?? 0) ? $level1 : $level2;
    }

    /**
     * 获取所有有效级别
     */
    public static function all(): array
    {
        return self::LEVELS;
    }
}
