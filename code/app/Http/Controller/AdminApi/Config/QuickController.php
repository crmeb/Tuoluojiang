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
use App\Http\Requests\system\QuickRequest;
use App\Http\Service\Config\QuickService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use FormBuilder\Exception\FormBuilderException;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 快捷入口.
 */
#[Prefix('ent/config/quick')]
#[Resource('/', false, names: [
    'index'   => '获取快捷入口列表接口',
    'create'  => '获取添加快捷入口接口',
    'store'   => '添加快捷入口保存接口',
    'show'    => '显示隐藏附件分类接口',
    'edit'    => '获取修改快捷入口接口',
    'update'  => '修改快捷入口保存接口',
    'destroy' => '删除快捷入口接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class QuickController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(QuickService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 创建数据.
     * @return mixed
     * @throws FormBuilderException
     */
    public function create()
    {
        $cid = $this->request->get('cid', '');
        return $this->success($this->service->resourceCreate(compact('cid')));
    }

    /**
     * 获取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['cid', ''],
            ['pc_url', ''],
            ['uni_url', ''],
            ['sort', 0],
            ['image', ''],
            ['types', 0],
            ['pc_show', 0],
            ['uni_show', 0],
            ['status', 1],
        ];
    }

    /**
     * 设置搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['cid', ''],
            ['name', '', 'name_like'],
        ];
    }

    protected function getRequestClassName(): string
    {
        return QuickRequest::class;
    }
}
