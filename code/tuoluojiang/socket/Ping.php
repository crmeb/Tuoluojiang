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

namespace Tuoluojiang\Open\socket;

use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class Ping
{
    public const CACHE_PINK_KEY = 'ws.p.';

    public const CACHE_SET_KEY = 'ws.s';

    /**
     * @var RedisStore
     */
    protected $cache;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string client
     */
    protected string $client;

    /**
     * Ping constructor.
     */
    public function __construct()
    {
        $this->cache  = Cache::store('redis')->getStore();
        $this->redis  = $this->cache->getRedis();
        $this->client = strtolower((string)Config::get('database.redis.client', ''));
    }

    /**
     * 创建Ping.
     */
    public function createPing(int $fd, int $time, int $timeout = 0)
    {
        $this->updateTime($fd, $time, $timeout);
        $this->redis->sAdd(self::CACHE_SET_KEY, $fd);
    }

    /**
     * 更新fd.
     */
    public function updateTime(int $fd, int $time, int $timeout = 0)
    {
        if ($this->client == 'predis') {
            $this->redis->set(self::CACHE_PINK_KEY . $fd, $time, 'EX', $timeout);
        } else {
            $this->redis->set(self::CACHE_PINK_KEY . $fd, $time, $timeout);
        }
    }

    /**
     * 删除fd.
     */
    public function removePing(int $fd)
    {
        $this->redis->del(self::CACHE_PINK_KEY . $fd);
        $this->redis->sRem(self::CACHE_SET_KEY, self::CACHE_PINK_KEY . $fd);
    }

    /**
     * 获取当前fd时间.
     * @return bool|string
     */
    public function getLastTime(int $fd)
    {
        try {
            return $this->redis->get(self::CACHE_PINK_KEY . $fd);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 清除fd.
     */
    public function destroy()
    {
        $members = $this->redis->sMembers(self::CACHE_SET_KEY) ?: [];
        foreach ($members as $k => $member) {
            $members[$k] = self::CACHE_PINK_KEY . $member;
        }
        if (count($members)) {
            $this->redis->sRem(self::CACHE_SET_KEY, ...$members);
        }
    }
}
