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

namespace App\Http\Controller\AdminApi\Config;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\ViewSearchRequest;
use App\Http\Service\Other\ViewSearchService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 视图搜索接口.
 */
#[Prefix('ent/config/view_search')]
#[Resource('/', false, except: ['create', 'show'], names: [
    'index'   => '获取视图搜索列表接口',
    'store'   => '添加视图保存数据接口',
    'edit'    => '获取修改视图搜索接口',
    'update'  => '修改视图搜索保存接口',
    'destroy' => '删除视图搜索接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ViewSearchController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(ViewSearchService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    #[Post('sort', '修改视图搜索排序接口')]
    public function sort()
    {
        [$data] = $this->request->postMore([
            ['id', ''],
        ], true);
        $this->service->resourceSort($data);
        return $this->success('保存成功');
    }

    protected function getRequestClassName(): string
    {
        return ViewSearchRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['category', ''],
            ['title', ''],
            ['uid', auth('admin')->id()],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['title', ''],
            ['category', ''],
            ['content', []],
            ['is_public', 0],
            ['sort', 0],
        ];
    }
}
