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
use App\Http\Requests\Knowledge\KnowledgeChunkRequest;
use App\Http\Service\Knowledge\KnowledgeChunkService;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 知识库分块管理.
 *
 * 路由前缀 ent/knowledge/chunk；鉴权 auth.admin + ent.auth + ent.log.
 * 支持手动新增、编辑（Markdown + 内嵌图片 OCR）、启停、删除与失败重试向量化.
 */
#[Prefix('ent/knowledge/chunk')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class KnowledgeChunkController extends AuthController
{
    /**
     * KnowledgeChunkController constructor.
     */
    public function __construct(KnowledgeChunkService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 新增手动分块（仅接收 Markdown content_html，content 由服务端解析生成）.
     */
    #[Post('/', '新增知识库文档分块')]
    public function store(): mixed
    {
        $request = app(KnowledgeChunkRequest::class)->scene('store');
        $request->check();
        $data = $this->request->postMore([
            ['doc_id', 0],
            ['content_html', ''],
            ['image_urls', []],
        ]);
        $id = $this->service->createManualChunk($data);

        return $this->success('新增成功', ['id' => $id]);
    }

    /**
     * 更新文档分块（仅接收 Markdown content_html，content 由服务端解析生成）.
     *
     * @param mixed $id
     */
    #[Put('{id}', '修改知识库文档分块')]
    public function update($id): mixed
    {
        if (! $id) {
            return $this->fail('分块ID不能为空');
        }

        $request = app(KnowledgeChunkRequest::class)->scene('update');
        $request->check();
        $data = $this->request->postMore([
            ['content_html', ''],
        ]);
        if ($this->request->has('image_urls')) {
            $data['image_urls'] = (array) $this->request->post('image_urls', []);
        }
        $this->service->updateChunk((int) $id, $data);

        return $this->success('修改成功');
    }

    /**
     * 开启或关闭分块.
     *
     * @param mixed $id
     */
    #[Put('{id}/enabled', '启停知识库文档分块')]
    public function enabled($id): mixed
    {
        if (! $id) {
            return $this->fail('分块ID不能为空');
        }

        $request = app(KnowledgeChunkRequest::class)->scene('enabled');
        $request->check();
        $enabled = (int) $this->request->post('enabled', 0);
        $this->service->setEnabled((int) $id, $enabled);

        return $this->success('操作成功');
    }

    /**
     * 手动触发分块向量化.
     *
     * @param mixed $id
     */
    #[Post('{id}/embed', '手动向量化知识库文档分块')]
    public function embed($id): mixed
    {
        if (! $id) {
            return $this->fail('分块ID不能为空');
        }

        $this->service->embedChunk((int) $id);

        return $this->success('已提交向量化');
    }

    /**
     * 删除分块.
     *
     * @param mixed $id
     */
    #[Delete('{id}', '删除知识库文档分块')]
    public function destroy($id): mixed
    {
        if (! $id) {
            return $this->fail('分块ID不能为空');
        }

        $this->service->deleteChunk((int) $id);

        return $this->success('删除成功');
    }
}
