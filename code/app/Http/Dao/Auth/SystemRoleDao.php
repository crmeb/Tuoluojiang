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

use App\Http\Model\System\Role;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 权限规则
 * Class SystemRoleDao.
 */
class SystemRoleDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;

    /**
     * 获取权限.
     * @return mixed
     */
    public function getRoule(array $where = [], ?string $field = null, ?string $key = null)
    {
        return $this->column($where, $field ?: 'role_name', $key ?: 'id');
    }

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
     * @return mixed|string
     */
    protected function setModel()
    {
        return Role::class;
    }
}
