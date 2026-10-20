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

namespace App\Http\Service\Customer;

use App\Http\Dao\Customer\CustomerProductAttrValueDao;
use App\Http\Dao\Customer\ProductAttrValueDao;
use App\Http\Service\BaseService;

/**
 * 产品属性值service
 * @mixin ProductAttrValueDao
 */
class ProductAttrValueService extends BaseService
{
    public function __construct(ProductAttrValueDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取关联列表.
     * @param null|mixed $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->joinSearch($where, $page, $limit, $with, $sort)->get()?->toArray();
        $count          = $this->dao->joinSearch($where)->count();
        return $this->listData($list, $count);
    }
}
