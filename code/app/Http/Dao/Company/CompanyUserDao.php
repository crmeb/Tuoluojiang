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

namespace App\Http\Dao\Company;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Admin\AdminInfo;
use App\Http\Model\Company\UserJobAnalysis;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Traits\Conditionable;

/**
 * 企业用户.
 */
class CompanyUserDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 插入数据.
     * @return bool
     * @throws BindingResolutionException
     */
    public function insert(array $data)
    {
        return $this->getModel(false)->insert($data);
    }

    /**
     * 搜索.
     * @param array|int|string $where
     * @return BaseModel|Conditionable|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        $frameIds = [];
        $name     = '';
        $entid    = 0;
        if (is_array($where)) {
            $frameIds = $where['frame_id'] ?? [];
            $name     = $where['name'] ?? '';
            $entid    = $where['entid'] ?? 0;
            unset($where['frame_id'], $where['name']);
        }
        return parent::search($where, $authWhere)
            ->when($frameIds, fn ($q) => $q->whereIn('id', fn ($query) => $query->from('frame_assist')
                ->whereIn('frame_id', $frameIds)
                ->select(['user_id'])))
            ->when($name !== '', fn ($q) => $q->whereIn('id', fn ($query) => $query->from('admin_info')
                ->where('name', 'like', '%' . $name . '%')
                ->select(['uid'])));
    }

    /**
     * 获取用户和企业关联数据.
     * @return BaseModel[]|Collection
     * @throws BindingResolutionException
     */
    public function userEnt(array $userIds, callable $callback)
    {
        return $this->getModel(false)->whereIn('id', $userIds)->select(['roles', 'id'])->get()->each($callback);
    }

    /**
     * 更新用户和企业关联数据.
     * @return int
     * @throws BindingResolutionException
     */
    public function updateUserEnterprise(array $ids, array $data)
    {
        return $this->getModel(false)->whereIn('id', $ids)->update($data);
    }

    public function validEntUser(array $uid, ?int $entId = null)
    {
        return $this->getModel(false)->where('verify', 1)->where('status', 1)->when($entId, function ($query) use ($entId) {
            $query->where('entid', $entId);
        })->whereIn('uid', $uid)->count();
    }

    /**
     * @return BaseModel|Conditionable|mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function selectModel($where, array $field = [], array $with = [])
    {
        return $this->search($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->when($this->defaultSort, function ($query) {
            if (is_array($this->defaultSort)) {
                foreach ($this->defaultSort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                $query->orderByDesc($this->defaultSort);
            }
        })->select($field ?: '*');
    }

    /**
     * 工作分析模型.
     * @return BaseModel|mixed
     * @throws BindingResolutionException
     */
    public function getJobAnalysisModel(array $where = []): mixed
    {
        $entId = $where['entid'] ?? 0;

        $table = $this->getModel(false)->getTable();

        $cardModel = app()->get(AdminInfo::class);
        $cardTable = $cardModel->getTable();

        /** @var UserJobAnalysis $jsonModel */
        $jsonModel = app()->get(UserJobAnalysis::class);
        $jobTable  = $jsonModel->getTable();

        return $this->getModel(false)
            ->join($jobTable, $jobTable . '.uid', '=', $table . '.id', 'left')
            ->join($cardTable, $cardTable . '.id', '=', $table . '.id', 'left')
            ->where($cardTable . '.status', 1)
            ->whereIn($cardTable . '.type', [0, 1, 2, 3])
            ->when(isset($where['uid']), fn ($q) => $q->whereIn($table . '.id', $where['uid']))
            ->when(isset($where['job']) && $where['job'], fn ($q) => $q->where($table . '.job', $where['job']))
            ->when(isset($where['search']) && $where['search'], fn ($q) => $q->whereIn($table . '.uid', fn ($q) => $q->from('admin_info')
                ->where('status', 1)->whereIn($cardTable . '.type', [0, 1, 2, 3])
                ->where('name', 'like', '%' . $where['search'] . '%')->select(['uid']))->select(['id']));
    }

    /**
     * 工作分析列表.
     * @param array $where 条件
     * @param array|string[] $field 显示字段
     * @param int $page 页码
     * @param int $limit 展示条数
     * @param null|array|string $sort 排序
     * @param array $with 关联
     * @throws BindingResolutionException
     */
    public function getJobAnalysisList(array $where = [], array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = []): mixed
    {
        return $this->getJobAnalysisModel($where)->select($field)->when($page && $limit, function ($query) use ($page, $limit) {
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
        })->with($with)->get();
    }

    /**
     * 工作分析列表统计
     * @param array $where 条件
     * @throws BindingResolutionException
     */
    public function getJobAnalysisCount(array $where = []): int
    {
        return $this->getJobAnalysisModel($where)->count();
    }

    /**
     * @email 136327134@qq.com
     * @date 2024/4/12
     * @return mixed[]
     * @throws BindingResolutionException
     */
    public function userIdByUserInfo(array $userId)
    {
        return $this->getModel()->whereIn('id', $userId)->select(['id', 'name'])->get()->toArray();
    }

    /**
     * 设置模型.
     * @return mixed|string
     */
    protected function setModel()
    {
        return Admin::class;
    }
}
