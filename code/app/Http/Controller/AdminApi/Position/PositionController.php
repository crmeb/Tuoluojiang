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

namespace App\Http\Controller\AdminApi\Position;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\rank\RankRequest;
use App\Http\Service\Position\PositionService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 企业职级.
 */
#[Prefix('ent/rank')]
#[Resource('/', false, except: ['show'], names: [
    'index'   => '获取职级列表接口',
    'create'  => '获取职级创建接口',
    'store'   => '保存职级接口',
    'edit'    => '获取职级信息接口',
    'update'  => '修改职级接口',
    'destroy' => '删除职级接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class PositionController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(PositionService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 新建表单.
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function create()
    {
        $data = $this->request->getMore([
            ['cate_id', ''],
        ]);
        return $this->success($this->service->resourceCreate($data));
    }

    public function rollBack(Schedule $schedule)
    {
        $schedule->command('migrate:rollback');
    }

    /**
     * 下拉数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('select', '职级下拉列表数据')]
    public function select(): mixed
    {
        return $this->success($this->service->getSelectList());
    }

    /**
     * 搜索字段.
     * @return array|string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['cate_id', 0],
            ['entid', 1],
        ];
    }

    /**
     * 设置.
     */
    protected function getRequestClassName(): string
    {
        return RankRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['cate_id', 0],
            ['info', ''],
            ['alias', ''],
            ['entid', 1],
            ['sort', 0],
            ['uuid', $this->uuid],
        ];
    }
}
