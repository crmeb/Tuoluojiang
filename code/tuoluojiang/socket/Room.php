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

use Illuminate\Support\Facades\Cache;
use Swoole\Table;

/**
 * Class Room.
 */
class Room
{
    /**
     * 房间名称.
     * @var string[]
     */
    protected $roomName = ['user' => 'user', 'admin' => 'admin', 'ent' => 'ent'];

    /**
     * @var \Redis
     */
    protected $cache;

    /**
     * @var \Redis
     */
    protected static $cacheStatic;

    /**
     * Room constructor.
     */
    public function __construct()
    {
        $this->cache = Cache::store('redis')->getRedis();
    }

    /**
     * @return \Redis
     */
    public static function redisHandler()
    {
        if (! self::$cacheStatic) {
            $this_             = new self();
            self::$cacheStatic = $this_->cache;
        }
        return self::$cacheStatic;
    }

    /**
     * 清空静态 Redis 句柄，避免 Swoole 下跨请求持有失效连接引用.
     */
    public static function resetRedisHandler(): void
    {
        self::$cacheStatic = null;
    }

    /**
     * 获取表实例.
     * @return Table
     */
    public function getTable()
    {
        return app('swoole')->userTable;
    }

    /**
     * @return string[]
     */
    public function getRoomName()
    {
        return $this->roomName;
    }

    /**
     * 添加用户.
     * @return bool
     */
    public function addUser(string $type, string $uid, string $fd, int $ent = 0, int $tourist = 0)
    {
        $type = $this->roomName[$type] ?: 'user';
        $data = ['fd' => $fd, 'type' => $type, 'uid' => $uid, 'ent_id' => $ent, 'to_uid' => 0, 'tourist' => $tourist];
        $this->getTable()->set($fd, $data);
        // 用户信息
        $this->login($type, $uid, $fd);
        // 登录企业
        $this->login($type . ':' . $ent, $uid, $fd);
        return true;
    }

    /**
     * 退出.
     * @return mixed
     */
    public function logout(string $type, string $uid, string $fd, int $entid = 1)
    {
        $type = $this->roomName[$type] ?: 'user';
        if ($fd) {
            // 删除普通的
            $this->srem($type, $fd, $uid);
            // 删除企业的
            if ($entid) {
                $this->srem($type . ':' . $entid, $fd, $uid);
            }
        }
        return $this->getTable()->del($fd);
    }

    /**
     * 查找fd下的信息.
     * @return mixed
     */
    public function select(string $key)
    {
        return $this->getTable()->get($key);
    }

    /**
     * 修改数据.
     * @param null $field
     * @param null $value
     * @return bool|mixed
     */
    public function update(string $key, $field = null, $value = null)
    {
        $res = true;
        if (is_array($field)) {
            $res = $this->getTable()->set($key, $field);
        } elseif (! is_array($field) && $value) {
            $data = $this->getTable()->get($key);
            if (! $data) {
                return false;
            }
            $data[$field] = $value;
            $res          = $this->getTable()->set($key, $data);
        }
        return $res;
    }

    /**
     * 登录.
     * @param mixed $type
     * @param mixed $uid
     * @param mixed $fd
     */
    public function login($type, $uid, $fd)
    {
        $key = '_ws_' . $type;
        $this->cache->sadd($key, $fd);
        $this->cache->sadd($key . $uid, $fd);
        $this->refresh($type, $uid);
    }

    /**
     * 刷新数据.
     * @param mixed $type
     * @param mixed $uid
     */
    public function refresh($type, $uid)
    {
        $key = '_ws_' . $type;
        $this->cache->expire($key, 1800);
        $this->cache->expire($key . $uid, 1800);
    }

    /**
     * 获取当前用户所有的fd.
     * @param mixed $type
     * @return array
     */
    public static function userFd($type, string $uid = '', int $entid = 1)
    {
        if ($entid) {
            $key = '_ws_' . $type . ':' . $entid . $uid;
        } else {
            $key = '_ws_' . $type . $uid;
        }
        return self::redisHandler()->smembers($key) ?: [];
    }

    /**
     * 删除redis数据.
     */
    protected function srem(string $type, int|string $fd, string $uid)
    {
        $key = '_ws_' . $type;
        $this->cache->srem($key, $fd);
        $this->cache->srem($key . $uid, $fd);
    }
}
