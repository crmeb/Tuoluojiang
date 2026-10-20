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
use App\Http\Requests\WorkExternalContact\MassTempRequest;
use App\Http\Service\WorkExternalContact\WorkMassMessagingTempService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 企微群发素材.
 */
#[Prefix('ent/work/mass_messaging_temp')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '获取群发素材列表接口',
    'store'   => '保存群发素材接口',
    'edit'    => '获取群发素材信息接口',
    'update'  => '修改群发素材接口',
    'destroy' => '删除群发素材接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkMassMessagingTempController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(WorkMassMessagingTempService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    protected function getRequestClassName(): string
    {
        return MassTempRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['time', ''],
            ['group_id', ''],
            ['types', 0],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['group_id', 0],
            ['content', ''],
            ['attach', []],
            ['uid', auth('admin')->id()],
        ];
    }
}
