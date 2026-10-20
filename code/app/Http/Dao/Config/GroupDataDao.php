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

namespace App\Http\Dao\Config;

use App\Http\Model\Config\GroupData;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class GroupDataDao.
 */
class GroupDataDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;

    /**
     * 获取组合数据.
     * @param array|string[] $field
     * @param int $limit
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getGroupDataList(array $where, array $field = ['*'], int $page = 0, $limit = 0)
    {
        return $this->search($where)->select($field)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->orderByDesc('sort')->get()->toArray();
    }

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return GroupData::class;
    }
}
