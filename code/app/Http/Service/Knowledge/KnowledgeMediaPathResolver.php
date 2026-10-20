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
 * 解析 Markdown/HTML 中的图片 URL 为本地可读路径.
 */
final class KnowledgeMediaPathResolver
{
    /**
     * 仅在本地文件系统解析图片路径，不触发远程下载与外链改写.
     *
     * 供多模态注入等需要直接读取本地文件字节流的场景；远程地址与缺失文件返回 null.
     */
    public function resolveLocal(string $url): ?string
    {
        $url = trim(str_replace('\\', '/', $url));
        if ($url === '' || str_starts_with($url, 'data:')) {
            return null;
        }

        if ($this->isRemotePath($url)) {
            // 站内绝对地址剥掉 site_url 前缀后仍可按本地路径解析.
            $siteUrl = rtrim((string) sys_config('site_url', config('app.url')), '/');
            if ($siteUrl === '' || ! str_starts_with($url, $siteUrl)) {
                return null;
            }
            $url = substr($url, strlen($siteUrl));
        }

        foreach ($this->buildLocalCandidates($url) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * 将图片 URL 或站点相对路径解析为本地文件路径.
     */
    public function resolve(string $url): ?string
    {
        $url = trim(str_replace('\\', '/', $url));
        if ($url === '' || str_starts_with($url, 'data:')) {
            return null;
        }

        if ($this->isRemotePath($url)) {
            return $this->resolveRemote($url);
        }

        $path = $url;
        $siteUrl = rtrim((string) sys_config('site_url', config('app.url')), '/');
        if ($siteUrl !== '' && str_starts_with($path, $siteUrl)) {
            $path = substr($path, strlen($siteUrl));
        }

        foreach ($this->buildLocalCandidates($path) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        if (function_exists('link_file')) {
            $linked = link_file(ltrim($path, '/'));
            if ($this->isRemotePath($linked)) {
                return $this->resolveRemote($linked);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function buildLocalCandidates(string $path): array
    {
        $normalized = ltrim($path, '/');
        $candidates = [
            public_path($normalized),
            base_path('public/' . $normalized),
            base_path('public' . ($path[0] === '/' ? $path : '/' . $path)),
            storage_path('app/public/' . $normalized),
        ];

        if (str_starts_with($normalized, 'uploads/')) {
            $candidates[] = public_path(substr($normalized, strlen('uploads/')));
        }

        return array_values(array_unique($candidates));
    }

    private function isRemotePath(string $path): bool
    {
        return str_contains($path, '://') || str_starts_with($path, '//');
    }

    private function resolveRemote(string $url): ?string
    {
        $tempDir = storage_path('app/temp/knowledge/images');
        if (! is_dir($tempDir) && ! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            return null;
        }

        $ext      = strtolower((string) pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        $cacheKey = md5($url);
        $tempPath = $tempDir . '/img_' . $cacheKey . ($ext !== '' ? '.' . $ext : '.png');
        if (is_file($tempPath) && filesize($tempPath) > 0) {
            return $tempPath;
        }

        $context = stream_context_create([
            'http' => ['timeout' => 15, 'follow_location' => 1],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $binary = @file_get_contents($url, false, $context);
        if ($binary === false || $binary === '') {
            return null;
        }
        if (@file_put_contents($tempPath, $binary) === false) {
            return null;
        }

        return $tempPath;
    }
}
