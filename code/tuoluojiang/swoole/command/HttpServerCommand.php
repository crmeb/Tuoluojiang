<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\swoole\command;

use Tuoluojiang\Open\swoole\server\FileWatcher;
use Tuoluojiang\Open\swoole\server\InteractsWithQueue;
use Illuminate\Support\Arr;
use Swoole\Process;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Server\Manager;

class HttpServerCommand extends \SwooleTW\Http\Commands\HttpServerCommand
{
    use InteractsWithQueue;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'open:http {action : start|stop|restart|reload|infos}';

    /**
     * Run swoole_http_server.
     */
    protected function start()
    {
        if ($this->isRunning()) {
            $this->error('Failed! Tuoluojiang is already running.');

            return;
        }
        if (extension_loaded('swoole')) {
            do {
                if (! file_exists(base_path('.env'))) {
                    @copy(base_path('.env.example'), base_path('.env'));
                    $this->call('jwt:secret');
                    $this->call('key:generate');
                    $this->call('config:clear');
                    $this->call('cache:clear');
                }
            } while (! file_exists(base_path('.env')));
            $host             = Arr::get($this->config, 'server.host');
            $port             = Arr::get($this->config, 'server.port');
            $hotReloadEnabled = Arr::get($this->config, 'hot_reload.enabled');
            $queueEnabled     = Arr::get($this->config, 'queue.enabled');
            $accessLogEnabled = Arr::get($this->config, 'server.access_log');

            $this->info('Starting swoole http server...');
            $this->info("Tuoluojiang http server started: <http://{$host}:{$port}>");
            if ($this->isDaemon()) {
                $this->info(
                    '> (You can run this command to ensure the ' .
                    'swoole_http_server process is running: ps aux|grep "swoole")'
                );
            }
            /** @var Manager $manager */
            $manager = $this->laravel->make(Manager::class);
            /** @var Server $server */
            $server = $this->laravel->make(Server::class);

            if ($accessLogEnabled) {
                $this->registerAccessLog();
            }

            if ($hotReloadEnabled) {
                $manager->addProcess($this->getHotReloadProcessNow($server));
            }

            if ($queueEnabled) {
                $this->prepareQueue($manager);
            }

            $manager->run();
        } else {
            $this->error('Failed! Tuoluojiang running must rely on the swoole extension.');
        }
    }

    /**
     * @param Server $server
     * @return Process|void
     */
    protected function getHotReloadProcessNow($server)
    {
        return new Process(function () use ($server) {
            $watcher = new FileWatcher(
                Arr::get($this->config, 'hot_reload.include', []),
                Arr::get($this->config, 'hot_reload.exclude', []),
                Arr::get($this->config, 'hot_reload.name', [])
            );

            $watcher->watch(function () use ($server) {
                $server->reload();
            });
        }, false, 0, true);
    }
}
