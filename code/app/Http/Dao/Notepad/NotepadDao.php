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

namespace App\Http\Dao\Notepad;

use App\Http\Model\Notepad\Notepad;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Support\Carbon;

/**
 * NotepadDao.
 */
class NotepadDao extends BaseDao
{
    use ListSearchTrait;
    use BatchSearchTrait;

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
        return $this->setTimeField('edit_time')->search($where)->select($field)->when($page && $limit, function ($query) use ($page, $limit) {
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
     * 分组列表.
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
    public function getGroupList(array $where = [], array $field = ['*'], int $page = 0, int $limit = 0, array|string $sort = 'updated_at', array $with = [], bool $cutContent = true): array
    {
        $time         = Carbon::now(config('app.timezone'));
        $currentMonth = date('Y-m', $time->getTimestamp());
        $lastMonth    = date('Y-m', $time->subMonth()->getTimestamp());
        return $this->search($where)->selectRaw("DATE_FORMAT(updated_at,'%Y-%m') as month")
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->groupBy('month')->orderByDesc('month')->get()->each(function (&$value) use ($where, $field, $sort, $with, $cutContent, $currentMonth, $lastMonth) {
                $list = $this->getList(array_merge($where, ['updated_at' => $value['month']]), $field, 0, 0, $sort, $with);
                if ($cutContent) {
                    foreach ($list as &$item) {
                        $item['content'] = mb_substr(strip_tags($item['content']), 0, 60);
                    }
                }
                $value['data']  = $list;
                $value['month'] = match ($value['month']) {
                    $lastMonth    => '上个月',
                    $currentMonth => '本月',
                    default       => date('Y年m月', datetime_timestamp($value['month']))
                };
            })->toArray();
    }

    /**
     * 分组条数.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupCount(array $where = []): int
    {
        return $this->search($where)->selectRaw("DATE_FORMAT(updated_at,'%Y-%m') as month")->groupBy('month')->get()->count();
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
    public function search($where, ?bool $authWhere = null)
    {
        $uid   = $where['uid'] ?? '';
        $pid   = $where['pid'] ?? 0;
        $title = $where['title'] ?? '';
        return parent::search($where, $authWhere)->when($title && $pid, function ($query) use ($title, $pid, $uid) {
            $query->orWhere(function ($query) use ($title, $pid, $uid) {
                $query->where(function ($query) use ($title) {
                    $query->where('title', 'like', '%' . $title . '%')->orWhere('content', 'like', '%' . $title . '%');
                })->whereIn('pid', function ($query) use ($pid, $uid) {
                    $query->from('user_memorial_category')->where('uid', $uid)->where('path', 'like', "%/{$pid}/%")->select(['id']);
                });
            });
        });
    }

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return Notepad::class;
    }
}
