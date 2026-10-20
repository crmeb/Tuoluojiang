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

/**
 * 知识库 OCR 入向量策略.
 *
 * 将演示截图 / 假数据表与扫描正文区分：前者 OCR 只进 metadata.ocr_text，
 * content 保留 caption，避免假数据污染向量检索；后者可按模式合并进 content.
 */
final class KnowledgeOcrEmbedPolicy
{
    public const KIND_DOCUMENT = 'document';

    public const KIND_SCREENSHOT = 'screenshot';

    public const KIND_DEMO_TABLE = 'demo_table';

    /** 自动：截图/演示表 caption_only，正文型 OCR 合并. */
    public const MODE_AUTO = 'auto';

    /** 永不合并 OCR 进 content. */
    public const MODE_CAPTION_ONLY = 'caption_only';

    /** 始终合并 OCR 进 content（旧行为）. */
    public const MODE_FULL = 'full';

    /** @var list<string> */
    private const UI_KEYWORDS = [
        '确定', '取消', '保存', '提交', '删除', '编辑', '搜索', '重置', '返回', '关闭',
        '登录', '注册', '下一步', '上一步', '查询', '导出', '导入', '新增', '详情',
        '新建', '刷新', '筛选', '批量', '更多', '操作', '列表', '首页', '退出',
        '新建对话', '最近对话', '清空对话', '小助手', '智能客服', '请问有什么可以帮您',
        '欢迎使用', '写留言', '点点赞', '点分享', '阅读',
    ];

    private KnowledgeImageOcrService $ocrService;

    public function __construct(?KnowledgeImageOcrService $ocrService = null)
    {
        $this->ocrService = $ocrService ?? new KnowledgeImageOcrService();
    }
    /**
     * 根据 OCR 文本形态判定图片类型.
     */
    public function classify(string $ocrText): string
    {
        $ocrText = trim($ocrText);
        if ($ocrText === '') {
            return self::KIND_DOCUMENT;
        }

        $lines = $this->nonEmptyLines($ocrText);
        $lineCount = count($lines);
        if ($lineCount === 0) {
            return self::KIND_DOCUMENT;
        }

        $shortLines = 0;
        $totalLen   = 0;
        foreach ($lines as $line) {
            $len = mb_strlen($line);
            $totalLen += $len;
            if ($len <= 12) {
                ++$shortLines;
            }
        }
        $avgLen     = $totalLen / $lineCount;
        $shortRatio = $shortLines / $lineCount;
        $uiHits     = $this->countUiKeywords($ocrText);
        $phoneHits  = preg_match_all('/1[3-9]\d{9}/u', $ocrText) ?: 0;
        $emailHits  = preg_match_all('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', $ocrText) ?: 0;
        $idHits     = preg_match_all('/\b\d{17}[\dXx]\b/u', $ocrText) ?: 0;

        $piiHits = $phoneHits + $emailHits + $idHits;
        if ($piiHits >= 3 && $lineCount >= 3) {
            return self::KIND_DEMO_TABLE;
        }
        if ($phoneHits >= 2 && $emailHits >= 2) {
            return self::KIND_DEMO_TABLE;
        }

        if ($uiHits >= 4 && $avgLen < 18) {
            return self::KIND_SCREENSHOT;
        }
        if ($shortRatio >= 0.55 && ($uiHits >= 2 || $lineCount >= 8) && $avgLen < 20) {
            return self::KIND_SCREENSHOT;
        }
        if ($shortRatio >= 0.7 && $lineCount >= 6 && $avgLen < 14) {
            return self::KIND_SCREENSHOT;
        }
        // 对话产品截图：OCR 常被识别成较长垃圾行，但仍带 UI 锚点词.
        if ($uiHits >= 2 && (
            str_contains($ocrText, '最近对话')
            || str_contains($ocrText, '新建对话')
            || str_contains($ocrText, '小助手')
            || str_contains($ocrText, '请问有什么可以帮您')
        )) {
            return self::KIND_SCREENSHOT;
        }

        return self::KIND_DOCUMENT;
    }

