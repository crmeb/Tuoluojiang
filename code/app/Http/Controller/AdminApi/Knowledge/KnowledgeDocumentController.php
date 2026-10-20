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
use App\Http\Requests\Knowledge\KnowledgeDocumentRequest;
use App\Http\Service\Knowledge\KnowledgeChunkService;
use App\Http\Service\Knowledge\KnowledgeDocumentService;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 知识库文档管理.
 *
 * 路由前缀 ent/knowledge/document；支持上传、重处理、手动向量化、批量启停与批量删除.
 * 文档关闭或删除后，其分块不再参与检索.
 */
#[Prefix('ent/knowledge/document')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class KnowledgeDocumentController extends AuthController
{
    /**
     * KnowledgeDocumentController constructor.
     */
    public function __construct(KnowledgeDocumentService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取文档列表.
     */
    #[Get('/', '获取知识库文档列表')]
    public function index(): mixed
    {
        $where = $this->request->getMore($this->getSearchField());

        return $this->success($this->service->getList($where));
    }

    /**
     * 上传文档.
     */
    #[Post('upload', '上传知识库文档')]
    public function upload(): mixed
    {
        [$kbId, $attachIds, $attachId] = $this->request->postMore([
            ['kb_id', 0],
            ['attach_ids', []],
            ['attach_id', 0],
        ], true);
        $kbId = (int) $kbId;
        if (! is_array($attachIds)) {
            $attachIds = $attachIds === '' || $attachIds === null ? [] : [(int) $attachIds];
        }
        if ((int) $attachId > 0) {
            $attachIds[] = (int) $attachId;
        }
        if (! $kbId || $attachIds === []) {
            return $this->fail('请选择知识库和附件');
        }

        $documents = $this->service->upload($kbId, $attachIds);

        return $this->success('上传成功', array_map(static fn ($document) => $document->toArray(), $documents));
    }

    /**
     * 批量启停文档（关闭后不可检索）.
     */
    #[Put('batch/enabled', '批量启停知识库文档')]
    public function batchEnabled(): mixed
    {
        $request = app(KnowledgeDocumentRequest::class)->scene('batch_enabled');
        $request->check();
        [$ids, $enabled] = $this->request->postMore([
            ['ids', []],
            ['enabled', 0],
        ], true);
        $result = $this->service->batchSetEnabled((array) $ids, (int) $enabled);

        return $this->success('操作成功', $result);
    }

    /**
     * 批量删除文档（删除后不可检索）.
     */
    #[Delete('batch', '批量删除知识库文档')]
    public function batchDelete(): mixed
    {
        $request = app(KnowledgeDocumentRequest::class)->scene('batch_delete');
        $request->check();
        $ids    = (array) $this->request->post('ids', []);
        $result = $this->service->batchDelete($ids);

        return $this->success('删除成功', $result);
    }

    /**
     * 获取文档详情.
     */
    #[Get('{id}', '获取知识库文档详情')]
    public function info($id): mixed
    {
        if (! $id) {
            return $this->fail('文档ID不能为空');
        }

        return $this->success($this->service->info((int) $id));
    }

    /**
     * 删除文档.
     */
    #[Delete('{id}', '删除知识库文档')]
    public function delete($id): mixed
    {
        if (! $id) {
            return $this->fail('文档ID不能为空');
        }

        $this->service->deleteInfo((int) $id);

        return $this->success('删除成功');
    }

    /**
     * 重新处理文档.
     */
    #[Post('{id}/reprocess', '重新处理知识库文档')]
    public function reprocess($id): mixed
    {
        if (! $id) {
            return $this->fail('文档ID不能为空');
        }

        return $this->success($this->service->reprocess((int) $id));
    }

    /**
     * 手动向量化文档分块（不重新分片）.
     * 可选 body.embedding_model_id 覆盖向量模型；不传则用知识库绑定模型.
     *
     * @param mixed $id
     */
    #[Post('{id}/embed', '手动向量化知识库文档')]
    public function embed($id): mixed
    {
        if (! $id) {
            return $this->fail('文档ID不能为空');
        }

        $embeddingModelId = (int) $this->request->post('embedding_model_id', 0);
        $this->service->embedDocument((int) $id, $embeddingModelId);

        return $this->success('已提交向量化');
    }

    /**
     * 获取文档分块.
     */
    #[Get('{id}/chunks', '获取知识库文档分块')]
    public function chunks($id, KnowledgeChunkService $service): mixed
    {
        if (! $id) {
            return $this->fail('文档ID不能为空');
        }

        return $this->success($service->listByDocument((int) $id));
    }

    /**
     * 获取搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['kb_id', ''],
            ['title', ''],
            ['file_type', ''],
            ['status', ''],
            ['enabled', ''],
        ];
    }
}
