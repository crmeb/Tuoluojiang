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

namespace App\Http\Service\Attendance;

use App\Http\Contract\Attendance\RosterCycleInterface;
use App\Http\Dao\Attendance\RosterCycleDao;
use App\Http\Dao\Attendance\RosterCycleShiftDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 排班周期记录
 * Class RosterCycleService.
 */
class RosterCycleService extends BaseService implements RosterCycleInterface
{
    use ResourceServiceTrait;

    protected RosterCycleShiftDao $shiftDao;

    public function __construct(RosterCycleDao $dao, RosterCycleShiftDao $shiftDao)
    {
        $this->dao      = $dao;
        $this->shiftDao = $shiftDao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = ['shifts']): array
    {
        return $this->dao->getList($where, $field, 0, 0, $sort, $with);
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveCycle(array $data, int $userId): mixed
    {
        $this->checkShift((int) $data['cycle'], (int) $data['group_id'], $data['shifts']);
        return $this->transaction(function () use ($data, $userId) {
            $res = $this->dao->create(['group_id' => $data['group_id'], 'cycle' => $data['cycle'], 'name' => $data['name']]);
            if (! $res) {
                throw $this->exception(__('common.insert.fail'));
            }
            foreach ($data['shifts'] as $key => $shiftId) {
                $this->shiftDao->create([
                    'number'   => $key + 1,
                    'cycle_id' => $res->id,
                    'shift_id' => $shiftId,
                    'uid'      => $userId,
                ]);
            }

            return $res;
        });
    }

    /**
     * 核对周期班次
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function checkShift(int $cycle, int $groupId, array $shifts): void
    {
        if ($cycle !== count($shifts)) {
            throw $this->exception('排班周期班次异常');
        }
        $shiftIds      = array_column($shifts, 'shift_id');
        $groupShiftIds = app()->get(AttendanceGroupService::class)->getShiftIds($groupId);
        foreach ($shiftIds as $shift) {
            if (! in_array($shift, $groupShiftIds) && (int) $shift !== 0) {
                throw $this->exception('考勤班次异常');
            }
        }
    }

    /**
     * 修改.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateCycle(int $id, array $data, int $userId): mixed
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        $this->checkShift((int) $data['cycle'], (int) $info->group_id, $data['shifts']);
        return $this->transaction(function () use ($info, $data, $userId) {
            $info->name  = $data['name'];
            $info->cycle = $data['cycle'];
            $info->save();
            $this->shiftDao->forceDelete(['cycle_id' => $info->id]);
            foreach ($data['shifts'] as $key => $shiftId) {
                $this->shiftDao->create([
                    'number'   => $key + 1,
                    'cycle_id' => $info->id,
                    'shift_id' => $shiftId,
                    'uid'      => $userId,
                ]);
            }
            return true;
        });
    }

    /**
     * 删除.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteCycle(array|int $id): bool
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }
        return $this->transaction(function () use ($id) {
            return $this->dao->forceDelete(['id' => $id]) && $this->shiftDao->forceDelete(['cycle_id' => $id]);
        });
    }

    /**
     * 批量删除考勤周期
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteByGroupId(int $groupId): bool
    {
        $ids = $this->dao->column(['group_id' => $groupId], 'id');
        if ($ids) {
            return $this->dao->delete(['id' => $ids]) && $this->shiftDao->delete(['cycle_id' => $ids]);
        }
        return true;
    }

    /**
     * 周期详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfo(int $groupId, int $id): array
    {
        $info = $this->dao->get(['group_id' => $groupId, 'id' => $id], ['*'], ['shifts']);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        return toArray($info);
    }

    /**
     * 检测班次是否使用.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function checkShiftExist(int $shiftId): bool
    {
        return $this->shiftDao->exists(['shift_id' => $shiftId]);
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getArrangeRosterCycleList(array $where, array $field = ['*'], $sort = null): array
    {
        $with = ['shifts' => fn ($q) => $q->withTrashedParents()];
        return $this->dao->setTrashed()->getList($where, $field, 0, 0, $sort, $with);
    }
}
