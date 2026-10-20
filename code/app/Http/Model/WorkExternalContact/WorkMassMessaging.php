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
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 企微群发消息.
 */
class WorkMassMessaging extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    protected $table = 'work_mass_messaging';

    protected $primaryKey = 'id';

    protected $casts = [
        'send_uid'      => 'array',
        'send_group'    => 'array',
        'send_customer' => 'array',
        'search'        => 'array',
        'sent_uid'      => 'array',
        'not_sent_uid'  => 'array',
        'fail_list'     => 'array',
        'id'            => 'integer',
        'uid'           => 'integer',
        'types'         => 'integer',
        'is_all'        => 'integer',
        'is_modify'     => 'integer',
        'temp_id'       => 'integer',
        'is_timed'      => 'integer',
        'send_time'     => 'datetime:Y-m-d H:i:s',
        'be_sent'       => 'integer',
        'is_send'       => 'integer',
        'is_sent'       => 'integer',
        'not_sent'      => 'integer',
        'status'        => 'integer',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
        'deleted_at'    => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 群发模板.
     * @return HasOne
     */
    public function temp()
    {
        return $this->hasOne(WorkMassMessagingTemp::class, 'id', 'temp_id')->with(['attach']);
    }

    /**
     * 关联创建人.
     * @return HasOne
     */
    public function creator()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    public function scopeSendMinute($query, $value)
    {
        $start = $value->startOfMinute()->toDateTimeString();
        $end   = $value->endOfMinute()->toDateTimeString();
        return $query->whereBetween('send_time', [$start, $end]);
    }

    public function scopeStatus($query, $value)
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }

    public function send_admin()
    {
        return $this->hasManyThrough(Admin::class, WorkMassMessagingTask::class, 'mass_id', 'id', 'id', 'uid')
            ->where('work_mass_messaging_task.status', 2)->select(['admin.id', 'admin.name', 'admin.avatar', 'admin.phone']);
    }

    public function not_send_admin()
    {
        return $this->hasManyThrough(Admin::class, WorkMassMessagingTask::class, 'mass_id', 'id', 'id', 'uid')
            ->where('work_mass_messaging_task.status', 0)->select(['admin.id', 'admin.name', 'admin.avatar', 'admin.phone']);
    }

    public function scopeContentLike($query, $value)
    {
        $query->whereExists(function ($subquery) use ($value) {
            $subquery->selectRaw(1)
                ->from('work_mass_messaging_temp')
                ->whereColumn('work_mass_messaging_temp.id', $this->table . '.temp_id')
                ->where('work_mass_messaging_temp.content', 'like', "%{$value}%");
        });
    }
}
