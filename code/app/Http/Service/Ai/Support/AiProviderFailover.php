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

namespace App\Http\Service\Ai\Support;

/**
 * Provider failover 判定与候选解析（T-4）.
 * 仅在「尚未向用户推送有效正文」且错误可重试时切换候选模型.
 */
final class AiProviderFailover
{
    /**
     * @return list<int> 去重后的模型 ID 列表（主模型在前）
     */
    public function candidateModelIds(int $primaryModelId, ?array $configuredIds = null): array
    {
        $ids = $configuredIds;
        if ($ids === null) {
            try {
                $ids = (array) config('ai.failover.model_ids', []);
            } catch (\Throwable) {
                $ids = [];
            }
        }
        $ordered = [$primaryModelId];
        foreach ($ids as $id) {
            $intId = (int) $id;
            if ($intId > 0 && ! in_array($intId, $ordered, true)) {
                $ordered[] = $intId;
            }
        }

        return $ordered;
    }

    public function maxAttempts(): int
    {
        try {
            return max(1, (int) config('ai.failover.max_attempts', 2));
        } catch (\Throwable) {
            return 2;
        }
    }

    public function enabled(): bool
    {
        try {
            return (bool) config('ai.failover.enabled', true);
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * 是否属于可切换备选模型的失败（429 / 5xx / 超时 / 空响应类）.
     */
    public function isRetryableError(string $message, int $httpStatus = 0): bool
    {
        if ($httpStatus === 429 || $httpStatus >= 500) {
            return true;
        }
        $message = trim($message);
        if ($message === '') {
            return false;
        }

        return (bool) preg_match(
            '/429|rate.?limit|too many requests|timeout|timed out|503|502|500|服务不可用|过载|没有返回有效回复|upstream/iu',
            $message
        );
    }

    /**
     * 指数退避秒数（含抖动上限）.
     */
    public function backoffSeconds(int $attempt): float
    {
        try {
            $base = max(0.0, (float) config('ai.failover.backoff_base_seconds', 0.5));
            $cap  = max($base, (float) config('ai.failover.backoff_max_seconds', 4.0));
        } catch (\Throwable) {
            $base = 0.5;
            $cap  = 4.0;
        }
        $sec = min($cap, $base * (2 ** max(0, $attempt - 1)));

        return round($sec, 3);
    }

    /**
     * 流式结果是否允许 failover（失败且无有效正文/工具调用）.
     *
     * @param array<string, mixed> $streamResult
     */
    public function shouldFailover(array $streamResult, string $errorHint = ''): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        $status = (int) ($streamResult['status'] ?? 0);
        $answer = trim((string) ($streamResult['answer'] ?? ''));
        $tools  = (array) ($streamResult['tool_calls'] ?? []);
        $failed = $status === \App\Constants\Ai\AiConversationEnum::MESSAGE_FAILED;
        if (! $failed) {
            return false;
        }
        // 已有工具调用或较长正文时不再切换（避免半截 SSE 后换模）.
        if ($tools !== []) {
            return false;
        }
        if (mb_strlen($answer) > 80 && ! $this->isRetryableError($answer) && ! $this->isRetryableError($errorHint)) {
            return false;
        }

        return $this->isRetryableError($errorHint !== '' ? $errorHint : $answer);
    }
}
