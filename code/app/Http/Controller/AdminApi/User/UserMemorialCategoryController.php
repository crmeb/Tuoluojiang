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

namespace App\Http\Controller\AdminApi\User;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\user\UserMemorialCategoryRequest;
use App\Http\Service\User\UserMemorialCategoryService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 备忘录分类控制器
 * Class UserMemorialCategoryController.
 */
#[Prefix('ent/user/memorial_cate')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '获取备忘录分类接口',
    'store'   => '保存备忘录分类接口',
    'edit'    => '获取备忘录分类信息接口',
    'update'  => '修改备忘录分类接口',
    'destroy' => '删除备忘录分类接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class UserMemorialCategoryController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    /**
     * UserMemorialCategoryController constructor.
     */
    public function __construct(UserMemorialCategoryService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('create/{pid}', '获取备忘录分类创建接口')]
    public function createCategory($pid = 0)
    {
        return $this->success($this->service->resourceCreate(['pid' => $pid, 'uid' => $this->uuid]));
    }

    protected function getRequestFields(): array
    {
        return [
            ['path', []],
            ['name', ''],
            ['pid', 0],
            ['uid', $this->uuid],
        ];
    }

    protected function getRequestClassName(): string
    {
        return UserMemorialCategoryRequest::class;
    }

    /**
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['uid', $this->uuid],
        ];
    }
}
