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

namespace App\Http\Requests;

use Tuoluojiang\Open\exceptions\ApiRequestException;
use Tuoluojiang\Open\interfaces\ApiRequestInterface;
use Tuoluojiang\Open\traits\RequestHelpTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validate场景数据验证
 * Class ApiValidate.
 * @mixin  Request
 */
abstract class ApiValidate implements ApiRequestInterface
{
    use RequestHelpTrait;

    /**
     * 是否实例化时自动验证
     * @var bool
     */
    public $authValidate = false;

    /**
     * 规则.
     * @var array
     */
    protected $rules = [];

    /**
     * 错误提醒.
     * @var array
     */
    protected $message = [];

    /**
     * 验证场景.
     * @var array
     */
    protected $scene = [];

    /**
     * 场景需要验证的规则.
     * @var array
     */
    protected $only = [];

    /**
     * 验证后正确的数据.
     * @var array
     */
    protected $data = [];

    /**
     * 验证场景.
     * @var string
     */
    protected $currentScene;

    /**
     * @var string
     */
    protected $error;

    /**
     * 自动抛出异常.
     * @var bool
     */
    protected $failException = true;

    /**
     * 数据状态
     * @var int
     */
    protected $code = 400;

    /**
     * HTTP响应状态
     * @var int
     */
    protected $statusCode = 200;

    /**
     * 实例化
     * ApiValidate constructor.
     * @throws ValidationException
     */
    public function __construct(?string $scene = null)
    {
        // 设置场景
        if ($scene) {
            $this->scene($scene);
        }

        // 自动验证
        if ($this->authValidate) {
            $this->check();
        }
    }

    /**
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->request(), $name)) {
            return $this->request()->{$name}(...$arguments);
        }
        throw new ApiRequestException(__('common.empty.method', ['class' => get_class($this->request()), 'method' => $name]));
    }

    /**
     * 设置错误信息.
     * @return $this
     */
    public function setMessage(array $message)
    {
        $this->message = array_merge($this->message, $message);

        return $this;
    }

    /**
     * 自动验证
     * @return bool
     * @throws ValidationException
     */
    public function check(array $data = [], array $rules = [])
    {
        if ($this->currentScene) {
            $this->getScene($this->currentScene);
        }

        if (empty($rules)) {
            // 读取验证规则
            $rules = $this->rules() ?: $this->rules;
        }

        if (empty($this->message) && method_exists($this, 'message')) {
            $this->message = $this->message();
        }

        $emptyData = empty($data);

        if (! empty($this->only)) {
            $sceneRules = [];
            $method     = strtolower($this->request()->method());
            if ($method !== 'post') {
                $method = 'get';
            }
            foreach ($this->only as $key => $value) {
                if (array_key_exists($value, $rules)) {
                    $sceneRules[$value] = $rules[$value];
                    if ($emptyData) {
                        $data[$value] = request()->{$method}($value);
                    }
                }
            }
            $rules = $sceneRules;
        } else {
            if ($emptyData) {
                $data = request()->all();
            }
        }

        $validator          = Validator::make($data, $rules, $this->message);
        $this->currentScene = null;
        if ($validator->fails()) {
            return $this->failedValidation($validator);
        }
        $this->data = $validator->validated();
        return true;
    }

    /**
     * 获取验证数据.
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 获取错误信息.
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置验证场景.
     * @param string $name 场景名
     * @return $this
     */
    public function scene(string $name)
    {
        // 设置当前场景
        $this->currentScene = $name;

        return $this;
    }

    /**
     * 是否抛出异常.
     * @return $this
     */
    public function failException(string $failException)
    {
        $this->failException = $failException;
        return $this;
    }

    /**
     * 设置request.
     * @return Request
     */
    protected function request()
    {
        return request();
    }

    /**
     * 验证规则.
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * 获取数据验证的场景.
     * @param string $scene 验证场景
     */
    protected function getScene(string $scene): void
    {
        $this->only  = [];
        $sceneAction = Str::studly($scene);
        if (method_exists($this, 'scene' . $sceneAction)) {
            call_user_func([$this, 'scene' . $sceneAction]);
        } elseif (isset($this->scene[$scene]) && ! empty($this->scene[$scene])) {
            // 如果设置了验证适用场景且场景值不为空
            $this->only = $this->scene[$scene];
        }
    }

    /**
     * 抛出错误.
     * @param \Illuminate\Contracts\Validation\Validator $validator
     */
    protected function failedValidation(\Illuminate\Validation\Validator $validator)
    {
        $message = $validator->errors()->first();
        $code = $this->code;

        // 解析消息中的 [CODE:xxxx] 格式，提取错误码
        if (preg_match('/\[CODE:(\d+)\]/', $message, $matches)) {
            $code = (int) $matches[1];
            $message = preg_replace('/\[CODE:\d+\]/', '', $message);
        }

        if ($this->failException) {
            throw new ApiRequestException(
                $message,
                $code,
                null,
                $this->statusCode
            );
        }
        $this->error = $message;

        return false;
    }
}
