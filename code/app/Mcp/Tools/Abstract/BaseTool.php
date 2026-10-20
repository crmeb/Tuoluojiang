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

namespace App\Mcp\Tools\Abstract;

use App\Http\Controller\Mcp\McpRequest;
use App\Mcp\Tools\Contracts\ToolInterface;

/**
 * MCP 工具基类
 * Class BaseTool.
 */
abstract class BaseTool implements ToolInterface
{
    /**
     * 获取工具描述.
     */
    abstract public function getDescription(): string;

    /**
     * 获取输入模式.
     */
    abstract public function getInputSchema(): array;

    /**
     * 执行工具.
     *
     * @param array $arguments
     * @return array
     */
    abstract public function execute(array $arguments): array;

    /**
     * 获取工具动作类型.
     */
    public function getAction(): string
    {
        return 'read';
    }

    /**
     * 获取当前企业ID.
     */
    protected function getEntId(): int
    {
        return $this->getMcpRequest()->getEntId();
    }

    /**
     * 获取 MCP 请求上下文.
     */
    protected function getMcpRequest(): McpRequest
    {
        return app(McpRequest::class);
    }

    /**
     * 获取工具名称 (默认从类名派生).
     * 规则: XxxTool -> xxx, XxxListTool -> xxx_list
     */
    public function getName(): string
    {
        $className = static::class;
        $shortName = $className;
        if (strpos($className, '\\') !== false) {
            $shortName = substr($className, strrpos($className, '\\') + 1);
        }
        // 去掉末尾的 Tool 后缀: CustomerListTool -> CustomerList
        $shortName = preg_replace('/Tool$/', '', $shortName);
        // 转成下划线格式: CustomerList -> customer_list
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }

    /**
     * 获取当前用户ID.
     */
    protected function getUserId(): int
    {
        return $this->getMcpRequest()->getUserId();
    }

    /**
     * 获取当前用户数据库ID.
     */
    protected function getUserDbId(): int
    {
        return $this->getMcpRequest()->getUserDbId() ?: (int) auth('admin')->id();
    }

    /**
     * 获取当前用户信息.
     */
    protected function getUserInfo(): array
    {
        return $this->getMcpRequest()->getUserInfo();
    }

    /**
     * 获取用户所属部门ID列表.
     */
    protected function getFrameIds(): array
    {
        return $this->getMcpRequest()->getFrameIds();
    }

    /**
     * 判断是否为管理员.
     */
    protected function isAdmin(): bool
    {
        return $this->getMcpRequest()->isAdmin();
    }

    /**
     * 获取数据权限范围内的用户ID.
     *
     * @param string $module
     * @param int $type
     * @return array
     */
    protected function getDataUids(string $module = '', int $type = 1): array
    {
        return $this->getMcpRequest()->getDataUids($module, $type);
    }

    /**
     * 归一化数据范围参数.
     */
    protected function normalizeScopeFrame(mixed $scopeFrame, string $default = 'all'): string
    {
        if (is_array($scopeFrame)) {
            $scopeFrame = end($scopeFrame) ?: $default;
        }
        $scopeFrame = (string) $scopeFrame;

        return match ($scopeFrame) {
            ''           => $default,
            'my', 'mine' => 'self',
            default      => $scopeFrame,
        };
    }

    /**
     * 检查用户对指定人员是否有数据权限.
     *
     * @param int $targetUserId
     * @param string $module
     * @return bool
     */
    protected function hasPermissionToUser(int $targetUserId, string $module = ''): bool
    {
        return $this->getMcpRequest()->hasPermissionToUser($targetUserId, $module);
    }
}
