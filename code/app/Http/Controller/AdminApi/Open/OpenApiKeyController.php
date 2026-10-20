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

namespace App\Http\Controller\AdminApi\Open;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\ApiRequest;
use App\Http\Service\Open\OpenApiKeyService;
use App\Http\Service\Open\OpenapiRuleService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 对外接口密钥.
 */
#[Prefix('ent/openapi')]
#[Resource('key', false, except: ['create'], names: [
    'index'   => '获取对外接口密钥列表',
    'store'   => '新增对外接口密钥',
    'edit'    => '获取对外接口密钥详情',
    'show'    => '修改对外接口密钥状态',
    'update'  => '修改对外接口密钥接口',
    'destroy' => '删除对外接口密钥接口',
], parameters: ['apply' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class OpenApiKeyController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(OpenApiKeyService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取权限规则.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('role', '获取权限规则')]
    public function getRole(OpenapiRuleService $service)
    {
        return $this->success($service->getRoleTree());
    }

    /**
     * 获取接口文档.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('docs', '获取接口文档')]
    public function getApiDoc(OpenapiRuleService $service)
    {
        return $this->success($service->getApiDoc());
    }

    /**
     * 查看sk.
     * @return mixed
     */
    #[Get('findsk/{id}', '查看sk')]
    public function findSk($id)
    {
        if (! $id) {
            return $this->fail('缺少参数');
        }
        $skInfo = $this->service->get($id);
        if (! $skInfo) {
            return $this->fail('没有查询到密钥信息');
        }
        return $this->success(['sk' => $skInfo->sk]);
    }

    protected function getRequestClassName(): string
    {
        return ApiRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['title', '', 'name_like'],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['title', ''],
            ['info', ''],
            ['auth', []],
            ['uid', auth('admin')->id()],
        ];
    }
}
