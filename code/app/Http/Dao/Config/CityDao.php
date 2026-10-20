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

namespace App\Http\Dao\Config;

use App\Http\Model\Config\City;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 城市数据
 * Class CityDao.
 */
class CityDao extends BaseDao
{
    use BatchSearchTrait;

    /**
     * 获取省市区.
     * @param array|string[] $field
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCityList(array $where = [], array $field = ['*'])
    {
        return $this->search($where)->select($field)->get()->toArray();
    }

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return City::class;
    }
}
