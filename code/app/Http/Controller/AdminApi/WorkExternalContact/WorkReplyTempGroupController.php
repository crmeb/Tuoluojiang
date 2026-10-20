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

namespace App\Http\Controller\AdminApi\WorkExternalContact;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\WorkExternalContact\GroupRequest;
use App\Http\Service\WorkExternalContact\WorkReplyTempGroupService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 快捷回复分组.
 */
#[Prefix('ent/work/reply_temp_group')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取快捷回复分组列表接口',
    'create'  => '获取新增快捷回复分组表单',
    'store'   => '保存快捷回复分组接口',
    'edit'    => '获取修改快捷回复分组表单',
    'update'  => '修改快捷回复分组接口',
    'destroy' => '删除快捷回复分组接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkReplyTempGroupController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(WorkReplyTempGroupService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    protected function getRequestClassName(): string
    {
        return GroupRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['sort', 0],
        ];
    }
}
