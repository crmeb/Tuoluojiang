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

use Tuoluojiang\Open\services\ApiResponseService;
use Tuoluojiang\Open\socket\WebsocketHandler;
use Illuminate\Http\Request;

/**
 * 聊天事件基类
 * Class WebSocketChatBase.
 */
abstract class WebSocketChatBase
{
    /**
     * @var string
     */
    protected $fd;

    /**
     * @var string
     */
    protected $roomName;

    /**
     * @var ApiResponseService
     */
    protected $response;

    /**
     * @var WebsocketHandler
     */
    protected $server;

    /**
     * 排除在外的事件.
     * @var array
     */
    protected $noContainMethod = ['success', 'responseMessage', 'fail'];

    /**
     * WebSocketChatBase constructor.
     */
    public function __construct(ApiResponseService $service)
    {
        $this->response = $service;
    }

    /**
     * @param mixed $socketHandler
     * @return mixed
     */
    public function handle($method, $socketHandler, $result)
    {
        $this->server   = $socketHandler;
        $this->fd       = array_shift($result);
        $this->roomName = array_shift($result);

        if (method_exists($this, $method) && ! in_array($method, $this->noContainMethod)) {
            if (! app()->bound('request')) {
                app()->instance('request', Request::create('/'));
            }
            return $this->{$method}(...$result);
        }
    }

    /**
     * 登录.
     * @return mixed
     */
    abstract public function login(array $data = []);

    /**
     * 创建socket response响应.
     * @param null|mixed $message
     * @return string
     */
    public function responseMessage(string $type, $message = null, array $data = [])
    {
        if (is_array($message)) {
            $data    = $message;
            $message = ApiResponseService::API_MESSAGE_SUCCESS;
        }
        return $this->response->socketMessage($type, $message, $data);
    }

    /**
     * 成功
     * @param null $message
     * @return string
     */
    public function success($message = null, array $data = [])
    {
        return $this->responseMessage('success', $message, $data);
    }

    /**
     * 失败.
     * @param null $message
     * @return string
     */
    public function fail($message = null, array $data = [])
    {
        return $this->responseMessage('error', $message, $data);
    }

    /**
     * 测试.
     * @return string
     */
    public function test(array $data = [])
    {
        return $this->responseMessage('test', $data);
    }
}
