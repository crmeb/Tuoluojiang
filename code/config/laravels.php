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
use App\Jobs\Assess\AssessAbnormalCronJob;
use App\Jobs\Assess\AssessAutoEndCronJob;
use App\Jobs\Assess\AssessCronJob;
use App\Jobs\Assess\AssessEvaluateCronJob;
use App\Jobs\Attend\AttendCalcApplyRecordCronJob;
use App\Jobs\Attend\AttendDailyTaskCronJob;
use App\Jobs\Attend\AttendPersonalPushCronJob;
use App\Jobs\Attend\AttendShiftRemindCronJob;
use App\Jobs\Attend\AttendStatisticsCronJob;
use App\Jobs\Attend\AttendTeamPushCronJob;
use App\Jobs\Customer\ClueAutoReturnCronJob;
use App\Jobs\Customer\ClueRemindCronJob;
use App\Jobs\Customer\ClueReturnCronJob;
use App\Jobs\Customer\ContractRemindCronJob;
use App\Jobs\Customer\ContractRenewRemindCronJob;
use App\Jobs\Customer\ContractStatusCronJob;
use App\Jobs\Customer\CustomerFollowCronJob;
use App\Jobs\Customer\CustomerReturnCronJob;
use App\Jobs\Customer\CustomerReturnRemindCronJob;
use App\Jobs\Crud\CrudEventCronJob;
use App\Jobs\DailyCronJob;
use App\Jobs\ScheduleCronJob;
use App\Jobs\Work\WorkCheckInCronJob;
use App\Jobs\Work\WorkCronJob;
use App\Jobs\Work\WorkMessageSaveCronJob;
use App\Jobs\WorkExternalContact\WorkMediaDeleteCronJob;
use App\Jobs\WorkExternalContact\WorkMediaUploadCronJob;
use App\Jobs\WorkExternalContact\WorkMessagingAddCronJob;
use App\Jobs\WorkExternalContact\WorkMessagingCensusCronJob;
use App\Jobs\WorkExternalContact\WorkMessagingResultCronJob;
use App\Jobs\WorkExternalContact\WorkMomentResultCronJob;
use App\Listeners\McpCleaner;
use App\Listeners\RequestStateCleaner;
use App\Listeners\RoleCleaner;
use App\Listeners\StaticCleaner;
use App\Listeners\swoole\DatabaseCleaner;
use App\Listeners\swoole\RedisCleaner;
use App\Listeners\swoole\SwooleStart;
use App\Listeners\WorkCleaner;
use Tuoluojiang\Open\socket\WebsocketHandler;
use Hhxsv5\LaravelS\Illuminate\Cleaners\AuthCleaner;
use Hhxsv5\LaravelS\Illuminate\Cleaners\JWTCleaner;
use Hhxsv5\LaravelS\Illuminate\Cleaners\RequestCleaner;
use Hhxsv5\LaravelS\Illuminate\Cleaners\SessionCleaner;
use Swoole\Table;

$swooleLogDir = storage_path(sprintf('logs/%s/%s', date('Y'), date('m')));
if (! is_dir($swooleLogDir) && ! @mkdir($swooleLogDir, 0755, true) && ! is_dir($swooleLogDir)) {
    $swooleLogDir = storage_path('logs');
}

