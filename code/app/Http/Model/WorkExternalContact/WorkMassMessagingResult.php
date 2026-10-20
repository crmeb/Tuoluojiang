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

use App\Http\Model\Admin\Admin;
use App\Http\Model\Work\WorkClient;
use App\Http\Model\Work\WorkGroupChat;
use App\Http\Model\BaseModel;

/**
 * 群发消息发送结果.
 */
class WorkMassMessagingResult extends BaseModel
{
    protected $table = 'work_mass_messaging_result';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'task_id'    => 'integer',
        'uid'        => 'integer',
        'is_comment' => 'integer',
        'is_like'    => 'integer',
        'status'     => 'integer',
        'send_time'  => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeExternalUserid($query, $value)
    {
        is_array($value) ? $query->whereIn('external_userid', $value) : $query->where('external_userid', $value);
    }

    public function scopeTaskId($query, $value)
    {
        is_array($value) ? $query->whereIn('task_id', $value) : $query->where('task_id', $value);
    }

    public function messaging()
    {
        return $this->hasOneThrough(WorkMassMessaging::class, WorkMassMessagingTask::class, 'id', 'id', 'task_id', 'mass_id');
    }

    public function chat_group()
    {
        return $this->hasOne(WorkGroupChat::class, 'chat_id', 'chat_id')->select(['chat_id', 'name', 'owner']);
    }

    public function customer()
    {
        return $this->hasOne(WorkClient::class, 'external_userid', 'external_userid')->select(['external_userid', 'name', 'corp_name', 'avatar', 'type']);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar']);
    }
}
