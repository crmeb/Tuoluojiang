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

namespace App\Http\Controller\AdminApi\Todo;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Todo\TodoItemService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 我的待办.
 * Class TodoController.
 */
#[Prefix('ent/todo')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class TodoController extends AuthController
{
    public function __construct(TodoItemService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 待办概览统计.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('overview', '待办概览统计')]
    public function overview()
    {
        $userId = auth('admin')->id();
        return $this->success($this->service->getOverview($userId));
    }

    /**
     * 待办列表（按创建时间倒序）.
     * @return mixed
     */
    #[Get('list', '全部待办列表')]
    public function list()
    {
        [$time, $type, $status] = $this->request->getMore([
            ['time', ''],
            ['type', ''],
            ['status', ''],
        ], true);

        return $this->success($this->service->getList(array_filter([
            'user_id' => auth('admin')->id(),
            'type'    => $type,
            'time'    => $time,
            'status'  => $status,
        ], static fn ($value) => $value !== '' && $value !== null)));
    }
}
