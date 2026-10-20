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

use App\Constants\AttendanceGroupEnum;
use App\Http\Model\Attendance\AttendanceGroup;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 考勤组Dao
 * Class AttendanceGroupDao.
 */
class AttendanceGroupDao extends BaseDao
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

        $member = $where['member'] ?? 0;
        if (isset($where['member'])) {
            unset($where['member']);
        }

        $frame = $where['frame'] ?? 0;
        if (isset($where['frame'])) {
            unset($where['frame']);
        }
        return parent::search($where, $authWhere)->when($name, fn ($q) => $q->where(function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%')
                ->orWhereIn('id', fn ($q) => $q->from('attendance_group_member')->where('type', AttendanceGroupEnum::MEMBER)
                    ->whereIn('member', fn ($q) => $q->from('admin')->where('name', 'like', '%' . $name . '%')->select(['id']))->select(['group_id']))
                ->orWhereIn('id', fn ($q) => $q->from('attendance_group_member')->where('type', AttendanceGroupEnum::FRAME)
                    ->whereIn('member', fn ($q) => $q->from('frame')->where('name', 'like', '%' . $name . '%')->select(['id']))->select(['group_id']));
        }))->when($member, fn ($q) => $q->where(function ($q) use ($member) {
            $q->whereIn('id', fn ($q) => $q->from('attendance_group_member')->where('type', AttendanceGroupEnum::MEMBER)->whereNull('deleted_at')->where('member', $member)->select(['group_id']));
        }))->when($frame, fn ($q) => $q->where(function ($q) use ($frame) {
            $q->whereIn('id', fn ($q) => $q->from('attendance_group_member')->where('type', AttendanceGroupEnum::FRAME)->whereNull('deleted_at')->where('member', $frame)->select(['group_id']));
        }));
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return AttendanceGroup::class;
    }
}
