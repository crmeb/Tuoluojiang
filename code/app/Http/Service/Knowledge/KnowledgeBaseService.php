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

namespace App\Http\Service\Knowledge;

use App\Constants\Ai\AiModelEnum;
use App\Constants\KnowledgeEnum;
use App\Http\Dao\Ai\AiModelDao;
use App\Http\Dao\Knowledge\KnowledgeBaseDao;
use App\Http\Model\Knowledge\KnowledgeBase;
use App\Http\Model\Knowledge\KnowledgeChunk;
use App\Http\Service\Ai\AiApplicationService;
use App\Http\Service\BaseService;

/**
 * 知识库服务.
 * @mixin KnowledgeBaseDao
 */
class KnowledgeBaseService extends BaseService
{
    /**
     * KnowledgeBaseService constructor.
     */
    public function __construct(KnowledgeBaseDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取知识库列表.
     *
     * 列表项额外包含 app_count，表示绑定该知识库、且当前登录人可见的 AI 应用数量，
     * 与详情页「关联应用」页签同一套可见范围。
     *
     * @param mixed $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = ['sort' => 'desc', 'id' => 'desc'], array $with = ['user']): array
    {
        [$page, $limit] = $this->getPageValue();
        $query          = $this->visibleQuery($where)
            ->with($with)
            ->select($field)
            ->orderByDesc('sort')
            ->orderByDesc('id');
        $count          = (clone $query)->count();
        $list           = $query->when($page && $limit, fn ($query) => $query->forPage($page, $limit))->get()->toArray();
        $applicationSvc = app(AiApplicationService::class);
        foreach ($list as &$item) {
            $item['app_count'] = $applicationSvc->countByKnowledge((int) ($item['id'] ?? 0));
        }
        unset($item);

        return $this->listData($list, $count);
    }

