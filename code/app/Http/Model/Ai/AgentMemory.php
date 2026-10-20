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

/**
 * Agent 分层记忆模型.
 *
 * 表名 agent_memories.
 * 主要字段：uid 用户 ID、app_id 应用 ID、conversation_id 会话 ID（0 表示跨会话）、
 * memory_type 记忆类型、scope 作用域（session/user）、content 记忆内容、
 * metadata 元数据、source_run_id/source_message_uuid 来源追溯.
 */
class AgentMemory extends BaseModel
{
    protected $table = 'agent_memories';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'uid'             => 'integer',
        'app_id'          => 'integer',
        'conversation_id' => 'integer',
        'source_run_id'   => 'integer',
        'metadata'        => 'array',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
    ];
}
