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

namespace App\Http\Service\Open;

use App\Http\Dao\Open\OpenApiKeyDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OpenApiKeyService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(OpenApiKeyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 查看列表.
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        return parent::getList($where, ['title', 'ak', 'sk', 'info', 'status', 'id', 'last_time', 'created_at'], ['id' => 'desc'], $with);
    }

    /**
     * 登录.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function login(string $ak, string $sk)
    {
        $info = $this->dao->get(['ak' => $ak]);
        if (! $info || $info->sk != $sk) {
            throw $this->exception('无效的密钥信息');
        }
        if ($info->status != 1) {
            throw $this->exception('密钥已被禁用');
        }
        $info->setHidden(['sk', 'status', 'last_time', 'last_ip', 'sys_auth', 'crud_auth']);
        $token = auth('openapi')->login($info, true);
        if (! $token) {
            throw $this->exception('token创建失败');
        }
        $info->last_ip   = app('request')->ip();
        $info->last_time = now()->toDateTimeString();
        return [
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('openapi')->factory()->getTTL() * 60,
        ];
    }

    /**
     * 验证接口权限.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function check()
    {
        $keyId = auth('openapi')->id();
        $info  = $this->dao->get($keyId)?->toArray();
        if (! $info) {
            auth('openapi')->logout();
            throw $this->exception('无效的密钥信息');
        }
        if ($info['status'] != 1) {
            auth('openapi')->logout();
            throw $this->exception('密钥已被禁用');
        }

        $route = app('request')->route()->uri();
        $route = str_replace('api/', '', $route);
        // 低代码路由
        if (str_contains($route, 'open/module/')) {
            $parameters = app('request')->route()->parameters();
            if (! empty($parameters['name'])) {
                $route = str_replace('{name}', $parameters['name'], $route);
            }
        }

        $methods = app('request')->route()->methods()[0] ?? '';
        $ruleId  = app()->get(OpenapiRuleService::class)->value(['url' => $route, 'method' => $methods], 'id');
        if (! $ruleId) {
            throw $this->exception('暂无权限访问该接口!');
        }

        if (! in_array($ruleId, $info['auth'])) {
            throw $this->exception('暂无权限访问该接口');
        }

        return $info;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id)
    {
        $apiKey = $this->dao->get($id);
        if (! $apiKey) {
            throw $this->exception('没有查询到密钥信息');
        }
        return $apiKey->toArray();
    }

    public function resourceUpdate($id, array $data)
    {
        $this->dao->update($id, $data);
        return true;
    }
}
