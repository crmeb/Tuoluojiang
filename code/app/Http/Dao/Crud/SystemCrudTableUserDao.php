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

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrudTableUser;
use App\Http\Dao\BaseDao;

/**
 * Class SystemCrudTableUserDao.
 * @email 136327134@qq.com
 * @date 2024/3/9
 */
class SystemCrudTableUserDao extends BaseDao
{
    /**
     * @return string
     * @email 136327134@qq.com
     * @date 2024/3/9
     */
    protected function setModel()
    {
        return SystemCrudTableUser::class;
    }
}
