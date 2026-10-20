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

namespace App\Http\Dao\User;

use App\Http\Model\User\UserMemorialCategory;
use App\Http\Service\User\UserMemorialService;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;

/**
 * Class UserMemorialCategoryDao.
 */
class UserMemorialCategoryDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 是否携带子文件夹数量.
     */
    public bool $withListCount = true;

    /**
     * 是否携带子文件夹数量.
     */
    public function setWithListCount(bool $withListCount): UserMemorialCategoryDao
    {
        $this->withListCount = $withListCount;
        return $this;
    }

    /**
     * 基础查询列表(通用).
     * @param array $where 条件
     * @param array|string[] $field 显示字段
     * @param int $page 页码
     * @param int $limit 展示条数
     * @param null|array|string $sort 排序
     * @param array $with 关联
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        /** @var UserMemorialService $service */
        $service = app()->get(UserMemorialService::class);
        $list    = $this->search($where)->select($field)
            ->when($limit, function ($query) use ($limit) {
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
        if ($this->withListCount) {
            $list = $list->each(function ($item) use ($service) {
                $item['count'] = $service->count(['uid' => $item['uid'], 'pid' => $item['id']]);
            });
        }
        return $list;
    }

    /**
     * 搜索.
     *
     * @param array|int|string $where
     *
     * @return BaseModel|BuildsQueries|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function uniSearch($where, ?bool $authWhere = null)
    {
        $uid  = $where['uid'] ?? '';
        $pid  = $where['pid'] ?? 0;
        $name = $where['name_like'] ?? '';
        return parent::search($where, $authWhere)->when($name && $pid, function ($query) use ($name, $pid, $uid) {
            $query->orWhere(function ($query) use ($name, $pid, $uid) {
                $query->where(function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })->whereIn('pid', function ($query) use ($pid, $uid) {
                    $query->from('user_memorial_category')->where('uid', $uid)->where('path', 'like', "%/{$pid}/%")
                        ->select(['id']);
                });
            });
        });
    }

    /**
     * 移动端基础查询列表.
     * @param array $where 条件
     * @param array|string[] $field 显示字段
     * @param int $page 页码
     * @param int $limit 展示条数
     * @param null|array|string $sort 排序
     * @param array $with 关联
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUniList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        /** @var UserMemorialService $service */
        $service = app()->get(UserMemorialService::class);
        $list    = $this->uniSearch($where)->select($field)
            ->when($limit, function ($query) use ($limit) {
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
        if ($this->withListCount) {
            $list = $list->each(function ($item) use ($service) {
                $item['count'] = $service->count(['uid' => $item['uid'], 'pid' => $item['id']]);
            });
        }
        return $list;
    }

    /**
     * 移动端基础查询条数.
     * @param array $where 条件
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUniCount(array $where): int
    {
        return $this->uniSearch($where)->count();
    }

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return UserMemorialCategory::class;
    }
}
