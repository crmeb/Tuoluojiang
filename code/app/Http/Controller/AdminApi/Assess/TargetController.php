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

namespace App\Http\Controller\AdminApi\Assess;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\assess\TargetRequest;
use App\Http\Service\Assess\AssessTargetService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 指标模板
 */
#[Prefix('ent/assess/target')]
#[Resource('/', false, names: [
    'index'   => '获取指标列表接口',
    'create'  => '获取指标创建接口',
    'store'   => '保存指标接口',
    'edit'    => '获取指标信息接口',
    'update'  => '修改指标接口',
    'show'    => '修改指标状态接口',
    'destroy' => '删除指标接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class TargetController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(AssessTargetService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 搜索字段.
     * @return array|\string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', ''],
            ['cate_id', ''],
        ];
    }

    /**
     * 设置.
     */
    protected function getRequestClassName(): string
    {
        return TargetRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['cate_id', 0],
            ['name', ''],
            ['content', ''],
            ['status', 1],
            ['entid', 1],
            ['uid', $this->uuid],
        ];
    }
}
