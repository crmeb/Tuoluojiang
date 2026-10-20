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

use Tuoluojiang\Open\socket\Ping;
use Swoole\Timer;

/**
 * WebSocket停止事件监听
 * Class WebSocketStop.
 */
class WebSocketStop
{
    /**
     * @var Ping
     */
    protected $ping;

    /**
     * Create the event listener.
     */
    public function __construct(Ping $ping)
    {
        $this->ping = $ping;
    }

    /**
     * Handle the event.
     *
     * @param object $event
     */
    public function handle($event)
    {
        if ($event->worker_id === 0) {
            $this->ping->destroy();
            Timer::clearAll();
        }
    }
}
