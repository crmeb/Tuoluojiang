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

namespace App\Http\Controller\AdminApi\WorkExternalContact;

use App\Constants\Work\MediaEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\WorkExternalContact\ReplyTempRequest;
use App\Http\Service\WorkExternalContact\UrlMetadataService;
use App\Http\Service\WorkExternalContact\WorkMaterialService;
use App\Http\Service\WorkExternalContact\WorkReplyTempService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 企微快捷回复.
 */
#[Prefix('ent/work/reply_temp')]
#[Resource('/', false, except: ['show', 'create'], names: [
    'index'   => '获取快捷回复列表接口',
    'store'   => '保存快捷回复接口',
    'edit'    => '获取快捷回复信息接口',
    'update'  => '修改快捷回复接口',
    'destroy' => '删除快捷回复接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class WorkReplyTempController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(WorkReplyTempService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 数据导入模板.
     */
    #[Get('import/temp', '数据导入模板')]
    public function importTemp(): mixed
    {
        return $this->success(['url' => link_file('/static/temp/reply_import_temp.xlsx')]);
    }

    /**
     * 获取URL元数据(标题、描述、封面图).
     */
    #[Post('url_metadata', '获取URL元数据')]
    public function getUrlMetadata(UrlMetadataService $urlMetadataService): mixed
    {
        [$url] = $this->request->postMore([
            ['url', ''],
        ], true);

        if (! $url) {
            return $this->fail('请填写网址链接');
        }

        try {
            $metadata = $urlMetadataService->getMetadata($url);
            return $this->success($metadata);
        } catch (\Exception $e) {
            \Log::error('URL metadata fetch failed: ' . $e->getMessage(), [
                'url' => $url,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->fail('获取链接信息失败，请稍后重试');
        }
    }

    /**
     * 数据导入.
     */
    #[Post('import', '数据导入')]
    public function import(): mixed
    {
        [$data] = $this->request->postMore([
            ['data', []],
        ], true);
        $result = $this->service->import($data, auth('admin')->id());
        return $this->success(sprintf('导入结果，成功:%s条,失败:%s条.', $result['success'], $result['error']));
    }

    /**
     * 获取素材列表（素材选择器）
     */
    #[Get('materials', '获取素材列表')]
    public function materials(WorkMaterialService $materialService): mixed
    {
        [$keyword, $type, $page, $limit] = $this->request->getMore([
            ['keyword', ''],
            ['type', ''],
            ['page', 1],
            ['limit', 20],
        ], true);

        $result = $materialService->search($keyword, $type, (int) $page, (int) $limit);
        return $this->success($result);
    }

    /**
     * 从素材创建快捷回复
     */
    #[Post('create_from_material', '从素材创建快捷回复')]
    public function createFromMaterial(WorkMaterialService $materialService): mixed
    {
        [$materialId] = $this->request->postMore([
            ['material_id', 0],
        ], true);

        $material = $materialService->getDetail((int) $materialId);
        if (! $material) {
            return $this->fail('素材不存在');
        }

        $data = [
            'types' => $material['types'] ?? 'text',
            'title' => $material['title'] ?? '',
            'content' => $material['content'] ?? '',
            'info' => $material['info'] ?? '',
            'link' => $material['link'] ?? '',
            'app_id' => $material['app_id'] ?? '',
            'file_id' => $material['file_id'] ?? 0,
        ];

        $result = $this->service->resourceSave($data);
        return $this->success(['id' => $result->id]);
    }

    protected function getRequestClassName(): string
    {
        return ReplyTempRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['time', ''],
            ['group_id', ''],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['group_id', 0],
            ['types', MediaEnum::TEMP_TEXT],
            ['title', ''],
            ['info', ''],
            ['link', ''],
            ['app_id', ''],
            ['content', ''],
            ['sort', 0],
            ['file_id', 0],
            ['uid', auth('admin')->id()],
        ];
    }
}
