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

namespace App\Http\Dao\Customer;

use App\Http\Model\Customer\Label;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 客户标签.
 */
class LabelDao extends BaseDao
{
    use ListSearchTrait;
    use BatchSearchTrait;

    public function search($where, ?bool $authWhere = null)
    {
        if (isset($where['group_type']) && (int) ($where['pid'] ?? 0) !== 0) {
            unset($where['group_type']);
        }

        return parent::search($where, $authWhere);
    }

    /**
     * @return mixed[]
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/3
     */
    public function idByValue(array $ids)
    {
        return array_column($this->getModel()->whereIn('id', $ids)->select(['id', 'name'])->get()->toArray(), 'name');
    }

    /**
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function idByWorkTagId(array $ids)
    {
        return array_values(array_filter(array_column($this->getModel()->whereIn('id', $ids)->select(['id', 'work_tag_id'])->get()->toArray(), 'work_tag_id')));
    }

    public function getInvalidWorkGroups(array $remoteGroupIds)
    {
        $query = $this->getModel()
            ->where('pid', 0)
            ->where('is_work', 1)
            ->where('work_group_id', '<>', '');

        if ($remoteGroupIds) {
            $query->whereNotIn('work_group_id', $remoteGroupIds);
        }

        return $query->get(['id', 'name', 'work_group_id']);
    }

    public function getWorkChildrenByPids(array $pids, array $field = ['id', 'pid', 'name', 'work_tag_id'])
    {
        if (! $pids) {
            return collect();
        }

        return $this->getModel()
            ->whereIn('pid', $pids)
            ->where(function ($query) {
                $query->where('is_work', 1)->orWhere('work_tag_id', '<>', '');
            })
            ->get($field);
    }

    public function getInvalidWorkTags(array $remoteGroupIds, array $remoteTagIds, array $excludePids = [])
    {
        $query = $this->getModel()
            ->where('pid', '<>', 0)
            ->where('is_work', 1)
            ->where('work_tag_id', '<>', '');

        if ($excludePids) {
            $query->whereNotIn('pid', $excludePids);
        }

        $query->whereHas('parent', function ($parentQuery) use ($remoteGroupIds) {
            $parentQuery->where('pid', 0)
                ->where('is_work', 1)
                ->where('work_group_id', '<>', '');

            if ($remoteGroupIds) {
                $parentQuery->whereIn('work_group_id', $remoteGroupIds);
            } else {
                $parentQuery->whereRaw('1 = 0');
            }
        });

        if ($remoteTagIds) {
            $query->whereNotIn('work_tag_id', $remoteTagIds);
        }

        return $query
            ->with(['parent' => fn ($parentQuery) => $parentQuery->select(['id', 'name', 'work_group_id'])])
            ->get(['id', 'pid', 'name', 'work_tag_id']);
    }

    public function getSameNameSyncedWorkGroup(int $id, string $name): ?array
    {
        if ($name === '') {
            return null;
        }

        $group = $this->getModel()
            ->where('pid', 0)
            ->where('id', '<>', $id)
            ->where('name', $name)
            ->where('is_work', 1)
            ->where('work_group_id', '<>', '')
            ->orderBy('id')
            ->first(['id', 'name', 'work_group_id', 'is_work']);

        return $group?->toArray();
    }

    public function getSameNameGroups(string $name): array
    {
        if ($name === '') {
            return [];
        }

        return $this->getModel()
            ->where('pid', 0)
            ->where('name', $name)
            ->get(['id', 'name', 'work_group_id', 'is_work'])
            ->toArray();
    }

    public function getSyncedGroupIdsByWorkGroupId(string $workGroupId): array
    {
        if ($workGroupId === '') {
            return [];
        }

        return $this->getModel()
            ->where('work_group_id', $workGroupId)
            ->where('is_work', 1)
            ->pluck('id')
            ->all();
    }

    public function updateByIds(array $ids, array $data): int
    {
        if (! $ids) {
            return 0;
        }

        return $this->getModel()->whereIn('id', $ids)->update($data);
    }

    public function getIdsByWorkTagId(string $workTagId): array
    {
        if ($workTagId === '') {
            return [];
        }

        return $this->getModel()->where('work_tag_id', $workTagId)->pluck('id')->all();
    }

    public function getLabelsByWorkTagId(string $workTagId)
    {
        if ($workTagId === '') {
            return collect();
        }

        return $this->getModel()->where('work_tag_id', $workTagId)->get(['id', 'pid', 'name', 'work_tag_id']);
    }

    public function getGroupsByWorkGroupId(string $workGroupId)
    {
        if ($workGroupId === '') {
            return collect();
        }

        return $this->getModel()->where('pid', 0)->where('work_group_id', $workGroupId)->get(['id', 'name', 'work_group_id']);
    }

    public function getLabelsByPids(array $pids, array $field = ['id', 'pid', 'name', 'work_tag_id'])
    {
        if (! $pids) {
            return collect();
        }

        return $this->getModel()->whereIn('pid', $pids)->get($field);
    }

    public function deleteByIds(array $ids): int
    {
        if (! $ids) {
            return 0;
        }

        return $this->getModel()->whereIn('id', $ids)->delete();
    }

    /**
     * @return string
     */
    protected function setModel()
    {
        return Label::class;
    }
}
