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

namespace App\Http\Service\Ai\Testing;

/**
 * Agent 行为测试桩（对齐 laravel/ai Agent::fake / assertPrompted，T-6）.
 */
final class AiAgent
{
    /** @var list<array<string, mixed>> */
    private static array $prompts = [];

    /** @var list<array<string, mixed>> */
    private static array $toolInvocations = [];

    private static bool $faking = false;

    public static function fake(): void
    {
        self::$faking           = true;
        self::$prompts          = [];
        self::$toolInvocations  = [];
    }

    public static function stopFaking(): void
    {
        self::$faking           = false;
        self::$prompts          = [];
        self::$toolInvocations  = [];
    }

    public static function isFaking(): bool
    {
        return self::$faking;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function recordPrompt(array $payload): void
    {
        if (! self::$faking) {
            return;
        }
        self::$prompts[] = $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function recordToolInvocation(array $payload): void
    {
        if (! self::$faking) {
            return;
        }
        self::$toolInvocations[] = $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function recordedPrompts(): array
    {
        return self::$prompts;
    }

    /**
     * @param callable(array<string, mixed>):bool|string|null $matcher
     */
    public static function assertPrompted($matcher = null): void
    {
        if (self::$prompts === []) {
            throw new \PHPUnit\Framework\AssertionFailedError('AiAgent::assertPrompted failed: no prompts recorded');
        }
        if ($matcher === null) {
            return;
        }
        if (is_string($matcher)) {
            foreach (self::$prompts as $prompt) {
                $haystack = json_encode($prompt, JSON_UNESCAPED_UNICODE) ?: '';
                if (str_contains($haystack, $matcher)) {
                    return;
                }
            }
            throw new \PHPUnit\Framework\AssertionFailedError(
                'AiAgent::assertPrompted failed: no prompt matched string "' . $matcher . '"'
            );
        }
        foreach (self::$prompts as $prompt) {
            if ($matcher($prompt)) {
                return;
            }
        }
        throw new \PHPUnit\Framework\AssertionFailedError('AiAgent::assertPrompted failed: no prompt matched callback');
    }

    public static function assertNotPrompted(): void
    {
        if (self::$prompts !== []) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                'AiAgent::assertNotPrompted failed: recorded ' . count(self::$prompts) . ' prompt(s)'
            );
        }
    }
}
