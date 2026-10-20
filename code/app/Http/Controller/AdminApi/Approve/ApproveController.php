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

namespace App\Http\Controller\AdminApi\Approve;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\approve\ApproveRequest;
use App\Http\Service\Approve\ApproveService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 审核配置.
 */
#[Prefix('ent/approve/config')]
#[Resource('/', false, except: ['create'], names: [
    'index'   => '获取审批配置列表',
    'store'   => '保存审批配置接口',
    'show'    => '显示隐藏审批配置接口',
    'edit'    => '获取审批配置接口',
    'update'  => '修改审批配置接口',
    'destroy' => '删除审批配置接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ApproveController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(ApproveService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取审批类型筛选列表.
     * @param int|string $types 获取类型：0、我可申请；1、下级审批；2、审批记录；3、我提交过的所有类型；
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('search/{types}', '获取审批类型筛选列表')]
    public function getSearchList($types)
    {
        return $this->success($this->service->getSearchList($types, auth('admin')->id()));
    }

    protected function getRequestFields(): array
    {
        return [
            ['baseConfig', []],
            ['formConfig', []],
            ['processConfig', []],
            ['ruleConfig', []],
        ];
    }

    protected function getRequestClassName(): string
    {
        return ApproveRequest::class;
    }

    /**
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['types', 0],
            ['status', ''],
            ['examine', 1],
            ['entid', 1],
        ];
    }
}
