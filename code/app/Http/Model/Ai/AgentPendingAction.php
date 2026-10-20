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

namespace App\Http\Model\Ai;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agent 待确认写操作模型.
 *
 * 表名 agent_pending_actions.
 * 主要字段：run_id 运行 ID、message_uuid 消息 UUID、tool_call_id 工具调用 ID、
 * tool_name 工具名称、tool_args 工具参数、status 状态、result 执行结果.
 * 关系：run 所属运行记录.
 */
class AgentPendingAction extends BaseModel
{
    protected $table = 'agent_pending_actions';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'        => 'integer',
        'run_id'    => 'integer',
        'tool_args' => 'array',
        'result'    => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'run_id', 'id');
    }
}
