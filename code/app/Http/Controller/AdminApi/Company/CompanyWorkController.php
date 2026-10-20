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
use App\Http\Requests\enterprise\user\EnterpriseUserWorkRequest;
use App\Http\Service\Company\CompanyUserWorkService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 工作经历
 * Class WorkController.
 */
#[Prefix('ent/work')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取工作经历列表接口',
    'create'  => '获取工作经历创建接口',
    'store'   => '保存工作经历接口',
    'edit'    => '获取修改工作经历表单接口',
    'update'  => '修改工作经历接口',
    'destroy' => '删除工作经历接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CompanyWorkController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(CompanyUserWorkService $services)
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
     * 获取请求数据.
     */
    protected function getRequestFields(): array
    {
        return [
            ['user_id', 0],
            ['start_time', ''],
            ['end_time', ''],
            ['company', ''],
            ['position', ''],
            ['describe', ''],
            ['quit_reason', ''],
        ];
    }

    /**
     * 设置数据验证
     */
    protected function getRequestClassName(): string
    {
        return EnterpriseUserWorkRequest::class;
    }

    /**
     * 搜索字段.
     * @return array|array[]
     */
    protected function getSearchField(): array
    {
        return [
            ['user_id', 0],
        ];
    }
}