    /**
     * 决定写入分片的 content 与 metadata 补丁.
     *
     * @param array<string, mixed> $meta 现有 metadata
     *
     * @return array{
     *     content: string,
     *     merged: bool,
     *     metadata: array<string, mixed>
     * }
     */
    public function resolve(string $baseContent, string $ocrText, array $meta = [], ?string $mode = null): array
    {
        $ocrText = trim($ocrText);
        $mode    = $this->normalizeMode($mode ?? $this->configuredMode());
        $kind    = $this->classify($ocrText);

        $shouldMerge = match ($mode) {
            self::MODE_FULL => $ocrText !== '',
            self::MODE_CAPTION_ONLY => false,
            default => $ocrText !== '' && $kind === self::KIND_DOCUMENT,
        };

        $effectiveMode = $shouldMerge ? self::MODE_FULL : self::MODE_CAPTION_ONLY;
        $content = $baseContent;
        if ($shouldMerge) {
            $content = $this->ocrService->mergeTexts($baseContent, $ocrText);
        }

        $existingOcr = trim((string) ($meta['ocr_text'] ?? ''));
        $mergedOcr   = $existingOcr === '' || $existingOcr === $ocrText
            ? $ocrText
            : $this->ocrService->mergeTexts($existingOcr, $ocrText);
        return [
            'content'  => $content,
            'merged'   => $shouldMerge,
            'metadata' => [
                'ocr_text'       => $mergedOcr,
                'image_kind'     => $kind,
                'ocr_embed_mode' => $effectiveMode,
                'ocr_merged'     => $shouldMerge,
            ],
        ];
    }

    /**
     * 从 image_only 已污染 content 中拆回 caption 与 OCR 尾部.
     *
     * @return array{caption: string, ocr_text: string}|null
     */
    public function stripMergedOcrFromImageOnly(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }

        if (preg_match('/^(文档《.+?》图片\d+|文档图片\d+)(?:\R([\s\S]*))?$/u', $content, $matches) !== 1) {
            return null;
        }

        $caption = trim((string) ($matches[1] ?? ''));
        $tail    = trim((string) ($matches[2] ?? ''));
        if ($caption === '' || $tail === '') {
            return null;
        }

        return [
            'caption'  => $caption,
            'ocr_text' => $tail,
        ];
    }

    /**
     * 组装注入模型的资料正文；截图/演示表不回填 OCR 假数据.
     *
     * @param array<string, mixed> $meta
     */
    public function promptBody(string $content, array $meta, int $maxChars = 800): string
    {
        $maxChars = max(1, $maxChars);
        $kind     = (string) ($meta['image_kind'] ?? '');
        $body     = trim($content);

        if ($kind === self::KIND_SCREENSHOT || $kind === self::KIND_DEMO_TABLE) {
            $body .= "\n（本段为界面/演示截图说明，图片中的姓名、手机号、金额等演示数据不可当作真实业务事实。）";
        }

        return mb_substr($body, 0, $maxChars);
    }

    /**
     * 截图/演示表命中的分数折减系数；其它为 1.
     *
     * @param array<string, mixed> $meta
     */
    public function scoreFactor(array $meta): float
    {
        $kind = (string) ($meta['image_kind'] ?? '');
        if ($kind !== self::KIND_SCREENSHOT && $kind !== self::KIND_DEMO_TABLE) {
            return 1.0;
        }

        $factor = 0.85;
        try {
            if (app()->bound('config')) {
                $factor = (float) config('knowledge.chunk_ocr.screenshot_score_factor', 0.85);
            }
        } catch (\Throwable) {
        }

        return max(0.1, min(1.0, $factor));
    }

    private function configuredMode(): string
    {
        try {
            if (app()->bound('config')) {
                return (string) config('knowledge.chunk_ocr.embed_mode', self::MODE_AUTO);
            }
        } catch (\Throwable) {
        }

        return self::MODE_AUTO;
    }

    private function normalizeMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return match ($mode) {
            self::MODE_FULL, self::MODE_CAPTION_ONLY, self::MODE_AUTO => $mode,
            default => self::MODE_AUTO,
        };
    }

    /**
     * @return list<string>
     */
    private function nonEmptyLines(string $text): array
    {
        $lines = [];
        foreach (preg_split("/\r\n|\r|\n/u", $text) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function countUiKeywords(string $text): int
    {
        $hits = 0;
        foreach (self::UI_KEYWORDS as $keyword) {
            if (mb_strpos($text, $keyword) !== false) {
                ++$hits;
            }
        }

        return $hits;
    }
}
