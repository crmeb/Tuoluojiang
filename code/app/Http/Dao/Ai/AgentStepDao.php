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

use App\Http\Model\Ai\AgentStep;
use App\Http\Dao\BaseDao;

/**
 * Agent 运行步骤数据访问层.
 *
 * 绑定模型 AgentStep（agent_steps）.
 * 提供按 run_id 查询步骤明细及批量写入.
 *
 * @mixin AgentStep
 */
class AgentStepDao extends BaseDao
{
    protected function setModel(): string
    {
        return AgentStep::class;
    }
}
