<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Controller\AdminApi\WorkExternalContact;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\WorkExternalContact\InviteGroupConfigRequest;
use App\Http\Service\WorkExternalContact\WorkChannelCodeGroupService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 企微渠道活码邀请加群配置.
 */
#[Prefix('ent/work/channel_code_invite_group')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '获取邀请加群配置列表',
    'store'   => '保存邀请加群配置',
    'edit'    => '获取邀请加群配置详情',
    'update'  => '修改邀请加群配置',
    'destroy' => '删除邀请加群配置',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkChannelCodeInviteGroupController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(WorkChannelCodeGroupService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    protected function getRequestClassName(): string
    {
        return InviteGroupConfigRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['status', ''],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['chat_ids', []],
            ['auto_create_room', 1],
            ['room_base_name', ''],
            ['room_name', ''],
            ['room_base_id', 1],
            ['room_base_start', 1],
            ['group_serial', 1],
            ['group_name', ''],
            ['join_way_remark', ''],
            ['scene', 2],
            ['max_member_num', 200],
            ['status', 1],
        ];
    }
}
