<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Dao\Warehouse;

use App\Http\Model\Warehouse\WarehouseOrderStockConfig;

/**
 * 订单库存扣减配置 DAO.
 */
class WarehouseOrderStockConfigDao extends WarehouseBaseDao
{
    /**
     * 设置当前 DAO 关联模型.
     */
    protected function setModel()
    {
        return WarehouseOrderStockConfig::class;
    }
}
