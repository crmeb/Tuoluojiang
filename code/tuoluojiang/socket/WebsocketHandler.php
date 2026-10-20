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

use Tuoluojiang\Open\services\ApiResponseService;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Http\Request;
use Swoole\Server as SwooleServer;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class WebsocketHandler.
 */
class WebsocketHandler implements WebSocketHandlerInterface
{
    /**
     * @var SwooleServer
     */
    protected $server;

    /**
     * @var Room
     */
    protected $room;

    /**
     * @var Ping
     */
    protected $ping;

    /**
     * @var int
     */
    protected $cacheTimeout;

    /**
     * @var array
     */
    protected $userType;

    /**
     * @var ApiResponseService
     */
    protected $response;

    /**
     * WebsocketHandler constructor.
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        $this->room         = app()->get(Room::class);
        $this->userType     = $this->room->getRoomName();
        $this->ping         = app()->get(Ping::class);
        $this->response     = app()->get(ApiResponseService::class);
        $this->cacheTimeout = intval(app()->config->get('swoole_websocket.ping_timeout', 60000) / 1000) + 2;
    }

    /**
     * 发送消息.
     *
     * 握手完成到 push 之间客户端可能已断开；未判断会话时 Swoole 会抛
     * “session does not exists”，被 Laravel 转成 ErrorException。
     *
     * @param null $exclude
     * @return bool
     */
    public function push($fds, $data, $exclude = null)
    {
        if ($data instanceof JsonResponse) {
            $data = $data->getData(true);
        }
        $data = is_array($data) ? json_encode($data) : $data;
        $fds  = is_array($fds) ? $fds : [$fds];
        foreach ($fds as $fd) {
            if (! $fd) {
                continue;
            }
            if ($exclude && is_array($exclude) && ! in_array($fd, $exclude)) {
                continue;
            }
            if ($exclude && $exclude == $fd) {
                continue;
            }
            if (! $this->server || ! $this->server->isEstablished((int) $fd)) {
                continue;
            }
            try {
                $this->server->push($fd, $data);
            } catch (\ErrorException $e) {
                // 判断后会话刚好关闭时，忽略 Swoole Warning，避免打成未捕获异常
                if (! str_contains($e->getMessage(), 'does not exist')) {
                    throw $e;
                }
            }
        }
        return true;
    }

    /**
     * @return bool|mixed|void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onOpen(Server $server, Request $request)
    {
        $this->server = $server;
        $type         = app('request')->get('type', 'user');
        $token        = app('request')->get('token', '');
        $touristUid   = app('request')->get('tourist_uid', '');
        $tourist      = (bool) $touristUid;
        if (! in_array($type, $this->userType)) {
            return $this->server->close($request->fd);
        }

        try {
            $data = $this->exec($type, 'login', [$request->fd, app('request')->get('form_type'), ['token' => $token, 'tourist' => $tourist]])->getData(true);
        } catch (\Throwable) {
            return $this->server->close($request->fd);
        }
        if ($data['type'] != 'success' || ! ($data['data']['uid'] ?? null)) {
            return $this->server->close($request->fd);
        }

        $uid   = $data['data']['uid'];
        $entid = $data['data']['entid'] ?? 0;
        $this->room->addUser($type, $uid, (string) $request->fd, (int) $entid);
        $this->send($request->fd, $this->response->socketMessage('ping', 'ok', ['now' => time()]));
        return $this->send($request->fd, $this->response->socketMessage('success'));
    }

    /**
     * @return bool
     */
    public function onMessage(Server $server, Frame $frame)
    {
        $this->server = $server;
        $info         = $this->room->select((string) $frame->fd);
        $result       = json_decode($frame->data, true) ?: [];

        if (! isset($result['type']) || ! $result['type']) {
            return true;
        }
        // 刷新redis登录过期时间
        $this->room->refresh($info['type'], $info['uid']);
        // 刷新企业数据
        if ($info['ent_id']) {
            $this->room->refresh($info['type'] . ':' . $info['ent_id'], $info['uid']);
        }
        // 刷新fd的ping时间检测
        $this->ping->updateTime($frame->fd, time(), $this->cacheTimeout);
        if ($result['type'] == 'ping') {
            return $this->send($frame->fd, $this->response->socketMessage('ping', 'ok', ['now' => time()]));
        }
        $data       = $result['data'] ?? [];
        $data       = is_array($data) ? $data : [$data];
        $frame->uid = $info['uid'];
        $res        = $this->exec($this->userType[$info['type']], $result['type'], [$frame->fd, $result['form_type'] ?? null, $data]);
        if ($res) {
            return $this->send($frame->fd, $res);
        }
        return true;
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        $this->server = $server;
        $tabfd        = (string) $fd;
        if ($this->room->getTable()->exist($tabfd)) {
            $data = $this->room->getTable()->get($tabfd);
            $this->room->logout($data['type'], $data['uid'], $tabfd, (int) $data['ent_id']);
            $this->exec($this->userType[$data['type']], 'close', [$fd, null, ['data' => $data]]);
        }
        $this->ping->removePing($fd);
    }

    /**
     * 执行事件.
     * @return null|array
     */
    protected function exec(string $type, string $method, array $result)
    {
        if (! in_array($type, array_values($this->userType))) {
            return null;
        }
        /* @var JsonResponse $response */
        return Event::until('swoole.websocket.' . $type, [$method, $this, $result]);
    }

    /**
     * 执行发送
     * @return bool
     */
    protected function send($fd, JsonResponse $response)
    {
        $this->ping->createPing($fd, time(), $this->cacheTimeout);
        return $this->push($fd, $response->getData(true));
    }
}
