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

use App\Http\Model\User\UserRemindLog;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Traits\Conditionable;

/**
 * Class UserRemindLogDao.
 */
class UserRemindLogDao extends BaseDao
{
    use BatchSearchTrait;

    /**
     * 搜索.
     * @param array|int|string $where
     * @return BaseModel|Conditionable|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function search($where, ?bool $authWhere = null)
    {
        $year    = $where['year'] ?? 0;
        $month   = $where['month'] ?? 0;
        $day     = $where['day'] ?? 0;
        $week    = $where['week'] ?? 0;
        $quarter = $where['quarter'] ?? 0;
        $userId  = $where['user_id'] ?? 0;
        $uids    = $where['uids'] ?? [];
        unset($where['year'], $where['quarter'], $where['month'], $where['day'], $where['week'], $where['user_id'], $where['uids']);
        return parent::search($where, $authWhere)
            ->when($year !== 0, fn ($q) => $q->where('year', $year))
            ->when($month !== 0, fn ($q) => $q->where('month', $month))
            ->when($day !== 0, fn ($q) => $q->where('day', $day))
            ->when($week !== 0, fn ($q) => $q->where('week', $week))
            ->when($quarter !== 0, fn ($q) => $q->where('quarter', $quarter))
            ->when($userId, function ($query) use ($userId) {
                if (is_array($userId)) {
                    $query->whereIn('user_id', $userId);
                } else {
                    $query->where('user_id', $userId);
                }
            })
            ->when($uids, fn ($q) => $q->whereIn('user_id', $uids));
    }

    /**
     * 设置模型.
     * @return mixed
     */
    protected function setModel()
    {
        return UserRemindLog::class;
    }
}
