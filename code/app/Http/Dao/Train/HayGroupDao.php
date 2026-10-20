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

namespace App\Http\Dao\Train;

use App\Http\Model\Company\HayGroup;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;

/**
 * 评估表Dao.
 * Class HayGroupDao.
 */
class HayGroupDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * 搜索.
     *
     * @param array|int|string $where
     *
     * @return BaseModel|BuildsQueries|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function search($where, ?bool $authWhere = null): mixed
    {
        $name = $where['name'] ?? '';
        unset($where['name']);
        return parent::search($where, $authWhere)
            ->when($name, function ($q) use ($name) {
                $q->where(function ($q) use ($name) {
                    $q->where('name', 'like', '%' . $name . '%')
                        ->orWhereIn('id', fn ($q) => $q->from('hay_group_data')
                            ->whereIn('col1', fn ($q) => $q->from('rank_job')
                                ->where('name', 'like', '%' . $name . '%')
                                ->select(['id']))->select(['group_id']));
                });
            });
    }

    protected function setModel(): string
    {
        return HayGroup::class;
    }
}
