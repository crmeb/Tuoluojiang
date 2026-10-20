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

namespace App\Http\Dao\Auth;

use App\Http\Model\Auth\Role;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 企业角色
 * Class RoleDao.
 * @method bool insert(array $data) 插入数据
 */
class RoleDao extends BaseDao
{
    use BatchSearchTrait;
    use TogetherSearchTrait;

    /**
     * 获取管理员身份列表.
     * @param array|string[] $field
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRoleList(array $where, array $field = ['*'], array $with = [])
    {
        return $this->search($where)->select($field)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->orderByDesc('id')->get()->toArray();
    }

    /**
     * 设置模型.
     * @return mixed|string
     */
    protected function setModel()
    {
        return Role::class;
    }
}
