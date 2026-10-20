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

namespace Tuoluojiang\Open\utils;

use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;

/**
 * Class Timer.
 */
class Timer
{
    /**
     * @var Server
     */
    protected $server;

    /**
     * @var int
     */
    protected $workerId = 0;

    /**
     * @var array
     */
    protected $timer = [];

    protected bool $debug = false;

    /**
     * Timer constructor.
     */
    public function __construct()
    {
        $this->server = app('swoole.server');
        $this->debug  = env('APP_DEBUG', false);
    }

    /**
     * @return $this
     */
    public function setWorkerId(int $workerId)
    {
        $workerNum = (int) config('swoole_http.server.options.worker_num');
        if ($workerId > $workerNum) {
            $workerId = $workerNum;
        }
        $this->workerId = $workerId;
        return $this;
    }

    /**
     * 执行.
     */
    public function runInSandbox(callable $callable, array $params = [])
    {
        try {
            $callable(...$params);
        } catch (\Throwable $e) {
            $this->debug && Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    /**
     * 添加定时器.
     * @return int
     */
    public function tick(int $ms, callable $callable)
    {
        if ($this->workerId === $this->server->getWorkerId()) {
            $objectId               = spl_object_id($callable);
            $this->timer[$objectId] = \Swoole\Timer::tick($ms, function () use ($callable) {
                $this->runInSandbox($callable);
            });
        }
    }

    /**
     * 执行一次的定时器.
     */
    public function after(int $ms, callable $callable, array $params = [])
    {
        if ($this->workerId === $this->server->getWorkerId()) {
            \Swoole\Timer::after($ms, function ($callable, $params) {
                $this->runInSandbox($callable, $params);
            }, $callable, $params);
        }
    }
}
