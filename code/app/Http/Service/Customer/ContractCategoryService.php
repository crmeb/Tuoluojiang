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

namespace App\Http\Service\Customer;

use App\Http\Dao\Customer\ContractCategoryDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;

/**
 * 客户订单分类业务服务.
 */
class ContractCategoryService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(ContractCategoryDao $dao)
    {
        $this->dao = $dao;
    }
}
