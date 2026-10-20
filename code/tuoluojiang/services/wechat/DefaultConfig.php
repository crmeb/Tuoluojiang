<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\services\wechat;

use Illuminate\Support\Facades\Config;

/**
 * 默认配置
 * Class DefaultConfig.
 */
class DefaultConfig
{
    // 小程序appid
    public const MINI_APPID = 'mini.appid';

    // 公众号appid
    public const OFFICIAL_APPID = 'official.appid';

    // 开放平台appid
    public const APP_APPID = 'app.appid';

    // 开放平台网页端appid
    public const WEB_APPID = 'web.appid';

    // 企业微信id
    public const WORK_CORP_ID = 'work.corp_id';

    // 商户id
    public const PAY_MCHID = 'pay.mchid';

    // 系统配置域名地址,携带,格式:http://www.a.com
    public const COMMENT_URL = 'comment.url';

    public const WECHAT_CONFIG = [
        // 请求响应日志
        'logger' => true,
        // 公用
        'comment' => [
            'url' => [
                'key' => 'site_url',
            ],
        ],
        // 企业微信
        'work' => [
            'corp_id' => [
                // 默认使用value值，没有值使用eb_system_config配置中的key的值
                'key' => 'wechat_work_corpid',
                // 配置值
                'value' => '',
            ],
            'token' => [
                // 默认使用value值，没有值使用eb_system_config配置中的key的值
                'key' => 'wechat_work_token',
                // 配置值
                'value' => '',
            ],
            'key' => [
                // 默认使用value值，没有值使用eb_system_config配置中的key的值
                'key' => 'wechat_work_aes_key',
                // 配置值
                'value' => '',
            ],
        ],
    ];

    /**
     * 获取配置,如果配置为数组则使用value的值，如果没有值返回key.
     * @return null|array|bool|mixed|string[]
     */
    public static function value(string $key)
    {
        $config = [];
        if (Config::has('wechat')) {
            $config = Config::get('wechat', []);
        }
        $config = array_merge(self::WECHAT_CONFIG, $config);

        $key   = explode('.', $key);
        $value = null;
        foreach ($key as $k) {
            if ($value) {
                $value = $value[$k] ?? null;
            } else {
                $value = $config[$k] ?? null;
            }
        }

        if (is_array($value)) {
            return $value['value'] ?? null;
        }
        return $value;
    }

    /**
     * @return null|mixed
     * @email 136327134@qq.com
     * @date 2023/9/18
     */
    public static function key(string $key)
    {
        $config = [];
        if (Config::has('wechat')) {
            $config = Config::get('wechat', []);
        }
        $config = array_merge(self::WECHAT_CONFIG, $config);
        $key    = explode('.', $key);
        $value  = null;
        foreach ($key as $k) {
            if ($value) {
                $value = $value[$k] ?? null;
            } else {
                $value = $config[$k] ?? null;
            }
        }

        if (is_array($value)) {
            $value = $value['key'] ?? null;
        }

        return $value;
    }
}
