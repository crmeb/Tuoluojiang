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

namespace App\Http\Model\Assess;

use App\Http\Model\BaseModel;

/**
 * Class AssessScore.
 */
class AssessScore extends BaseModel
{
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'assess_score';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'      => 'integer',
        'entid'   => 'integer',
        'user_id' => 'integer',
        'min'     => 'integer',
        'max'     => 'integer',
        'level'   => 'integer',
    ];

    public function scopeScore($query, $value)
    {
        if ($value !== '') {
            $query->where('min', '<', $value)->where('max', '>=', $value);
        }
    }

    public function scopeEntid($query, $value)
    {
        if ($value !== '') {
            $query->where('entid', $value);
        }
    }

    public function scopeLevel($query, $value)
    {
        if ($value !== '') {
            $query->where('level', $value);
        }
    }
}
