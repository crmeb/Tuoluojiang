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
use App\Http\Model\Attendance\AttendanceArrange;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 考勤排班Dao
 * Class AttendanceArrangeDao.
 */
class AttendanceArrangeDao extends BaseDao
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
        $name = $where['name'] ?? '';
        if (isset($where['name'])) {
            unset($where['name']);
        }
        return parent::search($where, $authWhere)->when($name, fn ($q) => $q->where(function ($q) use ($name) {
            $q->whereIn('group_id', fn ($q) => $q->from('attendance_group')->where('name', 'like', '%' . $name . '%')->select(['id']));
        }));
    }

    protected function setModel(): string
    {
        return AttendanceArrange::class;
    }
}
