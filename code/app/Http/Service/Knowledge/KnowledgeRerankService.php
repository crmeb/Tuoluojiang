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

namespace App\Http\Service\Knowledge;

use App\Http\Service\BaseService;

/**
 * 知识库精排（Rerank）服务.
 *
 * 外部精排模型字段已下线；本服务对粗排 hits 做词法重叠加权 + TopK 截断，
 * 抑制「向量分高但与问句几乎无字面交集」的噪声命中.
 */
class KnowledgeRerankService extends BaseService
{
    private const VECTOR_WEIGHT = 0.55;

    private const LEXICAL_WEIGHT = 0.45;

    /**
     * 对粗排结果做词法加权重排并截断为 topK.
     *
     * @param string                           $question 检索问句
     * @param array<int, array<string, mixed>> $hits     粗排命中列表
     * @param int                              $topK     返回条数上限
     *
     * @return array<int, array<string, mixed>> 重排后的命中
     */
    public function rerank(string $question, array $hits, int $topK): array
    {
        $topK = max(0, $topK);
        if ($hits === [] || $topK <= 0) {
            return [];
        }

        $tokens         = $this->tokenize($question);
        $versionTokens  = $this->versionTokens($tokens);
        $ranked         = [];
        foreach (array_values($hits) as $hit) {
            if (! is_array($hit)) {
                continue;
            }
            $title        = (string) ($hit['title'] ?? '');
            $content      = (string) ($hit['content'] ?? '');
            $vectorScore  = max(0.0, min(1.0, (float) ($hit['score'] ?? 0)));
            $lexicalScore = $this->lexicalScore($tokens, $versionTokens, $title, $content);
            $versionBonus = $this->versionMatchBonus($versionTokens, $title, $content);
            // 无字面重叠时仅保留向量分量（降权），不直接丢弃，避免中文同义表达误杀.
            $hit['score'] = round(
                max(0.0, min(1.0, $vectorScore * self::VECTOR_WEIGHT + $lexicalScore * self::LEXICAL_WEIGHT + $versionBonus)),
                6
            );
            $meta                   = (array) ($hit['metadata'] ?? []);
            $meta['vector_score']   = $vectorScore;
            $meta['lexical_score']  = $lexicalScore;
            $meta['version_bonus']  = $versionBonus;
            $hit['metadata']        = $meta;
            $ranked[]               = $hit;
        }

        usort($ranked, static fn (array $a, array $b): int => ((float) ($b['score'] ?? 0)) <=> ((float) ($a['score'] ?? 0)));

        return array_values(array_slice($ranked, 0, $topK));
    }

