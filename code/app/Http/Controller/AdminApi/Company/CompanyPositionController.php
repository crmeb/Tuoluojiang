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
use App\Http\Requests\enterprise\user\EnterpriseUserPositionRequest;
use App\Http\Service\Company\CompanyUserPositionService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 任职经历.
 */
#[Prefix('ent/position')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取任职经历列表接口',
    'create'  => '获取任职经历创建接口',
    'store'   => '保存任职经历接口',
    'edit'    => '获取修改任职经历表单接口',
    'update'  => '修改任职经历接口',
    'destroy' => '删除任职经历接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CompanyPositionController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(CompanyUserPositionService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 设置查询字段.
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['user_id', ''],
        ];
    }

    /**
     * 设置请求字段验证
     */
    protected function getRequestClassName(): string
    {
        return EnterpriseUserPositionRequest::class;
    }

    /**
     * 获取修改或新增字段.
     * @return array|\string[][]
     */
    protected function getRequestFields(): array
    {
        return [
            ['user_id', 0],
            ['start_time', ''],
            ['end_time', ''],
            ['position', ''],
            ['department', ''],
            ['is_admin', 0],
            ['status', 0],
            ['remark', ''],
        ];
    }
}
