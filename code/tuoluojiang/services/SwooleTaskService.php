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

namespace Tuoluojiang\Open\services;

use Tuoluojiang\Open\socket\Room;
use Illuminate\Support\Facades\Log;
use Swoole\Server;

/**
 * 异步任务
 * Class SwooleTaskService.
 */
class SwooleTaskService
{
    /**
     * @var Server
     */
    protected $server;

    /**
     * 任务类型.
     * @var string
     */
    protected $taskType = 'message';

    /**
     * 送达人.
     * @var array
     */
    protected $to;

    protected int $entid = 1;

    /**
     * 任务内容.
     * @var array
     */
    protected $data = ['type' => null, 'data' => []];

    /**
     * 排除发送人.
     * @var array
     */
    protected $except;

    /**
     * 任务区分类型.
     * @var string
     */
    protected $type;

    /**
     * 任务区分类型.
     * @var string
     */
    protected $event = [];

    /**
     * @var static
     */
    protected static $instance;

    public function __construct(?string $taskType = null)
    {
        if ($taskType) {
            $this->taskType = $taskType;
        }
        $this->server = app('swoole');
    }

    /**
     * 任务类型.
     * @return $this
     */
    public function taskType(string $taskType)
    {
        $this->taskType = $taskType;
        return $this;
    }

    /**
     * 消息类型.
     * @return $this
     */
    public function type(string $type)
    {
        $this->data['type'] = $type;
        return $this;
    }

    /**
     * 设置送达人.
     * @param mixed $to
     * @return $this
     */
    public function to($to)
    {
        $this->to = is_array($to) ? $to : func_get_args();
        return $this;
    }

    public function entid(int $entid)
    {
        $this->entid = $entid;
        return $this;
    }

    /**
     * 设置除那个用户不发送
     * @param mixed $except
     * @return $this
     */
    public function except($except)
    {
        $this->except = is_array($except) ? $except : [$except];
        return $this;
    }

    /**
     * 设置事件.
     * @return $this
     */
    public function event(string $event, array $param = [])
    {
        $this->event = compact('event', 'param');
        return $this;
    }

    /**
     * 设置参数.
     * @return $this
     */
    public function data(string $event, array $data)
    {
        $this->type         = $event;
        $this->data['data'] = $data;
        return $this;
    }

    /**
     * 执行任务
     */
    public function push()
    {
        try {
            $data = [
                'except' => $this->except,
                'entid'  => $this->entid,
                'data'   => $this->data,
                'uid'    => $this->to,
                'type'   => $this->type,
            ];
            $uid    = is_array($data['uid']) ? $data['uid'] : [$data['uid']];
            $entid  = $data['entid'] ?? 0;
            $except = $data['except'] ?? [];
            /** @var Room $room */
            $room = app()->get(Room::class);
            if (! count($uid) && $data['type'] != 'user') {
                $fds = $room->userFd($data['type'], '', (int) $entid);
                foreach ($fds as $fd) {
                    if (! in_array($fd, $except) && $this->server->isEstablished((int) $fd)) {
                        $this->server->push((int) $fd, json_encode($data['data']));
                    }
                }
            } else {
                $fdsAll = [];
                foreach ($uid as $id) {
                    if (is_array($id)) {
                        $id = $id['to_uid'] ?? 0;
                    }
                    if (! $this->isSocketUid($id)) {
                        $id = uid_to_uuid((int) $id);
                        if (! $id) {
                            continue;
                        }
                    }
                    $fdsAll = array_merge($fdsAll, $room->userFd($data['type'], (string) $id, (int) $entid));
                }
                if (! empty(array_unique($fdsAll))) {
                    foreach (array_unique($fdsAll) as $fd) {
                        if (! in_array($fd, $except) && $this->server->isEstablished((int) $fd)) {
                            $this->server->push((int) $fd, json_encode($data['data']));
                        }
                    }
                }
            }
            $this->reset();
        } catch (\Throwable $e) {
            Log::error('任务执行失败,失败原因:' . json_encode([
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]));
        }
    }

    /**
     * 实例化.
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 后台任务
     * @return SwooleTaskService
     */
    public static function admin()
    {
        self::instance()->type = __FUNCTION__;
        return self::instance();
    }

    /**
     * 用户任务
     * @return SwooleTaskService
     */
    public static function user()
    {
        self::instance()->type = __FUNCTION__;
        return self::instance();
    }

    /**
     * 企业任务
     * @return SwooleTaskService
     */
    public static function ent()
    {
        self::instance()->type = __FUNCTION__;
        return self::instance();
    }

    /**
     * 企业任务
     * @return SwooleTaskService
     */
    public static function notice()
    {
        self::instance()->taskType = __FUNCTION__;
        return self::instance();
    }

    /**
     * 重置数据.
     * @return $this
     */
    protected function reset()
    {
        $this->taskType = 'message';
        $this->except   = null;
        $this->data     = ['type' => null, 'data' => []];
        $this->to       = null;
        $this->entid    = 1;
        $this->type     = null;
        $this->event    = [];
        return $this;
    }

    /**
     * WebSocket 登录房间使用 admin.uid，历史数据存在 32/36 位两种 UUID 形态.
     */
    protected function isSocketUid(mixed $id): bool
    {
        if (! is_string($id)) {
            return false;
        }

        return (bool) preg_match('/^[0-9a-fA-F]{32}$|^[0-9a-fA-F-]{36}$/', $id);
    }
}
