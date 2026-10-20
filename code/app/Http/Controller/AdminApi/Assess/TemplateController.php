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
use App\Http\Requests\enterprise\assess\TemplateRequest;
use App\Http\Service\Assess\AssessTemplateService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 考核模板
 */
#[Prefix('ent/assess/template')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '获取考核模板列表接口',
    'store'   => '保存考核模板接口',
    'edit'    => '获取考核模板信息接口',
    'update'  => '修改考核模板接口',
    'destroy' => '删除考核模板接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class TemplateController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(AssessTemplateService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    public function edit($id)
    {
        if (! $id) {
            return $this->fail('缺少必要参数');
        }
        $way = $this->request->get('way', 0);
        return $this->success($this->service->resourceEdit((int) $id, compact('way')));
    }

    /**
     * 收藏模板
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Get('collect/{id}', name: '收藏考核模板')]
    public function collect($id)
    {
        if (! $id) {
            return $this->fail('缺少必要参数');
        }
        $this->service->collect($id, $this->uuid, $this->entId);
        return $this->success('common.operation.succ');
    }

    /**
     * 设置封面.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Post('cover/{id}', name: '设置考核模板封面')]
    public function cover($id)
    {
        [$cover,$color] = $this->request->postMore([
            ['cover', ''],
            ['color', ''],
        ], true);
        if (! $id) {
            return $this->fail('缺少必要参数');
        }
        $this->service->cover($id, $this->uuid, $this->entId, $cover, $color);
        return $this->success('common.operation.succ');
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
            ['uid', $this->uuid],
            ['entid', 1],
        ];
    }

    /**
     * 设置.
     */
    protected function getRequestClassName(): string
    {
        return TemplateRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['info', ''],
            ['cate_id', 0],
            ['status', 0],
            ['data', []],
            ['entid', 1],
            ['user_id', $this->uuid],
        ];
    }
}
