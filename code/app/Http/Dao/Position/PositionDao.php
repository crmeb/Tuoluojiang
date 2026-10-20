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

namespace App\Http\Dao\Position;

use App\Http\Model\Position\Position;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\interfaces\ResourceDaoInterface;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * 企业职级
 * Class PositionDao.
 */
class PositionDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;

    protected $each = false;

    /**
     * @return mixed|string
     */
    protected function setModel()
    {
        return Position::class;
    }
}
