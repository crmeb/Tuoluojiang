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

namespace App\Http\Dao\Attendance;

use App\Http\Dao\enterprise\attendance\BaseModel;
use App\Http\Dao\enterprise\attendance\BuildsQueries;
use App\Http\Model\Attendance\AttendanceShift;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 考勤班次Dao
 * Class AttendanceShiftDao.
 */
class AttendanceShiftDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 搜索.
     *
     * @param array|int|string $where
     *
     * @return BaseModel|BuildsQueries|mixed
     * @throws \ReflectionException*@throws BindingResolutionException
     * @throws BindingResolutionException
     */
    public function search($where, ?bool $authWhere = null): mixed
    {
        $name = $where['name_like'] ?? '';
        if (isset($where['name_like'])) {
            unset($where['name_like']);
        }

        $groupId = $where['group_id'] ?? '';
        if (isset($where['group_id'])) {
            unset($where['group_id']);
        }
        return parent::search($where, $authWhere)
            ->when($name, function ($q) use ($name) {
                $q->where(function ($q) use ($name) {
                    $q->where('name', 'like', '%' . $name . '%')
                        ->orWhereIn('uid', fn ($q) => $q->from('admin')->where('name', 'like', '%' . $name . '%')->select(['id']));
                });
            })->when($groupId, function ($q) use ($groupId) {
                $q->whereIn('id', fn ($q) => $q->from('attendance_group_shift')->where('group_id', $groupId)->select(['shift_id']))
                    ->orWhere('id', 1);
            });
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return AttendanceShift::class;
    }
}
