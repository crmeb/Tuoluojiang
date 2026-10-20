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

namespace App\Http\Service\Ai\Guest;

use App\Http\Model\Ai\AiApplication;
use App\Http\Service\BaseService;

/**
 * 外部 AI 访客内容安全：输入长度/注入/敏感词校验与流式输出过滤（含分片状态数组 API）.
 */
class AiGuestContentGuard extends BaseService
{
    /** @var array<int, string> */
    private array $words = [];

    /** @var array<int, string> */
    private array $injectionPatterns = [];

    private int $maxWordLength = 1;

    private string $replacement = '***';

    /**
     * 校验访客输入是否允许发送.
     *
     * @throws \Throwable 内容不合规
     */
    public function assertInputAllowed(AiApplication $app, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            throw $this->exception('消息不能为空');
        }

        $config  = AiGuestAuthService::guestConfigFromApplication($app);
        $maxLen  = (int) ($config['max_message_length'] ?? 0);
        if ($maxLen > 0 && mb_strlen($text) > $maxLen) {
            throw $this->exception('消息过长，请缩短后重试');
        }

        $this->bootLexicon($app);

        if ($this->matchSensitiveWord($text) !== null) {
            throw $this->exception('消息包含不当内容，请修改后重试');
        }

        if ($this->hasInjectionPattern($text)) {
            throw $this->exception('消息格式不符合要求');
        }
    }

    /**
     * 是否命中 Prompt 注入短语（字面匹配，供风险评分复用）.
     */
    public function hasInjectionPattern(string $text): bool
    {
        $this->bootInjectionPatterns();

        $haystack = mb_strtolower($text);
        foreach ($this->injectionPatterns as $phrase) {
            $needle = mb_strtolower(trim($phrase));
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 创建流式输出过滤器状态（数组，供 AiStreamService 挂载）.
     *
     * @return array{words: array<int, string>, max_word_length: int, replacement: string, buffer: string}
     */
    public function createOutputFilter(AiApplication $app): array
    {
        $this->bootLexicon($app);

        return [
            'words'            => $this->words,
            'max_word_length'  => $this->maxWordLength,
            'replacement'      => $this->replacement,
            'buffer'           => '',
        ];
    }

    /**
     * 过滤一个 SSE 正文分片（处理跨词边界）.
     *
     * @param array{words?: array<int, string>, max_word_length?: int, replacement?: string, buffer?: string} $state
     */
    public static function filterOutputChunk(array &$state, string $chunk): string
    {
        $words = (array) ($state['words'] ?? []);
        if ($chunk === '' || $words === []) {
            return $chunk;
        }

        $state['buffer'] = (string) ($state['buffer'] ?? '') . $chunk;
        $keepLen         = max(0, (int) ($state['max_word_length'] ?? 1) - 1);
        if (mb_strlen($state['buffer']) <= $keepLen) {
            return '';
        }

        $safeLen         = mb_strlen($state['buffer']) - $keepLen;
        $safe            = mb_substr($state['buffer'], 0, $safeLen);
        $state['buffer'] = mb_substr($state['buffer'], $safeLen);

        return self::replaceOutputWords($safe, $words, (string) ($state['replacement'] ?? '***'));
    }

    /**
     * 流结束时刷新过滤器缓冲区.
     *
     * @param array{words?: array<int, string>, replacement?: string, buffer?: string} $state
     */
    public static function flushOutputFilter(array &$state): string
    {
        $buffer = (string) ($state['buffer'] ?? '');
        if ($buffer === '') {
            return '';
        }
        $state['buffer'] = '';

        return self::replaceOutputWords(
            $buffer,
            (array) ($state['words'] ?? []),
            (string) ($state['replacement'] ?? '***')
        );
    }

    /**
     * @param array<int, string> $words
     */
    private static function replaceOutputWords(string $text, array $words, string $replacement): string
    {
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            $text = str_ireplace($word, $replacement, $text);
        }

        return $text;
    }

    /**
     * 合并系统与应用级敏感词与注入规则.
     */
    private function bootLexicon(AiApplication $app): void
    {
        if ($this->words !== []) {
            return;
        }

        $config = AiGuestAuthService::guestConfigFromApplication($app);
        $words  = array_merge(
            (array) config('ai.guest.content.sensitive_words', []),
            (array) ($config['sensitive_words'] ?? [])
        );
        $words = array_values(array_unique(array_filter(array_map(
            static fn ($word): string => trim((string) $word),
            $words
        ))));

        usort($words, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $this->words         = $words;
        $this->maxWordLength = 1;
        if ($words !== []) {
            $lengths             = array_map(static fn (string $w): int => mb_strlen($w), $words);
            $this->maxWordLength = max(1, ...$lengths);
        }
        $this->replacement   = (string) config('ai.guest.content.replacement', '***');
        $this->bootInjectionPatterns();
    }

    private function bootInjectionPatterns(): void
    {
        if ($this->injectionPatterns !== []) {
            return;
        }

        // 权威键为 injection_phrases；兼容旧键 injection_patterns（若有人仍写正则/短语混用则一律按字面短语处理）.
        $phrases = (array) config('ai.guest.content.injection_phrases', []);
        if ($phrases === []) {
            $phrases = (array) config('ai.guest.content.injection_patterns', []);
        }

        $this->injectionPatterns = array_values(array_unique(array_filter(array_map(
            static fn ($phrase): string => trim((string) $phrase),
            $phrases
        ))));
    }

    private function matchSensitiveWord(string $text): ?string
    {
        foreach ($this->words as $word) {
            if ($word !== '' && mb_stripos($text, $word) !== false) {
                return $word;
            }
        }

        return null;
    }
}
