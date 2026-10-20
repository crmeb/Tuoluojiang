<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2023 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 *  | @author: lunatic<453850531@qq.com>
 *  +----------------------------------------------------------------------
 */
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

if (! class_exists(MonthlyDailyRotatingFileHandler::class, false)) {
    class MonthlyDailyRotatingFileHandler extends RotatingFileHandler
    {
        protected function getTimedFilename(): string
        {
            $fileInfo = pathinfo($this->filename);
            $filename = $fileInfo['dirname'] . '/' . date('Y/m') . '/' . $fileInfo['filename'] . '-' . date('d');

            return isset($fileInfo['extension']) ? $filename . '.' . $fileInfo['extension'] : $filename;
        }

        protected function getGlobPattern(): string
        {
            $fileInfo = pathinfo($this->filename);
            $glob     = $fileInfo['dirname'] . '/[0-9][0-9][0-9][0-9]/[0-9][0-9]/' . $fileInfo['filename'] . '-[0-9][0-9]';

            return isset($fileInfo['extension']) ? $glob . '.' . $fileInfo['extension'] : $glob;
        }
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | 默认Log通道
    |--------------------------------------------------------------------------
    |
    | 此选项定义将消息写入日志时使用的默认日志通道。
    | 此选项中指定的名称应与“通道”配置数组中定义的通道之一匹配。
    |
    */

    'default' => env('LOG_CHANNEL', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver'            => 'stack',
            'channels'          => ['daily'],
            'ignore_exceptions' => false,
        ],
        'single' => [
            'driver'     => 'single',
            'path'       => storage_path('logs/service.log'),
            'level'      => env('LOG_LEVEL', 'debug'),
            'permission' => 0755,
        ],

        'daily' => [
            'driver'  => 'monolog',
            'handler' => MonthlyDailyRotatingFileHandler::class,
            'level'   => env('LOG_LEVEL', 'debug'),
            'with'    => [
                'filename'       => storage_path('logs/service.log'),
                'maxFiles'       => 14,
                'filePermission' => 0755,
            ],
        ],

        'mcp' => [
            'driver'  => 'monolog',
            'handler' => MonthlyDailyRotatingFileHandler::class,
            'level'   => env('LOG_LEVEL', 'debug'),
            'with'    => [
                'filename'       => storage_path('logs/mcp.log'),
                'maxFiles'       => 14,
                'filePermission' => 0755,
            ],
        ],

        'ai' => [
            'driver'  => 'monolog',
            'handler' => MonthlyDailyRotatingFileHandler::class,
            'level'   => env('LOG_LEVEL', 'debug'),
            'with'    => [
                'filename'       => storage_path('logs/ai.log'),
                'maxFiles'       => 14,
                'filePermission' => 0755,
            ],
        ],

        'slack' => [
            'driver'   => 'slack',
            'url'      => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji'    => ':boom:',
            'level'    => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver'       => 'monolog',
            'level'        => env('LOG_LEVEL', 'debug'),
            'handler'      => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver'    => 'monolog',
            'handler'   => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level'  => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level'  => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],
];
