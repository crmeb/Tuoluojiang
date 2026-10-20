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
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agent 运行记录模型.
 *
 * 表名 agent_runs.
 * 主要字段：conversation_id 会话 ID、message_uuid 消息 UUID、app_id 应用 ID、
 * uid 用户 ID、status 运行状态、intent 解析意图、max_steps/current_step 步数、token_usage Token 消耗.
 * 关系：steps 运行步骤列表（按 step_no 排序）.
 */
class AgentRun extends BaseModel
{
    protected $table = 'agent_runs';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'conversation_id' => 'integer',
        'app_id'          => 'integer',
        'uid'             => 'integer',
        'max_steps'       => 'integer',
        'current_step'    => 'integer',
        'intent'          => 'array',
        'token_usage'     => 'array',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(AgentStep::class, 'run_id', 'id')->orderBy('step_no');
    }
}
