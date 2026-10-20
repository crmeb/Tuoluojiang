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

namespace App\Http\Controller\AdminApi\News;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\enterprise\notice\NoticeRequest;
use App\Http\Service\News\NewsService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 通知公告.
 */
#[Prefix('ent/notice')]
#[Resource('list', false, except: ['create'], names: [
    'index'   => '获取通知公告列表',
    'store'   => '保存通知公告接口',
    'show'    => '显示隐藏通知公告接口',
    'edit'    => '通知公告修改表单',
    'update'  => '修改通知公告接口',
    'destroy' => '删除通知公告接口',
], parameters: ['list' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class NewsController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(NewsService $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取详情.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('detail/{id}', '获取详情接口')]
    public function getInfo($id)
    {
        return $this->success($this->service->getInfo($id));
    }

    /**
     * 获取全部选项.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('index_list', '全部选项接口')]
    public function defaultList(): mixed
    {
        $where = $this->request->getMore($this->getSearchField());
        return $this->success($this->service->getList($where));
    }

    /**
     * 置顶.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    #[Put('top/{id}', '置顶接口')]
    public function top($id): mixed
    {
        if (! $id) {
            return $this->fail(__('common.empty.attrs'));
        }

        $this->service->reversalTop((int) $id);
        return $this->success('common.operation.succ');
    }

    /**
     * 搜索字段.
     * @return array|string[][]
     */
    protected function getSearchField(): array
    {
        return [
            ['cate_id', ''],
            ['title', '', 'title_like'],
            ['status', ''],
            ['is_new', ''],
            ['is_read', ''],
            ['time', ''],
            ['entid', 1],
        ];
    }

    /**
     * 设置过滤.
     */
    protected function getRequestClassName(): string
    {
        return NoticeRequest::class;
    }

    /**
     * 提取字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['cate_id', 0],
            ['title', ''],
            ['cover', ''],
            ['info', ''],
            ['content', ''],
            ['is_top', 0],
            ['push_type', 0],
            ['push_time', ''],
            ['sort', 0],
            ['entid', 1],
        ];
    }
}
