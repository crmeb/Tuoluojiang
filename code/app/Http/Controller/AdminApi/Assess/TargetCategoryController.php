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
use App\Http\Requests\enterprise\assess\TargetCategoryRequest;
use App\Http\Service\Assess\AssessTargetCateService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 指标分类
 * Class TargetCategoryController.
 */
#[Prefix('ent/assess/target_cate')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '获取指标分类列表接口',
    'store'   => '保存指标分类接口',
    'edit'    => '获取指标分类信息接口',
    'update'  => '修改指标分类接口',
    'destroy' => '删除指标分类接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class TargetCategoryController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(AssessTargetCateService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 创建分类表单.
     * @param int $types
     * @return mixed
     */
    #[Get('create/{types}', name: '指标分类创建表单')]
    public function createForm($types = 0)
    {
        return $this->success($this->service->resourceCreate(['types' => $types]));
    }

    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['pid', 0],
            ['types', 0],
            ['entid', 1],
        ];
    }

    protected function getRequestClassName(): string
    {
        return TargetCategoryRequest::class;
    }

    /**
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['types', 1],
        ];
    }
}
