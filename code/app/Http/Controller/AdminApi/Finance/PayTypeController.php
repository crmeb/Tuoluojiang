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

namespace App\Http\Controller\AdminApi\Finance;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\bill\PayTypeRequest;
use App\Http\Service\Finance\PaytypeService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 支付方式
 * Class PayTypeController.
 */
#[Prefix('ent/pay_type')]
#[Resource('/', false, names: [
    'create'  => '获取添加支付方式接口',
    'index'   => '获取支付方式列表接口',
    'store'   => '保存支付方式接口',
    'show'    => '显示隐藏支付方式接口',
    'edit'    => '获取修改支付方式表单接口',
    'update'  => '修改支付方式接口',
    'destroy' => '删除支付方式接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class PayTypeController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(PaytypeService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['ident', ''],
            ['status', ''],
            ['info', ''],
            ['entid', 1],
            ['sort', 0],
        ];
    }

    protected function getRequestClassName(): string
    {
        return PayTypeRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['ident', ''],
            ['status', ''],
            ['entid', 1],
        ];
    }
}
