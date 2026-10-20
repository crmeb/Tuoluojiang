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

namespace App\Http\Controller\AdminApi\Company;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\user\EnterpriseUserEducationRequest;
use App\Http\Service\Company\CompanyUserEducationService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 教育经历.
 */
#[Prefix('ent/education')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取教育经历列表接口',
    'create'  => '获取教育经历创建接口',
    'store'   => '保存教育经历接口',
    'edit'    => '获取修改教育经历表单接口',
    'update'  => '修改教育经历接口',
    'destroy' => '删除教育经历接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CompanyEducationController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(CompanyUserEducationService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    public function create()
    {
        $data = $this->request->getMore([
            ['user_id', 0],
        ]);
        return $this->success($this->service->resourceCreate($data));
    }

    /**
     * 获取保存和修改字段.
     * @return array|\string[][]
     */
    protected function getRequestFields(): array
    {
        return [
            ['user_id', 0],
            ['start_time', ''],
            ['end_time', ''],
            ['school_name', ''],
            ['major', ''],
            ['education', ''],
            ['academic', ''],
            ['remark', ''],
        ];
    }

    /**
     * 字段验证
     */
    protected function getRequestClassName(): string
    {
        return EnterpriseUserEducationRequest::class;
    }

    /**
     * 搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['user_id', ''],
        ];
    }
}
