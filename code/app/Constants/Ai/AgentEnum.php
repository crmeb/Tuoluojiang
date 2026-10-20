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

namespace App\Constants\Ai;

/**
 * Agent 运行时枚举.
 *
 * 定义 Run/Pending/Step 状态、意图类型常量与默认步数上限，供 AgentRuntime / ThinDecisionBuilder 等使用.
 */
final class AgentEnum
{
    /** Run 状态：执行工具循环中. */
    public const RUN_STATUS_EXECUTING = 'executing';

    /** Run 状态：反思/总结阶段. */
    public const RUN_STATUS_REFLECTING = 'reflecting';

    /** Run 状态：正常结束. */
    public const RUN_STATUS_DONE = 'done';

    /** Run 状态：异常失败. */
    public const RUN_STATUS_FAILED = 'failed';

    /** Run 状态：用户中断流式输出. */
    public const RUN_STATUS_INTERRUPTED = 'interrupted';

    /** Run 状态：等待用户确认写操作. */
    public const RUN_STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';

    /** Run 状态：等待用户填写信息确认项. */
    public const RUN_STATUS_AWAITING_INPUT = 'awaiting_input';

    /** 写操作待确认：待用户批准/拒绝. */
    public const PENDING_STATUS_PENDING = 'pending';

    /** 写操作待确认：用户已批准. */
    public const PENDING_STATUS_APPROVED = 'approved';

    /** 写操作待确认：用户已拒绝. */
    public const PENDING_STATUS_REJECTED = 'rejected';

    /** 写操作待确认：工具已执行完毕. */
    public const PENDING_STATUS_EXECUTED = 'executed';

    /** 写操作待确认：工具执行后业务写入失败. */
    public const PENDING_STATUS_FAILED = 'failed';

    /** 信息确认项：待用户填写. */
    public const PENDING_INPUT_STATUS_PENDING = 'pending';

    /** 信息确认项：用户已提交. */
    public const PENDING_INPUT_STATUS_FILLED = 'filled';

    /** 信息确认项：已取消/跳过. */
    public const PENDING_INPUT_STATUS_CANCELLED = 'cancelled';

    /**
     * 观测快照：业务数据查询（历史/记忆标签；thin-react 不做意图路由）.
     */
    public const INTENT_DATA_QUERY = 'data_query';

    /**
     * 观测快照：写操作或审批类动作（写确认续跑等）.
     */
    public const INTENT_ACTION_REQUEST = 'action_request';

    /**
     * 观测快照：多步任务（thin-react 默认中性意图）.
     */
    public const INTENT_MULTI_STEP = 'multi_step';

    /** Step 类型：模型思考/规划. */
    public const STEP_TYPE_THINK = 'think';

    /** Step 类型：Skill 层调用（Knowledge/Sql/Mcp）. */
    public const STEP_TYPE_SKILL_CALL = 'skill_call';

    /** Step 类型：具体工具执行. */
    public const STEP_TYPE_TOOL_CALL = 'tool_call';

    /** Step 类型：反思总结. */
    public const STEP_TYPE_REFLECT = 'reflect';

    /** Step 类型：推送 input_required 事件. */
    public const STEP_TYPE_INPUT_REQUIRED = 'input_required';

    /** Step 类型：推送 confirm_required 写操作二次确认事件. */
    public const STEP_TYPE_CONFIRM_REQUIRED = 'confirm_required';

    /** Step 类型：最终回答生成. */
    public const STEP_TYPE_ANSWER = 'answer';

    /** Step 状态：排队. */
    public const STEP_STATUS_PENDING = 'pending';

    /** Step 状态：执行中. */
    public const STEP_STATUS_RUNNING = 'running';

    /** Step 状态：完成. */
    public const STEP_STATUS_DONE = 'done';

    /** Step 状态：失败. */
    public const STEP_STATUS_FAILED = 'failed';

    /** 单次 Agent Run 默认最大步数（防无限循环）. */
    public const DEFAULT_MAX_STEPS = 10;
}
