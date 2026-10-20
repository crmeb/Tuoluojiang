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

namespace App\Http\Dao\Customer;

use App\Http\Model\Customer\LabelRelation;
use App\Http\Dao\BaseDao;

/**
 * 客户标签关联.
 */
class LabelRelationDao extends BaseDao
{
    public function countByLabelIds(array $labelIds): int
    {
        if (! $labelIds) {
            return 0;
        }

        return $this->getModel()->whereIn('label_id', $labelIds)->count();
    }

    public function deleteByLabelIds(array $labelIds): int
    {
        if (! $labelIds) {
            return 0;
        }

        return $this->getModel()->whereIn('label_id', $labelIds)->delete();
    }

    protected function setModel()
    {
        return ClientLabels::class;
    }
}
