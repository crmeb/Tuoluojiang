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

namespace App\Http\Controller\AdminApi\Position;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Position\PositionLevelService;
use App\Http\Service\Position\PositionRelationService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 职级体系
 * Class RankLevelController.
 */
#[Prefix('ent/rank_level')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '获取职位等级列表接口',
    'store'   => '保存职位等级接口',
    'edit'    => '获取职级类别信息接口',
    'update'  => '修改职位等级接口',
    'destroy' => '删除职位等级接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class PositionLevelController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(PositionLevelService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 批量设置职等.
     * @param int $batch
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('batch/{batch}', '批量修改职位等级')]
    public function batchSection($batch = 1)
    {
        $this->service->batchSection((int) $batch, $this->entId);
        return $this->success('设置成功');
    }

    /**
     * 修改职等/分类.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Put('relation/{id}', '修改职位等级关联职级')]
    public function updateRelation(PositionRelationService $services, $id = 0)
    {
        $data = $this->request->postMore([
            ['level_id', 0],
            ['cate_id', 0],
            ['rank_id', 0],
            ['entid', 1],
        ]);
        $services->resourceUpdate($id, $data);
        return $id != 0 ? $this->success('修改成功') : $this->success('保存成功');
    }

    /**
     * 删除职等/分类.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Delete('relation/{id}', '删除职位等级关联职级')]
    public function deleteRelation(PositionRelationService $services, $id)
    {
        $services->resourceDelete($id);
        return $this->success('删除成功');
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('rank/{cate_id}', '获取未关联职级')]
    public function freeRank(PositionRelationService $services, $cate_id)
    {
        $levelIds = $this->service->column(['entid' => $this->entId], 'id') ?? [];
        return $this->success($services->getFreeRank((int) $cate_id, $levelIds));
    }

    /**
     * 字段.
     * @return array|\string[][]
     */
    protected function getRequestFields(): array
    {
        return [
            ['salary', ''],
            ['min_level', 0],
            ['max_level', 0],
            ['entid', 1],
        ];
    }

    /**
     * 验证类名.
     */
    protected function getRequestClassName(): string
    {
        return '';
    }

    /**
     * 搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['entid', 1],
        ];
    }
}
