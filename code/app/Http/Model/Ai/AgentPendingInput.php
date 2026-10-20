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
 * Agent 待用户补充信息模型.
 *
 * 表名 agent_pending_inputs.
 * 主要字段：run_id 运行 ID、message_uuid 消息 UUID、field_key/field_label 字段标识、
 * input_type 输入类型、options 可选项、meta 上下文、status 状态、user_value/user_label 用户提交值.
 * 关系：run 所属运行记录.
 */
class AgentPendingInput extends BaseModel
{
    protected $table = 'agent_pending_inputs';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'run_id'     => 'integer',
        'options'    => 'array',
        'meta'       => 'array',
        'user_value' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'run_id', 'id');
    }
}
