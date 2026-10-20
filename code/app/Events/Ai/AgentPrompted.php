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
 * Agent 向模型发起一轮 prompt（对齐 laravel/ai AgentPrompted 语义，T-5）.
 */
class AgentPrompted
{
    public string $messageUuid;

    public string $model;

    public int $round;

    /** @var array<string, mixed> */
    public array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $messageUuid, string $model, int $round, array $context = [])
    {
        $this->messageUuid = $messageUuid;
        $this->model       = $model;
        $this->round       = $round;
        $this->context     = $context;
    }
}
