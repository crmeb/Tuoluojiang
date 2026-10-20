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

namespace App\Http\Context;

/**
 * 数据权限上下文管理器
 *
 * 使用请求级容器绑定实现协程安全
 * 在 Swoole 多协程环境下，每个请求都有独立的上下文实例
 */
class DataPermissionContext
{
    /**
     * 容器绑定 key
     */
    private const CONTAINER_BINDING = 'context.data_permission';

    /**
     * 上下文数据
     */
    private array $context = [];

    /**
     * 设置权限上下文
     */
    public static function set(string $module, array $uids, int $dataLevel = 1, array $frameIds = [], int $directly = 0): void
    {
        self::getInstance()->store([
            'module' => $module,
            'uids' => $uids,
            'data_level' => $dataLevel,
            'frame_ids' => $frameIds,
            'directly' => $directly,
            'enabled' => true,
        ]);
    }

    /**
     * 获取上下文
     */
    public static function get(): array
    {
        return self::getInstance()->retrieve();
    }

    /**
     * 检查是否启用
     */
    public static function isEnabled(): bool
    {
        $context = self::getInstance()->retrieve();
        return ! empty($context['enabled']);
    }

    /**
     * 获取模块
     */
    public static function getModule(): ?string
    {
        $context = self::getInstance()->retrieve();
        return $context['module'] ?? null;
    }

    /**
     * 获取允许的 UID 列表
     */
    public static function getUids(): array
    {
        $context = self::getInstance()->retrieve();
        return $context['uids'] ?? [];
    }

    /**
     * 获取数据级别
     */
    public static function getDataLevel(): int
    {
        $context = self::getInstance()->retrieve();
        return $context['data_level'] ?? 1;
    }

    /**
     * 获取部门 ID 列表
     */
    public static function getFrameIds(): array
    {
        $context = self::getInstance()->retrieve();
        return $context['frame_ids'] ?? [];
    }

    /**
     * 是否包含直属下级
     */
    public static function getDirectly(): int
    {
        $context = self::getInstance()->retrieve();
        return $context['directly'] ?? 0;
    }

    /**
     * 清除上下文
     */
    public static function clear(): void
    {
        self::getInstance()->store([]);
    }

    /**
     * 禁用自动过滤（用于后台任务等不需要权限过滤的场景）
     */
    public static function disable(): void
    {
        $instance = self::getInstance();
        $context = $instance->retrieve();
        $context['enabled'] = false;
        $instance->store($context);
    }

    /**
     * 启用自动过滤
     */
    public static function enable(): void
    {
        $instance = self::getInstance();
        $context = $instance->retrieve();
        if (isset($context['module'])) {
            $context['enabled'] = true;
            $instance->store($context);
        }
    }

    /**
     * 获取当前实例（请求级容器单例）
     */
    private static function getInstance(): self
    {
        // 使用请求级容器绑定实现协程安全
        // 每个 HTTP 请求都会创建新的容器实例，从而获得独立的 DataPermissionContext
        if (! app()->bound(self::CONTAINER_BINDING)) {
            app()->instance(self::CONTAINER_BINDING, new self());
        }

        return app()->make(self::CONTAINER_BINDING);
    }

    /**
     * 存储上下文数据
     */
    private function store(array $context): void
    {
        $this->context = $context;
    }

    /**
     * 获取上下文数据
     */
    private function retrieve(): array
    {
        return $this->context;
    }
}
