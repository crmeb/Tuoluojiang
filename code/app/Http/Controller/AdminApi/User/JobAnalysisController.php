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

namespace App\Http\Controller\AdminApi\User;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\user\EnterpriseUserJobAnalysisRequest;
use App\Http\Service\Company\CompanyUserJobAnalysisService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 工作分析
 * Class JobAnalysisController.
 */
#[Prefix('ent/company/job_analysis')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class JobAnalysisController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(CompanyUserJobAnalysisService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取工作分析内容.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '工作分析详情')]
    public function info($id)
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }

        return $this->success($this->service->getInfo((int) $id));
    }

    /**
     * 获取工作分析内容.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('mine', '我的工作分析')]
    public function mine()
    {
        return $this->success($this->service->getInfoByUid(auth('admin')->id(), $this->entId));
    }

    /**
     * 工作分析列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('/', '工作分析列表')]
    public function index()
    {
        $where = $this->request->getMore([
            ['frame_id', ''],
            ['entid', 1],
            ['name', ''],
            ['types', [1, 2, 3]],
            ['status', 1],
            ['job_id', '', 'job'],
        ]);

        $list = $this->service->getJobAnalysisList($where, $this->uuid);
        return $this->success($list);
    }

    /**
     * 修改工作分析.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('{id}', '修改工作分析')]
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
     * 设置查询字段.
     * @return array|string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'search'],
            ['entid', 1],
        ];
    }

    /**
     * 设置请求字段验证
     */
    protected function getRequestClassName(): string
    {
        return EnterpriseUserJobAnalysisRequest::class;
    }

    /**
     * 获取修改或新增字段.
     * @return array|string[][]
     */
    protected function getRequestFields(): array
    {
        return [
            ['data', ''],
        ];
    }
}