    /**
     * 将问句切成可用于重叠计算的词元（中文 bigram + 英文/数字 token）.
     *
     * @return array<int, string>
     */
    public function tokenize(string $text): array
    {
        $text = mb_strtolower(trim($text));
        if ($text === '') {
            return [];
        }

        // 去掉常见停用疑问词，避免「有哪些/一下」抬高噪声命中.
        $text = preg_replace('/[我的俺们你您他她它们]|有哪些|哪些|什么|多少|一下|下|查|看|请|帮|关于/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        $tokens = [];
        // 保留带点版本号（如 2.5 / v2.5.1）；默认 [a-z0-9_] 会把「2.5」拆丢，导致精排无法区分 2.1/2.5 文档.
        if (preg_match_all('/v?\d+(?:\.\d+){1,3}/u', $text, $versionMatches)) {
            foreach ($versionMatches[0] as $token) {
                $tokens[] = $token;
                $bare     = ltrim($token, 'v');
                if ($bare !== $token) {
                    $tokens[] = $bare;
                } else {
                    $tokens[] = 'v' . $bare;
                }
            }
        }
        if (preg_match_all('/[a-z0-9_]{2,}/u', $text, $m)) {
            foreach ($m[0] as $token) {
                $tokens[] = $token;
            }
        }

        $chars = preg_replace('/[^\p{Han}]/u', '', $text) ?? '';
        $len   = mb_strlen($chars);
        if ($len === 1) {
            $tokens[] = $chars;
        } elseif ($len >= 2) {
            for ($i = 0; $i < $len - 1; ++$i) {
                $tokens[] = mb_substr($chars, $i, 2);
            }
        }

        return array_values(array_unique(array_filter($tokens, static fn (string $t): bool => $t !== '')));
    }

    /**
     * 从词元中抽出版本号（含 v 前缀变体）.
     *
     * @param array<int, string> $tokens
     *
     * @return array<int, string>
     */
    public function versionTokens(array $tokens): array
    {
        $versions = [];
        foreach ($tokens as $token) {
            if (preg_match('/^v?\d+(?:\.\d+){1,3}$/u', $token)) {
                $versions[] = ltrim(mb_strtolower($token), 'v');
            }
        }

        return array_values(array_unique($versions));
    }

    /**
     * 标题/正文是否包含指定版本号（兼容 2.5版 / v2.5 / V2.5，避免误伤 1.2.1 等序号）.
     */
    public function textHasVersion(string $text, string $version): bool
    {
        $version = ltrim(mb_strtolower(trim($version)), 'v');
        if ($version === '' || ! preg_match('/^\d+(?:\.\d+){1,3}$/u', $version)) {
            return false;
        }
        $quoted = preg_quote($version, '/');

        return (bool) preg_match(
            '/(?<![0-9.])v' . $quoted . '(?![0-9.])|(?<![0-9.])' . $quoted . '(?:版本|版)/iu',
            $text
        );
    }

    /**
     * 计算标题/正文与问句词元的重叠分（0–1）；版本号命中权重更高.
     *
     * @param array<int, string> $tokens
     * @param array<int, string> $versionTokens 已去 v 前缀的版本号
     */
    private function lexicalScore(array $tokens, array $versionTokens, string $title, string $content): float
    {
        if ($tokens === []) {
            return 0.0;
        }

        $haystack = mb_strtolower($title . "\n" . $content);
        if ($haystack === '') {
            return 0.0;
        }

        $versionSet = array_fill_keys($versionTokens, true);
        $otherHit   = 0;
        $otherTotal = 0;
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            $bare = ltrim($token, 'v');
            if (isset($versionSet[$bare]) || preg_match('/^v?\d+(?:\.\d+){1,3}$/u', $token)) {
                continue;
            }
            ++$otherTotal;
            if (mb_strpos($haystack, $token) !== false) {
                ++$otherHit;
            }
        }
        $otherScore = $otherTotal > 0 ? $otherHit / $otherTotal : 0.0;

        if ($versionTokens === []) {
            return min(1.0, $otherScore);
        }

        $versionHit = 0;
        foreach ($versionTokens as $version) {
            if ($this->textHasVersion($haystack, $version)) {
                ++$versionHit;
            }
        }
        $versionScore = $versionHit / count($versionTokens);

        // 版本对齐优先：问了 2.5 却命中 2.1 文档时，词法分会被显著拉低.
        return min(1.0, $otherScore * 0.4 + $versionScore * 0.6);
    }

    /**
     * 版本精确匹配加分；问句含版本但文档全无对应版本时给负分.
     *
     * @param array<int, string> $versionTokens
     */
    private function versionMatchBonus(array $versionTokens, string $title, string $content): float
    {
        if ($versionTokens === []) {
            return 0.0;
        }

        $haystack = $title . "\n" . $content;
        $hit      = 0;
        foreach ($versionTokens as $version) {
            if ($this->textHasVersion($haystack, $version)) {
                ++$hit;
            }
        }

        if ($hit === count($versionTokens)) {
            return 0.22;
        }
        if ($hit === 0) {
            return -0.18;
        }

        return 0.08 * ($hit / count($versionTokens));
    }
}
