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

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Customer\RecordService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 客户记录
 * Class RecordController.
 */
#[Prefix('ent/client/record')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class RecordController extends AuthController
{
    public function __construct(RecordService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 列表数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('/', '客户记录列表')]
    public function index(): mixed
    {
        $eid = $this->request->get('eid', 0);
        if (! $eid) {
            return $this->fail('common.empty.attrs');
        }
        $where = $this->request->getMore([
            ['eid', 0],
            ['link_type', CustomEnum::CUSTOMER],
        ]);
        return $this->success($this->service->getList($where));
    }
}
