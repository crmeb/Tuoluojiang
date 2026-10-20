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

namespace App\Http\Service;

use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\exceptions\ServicesException;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ServicesTrait;
use FormBuilder\Exception\FormBuilderException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * 基础services类
 * Class BaseService.
 */
abstract class BaseService
{
    use ServicesTrait;

    /**
     * @var BaseDao
     */
    protected $dao;

    /**
     * 设置的entid.
     * @var null
     */
    protected $entValue;

    /**
     * 列表默认返回字段.
     * @var string[]
     */
    protected $listField = [
        'list'  => 'list',
        'count' => 'count',
    ];

    /**
     * @param mixed $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $this->runListen($name);
        // TODO: Implement __call() method.
        return call_user_func_array([$this->dao, $name], $arguments);
    }

    /**
     * @return $this
     */
    public function setEntValue(int $entid)
    {
        $this->entValue = $entid;
        return $this;
    }

    /**
     * 获取分页配置.
     * @return int[]
     */
    public function getPageValue(bool $isPage = true, bool $isRelieve = true)
    {
        $page = $limit = 0;
        if ($isPage) {
            $page  = request()->input(Config::get('database.page.pageKey', 'page'), 0);
            $limit = request()->input(Config::get('database.page.limitKey', 'limit'), 0);
        }
        $limitMax     = Config::get('database.page.limitMax');
        $defaultLimit = Config::get('database.page.defaultLimit', 10);
        if (($limit > $limitMax || $limit == 0) && $isRelieve) {
            $limit = $limitMax;
        }
        return [(int) $page, (int) $limit, (int) $defaultLimit];
    }

    /**
     * 数据库事务操作.
     * @param bool $isTran
     * @return mixed
     */
    public function transaction(callable $closure, $isTran = true)
    {
        return $isTran ? DB::transaction($closure) : $closure();
    }

    /**
     * 创建Element ui 表单.
     * @param mixed $rule
     * @return array
     * @throws FormBuilderException
     * @throws BindingResolutionException
     */
    public function createElementForm(string $title, $rule, string $url, string $method = 'POST', array $confing = [])
    {
        $methodData = ['POST', 'PUT', 'GET', 'DELETE'];
        if (! in_array(strtoupper($method), $methodData)) {
            throw new ServicesException(__('request.error'));
        }
        /** @var FormService $formService */
        $formService = app()->get(FormService::class);

        if ($rule instanceof \Closure) {
            $field = $rule($formService);
        } elseif (is_array($rule)) {
            $field = $rule;
        } else {
            throw new ServicesException(__('data.typeError'));
        }

        $form   = $formService->createForm($url)->setMethod($method)->setTitle($title)->setRule($field);
        $rule   = $form->formRule();
        $action = $form->getAction();
        return compact('rule', 'title', 'action', 'method', 'confing');
    }

    /**
     * 抛出错误.
     * @param null|mixed $exception
     * @return ServicesException
     */
    public function exception(array|string $message, int $code = 0, $exception = null)
    {
        $message = is_array($message) ? json_encode($message) : $message;
        if (! $exception) {
            return new ServicesException($message, $code);
        }
        return new $exception($message, $code);
    }

    /**
     * 返回数据整理.
     * @param mixed $data
     * @return array
     */
    public function listData($data, int $count = 0, array $other = [])
    {
        if ($data instanceof Model) {
            $data = $data->toArray();
        }

        $returnData = [
            $this->listField['list']  => $data,
            $this->listField['count'] => $count,
        ];

        if ($other) {
            $returnData = array_merge($returnData, $other);
        }

        return $returnData;
    }

    /**
     * 是否为企业后台.
     * @return bool
     */
    protected function isEnt()
    {
        $request = request();
        return $request->hasMacro('isEnt') && $request->isEnt();
    }

    /**
     * 获取企业id.
     * @return \Closure|int
     */
    protected function entId(bool $isClosure = true)
    {
        $request = request();
        $closure = function () use ($request) {
            if ($this->entValue) {
                return $this->entValue;
            }
            return $request->hasMacro('entId') ? $request->entId() : 0;
        };
        return $isClosure ? $closure : $closure();
    }

    /**
     * 获取用户uid.
     * @return \Closure|int
     */
    protected function uuId(bool $isClosure = true)
    {
        $request = request();
        $closure = function () use ($request) {
            return $request->hasMacro('uuId') ? $request->uuId() : '';
        };
        return $isClosure ? $closure : $closure();
    }
}
