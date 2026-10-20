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

namespace App\Http\Controller\AdminApi\Position;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\EnterpriseJobRequest;
use App\Http\Service\Position\PositionJobService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 企业岗位.
 */
#[Prefix('ent/jobs')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取岗位tree型列表接口',
    'create'  => '获取创建岗位接口',
    'store'   => '保存岗位接口',
    'edit'    => '获取修改岗位接口',
    'update'  => '修改岗位接口',
    'destroy' => '删除岗位接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class PositionJobController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;
    use SearchTrait;

    public function __construct(PositionJobService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取创建职位数据.
     * @return mixed
     */
    public function create()
    {
        return $this->success($this->service->resourceCreate([$this->entId]));
    }

    /**
     * 修改状态
     * @param int $status
     * @return mixed
     */
    #[Put('show/{id}/{status}', '修改岗位状态')]
    public function show($id, $status = null)
    {
        if (! $id) {
            return $this->fail($this->message['show']['empty']);
        }
        if ($status === null) {
            return $this->fail($this->message['show']['status']);
        }
        if ($this->service->resourceShowUpdate($id, ['status' => $status])) {
            return $this->success($this->message['show']['success']);
        }
        return $this->fail($this->message['show']['fail']);
    }

    /**
     * 下拉数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('select', '岗位下拉列表数据')]
    public function select(): mixed
    {
        return $this->success($this->service->getSelectList());
    }

    /**
     * 下级岗位职责.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Get('subordinate', '下级岗位职责')]
    public function subordinate(): mixed
    {
        app('request')->merge(['scope_frame' => 'all']);
        $this->withScopeFrame();
        $where = $this->request->getMore([
            ['uid', [], 'id'],
            ['frame_id', 0],
            ['name', ''],
            ['pid', 0, 'frame_id'],
            ['types', [1, 2, 3]],
        ]);

        return $this->success($this->service->getSubordinateList($where));
    }

    /**
     * 获取职责数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('subordinate/{id}', '获取下级职责详情')]
    public function subordinateInfo($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        return $this->success($this->service->getSubordinateInfo((int) $id));
    }

    /**
     * 修改下级职责.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('subordinate/{id}', '修改下级职责')]
    public function subordinateUpdate($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $duty = $this->request->post('duty', '');
        $this->service->subordinateUpdate((int) $id, $duty);
        return $this->success('common.operation.succ');
    }

    /**
     * 字段.
     * @return array|string[][]
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['cate_id', ''],
            ['rank_id', ''],
            ['describe', ''],
            ['duty', ''],
            ['entid', 1],
        ];
    }

    /**
     * 验证类名.
     */
    protected function getRequestClassName(): string
    {
        return EnterpriseJobRequest::class;
    }

    /**
     * 搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['rank_id', ''],
            ['cate_id', ''],
            ['entid', 1],
        ];
    }
}
