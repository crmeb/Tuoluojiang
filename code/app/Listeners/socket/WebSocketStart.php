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

namespace App\Listeners\socket;

use Tuoluojiang\Open\interfaces\ListenerInterface;
use Tuoluojiang\Open\socket\Ping;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Swoole\Timer;

/**
 * socket启动事件
 * Class WebSocketStart.
 */
class WebSocketStart implements ListenerInterface
{
    /**
     * @var Config
     */
    protected $config;

    protected $server;

    /**
     * 定时器执行间隔(毫秒).
     * @var int
     */
    protected $interval = 1000;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        $this->config = app()->config;
        $this->server = app('swoole.server');
    }

    /**
     * Handle the event.
     *
     * @param object $event
     */
    public function handle($event): void
    {
        if ($event->worker_id == 0 && $this->config->get('swoole_http.websocket.enabled', false)) {
            $this->ping();
            $this->timer();
        }

        // 执行计划任务
        event('crontab');
    }

    /**
     * 心跳.
     * @throws BindingResolutionException
     */
    protected function ping()
    {
        /** @var Ping $ping */
        $ping    = app()->get(Ping::class);
        $timeout = intval($this->config->get('swoole_websocket.ping_timeout', 60000) / 1000);
        Timer::tick(1500, function () use ($ping, $timeout) {
            $nowTime = time();
            foreach ($this->server->connections as $fd) {
                if ($this->server->isEstablished((int) $fd)) {
                    $last = $ping->getLastTime((int) $fd);
                    if ($last && ($nowTime - $last) > $timeout) {
                        $this->server->push($fd, 'timeout');
                        $this->server->close($fd);
                    }
                }
            }
        });
    }

    /**
     * 开启定时器.
     */
    protected function timer()
    {
        Timer::tick($this->interval, function () {
            Event::dispatch('task2');
        });
        Timer::after(1000, function () {
            Event::dispatch('task.initialize');
        });
    }
}
