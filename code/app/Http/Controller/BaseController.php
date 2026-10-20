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

namespace App\Http\Controller;

use Casbin\Enforcer;
use App\Support\OpenAuth;
use Tuoluojiang\Open\utils\Arr;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * 控制器基础类（开源版，自 tuoluojiang/basic 拆出）
 * Class BaseController.
 */
abstract class BaseController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public array $notNeedLogin = [];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Enforcer
     */
    protected $enforcer;

    protected $service;

    /**
     * 规则.
     * @var array
     */
    protected $rule = [];

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        $this->request = request();
        $this->initialize();
    }

    /**
     * 不存在的方法.
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $alias = [
            'syncAuth'      => 'syncAuthInfo',
            'auth'          => 'getAuth',
            'check_auth'    => 'checkAuthDecrypt',
            'authCopyright' => 'authCopyright',
            'syncStatus'    => 'syncStatus',
        ];
        if (isset($alias[$method])) {
            return call_user_func([new OpenAuth($this->request), $alias[$method]]);
        }
        throw new \BadMethodCallException(__('common.empty.method', ['class' => static::class, 'method' => $method]));
    }

    /**
     * 获取属性.
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        if (in_array($name, array_keys($this->rule))) {
            return $this->request->{$this->rule[$name]}();
        }
        throw new \BadMethodCallException(__('common.empty.property', ['name' => $name]));
    }

    /**
     * 失败返回.
     * @param null $message
     * @return mixed
     */
    public function fail($message = null, array $data = [], array $replace = [], int $tips = 1)
    {
        return app('json')->fail($message, $data, $replace, $tips);
    }

    /**
     * 成功返回.
     * @param null $message
     * @return mixed
     */
    public function success($message = null, array $data = [], array $replace = [], int $tips = 1)
    {
        return app('json')->success($message, $data, $replace, $tips);
    }

    /**
     * 初始化.
     */
    protected function initialize()
    {
        if (! $this->request->hasMacro('postMore')) {
            $this->request->macro('postMore', function (array $params, bool $suffix = false) {
                return Arr::more(app()->request, $params, $suffix);
            });
        }
        if (! $this->request->hasMacro('getMore')) {
            $this->request->macro('getMore', function (array $params, bool $suffix = false) {
                return Arr::more(app()->request, $params, $suffix, 'get');
            });
        }
    }
}