    /**
     * 获取当前用户可见知识库ID.
     */
    public function visibleIds(int $spaceType = KnowledgeEnum::SPACE_INTERNAL): array
    {
        return $this->visibleQuery(['space_type' => $spaceType, 'status' => KnowledgeEnum::STATUS_ENABLED])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * 按 ID 列表返回可见知识库基本信息，保持入参顺序.
     *
     * 不可见或已删除的知识库跳过，不出现在结果中.
     *
     * @param array<int, int> $ids 知识库 ID 列表
     *
     * @return array<int, array{id: int, name: string, description: string, icon: string, color: string, space_type: int, status: int, doc_count: int}>
     */
    public function basicInfoByIds(array $ids): array
    {
        $result = [];
        foreach (KnowledgeBindingParser::normalizeIds($ids) as $id) {
            try {
                $base = $this->checkVisible($id);
            } catch (\Throwable) {
                continue;
            }
            $result[] = [
                'id'          => (int) $base->id,
                'name'        => (string) ($base->name ?? ''),
                'description' => (string) ($base->description ?? ''),
                'icon'        => (string) ($base->icon ?? ''),
                'color'       => (string) ($base->color ?? ''),
                'space_type'  => (int) ($base->space_type ?? 0),
                'status'      => (int) ($base->status ?? 0),
                'doc_count'   => (int) ($base->doc_count ?? 0),
            ];
        }

        return $result;
    }

    /**
     * 获取知识库详情.
     */
    public function info(int $id): array
    {
        $base = $this->dao->get($id, ['*'], ['user']);
        if (! $base || ! $this->canView($base)) {
            throw $this->exception('知识库不存在或无权限访问');
        }

        return $base->toArray();
    }

    /**
     * 保存知识库.
     */
    public function save(array $data): KnowledgeBase
    {
        $data = $this->normalize($data);
        $this->assertEmbeddingModel((int) $data['embedding_model_id']);

        return $this->dao->create($data);
    }

    /**
     * 更新知识库.
     */
    public function updateInfo(int $id, array $data): int
    {
        $base = $this->checkVisible($id);

        $normalized = $this->normalize($data, false);
        if (array_key_exists('embedding_model_id', $data)) {
            $this->assertEmbeddingModel((int) $normalized['embedding_model_id']);
        }

        $oldModelId = (int) $base->embedding_model_id;
        $newModelId = array_key_exists('embedding_model_id', $data)
            ? (int) $normalized['embedding_model_id']
            : $oldModelId;
        $result = $this->dao->update($id, $normalized);

        if ($newModelId !== $oldModelId) {
            // 仅重新排队未成功向量化的分块，已有向量的分块保持不变.
            app(KnowledgeEmbeddingService::class)->requeueKnowledgeBase($id);
        }

        return $result;
    }

    /**
     * 修改知识库状态.
     * @param mixed $id
     */
    public function showUpdate($id, array $data)
    {
        $this->checkVisible((int) $id);

        return $this->dao->update($id, ['status' => (int) ($data['status'] ?? KnowledgeEnum::STATUS_DISABLED)]);
    }

    /**
     * 更新知识库统计.
     */
    public function updateStats(int $id, int $docCount, int $chunkCount): int
    {
        return $this->dao->update($id, [
            'doc_count'   => $docCount,
            'chunk_count' => $chunkCount,
        ]);
    }

    /**
     * 批量重新排队知识库中待处理或失败的分块，并返回当前向量化状态.
     *
     * @param int $id 知识库 ID
     * @param int $embeddingModelId 可选向量模型配置 ID
     *
     * @return array<string, mixed>
     */
    public function embed(int $id, int $embeddingModelId = 0): array
    {
        $base = $this->checkVisible($id);
        if ($embeddingModelId > 0) {
            $this->assertEmbeddingModel($embeddingModelId);
        }

        $queued = app(KnowledgeEmbeddingService::class)->requeueKnowledgeBase($id, $embeddingModelId);

        return [
            'queued' => $queued,
            'stats'  => $this->embeddingStats((int) $base->id),
        ];
    }

    /**
     * 获取知识库分块向量化状态统计.
     *
     * @return array<string, mixed>
     */
    public function embeddingStats(int $id): array
    {
        $this->checkVisible($id);
        $counts = KnowledgeChunk::query()
            ->where('kb_id', $id)
            ->where('enabled', KnowledgeEnum::STATUS_ENABLED)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(static fn ($value): int => (int) $value)
            ->all();
        $total = array_sum($counts);
        $done  = $counts[KnowledgeEnum::CHUNK_DONE] ?? 0;

        return [
            'total'        => $total,
            'pending'      => $counts[KnowledgeEnum::CHUNK_PENDING] ?? 0,
            'done'         => $done,
            'failed'       => $counts[KnowledgeEnum::CHUNK_FAILED] ?? 0,
            'success_rate' => $total > 0 ? round($done / $total, 4) : 0,
        ];
    }

    /**
     * 删除知识库.
     */
    public function deleteInfo(int $id): bool
    {
        $this->checkVisible($id);

        return $this->transaction(function () use ($id) {
            app(KnowledgeDocumentService::class)->deleteByKnowledge($id);
            app(KnowledgeChunkService::class)->deleteByKnowledge($id);

            return (bool) $this->dao->delete($id);
        });
    }

    /**
     * 校验知识库可见.
     */
    public function checkVisible(int $id): KnowledgeBase
    {
        $base = $this->dao->get($id);
        if (! $base || ! $this->canView($base)) {
            throw $this->exception('知识库不存在或无权限访问');
        }

        return $base;
    }

    /**
     * 判断是否可查看（可见即可维护，无单独管理权限）.
     *
     * 可见范围：创建人、管理员、外部空间、可见性配置（全员/指定人员/指定部门）.
     */
    public function canView(array|KnowledgeBase $base): bool
    {
        $base = $base instanceof KnowledgeBase ? $base->toArray() : $base;
        $user = auth('admin')->user();
        if ((int) ($base['uid'] ?? 0) === (int) auth('admin')->id() || (bool) ($user->is_admin ?? false)) {
            return true;
        }

        if ((int) ($base['space_type'] ?? 0) === KnowledgeEnum::SPACE_EXTERNAL) {
            return true;
        }

        $visibility = (array) ($base['visibility'] ?? []);
        if (($visibility['type'] ?? KnowledgeEnum::VISIBILITY_ALL) === KnowledgeEnum::VISIBILITY_ALL) {
            return true;
        }

        $uid = (int) auth('admin')->id();
        if (in_array($uid, array_map('intval', (array) ($visibility['user_ids'] ?? [])), true)) {
            return true;
        }

        return (bool) array_intersect($this->currentFrameIds(), array_map('intval', (array) ($visibility['frame_ids'] ?? [])));
    }

    /**
     * 获取可见知识库查询.
     */
    public function visibleQuery(array $where)
    {
        $query = $this->dao->search($where);

        return $query->where(function ($query) {
            $query->where('uid', auth('admin')->id())
                ->orWhere('space_type', KnowledgeEnum::SPACE_EXTERNAL)
                ->orWhere('visibility->type', KnowledgeEnum::VISIBILITY_ALL)
                ->orWhereJsonContains('visibility->user_ids', auth('admin')->id());

            foreach ($this->currentFrameIds() as $frameId) {
                $query->orWhereJsonContains('visibility->frame_ids', $frameId);
            }
        });
    }

    /**
     * 校验 embedding 模型配置（已启用且 model_type 为向量）.
     *
     * @param int $modelId AI 模型配置 ID，0 表示跳过校验
     *
     * @throws \Exception
     */
    public function assertEmbeddingModel(int $modelId): void
    {
        if ($modelId <= 0) {
            return;
        }

        $model = app(AiModelDao::class)->get([
            'id'         => $modelId,
            'status'     => AiModelEnum::STATUS_ENABLED,
            'model_type' => AiModelEnum::MODEL_TYPE_EMBEDDING,
        ]);
        if (! $model || trim((string) $model->model_id) === '') {
            throw $this->exception('请选择已启用的向量模型配置');
        }

        if ($model->provider === AiModelEnum::DRIVER_VOLCENGINE
            && ! str_starts_with((string) $model->model_id, 'ep-')) {
            throw $this->exception('火山引擎向量模型必须填写 Embedding 接入点 ID（以 ep- 开头），不能填写模型展示名称');
        }
    }

    /**
     * 规范知识库数据.
     */
    private function normalize(array $data, bool $create = true): array
    {
        $visibility = $data['visibility'] ?: ['type' => KnowledgeEnum::VISIBILITY_ALL, 'user_ids' => [], 'frame_ids' => []];
        if (! is_array($visibility)) {
            $visibility = ['type' => KnowledgeEnum::VISIBILITY_ALL, 'user_ids' => [], 'frame_ids' => []];
        }
        $visibility = [
            'type'      => $visibility['type'] ?? KnowledgeEnum::VISIBILITY_ALL,
            'user_ids'  => array_values(array_filter(array_map('intval', (array) ($visibility['user_ids'] ?? [])))),
            'frame_ids' => array_values(array_filter(array_map('intval', (array) ($visibility['frame_ids'] ?? [])))),
        ];

        $save = [
            'name'               => (string) ($data['name'] ?? ''),
            'description'        => (string) ($data['description'] ?? ''),
            'icon'               => (string) ($data['icon'] ?? ''),
            'color'              => (string) ($data['color'] ?? ''),
            'space_type'         => (int) (($data['space_type'] ?? 0) ?: KnowledgeEnum::SPACE_INTERNAL),
            'visibility'         => $visibility,
            'embedding_model_id' => (int) ($data['embedding_model_id'] ?? 0),
            'chunk_mode'         => (int) (($data['chunk_mode'] ?? 0) ?: KnowledgeEnum::CHUNK_AUTO),
            'chunk_size'         => (int) (($data['chunk_size'] ?? 0) ?: KnowledgeEnum::DEFAULT_CHUNK_SIZE),
            'chunk_overlap'      => (int) (($data['chunk_overlap'] ?? 0) ?: KnowledgeEnum::DEFAULT_CHUNK_OVERLAP),
            'status'             => (int) (($data['status'] ?? null) ?? KnowledgeEnum::STATUS_ENABLED),
            'sort'               => (int) ($data['sort'] ?? 0),
        ];

        if ($create) {
            $save['uid'] = (int) auth('admin')->id();
        }

        return $save;
    }

    /**
     * 获取当前用户部门 ID.
     */
    private function currentFrameIds(): array
    {
        $frames = (array) (request()->hasMacro('userInfo') ? (request()->userInfo()['frames'] ?? []) : []);

        return array_values(array_filter(array_map('intval', array_column($frames, 'id'))));
    }
}
