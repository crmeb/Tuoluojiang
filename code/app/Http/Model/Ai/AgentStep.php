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
 * Agent 运行步骤模型.
 *
 * 表名 agent_steps.
 * 主要字段：run_id 运行 ID、step_no 步骤序号、type 步骤类型、
 * input/output 输入输出 JSON、skill_name/tool_name 技能与工具、status 步骤状态、latency_ms 耗时.
 * 关系：run 所属运行记录.
 */
class AgentStep extends BaseModel
{
    public $timestamps = false;

    protected $table = 'agent_steps';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'run_id'     => 'integer',
        'step_no'    => 'integer',
        'latency_ms' => 'integer',
        'input'      => 'array',
        'output'     => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'run_id', 'id');
    }
}
