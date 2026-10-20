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

namespace App\Http\Controller\AdminApi\Config;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\category\CategoryRequest;
use App\Http\Service\Config\QuickCateService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\CateControllerTrait;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 系统配置分类
 * Class ConfigCateController.
 */
#[Prefix('ent/config/quickCate')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取分类列表接口',
    'create'  => '获取添加分类接口',
    'store'   => '添加分类保存接口',
    'edit'    => '获取修改分类接口',
    'update'  => '修改分类保存接口',
    'destroy' => '删除分类接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class QuickCateController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;
    use CateControllerTrait;

    public function __construct(QuickCateService $services)
    {
        parent::__construct();
        $this->service = $services;
        $this->setShowField('is_show');
    }

    protected function getRequestClassName(): string
    {
        return CategoryRequest::class;
    }
}
