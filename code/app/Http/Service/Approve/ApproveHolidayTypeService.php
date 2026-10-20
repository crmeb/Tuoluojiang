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

namespace App\Http\Service\Approve;

use App\Http\Dao\Approve\ApproveHolidayTypeDao;
use App\Http\Service\Admin\AdminInfoService;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 假期类型.
 * class ApproveHolidayTypeService.
 */
class ApproveHolidayTypeService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(ApproveHolidayTypeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param mixed $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = 'sort', array $with = []): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveHolidayType(array $data): mixed
    {
        $this->checkName($data['name']);
        return $this->dao->create($data);
    }

    /**
     * 修改.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateHolidayType(int $id, array $data): mixed
    {
        $this->checkName($data['name'], $id);
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('类型不存在');
        }
        return $this->dao->update($id, $data);
    }

    /**
     * 删除.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteHolidayType(int $id): bool|int
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('类型不存在');
        }

        return $this->dao->delete(['id' => $id]);
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfo(array $where, array $field = ['*']): array
    {
        $info = $this->dao->get($where, $field);
        if (! $info) {
            throw $this->exception('类型不存在');
        }
        return $info->toArray();
    }

    /**
     * 下拉数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSelectList(int $uid): array
    {
        $list        = [];
        $work_time   = app()->get(AdminInfoService::class)->value($uid, 'work_time') ?: now()->toDateString();
        $tz          = config('app.timezone');
        $monthNumber = Carbon::parse($work_time, $tz)->diffInMonths(Carbon::now($tz), false);
        $field       = ['id as value', 'name as label', 'duration_type', 'new_employee_limit', 'new_employee_limit_month'];
        $typeList    = $this->dao->getList([], $field, sort: 'sort');
        foreach ($typeList as $item) {
            if ($item['new_employee_limit'] && (! $work_time || $item['new_employee_limit_month'] > $monthNumber)) {
                continue;
            }
            $list[] = ['value' => $item['value'], 'label' => $item['label'], 'duration_type' => $item['duration_type']];
        }
        return $list;
    }

    /**
     * 获取统计相关假期类型数据.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getStatisticsListWithDestroy(array $ids): array
    {
        $list = $this->getTypeList(['id', 'name', 'duration_type']);
        if ($ids) {
            $filterNormal = [];
            $deletedData  = $this->dao->setTrashed()->getList(['filter_normal' => true], ['id', 'name', 'duration_type']);
            foreach ($deletedData as $deletedDatum) {
                $filterNormal[$deletedDatum['id']] = $deletedDatum;
            }

            foreach ($ids as $leaveHolidayTypeId) {
                if (isset($filterNormal[$leaveHolidayTypeId])) {
                    $list[] = $filterNormal[$leaveHolidayTypeId];
                }
            }
        }

        return $list;
    }

    /**
     * 获取类型列表.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTypeList(array $field = ['id', 'name']): array
    {
        return $this->dao->getList([], $field);
    }

    /**
     * 核对名称.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function checkName(string $name, int $id = 0): void
    {
        $where = ['name' => $name];
        if ($id) {
            $where['not_id'] = $id;
        }

        $this->dao->exists($where) && throw $this->exception('类型名称重复');
    }
}
