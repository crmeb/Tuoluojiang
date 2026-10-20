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

namespace App\Events\Ai;

/**
 * Agent 完成一次工具调用（对齐 laravel/ai ToolInvoked 语义，T-5）.
 */
class ToolInvoked
{
    public string $messageUuid;

    public string $toolName;

    public bool $isError;

    /** @var array<string, mixed> */
    public array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $messageUuid, string $toolName, bool $isError = false, array $context = [])
    {
        $this->messageUuid = $messageUuid;
        $this->toolName    = $toolName;
        $this->isError     = $isError;
        $this->context     = $context;
    }
}
