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

namespace App\Http\Controller\AdminApi\Company;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\User\UserSalaryService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 薪资变更.
 */
#[Prefix('ent/company/salary')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '调薪记录列表',
    'store'   => '保存调薪记录',
    'edit'    => '获取调薪记录',
    'update'  => '修改调薪记录',
    'destroy' => '删除调薪记录',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CompanySalaryController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;
    use SearchTrait;

    public $service;

    public function __construct(UserSalaryService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 展示数据.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function index()
    {
        $this->withScopeFrame('user_id');
        $where = $this->request->getMore($this->getSearchField());
        return $this->success($this->service->getSalaryList($where));
    }

    #[Get('last/{card_id}', '调薪最近记录')]
    public function lastRecord($card_id): mixed
    {
        if (! $card_id) {
            return $this->fail('缺少必要参数');
        }
        return $this->success($this->service->get(['card_id' => $card_id], ['*'], [], ['take_date', 'id'])->toArray());
    }

    protected function getSearchField(): array
    {
        return [
            ['card_id', 0],
            ['entid', 1],
            ['types', ''],
            ['link_id', ''],
            ['id', 0],
            ['user_id', []],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['card_id', 0],
            ['total', ''],
            ['content', ''],
            ['mark', ''],
            ['take_date', ''],
            ['entid', 1],
        ];
    }

    protected function getRequestClassName(): string
    {
        return '';
    }
}
