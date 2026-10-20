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

namespace Tuoluojiang\Open\traits;

use App\Http\Requests\ApiValidate;
use App\Http\Controller\BaseController;
use Tuoluojiang\Open\interfaces\ApiRequestInterface;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Trait ControllerTrait.
 * @mixin BaseController
 * @property ResourceServicesInterface $service
 * @property Request $request
 */
trait ResourceControllerTrait
{
    /**
     * 显示字段.
     */
    protected string $showField = 'status';

    /**
     * 错误提示.
     * @var string[][]
     */
    protected array $message = [
        'store' => [
            'success' => '添加成功',
            'fail'    => '添加失败',
        ],
        'show' => [
            'success' => '修改成功',
            'fail'    => '修改失败',
            'empty'   => '修改的ID不能为空',
            'status'  => '修改状态值不存在',
        ],
        'update' => [
            'success' => '修改成功',
            'fail'    => '修改失败',
            'empty'   => '修改的ID不能为空',
        ],
        'edit' => [
            'fail'  => '没有查询到需要修改的数据',
            'empty' => '修改的ID不能为空',
        ],
        'destroy' => [
            'success' => '删除成功',
            'fail'    => '没有查询到需要删除的数据',
            'empty'   => '修改的ID不能为空',
        ],
    ];

    /**
     * 展示数据.
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore($this->getSearchField());
        return $this->success($this->service->getList($where));
    }

    /**
     * 创建数据.
     * @return mixed
     */
    public function create()
    {
        $data = $this->request->getMore($this->getRequestFields());
        return $this->success($this->service->resourceCreate($data));
    }

    /**
     * 添加.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function store()
    {
        $data = $this->request()->postMore($this->getRequestFields());
        $res  = $this->service->resourceSave($data);
        if ($res) {
            if (is_object($res)) {
                return $this->success($this->message['store']['success'], ['id' => $res->id]);
            }

            return $this->success($this->message['store']['success'], is_array($res) ? $res : []);
        }
        return $this->fail($this->message['store']['fail']);
    }

    /**
     * 隐藏展示.
     * @param mixed $id
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function show($id)
    {
        if (! $id) {
            return $this->fail($this->message['show']['empty']);
        }
        $status = $this->request->get('status');
        if ($status === null) {
            return $this->fail($this->message['show']['status']);
        }
        if ($this->service->resourceShowUpdate($id, [$this->showField => $status])) {
            return $this->success($this->message['show']['success']);
        }
        return $this->fail($this->message['show']['fail']);
    }

    /**
     * 获取修改数据.
     * @param mixed $id
     * @return mixed
     */
    public function edit($id)
    {
        if (! $id) {
            return $this->fail($this->message['edit']['empty']);
        }
        $other = $this->request->getMore($this->getSearchField());
        return $this->success($this->service->resourceEdit((int) $id,$other));
    }

    /**
     * 修改数据.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     */
    public function update($id)
    {
        if (! $id) {
            return $this->fail($this->message['update']['empty']);
        }
        $data = $this->request()->postMore($this->getRequestFields());
        if ($this->service->resourceUpdate($id, $data)) {
            return $this->success($this->message['update']['success']);
        }
        return $this->fail($this->message['update']['fail']);
    }

    /**
     * 删除数据.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function destroy($id)
    {
        if (! $id) {
            return $this->fail($this->message['destroy']['empty']);
        }
        if ($this->service->resourceDelete($id)) {
            return $this->success($this->message['destroy']['success']);
        }
        return $this->fail($this->message['destroy']['fail']);
    }

    /**
     * 设置Request类名.
     */
    abstract protected function getRequestClassName(): string;

    /**
     * 设置搜索字段.
     */
    abstract protected function getSearchField(): array;

    /**
     * 设置请求参数获取字段.
     * @return mixed
     */
    abstract protected function getRequestFields(): array;

    /**
     * 设置showField字段.
     * @return $this
     */
    protected function setShowField(string $showField)
    {
        $this->showField = $showField;
        return $this;
    }

    /**
     * 解析request.
     * @return ApiRequestInterface|ApiValidate|Request
     * @throws BindingResolutionException
     */
    protected function request(bool $make = true)
    {
        $validate = $this->getRequestClassName();
        if (strpos($validate, '.')) {
            // 支持场景
            [$validate, $scene] = explode('.', $validate);
        }
        if ($validate) {
            $request = app()->make($validate);
            if (property_exists($request, 'authValidate') && ! $request->authValidate && $make) {
                // 设置场景
                if (isset($scene) && method_exists($request, 'scene')) {
                    $request->scene($scene);
                }
                // 验证数据
                if (method_exists($request, 'check')) {
                    $request->check();
                }
            }
        } else {
            $request = $this->request;
        }
        return $request;
    }
}
