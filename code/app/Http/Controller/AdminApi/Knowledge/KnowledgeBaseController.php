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

namespace App\Http\Controller\AdminApi\Knowledge;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Knowledge\KnowledgeBaseRequest;
use App\Http\Service\Knowledge\KnowledgeBaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 知识库管理.
 */
#[Prefix('ent/knowledge/base')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class KnowledgeBaseController extends AuthController
{
    /**
     * KnowledgeBaseController constructor.
     */
    public function __construct(KnowledgeBaseService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取知识库列表.
     */
    #[Get('/', '获取知识库列表')]
    public function index(): mixed
    {
        $where = $this->request->getMore($this->getSearchField());

        return $this->success($this->service->getList($where));
    }

    /**
     * 获取知识库详情.
     */
    #[Get('{id}', '获取知识库详情')]
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail('知识库ID不能为空');
        }

        return $this->success($this->service->info((int) $id));
    }

    /**
     * 保存知识库.
     * @throws BindingResolutionException
     */
    #[Post('/', '保存知识库接口')]
    public function store(): mixed
    {
        $request = app(KnowledgeBaseRequest::class)->scene('store');
        $request->check();
        $res = $this->service->save($this->request->postMore($this->getRequestFields()));

        return $this->success('添加成功', ['id' => $res->id]);
    }

    /**
     * 修改知识库.
     * @throws BindingResolutionException
     */
    #[Put('{id}', '修改知识库接口')]
    public function update($id): mixed
    {
        if (! $id) {
            return $this->fail('知识库ID不能为空');
        }

        $request = app(KnowledgeBaseRequest::class)->scene('update');
        $request->check();
        $this->service->updateInfo((int) $id, $this->request->postMore($this->getRequestFields()));

        return $this->success('修改成功');
    }

    /**
     * 修改知识库状态.
     */
    #[Get('{id}/status', '修改知识库状态')]
    public function status($id): mixed
    {
        if (! $id) {
            return $this->fail('知识库ID不能为空');
        }

        $this->service->showUpdate((int) $id, ['status' => $this->request->get('status', 0)]);

        return $this->success('修改成功');
    }

    /**
     * 批量向量化知识库中待处理或失败的分块.
     */
    #[Post('{id}/embed', '批量向量化知识库')]
    public function embed($id): mixed
    {
        if (! $id) {
            return $this->fail('知识库ID不能为空');
        }

        $embeddingModelId = (int) $this->request->post('embedding_model_id', 0);

        return $this->success('已提交向量化', $this->service->embed((int) $id, $embeddingModelId));
    }

    /**
     * 删除知识库.
     */
    #[Delete('{id}', '删除知识库接口')]
    public function delete($id): mixed
    {
        if (! $id) {
            return $this->fail('知识库ID不能为空');
        }

        $this->service->deleteInfo((int) $id);

        return $this->success('删除成功');
    }

    /**
     * 获取搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['name', ''],
            ['space_type', ''],
            ['status', ''],
        ];
    }

    /**
     * 获取请求字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['description', ''],
            ['icon', ''],
            ['color', ''],
            ['space_type', 1],
            ['visibility', []],
            ['embedding_model_id', 0],
            ['chunk_mode', 1],
            ['chunk_size', 800],
            ['chunk_overlap', 100],
            ['status', 1],
            ['sort', 0],
        ];
    }
}
