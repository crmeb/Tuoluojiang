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

namespace App\Http\Model\Customer;

use App\Http\Model\BaseModel;
use Illuminate\Database\Query\Builder;

/**
 * 客户标签关联表
 * Class ClientLabels.
 */
class LabelRelation extends BaseModel
{
    /**
     * @var string
     */
    protected $id = 'id';

    /**
     * @var string
     */
    protected $table = 'client_labels';

    /**
     * 客户ID作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeEid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('eid', $value);
        } elseif ($value !== '') {
            $query->where('eid', $value);
        }
    }

    /**
     * 标签ID作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopeLabelId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('label_id', $value);
        } elseif ($value !== '') {
            $query->where('label_id', $value);
        }
    }
}
