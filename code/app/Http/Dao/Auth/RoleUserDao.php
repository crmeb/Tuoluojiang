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

use App\Http\Model\Auth\RoleUser;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Tuoluojiang\Open\traits\dao\GroupDateSearchTrait;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class RoleUser.
 */
class RoleUserDao extends BaseDao
{
    use BatchSearchTrait;
    use ListSearchTrait;
    use GroupDateSearchTrait;

    /**
     * 获取角色ID.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getRoleIds(array $where)
    {
        return $this->search($where)->select('role_id')->groupBy('role_id')->get()->map(function ($item) {
            return $item['role_id'];
        })->toArray();
    }

    public function getCount($where)
    {
        return $this->search($where)->distinct()->count('user_id');
    }

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return RoleUser::class;
    }
}
