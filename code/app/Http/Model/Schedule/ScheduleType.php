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

namespace App\Http\Model\Schedule;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;

/**
 * 日程类型表.
 */
class ScheduleType extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'schedule_type';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'entid'      => 'integer',
        'sort'       => 'integer',
        'is_public'  => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeUidLike($query, $value)
    {
        if ($value) {
            $query->where(fn ($q) => $q->orWhere('uid', $value)->orWhere('uid', ''));
        }
    }

    public function scopeUseridLike($query, $value)
    {
        if ($value) {
            $query->where(fn ($q) => $q->orWhere('user_id', $value)->orWhere('user_id', 0));
        }
    }

    public function scopeEntLike($query, $value)
    {
        if ($value) {
            $query->where(fn ($q) => $q->orWhere('entid', $value)->orWhere('entid', 0));
        }
    }
}
