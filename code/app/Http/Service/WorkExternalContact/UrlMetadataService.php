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

namespace App\Http\Service\WorkExternalContact;

use App\Http\Service\CommonService;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use App\Http\Service\BaseService;
use Illuminate\Support\Facades\Cache;

/**
 * URL元数据获取服务
 */
class UrlMetadataService extends BaseService
{
    private const CACHE_PREFIX = 'url_metadata:v3:';
    private const CACHE_TTL = 1800; // 30分钟

    /**
     * 根据URL获取元数据
     * @param string $url 目标URL
     * @return array{title: string, description: string, cover_image: string}
     */
    public function getMetadata(string $url): array
    {
        $url = $this->normalizeUrl($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw $this->exception('无效的URL格式');
        }

        $cacheKey = self::CACHE_PREFIX . md5($url);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $page = $this->fetchUrl($url);
        if ($page['body'] === '') {
            throw $this->exception('无法获取网页内容');
        }

        $metadata = $this->parseMetadata($page['body'], $page['url']);

        Cache::put($cacheKey, $metadata, self::CACHE_TTL);

        return $metadata;
    }

    /**
     * 抓取URL内容
     */
    protected function fetchUrl(string $url): array
    {
        if ($this->isPrivateUrl($url)) {
            return ['body' => '', 'url' => $url];
        }

        $maxRedirects = 5;
        $currentUrl = $url;

        for ($i = 0; $i <= $maxRedirects; $i++) {
            $headers = [];
            $ch = curl_init($currentUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER   => true,
                CURLOPT_FOLLOWLOCATION   => false,
                CURLOPT_MAXREDIRS        => 0,
                CURLOPT_TIMEOUT          => 15,
                CURLOPT_CONNECTTIMEOUT   => 5,
                CURLOPT_SSL_VERIFYPEER   => false,
                CURLOPT_SSL_VERIFYHOST   => false,
                CURLOPT_USERAGENT        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                CURLOPT_ENCODING         => '',
                CURLOPT_HTTPHEADER       => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                ],
                CURLOPT_PROTOCOLS        => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS  => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_HEADERFUNCTION   => static function ($ch, string $header) use (&$headers): int {
                    $length = strlen($header);
                    $parts = explode(':', $header, 2);
                    if (count($parts) === 2) {
                        $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                    }
                    return $length;
                },
            ]);

            $content = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            if ($content === false) {
                curl_close($ch);
                return ['body' => '', 'url' => $currentUrl];
            }
            curl_close($ch);

            if ($httpCode >= 300 && $httpCode < 400 && isset($headers['location'])) {
                $redirectUrl = $this->resolveUrl($headers['location'], $effectiveUrl ?: $currentUrl);
                if ($this->isPrivateUrl($redirectUrl)) {
                    return ['body' => '', 'url' => $currentUrl];
                }
                $currentUrl = $redirectUrl;
                continue;
            }

