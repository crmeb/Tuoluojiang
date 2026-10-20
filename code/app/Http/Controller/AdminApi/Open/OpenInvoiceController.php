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

namespace App\Http\Controller\AdminApi\Open;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthOpenApi;
use App\Http\Requests\Customer\InvoiceRequest;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Customer\InvoiceService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('open/invoice')]
#[Middleware([AuthOpenApi::class])]
class OpenInvoiceController extends AuthController
{
    public function __construct(InvoiceService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 保存发票.
     * @param AdminService $adminService
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Post('/', '保存发票接口')]
    public function store(AdminService $adminService): mixed
    {
        $data = $this->request->postMore([
            ['uid', 0],
            ['bill_id', []],
            ['eid', 0],
            ['price', ''],
            ['bill_date', ''],
            ['collect_type', ''],
            ['collect_name', ''],
            ['collect_tel', ''],
            ['collect_email', ''],
            ['mail_address', ''],
            ['types', ''],
            ['amount', ''],
            ['title', ''],
            ['ident', ''],
            ['mark', ''],
        ]);

        if ($data['uid'] > 0 && !$adminService->exists(['id' => $data['uid'], 'status' => 1])) {
            return $this->fail('业务员不存在');
        }
        $res = $this->service->saveInvoice($data, (int)$data['uid']);
        return $this->success('common.insert.succ', ['id' => $res]);
    }

    /**
     *  发票冲红.
     * @param InvoiceRequest $request
     * @param AdminService $adminService
     * @param $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws ValidationException
     * @throws \ReflectionException
     */
    #[Put('/{id}/invalid', '发票冲红接口')]
    public function invalid(InvoiceRequest $request, AdminService $adminService, $id): mixed
    {
        if (!$id) {
            return $this->fail('缺失发票ID！');
        }

        $request->scene('open_invalid')->check();
        [$uid, $remark] = $this->request->postMore([
            ['uid', 0],
            ['remark', ''],
        ], true);

        if ($uid && !$adminService->exists(['id' => $uid, 'status' => 1])) {
            return $this->fail('业务员不存在');
        }

        $this->service->invalidReview((int)$id, (int)$uid, 2, $remark);
        return $this->success('common.operation.succ');
    }
}
