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

namespace App\Http\Model\Assess;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;

/**
 * Class AssessPlan.
 */
class AssessPlan extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'assess_plan';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $casts = [
        'test_frame'   => 'array',
        'test_user'    => 'array',
        'id'           => 'integer',
        'entid'        => 'integer',
        'create_time'  => 'integer',
        'create_month' => 'integer',
        'assess_type'  => 'integer',
        'period'       => 'integer',
        'make_day'     => 'integer',
        'eval_day'     => 'integer',
        'verify_day'   => 'integer',
        'status'       => 'integer',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }
}
