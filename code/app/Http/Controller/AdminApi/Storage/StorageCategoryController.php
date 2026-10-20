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

namespace App\Http\Controller\AdminApi\Storage;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\storage\StorageCategoryRequest;
use App\Http\Service\Storage\StorageCategoryService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 物资分类
 * Class StorageCategoryController.
 */
#[Prefix('ent/storage')]
#[Resource('cate', false, except: ['show'], names: [
    'index'   => '获取物资分类列表',
    'create'  => '创建物资分类表单',
    'store'   => '保存物资分类接口',
    'edit'    => '获取物资分类接口',
    'update'  => '修改物资分类接口',
    'destroy' => '删除物资分类接口',
], parameters: ['cate' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class StorageCategoryController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(StorageCategoryService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 新建表单.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function create()
    {
        $where = $this->request->getMore([
            ['path', []],
            ['type', 0],
        ]);
        $where['path'] = array_map('intval', $where['path']);
        return $this->success($this->service->resourceCreate($where));
    }

    protected function getRequestClassName(): string
    {
        return StorageCategoryRequest::class;
    }

    protected function getRequestFields(): array
    {
        return [
            ['path', []],
            ['cate_name', ''],
            ['pid', 0],
            ['type', 0],
            ['sort', 0],
            ['entid', 1],
        ];
    }

    protected function getSearchField(): array
    {
        return [
            ['cate_name', '', 'name_like'],
            ['type', ''],
            ['pid', ''],
            ['entid', 1],
        ];
    }
}
