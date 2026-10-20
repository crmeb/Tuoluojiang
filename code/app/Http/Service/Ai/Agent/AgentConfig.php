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

namespace App\Http\Service\Ai\Agent;

use App\Constants\Ai\AgentEnum;
use App\Constants\Ai\AgentMemoryEnum;
use App\Constants\Ai\AgentQuotaEnum;
use App\Http\Model\Ai\AiApplication;

/**
 * AI 应用 Agent 编排调参（存于 chat_applications.agent_json）.
 *
 * 职责：封装 max_steps、反思、写确认、记忆与配额等运行时开关.
 * 配额原 AgentQuotaConfig 已内联为 {@see $quota} 对象属性.
 */
class AgentConfig
{
    public int $maxSteps;

    public bool $enableReflection;

    public bool $writeConfirmation;

    public bool $memoryEnabled;

    public int $maxMemories;

    public int $sessionTurns;

    /**
     * 配额子配置（原 AgentQuotaConfig）.
     *
     * @var object{
     *     enabled: bool,
     *     dailyRunsPerUser: int,
     *     dailyTokensPerUser: int,
     *     maxConcurrentRuns: int
     * }
     */
    public object $quota;

    /**
     * @param null|array{enabled?: bool, daily_runs_per_user?: int, daily_tokens_per_user?: int, max_concurrent_runs?: int}|object $quota
     */
    public function __construct(
        int $maxSteps = AgentEnum::DEFAULT_MAX_STEPS,
        bool $enableReflection = true,
        bool $writeConfirmation = true,
        bool $memoryEnabled = true,
        int $maxMemories = AgentMemoryEnum::DEFAULT_MAX_MEMORIES,
        int $sessionTurns = AgentMemoryEnum::DEFAULT_SESSION_TURNS,
        array|object|null $quota = null
    ) {
        $this->maxSteps          = max(1, $maxSteps);
        $this->enableReflection  = $enableReflection;
        $this->writeConfirmation = $writeConfirmation;
        $this->memoryEnabled     = $memoryEnabled;
        $this->maxMemories       = max(1, $maxMemories);
        $this->sessionTurns      = max(1, $sessionTurns);
        $this->quota             = self::normalizeQuota($quota);
    }

    /**
     * 按本轮 decision.max_tool_rounds 收紧工具循环步数（不写回应用配置）.
     *
     * @param int $maxToolRounds ThinDecisionBuilder 给出的工具轮上限
     */
    public function applyToolRoundLimit(int $maxToolRounds): void
    {
        if ($maxToolRounds <= 0) {
            $this->maxSteps = 1;

            return;
        }

        $this->maxSteps = min($this->maxSteps, max(1, $maxToolRounds));
    }

    /**
     * 从 AI 应用 agent_json 字段构建配置对象.
     */
    public static function fromApplication(AiApplication $app): self
    {
        return self::fromArray((array) ($app->agent_json ?? []));
    }

    /**
     * 从数组解析 Agent 配置项.
     *
     * @param array<string, mixed> $config agent_json 或等价结构
     */
    public static function fromArray(array $config): self
    {
        $memory = (array) ($config['memory'] ?? []);

        return new self(
            (int) ($config['max_steps'] ?? AgentEnum::DEFAULT_MAX_STEPS),
            (bool) ($config['enable_reflection'] ?? true),
            (bool) ($config['write_confirmation'] ?? true),
            (bool) ($memory['enabled'] ?? true),
            (int) ($memory['max_memories'] ?? AgentMemoryEnum::DEFAULT_MAX_MEMORIES),
            (int) ($memory['session_turns'] ?? AgentMemoryEnum::DEFAULT_SESSION_TURNS),
            (array) ($config['quota'] ?? [])
        );
    }

    /**
     * 序列化为可持久化的数组结构.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'max_steps'          => $this->maxSteps,
            'enable_reflection'  => $this->enableReflection,
            'write_confirmation' => $this->writeConfirmation,
            'memory'             => [
                'enabled'       => $this->memoryEnabled,
                'max_memories'  => $this->maxMemories,
                'session_turns' => $this->sessionTurns,
            ],
            'quota'              => [
                'enabled'               => (bool) $this->quota->enabled,
                'daily_runs_per_user'   => (int) $this->quota->dailyRunsPerUser,
                'daily_tokens_per_user' => (int) $this->quota->dailyTokensPerUser,
                'max_concurrent_runs'   => (int) $this->quota->maxConcurrentRuns,
            ],
        ];
    }

    /**
     * @param null|array<string, mixed>|object $quota
     *
     * @return object{
     *     enabled: bool,
     *     dailyRunsPerUser: int,
     *     dailyTokensPerUser: int,
     *     maxConcurrentRuns: int
     * }
     */
    public static function normalizeQuota(array|object|null $quota): object
    {
        if (is_object($quota)
            && isset($quota->enabled, $quota->dailyRunsPerUser, $quota->dailyTokensPerUser, $quota->maxConcurrentRuns)
        ) {
            return (object) [
                'enabled'             => (bool) $quota->enabled,
                'dailyRunsPerUser'    => max(0, (int) $quota->dailyRunsPerUser),
                'dailyTokensPerUser'  => max(0, (int) $quota->dailyTokensPerUser),
                'maxConcurrentRuns'   => max(0, (int) $quota->maxConcurrentRuns),
            ];
        }

        $config = is_array($quota) ? $quota : [];

        return (object) [
            'enabled'            => (bool) ($config['enabled'] ?? true),
            'dailyRunsPerUser'   => max(0, (int) ($config['daily_runs_per_user'] ?? $config['dailyRunsPerUser'] ?? AgentQuotaEnum::DEFAULT_DAILY_RUNS_PER_USER)),
            'dailyTokensPerUser' => max(0, (int) ($config['daily_tokens_per_user'] ?? $config['dailyTokensPerUser'] ?? AgentQuotaEnum::DEFAULT_DAILY_TOKENS_PER_USER)),
            'maxConcurrentRuns'  => max(0, (int) ($config['max_concurrent_runs'] ?? $config['maxConcurrentRuns'] ?? AgentQuotaEnum::DEFAULT_MAX_CONCURRENT_RUNS)),
        ];
    }
}
