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

namespace App\Http\Dao\Auth;

use App\Http\Model\System\Menus;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Tuoluojiang\Open\traits\dao\PathUpdateTrait;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 菜单
 * Class SystemMenusDao.
 */
class SystemMenusDao extends BaseDao
{
    use ListSearchTrait;
    use TogetherSearchTrait;
    use PathUpdateTrait;

    /**
     * 获取菜单列表.
     * @param array|string[] $field
     * @param null|mixed $sort
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getMenusList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null)
    {
        return $this->getList($where, $field, $page, $limit, $sort);
    }

    /**
     * 获取个人中心菜单权限.
     * @param array|string[] $field
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUserMenusList(array $where, array $field = ['*'])
    {
        return $this->getList($where, $field);
    }

    public function getModelMenusName($key = 'list', $other = [])
    {
        $menuPaths  = $this->getModel()->where('menu_path', 'like', '/crud/module/%/' . $key)->select(['menu_path'])->get()?->toArray();
        $tableNames = [];
        foreach ($menuPaths as $path) {
            $tableNames[] = str_replace(['/admin/crud/module/', '/list', ''], '', $path['menu_path']);
        }
        return array_diff($tableNames, $other);
    }

    /**
     * 设置模型.
     * @return mixed|string
     */
    protected function setModel()
    {
        return Menus::class;
    }
}
