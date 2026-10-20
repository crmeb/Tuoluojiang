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
use Lauthz\Adapters\DatabaseAdapter;

return [
    /*
     *Default Lauthz driver
     */
    'default' => 'basic',

    /*
     * Lauthz Localizer
     */
    'localizer' => [
        // changes whether enforcer will register at gates.
        'enabled_register_at_gates' => true,
    ],

    'basic' => [
        /*
        * Casbin model setting.
        */
        'model' => [
            // Available Settings: "file", "text", "url"
            'config_type' => 'file',

            'config_file_path' => __DIR__ . DIRECTORY_SEPARATOR . 'lauthz-rbac-model.conf',

            'config_text' => '',

            'config_url' => '',
        ],

        /*
        * Casbin adapter .
        */
        'adapter' => DatabaseAdapter::class,

        /*
        * Database setting.
        */
        'database' => [
            // Database connection for following tables.
            'connection' => '',

            // Rule table name.
            'rules_table' => 'rules',
        ],

        'log' => [
            // changes whether Lauthz will log messages to the Logger.
            'enabled' => false,

            // Casbin Logger, Supported: \Psr\Log\LoggerInterface|string
            'logger' => 'log',
        ],

        'cache' => [
            // changes whether Lauthz will cache the rules.
            'enabled' => true,

            // cache store
            'store' => 'default',

            // cache Key
            'key' => 'rules',

            // ttl \DateTimeInterface|\DateInterval|int|null
            'ttl' => 24 * 60,
        ],
    ],
];
