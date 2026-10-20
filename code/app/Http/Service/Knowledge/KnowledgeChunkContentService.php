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
 * 知识库 Markdown 分块内容构建.
 *
 * 从 Markdown/HTML 提取正文与图片 URL，对图片 OCR 后生成用于向量化的 content.
 */
final class KnowledgeChunkContentService
{
    /** @var list<string> */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff'];

    /**
     * 文件名中若在最终图片扩展名之前仍带有其它扩展段，视为伪装，一律拒绝.
     * 覆盖脚本/文档/压缩包/可执行文件及 svg（可含脚本）等.
     */
    private const DISGUISED_EXTENSIONS = 'php\d*|phtml|phar|asp|aspx|jsp|cgi|exe|dll|bat|cmd|com|scr|ps1|sh|bash|zsh|js|mjs|cjs|ts|html?|htm|shtml|xhtml|svg|xml|json|pdf|zip|rar|7z|tar|gz|tgz|bz2|xz|docm?|dotm?|xlsm?|xltm?|pptm?|ppsm?|apk|dmg|iso|img|bin|so|dylib|wasm|jar|war|class|py|rb|pl|lua';

    private KnowledgeImageOcrService $imageOcr;

    private KnowledgeMediaPathResolver $pathResolver;

    public function __construct(
        KnowledgeImageOcrService $imageOcr,
        KnowledgeMediaPathResolver $pathResolver
    ) {
        $this->imageOcr     = $imageOcr;
        $this->pathResolver = $pathResolver;
    }

    /**
     * 构建分块入库内容：content_html 存 Markdown，content 为纯文本（可选同步 OCR）.
     *
     * 默认不做 OCR：由分片级队列任务异步识别，避免阻塞入库与向量化.
     *
     * @return array{content:string,content_html:string,metadata:array<string,mixed>}
     */
    public function prepare(string $contentHtml, bool $withOcr = false): array
    {
        $markdown = trim($contentHtml);
        $plain    = KnowledgeTextNormalizer::normalize($this->markdownToPlainText($markdown));
        $urls     = $this->extractImageUrls($markdown);
        $paths    = $this->resolveImagePaths($urls);

        $ocrApplied = false;
        $ocrCount   = 0;
        if ($withOcr && $paths !== []) {
            $before = $plain;
            try {
                $plain      = $this->imageOcr->enrichWithImages($plain, $paths);
                $ocrApplied = $plain !== $before;
            } catch (\Throwable $e) {
                // OCR 超时/失败不影响正文与 image_urls 落库.
                $plain = $before;
            }
            $ocrCount = count($paths);
        } elseif ($paths !== []) {
            $ocrCount = count($paths);
        }

        $metadata = [
            'content_format' => 'markdown',
        ];
        if ($ocrCount > 0) {
            $metadata['ocr_image_count'] = $ocrCount;
            $metadata['ocr_applied']     = $ocrApplied;
            $metadata['ocr_pending']     = ! $withOcr;
        }

        return [
            'content'      => $plain,
            'content_html' => $markdown,
            'metadata'     => $metadata,
        ];
    }

    /**
     * 从 Markdown / HTML 中提取图片 URL（仅保留严格校验通过的图片地址）.
     *
     * @return list<string>
     */
    public function extractImageUrls(string $markdownOrHtml): array
    {
        $urls = [];
        if (preg_match_all('/!\[[^\]]*\]\(([^)]+)\)/u', $markdownOrHtml, $matches) > 0) {
            foreach ($matches[1] as $url) {
                $urls[] = trim((string) $url, " \t\n\r\0\x0B\"'");
            }
        }
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/iu', $markdownOrHtml, $htmlMatches) > 0) {
            foreach ($htmlMatches[1] as $url) {
                $urls[] = trim((string) $url);
            }
        }

        $unique = [];
        foreach ($urls as $url) {
            if (! $this->isValidImageUrl($url)) {
                continue;
            }
            $unique[$url] = true;
        }

