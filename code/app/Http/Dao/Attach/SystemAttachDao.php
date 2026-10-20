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

namespace App\Http\Dao\Attach;

use App\Http\Model\Attach\SystemAttach;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\HigherOrderWhenProxy;

class SystemAttachDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;

    public function setModel(): string
    {
        return SystemAttach::class;
    }

    /**
     * 修改图片分类.
     * @return bool
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function move(array $data)
    {
        $images = $data['images'];
        // 将逗号分隔的字符串转换为数组
        if (is_string($images)) {
            $images = array_filter(explode(',', $images), fn($v) => $v !== '');
            $images = array_map('intval', $images);
        }
        if (empty($images)) {
            return false;
        }
        return $this->search(['id' => $images])->update(['cid' => $data['pid']]);
    }

    public function sumSize($entId)
    {
        return $this->getModel(false)->where('entid', $entId)->sum('att_size');
    }

    /**
     * 关联查询.
     * @param mixed $where
     * @return BaseModel|HigherOrderWhenProxy|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function relationSearch($where)
    {
        $eid          = $where['eid'] ?? 0;
        $entId        = $where['entid'] ?? 1;
        $relationType = $where['relation_type'] ?? [];
        if (isset($where['eid'])) {
            unset($where['eid']);
        }
        if (isset($where['entid'])) {
            unset($where['entid']);
        }
        if (isset($where['relation_type']) && $relationType == [2, 3, 4, 5, 6]) {
            unset($where['relation_type']);
        }
        return $this->search($where)->when($relationType && is_array($relationType), function ($query) use ($eid, $entId, $relationType) {
            foreach ($relationType as $item) {
                if ($item == 2) {
                    $query->orWhere(function ($query) use ($eid, $entId, $item) {
                        $query->where('relation_type', $item)->where('entid', $entId)->whereIn('relation_id', function ($query) use ($eid, $entId) {
                            $query->from('client_bill')->where('entid', $entId)->where('eid', $eid)->select(['id']);
                        });
                    });
                }

                if ($item == 3) {
                    $query->orWhere(function ($query) use ($eid, $entId, $item) {
                        $query->where('relation_type', $item)->where('entid', $entId)->whereIn('relation_id', function ($query) use ($eid, $entId) {
                            $query->from('contract_resource')->where('entid', $entId)->where('eid', $eid)->select(['id']);
                        });
                    });
                }

                if ($item == 4) {
                    $query->orWhere(function ($query) use ($eid, $entId, $item) {
                        $query->where('relation_type', $item)->where('entid', $entId)->where('relation_id', $eid);
                    });
                }

                if ($item == 5) {
                    $query->orWhere(function ($query) use ($eid, $entId, $item) {
                        $query->where('relation_type', $item)->where('entid', $entId)->whereIn('relation_id', function ($query) use ($eid, $entId) {
                            $query->from('client_follow')->where('entid', $entId)->where('eid', $eid)->select(['id']);
                        });
                    });
                }

                if ($item == 6) {
                    $query->orWhere(function ($query) use ($eid, $entId, $item) {
                        $query->where('relation_type', $item)->where('entid', $entId)->whereIn('relation_id', function ($query) use ($eid, $entId) {
                            $query->from('client_invoice')->where('entid', $entId)->where('eid', $eid)->select(['id']);
                        });
                    });
                }
            }
        });
    }

    /**
     * 关联查询列表(通用).
     *
     * @param array $where 条件
     * @param array|string[] $field 显示字段
     * @param int $page 页码
     * @param int $limit 展示条数
     * @param null|array|string $sort 排序
     * @param array $with 关联
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getListByRelation(array $where = [], array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = []): array
    {
        return $this->relationSearch($where)->select($field)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->when($sort, function ($query) use ($sort) {
            if (is_array($sort)) {
                foreach ($sort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                $query->orderByDesc($sort);
            }
        })->with($with)->get()->toArray();
    }

    /**
     * 关联查询列表统计
     */
    public function getListCountByRelation(array $where, array $with = []): int
    {
        return $this->relationSearch($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->count();
    }

    public function getAttachNum(array $where, string $group = 'relation_id', string $field = 'relation_id, COUNT(id) as count')
    {
        return $this->search($where)->groupBy($group)->selectRaw($field)->get();
    }
}
