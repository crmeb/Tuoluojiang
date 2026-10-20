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

namespace App\Exceptions\Mcp;

use Exception;

/**
 * MCP 异常类
 */
class McpException extends Exception
{
    /**
     * 错误码定义
     */
    public const CODE_TOOL_NOT_FOUND = -32601;
    public const CODE_INVALID_PARAMS = -32602;
    public const CODE_INTERNAL_ERROR = -32603;
    public const CODE_UNAUTHORIZED = -32604;
    public const CODE_FORBIDDEN = -32605;

    protected int $errorCode;

    public function __construct(string $message = '', int $errorCode = -32603)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 工具未找到
     */
    public static function toolNotFound(string $toolName): self
    {
        return new self("工具不存在: {$toolName}", self::CODE_TOOL_NOT_FOUND);
    }

    /**
     * 参数无效
     */
    public static function invalidParams(string $message): self
    {
        return new self("参数错误: {$message}", self::CODE_INVALID_PARAMS);
    }

    /**
     * 工具执行失败
     */
    public static function toolExecutionFailed(string $toolName, string $reason): self
    {
        return new self("工具 [{$toolName}] 执行失败: {$reason}", self::CODE_INTERNAL_ERROR);
    }

    /**
     * 未授权
     */
    public static function unauthorized(string $message = '未授权'): self
    {
        return new self($message, self::CODE_UNAUTHORIZED);
    }

    /**
     * 禁止访问
     */
    public static function forbidden(string $message = '禁止访问'): self
    {
        return new self($message, self::CODE_FORBIDDEN);
    }

    /**
     * 转换为 JSON-RPC 错误格式
     */
    public function toRpcError(): array
    {
        $error = [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
        ];

        if (config('app.debug')) {
            $error['data'] = [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
            ];
        }

        return $error;
    }
}
