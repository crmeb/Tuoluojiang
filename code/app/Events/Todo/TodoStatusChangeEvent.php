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

namespace App\Events\Todo;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 待办状态变更事件
 * 当业务数据变更时触发，用于实时同步待办状态.
 */
class TodoStatusChangeEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var int 受影响的用户ID
     */
    public int $userId;

    /**
     * @var string 待办类型 (schedule/assess_self/customer 等)
     */
    public string $type;

    /**
     * @var int 业务记录ID
     */
    public int $sourceId;

    /**
     * @var string 动作类型 (create/update/delete/status_change)
     */
    public string $action;

    /**
     * @var array 额外数据
     */
    public array $extra;

    /**
     * @param int $userId 受影响的用户ID
     * @param string $type 待办类型
     * @param int $sourceId 业务记录ID
     * @param string $action 动作类型
     * @param array $extra 额外数据
     */
    public function __construct(int $userId, string $type, int $sourceId, string $action, array $extra = [])
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->sourceId = $sourceId;
        $this->action = $action;
        $this->extra = $extra;
    }
}
