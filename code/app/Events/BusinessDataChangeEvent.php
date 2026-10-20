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

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 业务数据变更事件
 * 当业务数据发生变更时触发，用于通知待办系统同步更新.
 *
 * 与 TodoStatusChangeEvent 的区别：
 * - 此事件仅包含 type 和 sourceId，不携带 userId
 * - 由模型观察者触发，而非业务 Service
 * - 监听器根据 sourceId 查询应同步的用户范围
 */
class BusinessDataChangeEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var string 待办类型 (schedule/assess_self/customer/contract/invoice/task/notice/approve_submit/approve_pending)
     */
    public string $type;

    /**
     * @var int 业务记录ID
     */
    public int $sourceId;

    /**
     * @var string 动作类型 (create/update/delete)
     */
    public string $action;

    /**
     * @var array<int, int> 预加载的用户ID列表（删除时模型已不存在，需提前保存）
     */
    public array $userIds;

    /**
     * @param string $type 待办类型
     * @param int $sourceId 业务记录ID
     * @param string $action 动作类型
     * @param array<int, int> $userIds 预加载的用户ID列表
     */
    public function __construct(string $type, int $sourceId, string $action = 'update', array $userIds = [])
    {
        $this->type = $type;
        $this->sourceId = $sourceId;
        $this->action = $action;
        $this->userIds = $userIds;
    }
}
