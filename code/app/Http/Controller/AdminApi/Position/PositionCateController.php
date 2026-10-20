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
use App\Http\Requests\enterprise\rank\RankCategoryRequest;
use App\Http\Service\Position\PositionCateService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 职级类别.
 */
#[Prefix('ent/rank_cate')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取职级类别列表接口',
    'create'  => '获取职级类别创建接口',
    'store'   => '保存职级类别接口',
    'edit'    => '获取职级类别信息接口',
    'update'  => '修改职级类别接口',
    'destroy' => '删除职级类别接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class PositionCateController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(PositionCateService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['entid', 1],
        ];
    }

    protected function getRequestClassName(): string
    {
        return RankCategoryRequest::class;
    }

    /**
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
        ];
    }
}
