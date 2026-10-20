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

namespace App\Http\Controller\AdminApi\Approve;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\approve\ApproveReplyRequest;
use App\Http\Service\Approve\ApproveReplyService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 审核评价留言
 */
#[Prefix('ent/approve/reply')]
#[Resource('/', false, except: ['index', 'show', 'edit', 'create', 'update'], names: [
    'store'   => '保存审批评价接口',
    'destroy' => '删除审批评价接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ApproveReplyController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(ApproveReplyService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    protected function getRequestFields(): array
    {
        return [
            ['apply_id', ''],
            ['content', ''],
            ['files', []],
        ];
    }

    protected function getRequestClassName(): string
    {
        return ApproveReplyRequest::class;
    }

    /**
     * @return array|string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['types', 0],
        ];
    }
}
