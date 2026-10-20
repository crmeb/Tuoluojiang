<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Response
 * Class ApiResponseService.
 */
class ApiResponseService
{
    public const API_MESSAGE_SUCCESS = 'ok';

    public const API_MESSAGE_ERROR = 'error';

    /**
     * @var int
     */
    protected $status;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string[]
     */
    protected $field = [
        'status'  => 'status',
        'data'    => 'data',
        'message' => 'message',
        'tips'    => 'tips',
    ];

    protected $defaultField = [];

    /**
     * @var int
     */
    protected $httpStatus = 200;

    /**
     * 自动多语言
     * @var bool
     */
    protected $authLang = true;

    private JsonResponse $response;

    public function __construct(JsonResponse $response)
    {
        $this->defaultField = $this->field;
        $this->response     = $response;
    }

    /**
     * 自动多语言
     * @return $this
     */
    public function authLang(bool $authLang)
    {
        $this->authLang = $authLang;
        return $this;
    }

    /**
     * 设置返回字段.
     * @return $this
     */
    public function data(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 设置字段.
     * @param bool|string $field
     * @return $this
     */
    public function field(string $name, $field)
    {
        $kes = collect($this->field)->keys()->all();
        if (in_array($name, $kes)) {
            $this->field[$name] = $field;
        }
        return $this;
    }

    /**
     * 设置错误状态
     * @return $this
     */
    public function status(int $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * 设置http响应状态
     * @return $this
     */
    public function httpStatus(int $status)
    {
        $this->httpStatus = $status;
        return $this;
    }

    /**
     * 设置提示语.
     * @return $this
     */
    public function message(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 创建 Response.
     * @param null $message
     * @return JsonResponse
     */
    public function make(int $status = 200, $message = null, array $content = [], array $replace = [], array $headers = [], int $tips = 1)
    {
        if ($message === null && $this->message) {
            $message = $this->message;
        }

        if ($status === 200 && $this->status) {
            $status = $this->status;
        }

        if (! $content && $this->data) {
            $content = $this->data;
        }

        if (is_array($message)) {
            $content = $message;
            $message = $status === 200 ? self::API_MESSAGE_SUCCESS : self::API_MESSAGE_ERROR;
        }

        $data = [
            $this->field['message'] => $this->authLang ? __($message, $replace) : $message,
        ];

        if ($this->field['status']) {
            $data[$this->field['status']] = $status;
        }

        if (is_object($content) && method_exists($content, 'toArray')) {
            $content = $content->toArray();
        }

        if (is_array($content) || $content) {
            $data[$this->field['data']] = $content;
        }
        $data[$this->field['tips']] = $tips;

        $httpStatus = $this->httpStatus;

        $this->rest();
        return $this->response->setData($data)->setStatusCode($httpStatus)->withHeaders($headers);
    }

    /**
     * 创建成功 Response.
     * @param mixed $message
     * @param mixed $content
     * @param mixed $tips
     * @return JsonResponse
     */
    public function success($message, $content = [], array $replace = [], $tips = 1)
    {
        if (is_array($message)) {
            $content = $message;
            $message = self::API_MESSAGE_SUCCESS;
        } elseif (! $message) {
            $message = self::API_MESSAGE_SUCCESS;
        }
        return $this->make(200, $message, $content, $replace, tips: $tips);
    }

    /**
     * 创建 Response.
     * @return JsonResponse
     */
    public function create(Collection $collection)
    {
        $content = $collection->get($this->field['data'], []);
        $replace = $collection->get('replace', []);
        $message = $collection->get($this->field['message'], self::API_MESSAGE_SUCCESS);
        $status  = (int) $collection->get($this->field['status'], 200);
        $header  = $collection->get('header', []);
        $tips    = $collection->get('tips', 1);
        return $this->make($status, $message, $content, $replace, $header, $tips);
    }

    /**
     * 创建失败 Response.
     * @param string $message
     * @return JsonResponse
     */
    public function fail($message, array $content = [], array $replace = [], int $tips = 1)
    {
        if (is_array($message)) {
            $content = $message;
            $message = self::API_MESSAGE_ERROR;
        } elseif (! $message) {
            $message = self::API_MESSAGE_ERROR;
        }

        return $this->make(400, $message, $content, $replace, tips: $tips);
    }

    /**
     * 创建socket Response.
     * @return JsonResponse
     */
    public function socketMessage(string $type, string $message = self::API_MESSAGE_SUCCESS, array $data = [])
    {
        $this->authLang = false;
        $this->field('status', 'type');
        $this->status = $type;
        return $this->make(200, $message, $data);
    }

    /**
     * 重置.
     */
    protected function rest()
    {
        $this->status     = 200;
        $this->message    = null;
        $this->data       = [];
        $this->httpStatus = 200;
        $this->authLang   = true;
        $this->field      = $this->defaultField;
    }
}