        return array_keys($unique);
    }

    /**
     * 严格校验是否为可入库的图片地址（拒绝非图片扩展名与伪装双扩展名等）.
     */
    public function isValidImageUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2000) {
            return false;
        }

        // 控制字符 / 空字节（含编码前探测；路径解码后再检一次）.
        if (preg_match('/[\x00-\x1f\x7f]/', $url) === 1) {
            return false;
        }

        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: ''));
        if ($scheme !== '' && ! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        // 无 scheme 时仍拦截伪协议前缀（javascript:alert(1)、data:image/...）.
        $lower = strtolower($url);
        foreach (['javascript:', 'data:', 'vbscript:', 'file:', 'blob:'] as $blocked) {
            if (str_starts_with($lower, $blocked)) {
                return false;
            }
        }

        $path = $this->extractUrlPath($url);
        if ($path === null) {
            return false;
        }

        // 多次解码，防止 %252ephp 一类二次编码伪装.
        for ($i = 0; $i < 3; ++$i) {
            $decoded = rawurldecode($path);
            if ($decoded === $path) {
                break;
            }
            $path = $decoded;
        }

        if ($path === '' || preg_match('/[\x00-\x1f\x7f]/', $path) === 1 || str_contains($path, '..')) {
            return false;
        }

        $basename = strtolower(basename(str_replace('\\', '/', $path)));
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return false;
        }

        $extPattern = implode('|', self::IMAGE_EXTENSIONS);
        if (preg_match('/\.(' . $extPattern . ')$/i', $basename, $match) !== 1) {
            return false;
        }

        // 伪装双扩展：photo.php.jpg / report.pdf.png / page.html.webp 等一律拒绝.
        $stem = substr($basename, 0, -(strlen($match[1]) + 1));
        if ($stem === '' || preg_match('/\.(?:' . self::DISGUISED_EXTENSIONS . ')(?:\.|$)/i', $stem) === 1) {
            return false;
        }

        return true;
    }

    /**
     * Markdown 转纯文本（保留可见文字，去掉图片语法等）.
     */
    public function markdownToPlainText(string $markdown): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $markdown);
        // 图片：保留 alt 文本.
        $text = (string) preg_replace('/!\[([^\]]*)\]\([^\)]+\)/u', '$1', $text);
        // 链接：保留锚文本.
        $text = (string) preg_replace('/\[([^\]]+)\]\([^\)]+\)/u', '$1', $text);
        // 标题前缀.
        $text = (string) preg_replace('/^#{1,6}\s+/m', '', $text);
        // 行内代码与代码块（保留内容）.
        $text = (string) preg_replace('/```[^\n]*\n([\s\S]*?)```/u', '$1', $text);
        $text = (string) preg_replace('/`([^`]+)`/u', '$1', $text);
        // 粗体/斜体标记.
        $text = (string) preg_replace('/(\*\*|__|\*|_)(.*?)\1/u', '$2', $text);
        // HTML 标签.
        if (preg_match('/<[^>]+>/', $text) === 1) {
            $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return trim($text);
    }

    /**
     * @param list<string> $urls
     *
     * @return list<string>
     */
    public function resolveImagePaths(array $urls): array
    {
        $maxImages = 10;
        try {
            if (app()->bound('config')) {
                $maxImages = max(1, (int) config('knowledge.chunk_ocr.max_images', 10));
            }
        } catch (\Throwable) {
        }
        $paths = [];
        foreach (array_slice($urls, 0, $maxImages) as $url) {
            $path = $this->pathResolver->resolve($url);
            if ($path !== null && is_readable($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * 取出 URL 路径部分（忽略 query/fragment）；相对路径按整段处理.
     */
    private function extractUrlPath(string $url): ?string
    {
        $withoutFragment = explode('#', $url, 2)[0];
        $withoutQuery    = explode('?', $withoutFragment, 2)[0];

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $withoutQuery) === 1
            || str_starts_with($withoutQuery, '//')) {
            $path = parse_url($withoutQuery, PHP_URL_PATH);
            if (! is_string($path) || $path === '') {
                return null;
            }

            return $path;
        }

        return $withoutQuery;
    }
}
