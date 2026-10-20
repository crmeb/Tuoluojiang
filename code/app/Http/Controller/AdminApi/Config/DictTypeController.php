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

use App\Http\Requests\system\DIctTypeRequest;
use App\Http\Service\Config\DictTypeService;
use App\Http\Controller\BaseController;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 数据字典分类.
 */
#[Prefix('ent/config/dict_type')]
#[Resource('/', false, names: [
    'index'   => '获取字典列表接口',
    'create'  => '获取添加字典接口',
    'store'   => '添加字典保存接口',
    'show'    => '显示隐藏字典接口',
    'edit'    => '获取修改字典接口',
    'update'  => '修改字典保存接口',
    'destroy' => '删除字典接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class DictTypeController extends BaseController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(DictTypeService $service)
    {
        $this->service = $service;
        parent::__construct();
    }

    /**
     * 获取字典数据详情.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('info/{id}', '获取字典数据详情')]
    public function info($id)
    {
        return $this->success($this->service->info($id));
    }

    protected function getRequestClassName(): string
    {
        return DIctTypeRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['link_type', 'custom'],
            ['status', ''],
            ['crud_id', ''],
            ['cate_id', ''],
            ['form_value', ''],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['ident', ''],
            ['link_type', 'custom'],
            ['status', 1],
            ['level', 1],
            ['mark', ''],
        ];
    }
}
