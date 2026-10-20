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
 * AI 模型供应商 Logo 静态资源.
 *
 * 优先匹配本地 svg / png；方形容器展示时由前端 object-fit:contain 保证长图不裁切.
 */
final class AiModelLogoEnum
{
    /**
     * Logo 静态资源目录(相对站点根路径).
     */
    public const LOGO_DIR = '/static/ai/providers/';

    /**
     * 支持的本地 Logo 扩展名(按优先级).
     */
    private const EXTENSIONS = ['svg', 'png'];

    /**
     * 默认 Logo 驱动(自定义模型).
     */
    public const DEFAULT_DRIVER = AiModelEnum::DRIVER_OPENAI_COMPATIBLE;

    /**
     * 获取供应商 Logo 相对路径.
     */
    public static function path(string $driver): string
    {
        $driver = trim($driver);
        if ($driver !== '') {
            $relative = self::relativePath($driver);
            if ($relative !== null) {
                return $relative;
            }
        }

        return self::LOGO_DIR . self::DEFAULT_DRIVER . '.svg';
    }

    /**
     * 获取供应商 Logo 完整 URL(供前端渲染).
     */
    public static function url(string $driver): string
    {
        return link_file(self::path($driver));
    }

    /**
     * 判断供应商 Logo 是否已落地到本地静态目录.
     */
    public static function exists(string $driver): bool
    {
        return self::relativePath(trim($driver)) !== null;
    }

    /**
     * 按扩展名优先级解析本地相对路径.
     */
    private static function relativePath(string $driver): ?string
    {
        if ($driver === '') {
            return null;
        }

        foreach (self::EXTENSIONS as $ext) {
            if (is_file(self::logoFile($driver, $ext))) {
                return self::LOGO_DIR . $driver . '.' . $ext;
            }
        }

        return null;
    }

    /**
     * 获取 Logo 本地绝对路径.
     */
    private static function logoFile(string $driver, string $ext = 'svg'): string
    {
        return dirname(__DIR__, 3) . '/public/static/ai/providers/' . $driver . '.' . $ext;
    }
}
