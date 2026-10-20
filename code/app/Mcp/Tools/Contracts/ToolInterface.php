<?php
declare(strict_types=1);

namespace App\Mcp\Tools\Contracts;

interface ToolInterface
{
    /**
     * 获取工具唯一名称
     */
    public function getName(): string;

    /**
     * 获取工具描述
     */
    public function getDescription(): string;

    /**
     * 获取输入参数 Schema (JSON Schema 格式)
     */
    public function getInputSchema(): array;

    /**
     * 执行工具
     * @param array $arguments 工具参数
     * @return array 执行结果
     */
    public function execute(array $arguments): array;
}
