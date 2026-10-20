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

use App\Http\Model\Ai\AgentRun;
use App\Http\Dao\BaseDao;

/**
 * Agent 运行记录数据访问层.
 *
 * 绑定模型 AgentRun（agent_runs）.
 * 提供按会话、应用、用户、消息 UUID 查询运行记录及关联步骤加载.
 *
 * @mixin AgentRun
 */
class AgentRunDao extends BaseDao
{
    protected function setModel(): string
    {
        return AgentRun::class;
    }
}
