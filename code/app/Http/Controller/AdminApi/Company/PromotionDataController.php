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
use App\Http\Service\Train\PromotionDataService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 晋升数据
 * Class PromotionDataController.
 */
#[Prefix('ent/company/promotion/data')]
#[Resource('/', false, except: ['show', 'create', 'edit'], names: [
    'index'   => '晋升表数据列表',
    'store'   => '晋升表数据保存',
    'update'  => '晋升表数据修改',
    'destroy' => '晋升表数据删除',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class PromotionDataController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(PromotionDataService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 修改标准.
     * @throws BindingResolutionException
     */
    #[Post('standard/{id}', '晋升表数据标准修改')]
    public function updateStandard($id): mixed
    {
        if (! $id) {
            return $this->fail('common.empty.attrs');
        }
        $res = $this->service->update((int) $id, ['standard' => $this->request->post('standard', '')]);
        return $res ? $this->success('common.update.succ') : $this->fail('common.update.fail');
    }

    /**
     * 排序.
     * @throws BindingResolutionException
     */
    #[Post('sort/{pid}', '晋升表数据排序')]
    public function sort($pid): mixed
    {
        if (! $pid) {
            return $this->fail('common.empty.attrs');
        }

        $this->service->sort((int) $pid, $this->request->post('data', []));
        return $this->success('common.operation.succ');
    }

    /**
     * 设置Request类名.
     */
    protected function getRequestClassName(): string
    {
        return '';
    }

    /**
     * 设置搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['promotion_id', 1],
        ];
    }

    /**
     * 设置请求参数获取字段.
     * @return mixed
     */
    protected function getRequestFields(): array
    {
        return [
            ['promotion_id', 0],
            ['standard', ''],
            ['position', []],
            ['rank', []],
            ['benefit', []],
            ['total', 0],
        ];
    }
}
