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

namespace App\Http\Controller\AdminApi\Finance;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\bill\BillCategoryRequest;
use App\Http\Service\Finance\BillCategoryService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 财务流水分类
 * Class BillCategoryController.
 */
#[Prefix('ent/bill_cate')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取财务流水类别列表接口',
    'create'  => '获取财务流水类别创建接口',
    'store'   => '保存财务流水类别接口',
    'edit'    => '获取财务流水类别信息接口',
    'update'  => '修改财务流水类别接口',
    'destroy' => '删除财务流水类别接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class BillCategoryController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    /**
     * RankCategoryController constructor.
     */
    public function __construct(BillCategoryService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 创建表单.
     */
    public function create()
    {
        return $this->success($this->service->resourceCreate(['pid' => (int) $this->request->get('pid', 0)]));
    }

    protected function getRequestFields(): array
    {
        return [
            ['path', []],
            ['name', ''],
            ['pid', 0],
            ['types', ''],
            ['entid', 1],
            ['sort', 0],
        ];
    }

    protected function getRequestClassName(): string
    {
        return BillCategoryRequest::class;
    }

    /**
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['types', ''],
        ];
    }
}
