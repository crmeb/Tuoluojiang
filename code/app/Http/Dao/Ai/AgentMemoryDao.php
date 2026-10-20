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

namespace App\Http\Dao\Ai;

use App\Http\Model\Ai\AgentMemory;
use App\Http\Dao\BaseDao;

/**
 * Agent 分层记忆数据访问层.
 *
 * 绑定模型 AgentMemory（agent_memories）.
 * 提供按用户、应用、作用域（session/user）检索与写入记忆.
 *
 * @mixin AgentMemory
 */
class AgentMemoryDao extends BaseDao
{
    protected function setModel(): string
    {
        return AgentMemory::class;
    }
}
