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

namespace App\Http\Controller\AdminApi\Assess;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\assess\PlanRequest;
use App\Http\Service\Assess\AssessPlanService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 考核计划控制器.
 */
#[Prefix('ent/assess/plan')]
#[Resource('/', false, except: ['create', 'show', 'index'], names: [
    'store'   => '保存考核计划接口',
    'edit'    => '获取考核计划信息接口',
    'update'  => '修改考核计划接口',
    'destroy' => '删除考核计划接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class PlanController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(AssessPlanService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取已启用的考核计划周期
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('period', '已启用的考核周期')]
    public function getEnablePeriod(): mixed
    {
        return $this->success($this->service->getEnablePeriod(auth('admin')->id()));
    }

    /**
     * 获取查看一选择绩效成员信息.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('user_list', '当前考核周期选中人员')]
    public function getPlanUserList()
    {
        $id = $this->request->get('id');
        if (! $id) {
            return $this->fail('缺少参数');
        }
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
            ['frame_id', []],
            ['uni', 0],
        ]);

        return $this->success($this->service->getPlanUserList((int) $id, $where));
    }

    /**
     * 搜索字段.
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['time', ''],
            ['period', ''],
            ['check_uid', []],
            ['test_uid', []],
        ];
    }

    /**
     * 设置.
     */
    protected function getRequestClassName(): string
    {
        return PlanRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['create_time', ''],
            ['create_month', ''],
            ['make_type', ''],
            ['make_day', ''],
            ['eval_type', ''],
            ['eval_day', ''],
            ['verify_type', 'after'],
            ['verify_day', ''],
            ['test', [],'test_user'],
            ['test_frame', []],
            ['assess_type', 0],
            ['status', 0],
            ['entid', 1],
        ];
    }
}
