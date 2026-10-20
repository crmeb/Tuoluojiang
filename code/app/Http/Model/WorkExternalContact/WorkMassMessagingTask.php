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

namespace App\Http\Model\WorkExternalContact;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 群发消息发送任务.
 */
class WorkMassMessagingTask extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    protected $table = 'work_mass_messaging_task';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'             => 'integer',
        'mass_id'        => 'integer',
        'uid'            => 'integer',
        'status'         => 'integer',
        'sum_count'      => 'integer',
        'not_send_count' => 'integer',
        'success_count'  => 'integer',
        'fail_count'     => 'integer',
        'types'          => 'integer',
        'fail_list'      => 'array',
        'send_time'      => 'datetime:Y-m-d H:i:s',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
        'deleted_at'     => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeTypes($query, $value)
    {
        is_array($value) ? $query->whereIn('types', $value) : $query->where('types', $value);
    }
}