return [
    /*
    |--------------------------------------------------------------------------
    | LaravelS Settings
    |--------------------------------------------------------------------------
    |
    | English https://github.com/hhxsv5/laravel-s/blob/master/Settings.md#laravels-settings
    | Chinese https://github.com/hhxsv5/laravel-s/blob/master/Settings-CN.md#laravels-%E9%85%8D%E7%BD%AE%E9%A1%B9
    |
    */

    /*
    |--------------------------------------------------------------------------
    | The IP address of the server
    |--------------------------------------------------------------------------
    |
    | IPv4: use "127.0.0.1" to listen local address, and "0.0.0.0" to listen all addresses.
    | IPv6: use "::1" to listen local address, and "::"(equivalent to 0:0:0:0:0:0:0:0) to listen all addresses.
    |
    */

    'listen_ip' => env('LARAVELS_LISTEN_IP', '0.0.0.0'),

    /*
    |--------------------------------------------------------------------------
    | The port of the server
    |--------------------------------------------------------------------------
    |
    | Require root privilege if port is less than 1024.
    |
    */

    'listen_port' => env('LARAVELS_LISTEN_PORT', 20200),

    /*
    |--------------------------------------------------------------------------
    | The socket type of the server
    |--------------------------------------------------------------------------
    |
    | Usually, you don’t need to care about it.
    | Unless you want Nginx to proxy to the UnixSocket Stream file, you need
    | to modify it to SWOOLE_SOCK_UNIX_STREAM, and listen_ip is the path of UnixSocket Stream file.
    | List of socket types:
    | SWOOLE_SOCK_TCP: TCP
    | SWOOLE_SOCK_TCP6: TCP IPv6
    | SWOOLE_SOCK_UDP: UDP
    | SWOOLE_SOCK_UDP6: UDP IPv6
    | SWOOLE_UNIX_DGRAM: Unix socket dgram
    | SWOOLE_UNIX_STREAM: Unix socket stream
    | Enable SSL: $sock_type | SWOOLE_SSL. To enable SSL, check the configuration about SSL.
    | https://www.swoole.co.uk/docs/modules/swoole-server-doc
    | https://www.swoole.co.uk/docs/modules/swoole-server/configuration
    |
    */

    'socket_type' => defined('SWOOLE_SOCK_TCP') ? SWOOLE_SOCK_TCP : 1,

    /*
    |--------------------------------------------------------------------------
    | Server Name
    |--------------------------------------------------------------------------
    |
    | This value represents the name of the server that will be
    | displayed in the header of each request.
    |
    */

    'server' => env('LARAVELS_SERVER', 'LaravelS'),

    /*
    |--------------------------------------------------------------------------
    | Handle Static Resource
    |--------------------------------------------------------------------------
    |
    | Whether handle the static resource by LaravelS(Require Swoole >= 1.7.21, Handle by Swoole if Swoole >= 1.9.17).
    | Suggest that Nginx handles the statics and LaravelS handles the dynamics.
    | The default path of static resource is base_path('public'), you can modify swoole.document_root to change it.
    |
    */

    'handle_static' => env('LARAVELS_HANDLE_STATIC', true),

    /*
    |--------------------------------------------------------------------------
    | Laravel Base Path
    |--------------------------------------------------------------------------
    |
    | The basic path of Laravel, default base_path(), be used for symbolic link.
    |
    */

    'laravel_base_path' => env('LARAVEL_BASE_PATH', base_path()),

    /*
    |--------------------------------------------------------------------------
    | Inotify Reload
    |--------------------------------------------------------------------------
    |
    | This feature requires inotify extension.
    | https://github.com/hhxsv5/laravel-s#automatically-reload-after-modifying-code
    |
    */

    'inotify_reload' => [
        // Whether enable the Inotify Reload to reload all worker processes when your code is modified.
        'enable' => env('LARAVELS_INOTIFY_RELOAD', false),

        // The file path that Inotify watches
        'watch_path' => base_path(),

        // The file types that Inotify watches
        'file_types' => ['.php'],

        // The excluded/ignored directories that Inotify watches
        'excluded_dirs' => [],

        // Whether output the reload log
        'log' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Event Handlers
    |--------------------------------------------------------------------------
    |
    | Configure the event callback function of Swoole, key-value format,
    | key is the event name, and value is the class that implements the event
    | processing interface.
    |
    | https://github.com/hhxsv5/laravel-s#configuring-the-event-callback-function-of-swoole
    |
    */

    'event_handlers' => [
        'WorkerStart' => SwooleStart::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSockets
    |--------------------------------------------------------------------------
    |
    | Swoole WebSocket Server settings.
    |
    | https://github.com/hhxsv5/laravel-s#enable-websocket-server
    |
    */

    'websocket' => [
        'enable'  => env('LARAVELS_WEBSOCKET_ENABLE', false),
        'handler' => WebsocketHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sockets - multi-port mixed protocol
    |--------------------------------------------------------------------------
    |
    | The socket(port) list for TCP/UDP.
    |
    | https://github.com/hhxsv5/laravel-s#multi-port-mixed-protocol
    |
    */

    'sockets' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Process
    |--------------------------------------------------------------------------
    |
    | Support developers to create custom processes for monitoring,
    | reporting, or other special tasks.
    |
    | https://github.com/hhxsv5/laravel-s#custom-process
    |
    */

    'processes' => [],
    /*
    |--------------------------------------------------------------------------
    | Timer
    |--------------------------------------------------------------------------
    |
    | Wrapper cron job base on Swoole's Millisecond Timer, replace Linux Crontab.
    |
    | https://github.com/hhxsv5/laravel-s#millisecond-cron-job
    |
    */

    'timer' => [
        'enable' => env('LARAVELS_TIMER', false),

        // The list of cron job
        'jobs' => [
            AssessCronJob::class, // 绩效提醒
            DailyCronJob::class, // 日报提醒
            AssessEvaluateCronJob::class, // 自我评价提醒和开启考核任务提醒
            AssessAutoEndCronJob::class, // 自动结束考核
            AssessAbnormalCronJob::class, // 定时检测考核结束期没有创建绩效的人员提醒
            ContractRenewRemindCronJob::class, // 合同金额续费相关定时任务
            ContractRemindCronJob::class, // 合同定时任务
            ScheduleCronJob::class, // 日程提醒
            AttendStatisticsCronJob::class, // 考勤短提醒（每分钟）
            AttendDailyTaskCronJob::class, // 考勤数据创建（每天00:01）
            AttendShiftRemindCronJob::class, // 班次提醒创建（每天00:30）
            AttendCalcApplyRecordCronJob::class, // 审批考勤计算（每天01:00）
            AttendTeamPushCronJob::class, // 团队考勤推送（日/周/月报）
            AttendPersonalPushCronJob::class, // 个人考勤推送（周/月报）
            CustomerReturnCronJob::class, // 客户自动退回公海
            CustomerReturnRemindCronJob::class, // 客户退回提醒
            CustomerFollowCronJob::class, // 客户跟进提醒
            ContractStatusCronJob::class, // 合同状态变更
            CrudEventCronJob::class, // 实体触发器定时执行
            WorkMessageSaveCronJob::class, // 工作消息保存定时任务
            WorkCronJob::class, // 企业架构信息同步
            WorkCheckInCronJob::class, // 企业架构信息同步
            WorkMediaDeleteCronJob::class, // 删除未关联企微素材定时任务
            WorkMediaUploadCronJob::class, // 上传正常企微素材定时任务
            WorkMessagingAddCronJob::class, // 企微群发定时任务
            WorkMessagingResultCronJob::class, // 定时获取群发任务结果
            WorkMomentResultCronJob::class, // 定时获取朋友圈结果
            WorkMessagingCensusCronJob::class, // 统计群发消息结果
            ClueReturnCronJob::class, // 线索退回提醒
            ClueRemindCronJob::class, // 线索跟进提醒
            ClueAutoReturnCronJob::class, // 线索自动退回
        ],

        // Max waiting time of reloading
        'max_wait_time' => 5,

        // Enable the global lock to ensure that only one instance starts the timer
        // when deploying multiple instances.
        // This feature depends on Redis https://laravel.com/docs/8.x/redis
        'global_lock'     => env('LARAVELS_TIMER_GLOBAL_LOCK', true),
        'global_lock_key' => config('app.name', 'Laravel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Tables
    |--------------------------------------------------------------------------
    |
    | All defined tables will be created before Swoole starting.
    |
    | https://github.com/hhxsv5/laravel-s#use-swooletable
    |
    */

    'swoole_tables' => ! is_win() ? [
        // WebSocket 在线态：仅存 fd 索引热数据，列宽按真实字段收敛
        'user' => [
            'size'   => 4096,
            'column' => [
                ['name' => 'fd', 'type' => Table::TYPE_INT, 'size' => 8],
                ['name' => 'type', 'type' => Table::TYPE_STRING, 'size' => 16],
                ['name' => 'ent_id', 'type' => Table::TYPE_INT, 'size' => 8],
                ['name' => 'uid', 'type' => Table::TYPE_STRING, 'size' => 64],
                ['name' => 'to_uid', 'type' => Table::TYPE_STRING, 'size' => 64],
                ['name' => 'tourist', 'type' => Table::TYPE_INT, 'size' => 1],
            ],
        ],
        // 定时任务调度热数据；JSON 大字段仍写入但列宽按实际上限收缩（不再统一 1024）
        'task' => [
            'size'   => 2048,
            'column' => [
                ['name' => 'id', 'type' => Table::TYPE_INT, 'size' => 8],
                ['name' => 'name', 'type' => Table::TYPE_STRING, 'size' => 128],
                ['name' => 'func', 'type' => Table::TYPE_STRING, 'size' => 512],
                ['name' => 'period', 'type' => Table::TYPE_STRING, 'size' => 16],
                ['name' => 'persist', 'type' => Table::TYPE_INT, 'size' => 1],
                ['name' => 'run_count', 'type' => Table::TYPE_INT, 'size' => 8],
                ['name' => 'exe_count', 'type' => Table::TYPE_INT, 'size' => 8],
                ['name' => 'interval', 'type' => Table::TYPE_STRING, 'size' => 256],
                ['name' => 'parameter', 'type' => Table::TYPE_STRING, 'size' => 1024],
                ['name' => 'uniqued', 'type' => Table::TYPE_STRING, 'size' => 64],
                ['name' => 'rate', 'type' => Table::TYPE_INT, 'size' => 8],
                ['name' => 'end_time', 'type' => Table::TYPE_STRING, 'size' => 32],
                ['name' => 'created_at', 'type' => Table::TYPE_STRING, 'size' => 32],
                ['name' => 'updated_at', 'type' => Table::TYPE_STRING, 'size' => 32],
            ],
        ],
    ] : [],

    /*
    |--------------------------------------------------------------------------
    | Re-register Providers
    |--------------------------------------------------------------------------
    |
    | The Service Provider list, will be re-registered each request, and run method boot()
    | if it exists. Usually, be used to clear the Service Provider
    | which registers Singleton instances.
    |
    | https://github.com/hhxsv5/laravel-s/blob/master/Settings.md#register_providers
    |
    */

    'register_providers' => [],

    /*
    |--------------------------------------------------------------------------
    | Cleaners
    |--------------------------------------------------------------------------
    |
    | The list of cleaners for each request is used to clean up some residual
    | global variables, singleton objects, and static properties to avoid
    | data pollution between requests.
    |
    | https://github.com/hhxsv5/laravel-s/blob/master/Settings.md#cleaners
    |
    */

    'cleaners' => [
        SessionCleaner::class,
        AuthCleaner::class,
        JWTCleaner::class,
        RequestCleaner::class,
        RoleCleaner::class,
        WorkCleaner::class,
        // 请求态统一清理（权限上下文 / AI 流式残留等）
        RequestStateCleaner::class,
        StaticCleaner::class,
        McpCleaner::class,
        // 数据库和 Redis：失败再重连，避免每请求 ping
        DatabaseCleaner::class,
        RedisCleaner::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Destroy Controllers
    |--------------------------------------------------------------------------
    |
    | Automatically destroy the controllers after each request to solve
    | the problem of the singleton controllers.
    |
    | https://github.com/hhxsv5/laravel-s/blob/master/KnownIssues.md#singleton-controller
    |
    */

    'destroy_controllers' => [
        'enable'        => true,
        'excluded_list' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Settings
    |--------------------------------------------------------------------------
    |
    | Swoole's original configuration items.
    |
    | More settings
    | Chinese https://wiki.swoole.com/#/server/setting
    | English https://www.swoole.co.uk/docs/modules/swoole-server/configuration
    |
    */

    'swoole' => [
        'daemonize'        => env('LARAVELS_DAEMONIZE', false),
        'dispatch_mode'    => env('LARAVELS_DISPATCH_MODE', 2),
        'worker_num'       => env('LARAVELS_WORKER_NUM', swoole_cpu_num()),
        'task_worker_num'  => env('LARAVELS_TASK_WORKER_NUM', swoole_cpu_num()),
        'task_ipc_mode'    => 1,
        'task_max_request' => env('LARAVELS_TASK_MAX_REQUEST', 100000),
        'task_tmpdir'      => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
        'max_request'      => env('LARAVELS_MAX_REQUEST', 100000),
        'open_tcp_nodelay' => true,
        'pid_file'         => storage_path('laravels.pid'),
        'log_level'        => env('LARAVELS_LOG_LEVEL', 4),
        'log_file'         => $swooleLogDir . sprintf('/swoole-%s.log', date('d')),
        'log_rotation'     => defined('SWOOLE_LOG_ROTATION_SINGLE') ? SWOOLE_LOG_ROTATION_SINGLE : 0,
        'document_root'    => base_path('public'),
        // 接收数据缓存区大小B.
        'package_max_length' => 60 * 1024 * 1024,
        // 发送数据缓冲区大小B.
        'buffer_output_size' => 10 * 1024 * 1024,
        // socket缓冲区最大连接数
        'socket_buffer_size' => 4 * 1024 * 1024,
        'reload_async'       => true,
        'max_wait_time'      => 60,
        'enable_reuse_port'  => true,
        'enable_coroutine'   => true,
        'upload_tmp_dir'     => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
        'http_compression'   => env('LARAVELS_HTTP_COMPRESSION', false),
    ],

    'hot_reload' => [
        'enable'  => true,
        'name'    => [],
        'include' => [app_path(), base_path('tuoluojiang'), config_path()],
        'exclude' => [],
    ],
    // 队列进程数量
    'queue_worker_num' => env('LARAVELS_QUEUE_WORKER_NUM', swoole_cpu_num()),
    'queue'            => [
        'queue_name' => env('REDIS_QUEUE', 'CRMEB_OA'),
        'enable'     => true,
        'sleep'      => 3,    // 没有任务可用时的睡眠秒数
        'tries'      => 1,    // 任务失败重试次数
        'rest'       => 0,    // 任务之间的休息秒数
        'memory'     => env('LARAVELS_QUEUE_MEMORY', 96),  // 以兆字节为单位的内存限制
        'timeout'    => max(900, (int) env('LARAVELS_QUEUE_TIMEOUT', 900)), // 任务处理时间超时秒数
    ],
];
