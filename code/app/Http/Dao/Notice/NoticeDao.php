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

namespace App\Http\Dao\Notice;

use App\Http\Model\News\News;
use App\Http\Model\News\NewsVisit;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class NoticeDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 日期分组列表.
     * @param array $where 条件
     * @param array|string[] $field 显示字段
     * @param int $page 页码
     * @param int $limit 展示条数
     * @param null|array|string $sort 排序
     * @param array $with 关联
     * @param mixed $reads
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getGroupList(array $where = [], array $field = ['*'], int $page = 0, int $limit = 0, null|array|string $sort = null, array $with = [], $reads = []): array
    {
        return $this->search($where)->selectRaw("DATE_FORMAT(push_time,'%Y-%m-%d') as days")
            ->groupBy('days')->when($page && $limit, function ($query) use ($page, $limit) {
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
            })->get()->each(function (&$value) use ($where, $field, $sort, $with, $reads) {
                $where['equal_push_time'] = $value['days'];
                if (isset($where['is_new'])) {
                    unset($where['is_new']);
                }
                $list = $this->getList($where, $field, 0, 0, $sort, $with);
                if (! empty($list)) {
                    foreach ($list as &$item) {
                        if (in_array($item['id'], $reads)) {
                            $item['is_read'] = 1;
                        } else {
                            $item['is_read'] = 0;
                        }
                    }
                }
                $value['data'] = $list;
            })->toArray();
    }

    /**
     * 全部选项列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function noticeList(array $where, array $field = ['enterprise_notice.*'], int $page = 0, int $limit = 0, array $sort = ['push_time'], array $with = []): array
    {
        $userId = $where['user_id'] ?? 0;
        if (isset($where['user_id'])) {
            unset($where['user_id']);
        }

        /** @var NewsVisit $joinModel */
        $joinModel = app()->get(NewsVisit::class);
        $joinTable = $joinModel->getTable();
        $prefix    = Config::get('database.connections.mysql.prefix');

        return $this->search($where)->where(function ($query) {})->select($field)->selectRaw("IF({$prefix}{$joinTable}.`notice_id`, 1, 0) as `is_read`")
            ->leftJoin($joinTable, fn ($q) => $q->on($joinTable . '.notice_id', '=', 'enterprise_notice.id')->where($joinTable . '.user_id', $userId))
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->orderBy('is_read')
            ->when($sort, function ($query) use ($sort) {
                foreach ($sort as $item) {
                    $query->orderByDesc($item);
                }
            })->with($with)->get()->toArray();
    }

    /**
     * 设置模型.
     * @return string
     */
    protected function setModel()
    {
        return News::class;
    }
}
