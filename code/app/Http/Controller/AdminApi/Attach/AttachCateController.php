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

namespace App\Http\Controller\AdminApi\Attach;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\category\CategoryRequest;
use App\Http\Service\Attach\AttachCateService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 附件分类
 * Class AttachCateController.
 */
#[Prefix('ent/system/attach_cate')]
#[Resource('/', false, except: ['show'], names: [
    'create'  => '获取添加附件分类接口',
    'index'   => '获取附件分类列表接口',
    'store'   => '保存附件分类接口',
    'show'    => '显示隐藏附件分类接口',
    'edit'    => '获取修改附件分类表单接口',
    'update'  => '修改附件分类接口',
    'destroy' => '删除附件分类接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AttachCateController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(AttachCateService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 设置Request类名.
     */
    protected function getRequestClassName(): string
    {
        return CategoryRequest::class;
    }

    /**
     * 设置搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['path', []],
            ['pid', 0],
            ['cate_name', ''],
            ['is_show', ''],
            ['add_time', '', 'time'],
            ['entid', 1],
        ];
    }

    /**
     * 设置请求参数获取字段.
     * @return mixed
     */
    protected function getRequestFields(): array
    {
        return [
            ['path', []],
            ['entid', 0],
            ['pid', 0],
            ['cate_name', ''],
            ['pic', ''],
            ['is_show', ''],
            ['sort', 0],
            ['type', 'systemAttach'],
            ['entid', 1],
        ];
    }
}
