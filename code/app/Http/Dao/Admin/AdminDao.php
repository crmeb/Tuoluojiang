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

namespace App\Http\Dao\Admin;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Admin\AdminInfo;
use App\Http\Model\Frame\FrameAssist;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AdminDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;
    use TogetherSearchTrait;
    use JoinSearchTrait;

    /**
     * 用户名片列表.
     * @param null|mixed $sort
     * @return Builder
     * @throws BindingResolutionException
     */
    public function adminListSearch(array $where, array $field = ['*'], int $page = 1, int $limit = 15, $sort = null, array $with = [])
    {
        $assistService = app()->get(FrameAssistService::class);
        return $this->getJoinModel('id', 'id', type: 'left')
            ->when(isset($where['work_time']) && $where['work_time'], function ($query) use ($where) {
                if (str_contains($where['work_time'], '-')) {
                    [$startTime, $endTime] = explode('-', $where['work_time']);
                    $startTime             = str_replace('/', '-', trim($startTime));
                    $endTime               = str_replace('/', '-', trim($endTime));
                    if (! str_contains($startTime, ':') && ! str_contains($endTime, ':')) {
                        $endDate = Carbon::parse($endTime)->timezone(config('app.timezone'))->addDay()->toDateString();
                        $query->whereDate($this->getFiled('work_time', $this->aliasB), '>=', $startTime)->whereDate($this->getFiled('work_time', $this->aliasB), '<', $endDate);
                    }
                    if ($startTime && $endTime && $startTime != $endTime) {
                        $query->whereBetween($this->getFiled('work_time', $this->aliasB), [$startTime, $endTime]);
                    }
                    if ($startTime && $endTime && $startTime == $endTime) {
                        $query->whereBetween($this->getFiled('work_time', $this->aliasB), [$startTime, date('Y-m-d H:i:s', datetime_timestamp($endTime) + 86400)]);
                    }
                    if (! $startTime && $endTime) {
                        $query->whereTime($this->getFiled('work_time', $this->aliasB), '<', $endTime);
                    }
                    if ($startTime && ! $endTime) {
                        $query->whereTime($this->getFiled('work_time', $this->aliasB), '>=', $startTime);
                    }
                }
            })
            ->when(isset($where['quit_time']) && $where['quit_time'], function ($query) use ($where) {
                if (str_contains($where['quit_time'], '-')) {
                    [$startTime, $endTime] = explode('-', $where['quit_time']);
                    $startTime             = str_replace('/', '-', trim($startTime));
                    $endTime               = str_replace('/', '-', trim($endTime));
                    if (! str_contains($startTime, ':') && ! str_contains($endTime, ':')) {
                        $endDate = Carbon::parse($endTime)->timezone(config('app.timezone'))->addDay()->toDateString();
                        $query->whereDate($this->getFiled('quit_time', $this->aliasB), '>=', $startTime)->whereDate($this->getFiled('quit_time', $this->aliasB), '<', $endDate);
                    }
                    if ($startTime && $endTime && $startTime != $endTime) {
                        $query->whereBetween($this->getFiled('quit_time', $this->aliasB), [$startTime, $endTime]);
                    }
                    if ($startTime && $endTime && $startTime == $endTime) {
                        $query->whereBetween($this->getFiled('quit_time', $this->aliasB), [$startTime, date('Y-m-d H:i:s', datetime_timestamp($endTime) + 86400)]);
                    }
                    if (! $startTime && $endTime) {
                        $query->whereTime($this->getFiled('quit_time', $this->aliasB), '<', $endTime);
                    }
                    if ($startTime && ! $endTime) {
                        $query->whereTime($this->getFiled('quit_time', $this->aliasB), '>=', $startTime);
                    }
                }
            })
            ->when(isset($where['types']) && $where['types'], function ($query) use ($where) {
                if (is_array($where['types'])) {
                    $query->whereIn($this->getFiled('type', $this->aliasB), $where['types']);
                } else {
                    $query->where($this->getFiled('type', $this->aliasB), $where['types']);
                }
            })
            ->when(isset($where['ids']) && $where['ids'] !== '', function ($query) use ($where) {
                $query->whereIn($this->getFiled('id'), $where['ids']);
            })
            ->when(isset($where['education']) && $where['education'] !== '', function ($query) use ($where) {
                is_array($where['education']) ? $query->whereIn($this->getFiled('education', $this->aliasB), $where['education'])
                : $query->where($this->getFiled('education', $this->aliasB), $where['education']);
            })
            ->when(isset($where['sex']) && $where['sex'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('sex', $this->aliasB), $where['sex']);
            })
            ->when(isset($where['is_part']) && $where['is_part'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('is_part', $this->aliasB), $where['is_part']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('status'), $where['status']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('type', $this->aliasB), $where['type']);
            })
            ->when(isset($where['frame_id']) && $where['frame_id'], function ($query) use ($where, $assistService) {
                $query->whereIn($this->getFiled('id', $this->aliasB), $assistService->setTrashed()->column(['frame_id' => $where['frame_id']], 'user_id'));
            })
            ->when(isset($where['search']) && $where['search'], function ($query) use ($where) {
                $query->where(function ($q) use ($where) {
                    $q->orWhere('name', 'LIKE', "%{$where['search']}%")
                        ->orWhere('phone', 'LIKE', "%{$where['search']}%")
                        ->orWhere('account', 'LIKE', "%{$where['search']}%");
                });
            })
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })
            ->when($sort = sort_mode($sort), function ($query) use ($sort) {
                if (is_array($sort)) {
                    foreach ($sort as $k => $v) {
                        if (is_numeric($k)) {
                            $query->orderByDesc($v);
                        } else {
                            $query->orderBy(str_contains($k, '.') ? $k : $this->getFiled($k, $this->aliasB), $v);
                        }
                    }
                } else {
                    $query->orderByDesc(str_contains($sort, '.') ? $sort : $this->getFiled($sort, $this->aliasB));
                }
            })
            ->select($field)
            ->with($with);
    }

    /**
     * 获取管理员信息.
     * @return null|Builder|Model|object
     * @throws BindingResolutionException
     */
    public function adminInfo(int $id, array $with = [])
    {
        return $this->getJoinModel('id', 'id', type: 'left')->where($this->getFiled('id', $this->aliasA), $id)->with($with)->first();
    }

    /**
     * 获取管理员列表.
     * @param null|mixed $sort
     * @return Builder
     * @throws BindingResolutionException
     */
    public function listSearch(array $where, int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        return $this->getJoinModel('id', 'id', type: 'left')
            ->rightJoin('frame_assist', 'admin.id', '=', 'frame_assist.user_id')
            ->when(isset($where['name']) && $where['name'], function ($query) use ($where) {
                $query->where(function ($q) use ($where) {
                    $q->where('name', 'LIKE', "%{$where['name']}%")->orWhere('phone', 'LIKE', "%{$where['name']}%");
                });
            })
            ->when(isset($where['frame_id']) && $where['frame_id'], function ($query) use ($where) {
                $query->where(function ($que) use ($where) {
                    $que->whereIn('frame_assist.frame_id', $where['frame_ids'])->orWhere('frame_assist.frame_id', $where['frame_id']);
                });
            })
            ->when(isset($where['types']) && $where['types'], function ($query) use ($where) {
                if (is_array($where['types'])) {
                    $query->whereIn($this->getFiled('type', $this->aliasB), $where['types']);
                } else {
                    $query->where($this->getFiled('type', $this->aliasB), $where['types']);
                }
            })
            ->when(isset($where['ids']) && $where['ids'] !== '', function ($query) use ($where) {
                $query->whereIn($this->getFiled('id'), $where['ids']);
            })
            ->when(isset($where['job']) && $where['job'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('job'), $where['job']);
            })
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })
            ->when($sort = sort_mode($sort), function ($query) use ($sort) {
                if (is_array($sort)) {
                    foreach ($sort as $k => $v) {
                        if (is_numeric($k)) {
                            $query->orderByDesc($v);
                        } else {
                            $query->orderBy(str_contains($k, '.') ? $k : $this->getFiled($k, $this->aliasA), $v);
                        }
                    }
                } else {
                    $query->orderByDesc(str_contains($sort, '.') ? $sort : $this->getFiled($sort, $this->aliasB));
                }
            })
            ->distinct()
            ->with($with)->select([$this->getFiled('*', $this->aliasA), $this->getFiled('work_time as join_time', $this->aliasB)]);
    }

    /**
     * 根据用户ID获取用户UID.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function uidToUuid(int $id): string
    {
        return $this->getModel()->where($this->getPk(), $id)->value('uid') ?: '';
    }

    /**
     * 根据企业用户ID获取名片ID.
     */
    public function uidToCardId(int $id): int
    {
        return $id;
    }

    /**
     * 根据uid获取企业用户ID.
     * @throws BindingResolutionException
     */
    public function uuidToUid(string $uuid, int $entid = 1): int
    {
        return $this->getModel()->where('uid', $uuid)->value('id') ?: 0;
    }

    /**
     * 根据uid获取企业用户名片ID.
     * @throws BindingResolutionException
     */
    public function uuidToCardid(string $uuid, int $entid = 1): int
    {
        return $this->getModel()->where('uid', $uuid)->value('id') ?: 0;
    }

    /**
     * 根据名片ID获取用户ID.
     */
    public function cardToUid(int $cardId): int
    {
        return $cardId;
    }

    /**
     * @return null|array
     * @throws BindingResolutionException
     */
    public function info(int $id, array $field = ['*'])
    {
        return $this->getJoinModel('id', 'id', type: 'left')->where($this->getFiled('id'), $id)
            ->select($field)->firstOrFail()?->toArray();
    }

    /**
     * @param mixed $where
     * @return BaseModel
     * @throws BindingResolutionException
     */
    public function joinSearch($where = [])
    {
        return $this->getJoinModel('id', 'id', type: 'left')
            ->when(isset($where['types']) && $where['types'], function ($query) use ($where) {
                if (is_array($where['types'])) {
                    $query->whereIn($this->getFiled('type', $this->aliasB), $where['types']);
                } else {
                    $query->where($this->getFiled('type', $this->aliasB), $where['types']);
                }
            })
            ->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
                is_array($where['id']) ? $query->whereIn($this->getFiled('id'), $where['id']) : $query->where($this->getFiled('id'), $where['id']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('status'), $where['status']);
            })
            ->when(isset($where['search']) && $where['search'], function ($query) use ($where) {
                $query->where(function ($q) use ($where) {
                    $q->orWhere('name', 'LIKE', "%{$where['search']}%")
                        ->orWhere('phone', 'LIKE', "%{$where['search']}%")
                        ->orWhere('account', 'LIKE', "%{$where['search']}%");
                });
            });
    }

    public function userIdByUserInfo(array $userId)
    {
        return $this->getModel()->whereIn('id', $userId)->select(['id', 'phone', 'avatar', 'name'])->get()->toArray();
    }

    protected function setModel(): string
    {
        return Admin::class;
    }

    protected function setModelB(): string
    {
        return AdminInfo::class;
    }

    protected function setModelC(): string
    {
        return FrameAssist::class;
    }
}
