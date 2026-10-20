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

namespace Tuoluojiang\Open\swoole\server;

/**
 * Trait WithContainer.
 */
trait WithContainer
{
    /**
     * 获取配置.
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $name, $default = null)
    {
        return $this->container->make('config')->get("swoole_http.{$name}", $default);
    }

    /**
     * 监听事件.
     * @param mixed $listener
     */
    public function onEvent(string $event, $listener)
    {
        $this->container->make('events')->listen("swoole.{$event}", $listener);
    }

    /**
     * 触发事件.
     * @param null $params
     */
    protected function triggerEvent(string $event, $params = null): void
    {
        $this->container->make('events')->dispatch("swoole.{$event}", $params);
    }
}
