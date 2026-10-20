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

namespace App\Http\Dao\Frame;

use App\Http\Model\Frame\Frame;
use App\Http\Model\Frame\FrameAssist;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\PathUpdateTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FrameDao extends BaseDao
{
    use PathUpdateTrait;
    use TogetherSearchTrait;

    /**
     * 分级排序列表.
     * @param array|string[] $field
     * @param string $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTierList($where, array $field = ['*'], array $with = [], $sort = 'sort', ?callable $callable = null): array
    {
        return toArray($this->search($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
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
        })->get($field)->when($callable, fn ($query) => $query->each($callable)));
    }

    /**
     * 减少user数量.
     * @return int
     * @throws BindingResolutionException
     */
    public function decUserCount(array $ids, int $amount = 1)
    {
        return $this->getModel(false)->whereIn('id', $ids)->decrement('user_count', $amount);
    }

    /**
     * 增加user数量.
     * @return int
     * @throws BindingResolutionException
     */
    public function incUserCount(array $ids, int $amount = 1)
    {
        return $this->getModel(false)->whereIn('id', $ids)->increment('user_count', $amount);
    }

    /**
     * 批量修改.
     * @return int
     * @throws BindingResolutionException
     */
    public function updateBath(array $ids, array $data)
    {
        return $this->getModel(false)->whereIn('id', $ids)->update($data);
    }

    /**
     * 获取当前模型.
     * @return BaseModel[]|Collection
     * @throws BindingResolutionException
     */
    public function getFrameModel(int $entid, int $frameId, callable $callback)
    {
        return $this->getModel(false)
            ->when($frameId, function ($query, $frameId) {
                $query->where('id', $frameId);
            })->when($entid, function ($query, $entid) {
                $query->where('entid', $entid);
            })
            ->select(['id', 'path', 'user_count'])
            ->get()
            ->each($callback);
    }

    /**
     * 修改下级.
     * @throws BindingResolutionException
     */
    public function updatePath(int $id, string $path, string $newPath)
    {
        $path .= $id . '/';
        $newPath .= $id . '/';
        $this->getModel()->where($this->getPathField(), 'like', '%/' . $id . '/%')
            ->select($this->getFields())->get()
            ->each(function ($item) use ($path, $newPath) {
                foreach ($this->getFields() as $field) {
                    $method = 'get' . Str::studly($field) . 'Value';
                    if (method_exists($this, $method)) {
                        $item->{$field} = $this->{$method}($item, $path, $newPath);
                    }
                }
                $item->save();
            })->all();
    }

    /**
     * 获取当前id包含的所有下级id.
     * @return array
     * @throws BindingResolutionException
     */
    public function getFrameTotalIds(int $id, int $entid)
    {
        return $this->getModel(false)
            ->where('entid', $entid)
            ->where('path', 'LIKE', '%/' . $id . '/%')
            ->select(['id'])
            ->get()->map(function ($item) {
                return $item['id'];
            })->all();
    }

    /**
     * 获取组织架构所有上级或者下级组织的用户信息和组织架构信息.
     * @return array
     * @throws BindingResolutionException
     */
    public function getFrameUserLevel(int $entid, array $ids, int $level, string $operators = '=')
    {
        $model = $this->getModel(false);
        $table = $model->getTable();
        /** @var FrameAssist $jsonModel */
        $jsonModel = app()->get(FrameAssist::class);
        $joinTabel = $jsonModel->getTable();
        return $model->join($joinTabel, $joinTabel . '.frame_id', '=', $table . '.id')
            ->whereIn($table . '.id', $ids)
            ->where($table . '.entid', $entid)
            ->where($joinTabel . '.is_admin', 1)
            ->where($table . '.level', $operators, $level)
            ->groupBy($table . '.id')
            ->select([$table . '.name', $table . '.id', $table . '.level', $joinTabel . '.*'])
            ->get()
            ->toArray();
    }

    /**
     * 获取部门员工id.
     * @throws BindingResolutionException
     */
    public function getUserIds(array $ids, int $entId): array
    {
        /** @var FrameAssist $jsonModel */
        $jsonModel = app()->get(FrameAssist::class);
        $joinTable = $jsonModel->getTable();

        $model = $this->getModel(false);
        $table = $model->getTable();

        return $model->join($joinTable, $joinTable . '.frame_id', '=', $table . '.id')
            ->where($table . '.entid', $entId)->where($joinTable . '.is_mastart', 1)->whereIn($table . '.id', $ids)
            ->select([DB::raw('GROUP_CONCAT(`user_id`) as `user_id`')])->first()->toArray();
    }

    /**
     * 获取默认部门ID.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getDefaultFrame(int $entId): int
    {
        return (int) $this->search(['entid' => $entId])->where(fn ($q) => $q->where('pid', 0))->value('id');
    }

    /**
     * 设置模型.
     * @return mixed|string
     */
    protected function setModel()
    {
        return Frame::class;
    }
}
