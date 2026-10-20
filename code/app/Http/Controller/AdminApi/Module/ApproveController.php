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

namespace App\Http\Controller\AdminApi\Module;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Middleware\CheckRuleCompany;
use App\Http\Requests\Crud\SystemCrudApproveRequest;
use App\Http\Service\Crud\SystemCrudApproveService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 低代码审批流程.
 */
#[Prefix('ent/crud/approve')]
#[Resource('/', false, except: ['create'], names: [
    'index'   => '获取实体审批配置列表',
    'store'   => '保存实体审批配置接口',
    'show'    => '显示隐藏实体审批配置接口',
    'edit'    => '获取实体审批配置接口',
    'update'  => '修改实体审批配置接口',
    'destroy' => '删除实体审批配置接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ApproveController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(SystemCrudApproveService $services)
    {
        parent::__construct();
        $this->service = $services;
        $this->middleware([AuthAdmin::class, AuthEnterprise::class, CheckRuleCompany::class]);
    }

    protected function getRequestFields(): array
    {
        return [
            ['baseConfig', []],
            ['processConfig', []],
            ['ruleConfig', []],
        ];
    }

    protected function getRequestClassName(): string
    {
        return SystemCrudApproveRequest::class;
    }

    /**
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['status', ''],
            ['crud_id', ''],
            ['cate_id', ''],
        ];
    }
}
