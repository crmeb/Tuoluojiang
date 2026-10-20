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

namespace App\Http\Model\WorkExternalContact;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 企微渠道活码客户触达日志.
 */
class WorkChannelCodeLog extends BaseModel
{
    use TimeDataTrait;

    protected $table = 'work_channel_code_log';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'channel_id'      => 'integer',
        'group_id'        => 'integer',
        'send_status'     => 'integer',
        'group_status'    => 'integer',
        'customer_status' => 'integer',
        'joined_at'       => 'datetime:Y-m-d H:i:s',
        'left_at'         => 'datetime:Y-m-d H:i:s',
        'lost_at'         => 'datetime:Y-m-d H:i:s',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
    ];

    public function channel(): HasOne
    {
        return $this->hasOne(WorkChannelCode::class, 'id', 'channel_id');
    }

    public function group(): HasOne
    {
        return $this->hasOne(WorkChannelCodeGroup::class, 'id', 'group_id');
    }

    public function scopeExternalUserid($query, $value): void
    {
        is_array($value) ? $query->whereIn('external_userid', $value) : $query->where('external_userid', $value);
    }

    public function scopeGroupId($query, $value): void
    {
        is_array($value) ? $query->whereIn('group_id', $value) : $query->where('group_id', $value);
    }

    public function scopeState($query, $value): void
    {
        $query->where('state', $value);
    }
}
