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

use App\Constants\Crud\CrudDashboardEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Crud\SystemCrudDashboardRequest;
use App\Http\Service\Crud\SystemCrudDashboardService;
use Tuoluojiang\Open\traits\SearchTrait;
use Tuoluojiang\Open\utils\Arr;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * Class CrudDashboardController.
 */
#[Prefix('ent/crud/dashboard')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '统计看板列表',
    'store'   => '统计看板保存',
    'update'  => '统计看板修改',
    'destroy' => '统计看板删除',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CrudDashboardController extends AuthController
{
    use SearchTrait;

    public function __construct(SystemCrudDashboardService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取看板列表.
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', '', 'name_like'],
        ]);
        return $this->success($this->service->getList($where));
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     */
    public function store(SystemCrudDashboardRequest $request): mixed
    {
        $data = $request->postMore($this->getRequestFields());
        $res = $this->service->saveDashboard($data);
        return $this->success('common.insert.succ', ['id' => $res->id]);
    }

    /**
     * 修改.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function update($id, SystemCrudDashboardRequest $request): mixed
    {
        if (!$id) {
            return $this->fail(__('common.empty.attrs'));
        }
        $data = $request->postMore($this->getRequestFields());
        $this->service->updateDashboard((int)$id, $data);
        return $this->success('common.operation.succ');
    }

    /**
     * 删除.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function destroy($id): mixed
    {
        if (!$id) {
            return $this->fail(__('common.empty.attrs'));
        }
        $this->service->deleteDashboard((int)$id);
        return $this->success('common.delete.succ');
    }

    /**
     * 获取配置.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('design/{id}', '统计看板配置')]
    public function getConfigure($id): mixed
    {
        if (!$id) {
            return $this->fail(__('common.empty.attrs'));
        }
        return $this->success('ok', $this->service->getConfigure((int)$id));
    }

    /**
     * 设置配置.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('design/{id}', '保存统计看板配置')]
    public function setConfigure($id): mixed
    {
        if (!$id) {
            return $this->fail(__('common.empty.attrs'));
        }
        $configure = $this->request->post('chartData', '');
        $this->service->setConfigure((int)$id, $configure);
        return $this->success('common.operation.succ');
    }

    /**
     * 图表数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('chart', '统计看板图表数据')]
    public function chartQuery(): mixed
    {
        $this->withScopeFrame('userId');
        $queryWhere = $this->request->postMore([
            ['type', ''],
            ['tableNameEn', ''],
            ['additionalSearch', []],
            ['additionalSearchBoolean', 0],
            ['dimensionList', []],
            ['indicatorList', []],
            ['noPrivileges', 0],
            ['userId', []],
            ['search', []],
            ['uniqued', ''],
            ['chart_id', ''],
            ['chartData', ''],
        ]);
        $data = $this->service->chartQuery(Arr::snake($queryWhere));
        return $this->success($data);
    }

    /**
     * 数据列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('list', '统计看板数据列表')]
    public function listQuery(): mixed
    {
        $this->withScopeFrame('userId');
        $queryWhere = $this->request->postMore([
            ['type', CrudDashboardEnum::DATA_LIST],
            ['tableNameEn', ''],
            ['systemUserId', []],
            ['showSearchType', ''],
            ['userId', []],
            ['orderBy', []],
            ['additionalSearch', []],
            ['additionalSearchBoolean', 0],
            ['keywordDefault', ''],
            ['crudId', ''],
            ['scopeFrame', ''],
            ['showField', []],
            ['page', 0],
            ['limit', 10],
            ['noPrivileges', 0],
            ['search', []],
            ['uniqued', ''],
            ['chart_id', ''],
            ['chartData', ''],
        ]);
        $data = $this->service->dataListQuery(Arr::snake($queryWhere));
        return $this->success($data);
    }

    /**
     * 获取实体搜索字段展示.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('view/{id}', '统计看板实体搜索字段')]
    public function getViewSearchField($id): mixed
    {
        if (!$id) {
            return $this->fail(__('common.empty.attrs'));
        }
        return $this->success($this->service->viewSearchField((int)$id));
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['path', []],
            ['icon', ''],
            ['uni_img', ''],
        ];
    }
}
