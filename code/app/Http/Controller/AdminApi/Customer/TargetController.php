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

namespace App\Http\Controller\AdminApi\Customer;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Customer\TargetService;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 客户管理
 * Class CustomerController.
 */
#[Prefix('ent/client/targets')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class TargetController extends AuthController
{
    public function __construct(TargetService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    #[Get('/rate', '业绩目标完成度')]
    public function targetRate()
    {
        $where = $this->request->getMore([
            ['year', now()->format('Y')],
            ['frame_id', ''],
            ['user_id', ''],
            ['link_type', '', 'types'],
        ]);
        return $this->success($this->service->getTargetRate($where));
    }

    #[Get('/census', '业绩目标完成图')]
    public function targetStatistics()
    {
        $where = $this->request->getMore([
            ['year', now()->format('Y')],
            ['frame_id', ''],
            ['user_id', ''],
            ['link_type', '', 'types'],
        ]);
        return $this->success($this->service->getTargetStatistics($where));
    }

    #[Get('/', '业绩目标列表')]
    public function index()
    {
        $where = $this->request->getMore([
            ['year', now()->format('Y')],
            ['frame_id', ''],
            ['user_id', ''],
            ['link_type', '', 'types'],
        ]);
        return $this->success($this->service->getData($where));
    }

    #[Delete('/', '删除业绩目标')]
    public function delete()
    {
        $where = $this->request->getMore([
            ['year', now()->format('Y')],
            ['link_id', ''],
            ['link_type', '', 'types'],
        ]);
        $this->service->deleteData($where);
        return $this->success('删除成功');
    }

    #[Put('/', '保存业绩目标')]
    public function store()
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);
        $this->service->saveData(auth('admin')->id(), $data);
        return $this->success('保存成功');
    }
}