            return [
                'body'         => (string) $content,
                'content_type' => $headers['content-type'] ?? '',
                'status'       => $httpCode,
                'url'          => $effectiveUrl ?: $currentUrl,
            ];
        }

        return ['body' => '', 'url' => $currentUrl];
    }

    /**
     * 检查URL是否为私有网络地址（SSRF防护）
     */
    private function isPrivateUrl(string $url): bool
    {
        $parsed = parse_url($url);
        $host = strtolower(rtrim($parsed['host'] ?? '', '.'));

        if ($host === '' || $host === 'localhost') {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isPrivateIp($host);
        }

        foreach ([DNS_A, DNS_AAAA] as $type) {
            $records = @dns_get_record($host, $type);
            if (! is_array($records)) {
                continue;
            }

            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? '';
                if ($ip !== '' && $this->isPrivateIp($ip)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 解析HTML中的元数据
     */
    private function parseMetadata(string $html, string $baseUrl): array
    {
        $result = [
            'title' => '',
            'description' => '',
            'cover_image' => '',
        ];

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if ($loaded === false) {
            return $result;
        }

        $xpath = new DOMXPath($dom);
        $result['title'] = $this->firstNonEmpty([
            $this->queryAttribute($xpath, "//meta[translate(@property, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='og:title']/@content"),
            $this->queryAttribute($xpath, "//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='twitter:title']/@content"),
            trim((string) $xpath->evaluate('string(//title[1])')),
            trim((string) $xpath->evaluate('string(//h1[1])')),
            $this->queryAttribute($xpath, "//meta[translate(@property, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='og:site_name']/@content"),
            parse_url($baseUrl, PHP_URL_HOST) ?: '',
        ]);

        $result['description'] = $this->firstNonEmpty([
            $this->queryAttribute($xpath, "//meta[translate(@property, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='og:description']/@content"),
            $this->queryAttribute($xpath, "//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='twitter:description']/@content"),
            $this->queryAttribute($xpath, "//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='description']/@content"),
        ]);

        $result['cover_image'] = $this->firstNonEmpty([
            $this->queryAttribute($xpath, "//meta[translate(@property, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='og:image']/@content"),
            $this->queryAttribute($xpath, "//meta[translate(@property, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='og:image:secure_url']/@content"),
            $this->queryAttribute($xpath, "//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='twitter:image']/@content"),
            $this->queryAttribute($xpath, "//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='twitter:image:src']/@content"),
            $this->queryAttribute($xpath, "//link[contains(concat(' ', translate(normalize-space(@rel), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), ' '), ' apple-touch-icon ')]/@href"),
            $this->queryAttribute($xpath, "//link[contains(concat(' ', translate(normalize-space(@rel), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), ' '), ' icon ')]/@href"),
        ]);

        if ($result['cover_image'] !== '') {
            $result['cover_image'] = $this->resolveUrl($result['cover_image'], $baseUrl);
            $result['cover_image'] = $this->storeIcoCoverImage($result['cover_image']);
        }

        return $result;
    }

    /**
     * 相对路径转绝对路径
     */
    protected function resolveUrl(string $url, string $baseUrl): string
    {
        if ($url === '' || str_starts_with($url, 'data:')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            $parsed = parse_url($baseUrl);
            $scheme = $parsed['scheme'] ?? 'https';
            return $scheme . ':' . $url;
        }

        if (preg_match('#^[a-z][a-z0-9+\-.]*://#i', $url)) {
            return $url;
        }

        try {
            return (string) UriResolver::resolve(new Uri($baseUrl), new Uri($url));
        } catch (\Throwable) {
            return $url;
        }
    }

    private function storeIcoCoverImage(string $url): string
    {
        if (! $this->isIcoUrl($url)) {
            return $url;
        }

        try {
            $image = $this->fetchUrl($url);
            if (($image['status'] ?? 0) < 200 || ($image['status'] ?? 0) >= 300 || $image['body'] === '') {
                return $url;
            }

            $png = $this->icoToPng((string) $image['body']);
            if ($png === '') {
                return $url;
            }

            return $this->uploadPngAttachment($png) ?: $url;
        } catch (\Throwable) {
            return $url;
        }
    }

    protected function uploadPngAttachment(string $pngContent): string
    {
        $result = app()->get(CommonService::class)->uploadFromResource('data:image/png;base64,' . base64_encode($pngContent), [
            'upload_type'    => 1,
            'pid'            => 0,
            'uuid'           => $this->currentAdminId(),
            'way'            => 0,
            'entId'          => $this->currentEntId(),
            'link_type'      => 'url_metadata_cover',
            'link_id'        => 0,
        ]);

        return $result['new_url'] ?? '';
    }

    private function icoToPng(string $content): string
    {
        if (! $this->hasConvertCommand()) {
            return '';
        }

        $process = proc_open('convert ico:- png:-', [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        if (! is_resource($process)) {
            return '';
        }

        fwrite($pipes[0], $content);
        fclose($pipes[0]);
        $png = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return $exitCode === 0 && str_starts_with((string) $png, "\x89PNG\r\n\x1a\n") ? (string) $png : '';
    }

    private function hasConvertCommand(): bool
    {
        return trim((string) shell_exec('command -v convert 2>/dev/null')) !== '';
    }

    private function isIcoUrl(string $url): bool
    {
        return strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION)) === 'ico';
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (! preg_match('#^[a-z][a-z0-9+\-.]*://#i', $url)) {
            return 'https://' . ltrim($url, '/');
        }

        return $url;
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function firstNonEmpty(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function queryAttribute(DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        return trim((string) $nodes->item(0)?->nodeValue);
    }

    private function currentEntId(): int
    {
        try {
            $request = request();
            return $request->hasMacro('entId') ? (int) $request->entId() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function currentAdminId(): string
    {
        try {
            return (string) (auth('admin')->id() ?: '');
        } catch (\Throwable) {
            return '';
        }
    }

}
