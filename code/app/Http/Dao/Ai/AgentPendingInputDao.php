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

use App\Http\Model\Ai\AgentPendingInput;
use App\Http\Dao\BaseDao;

/**
 * Agent 待用户补充信息数据访问层.
 *
 * 绑定模型 AgentPendingInput（agent_pending_inputs）.
 * 提供按 run_id、message_uuid、status 查询待确认输入项及更新用户提交值.
 *
 * @mixin AgentPendingInput
 */
class AgentPendingInputDao extends BaseDao
{
    protected function setModel(): string
    {
        return AgentPendingInput::class;
    }
}
