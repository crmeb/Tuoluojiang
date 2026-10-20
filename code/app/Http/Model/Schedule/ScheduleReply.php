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

namespace App\Http\Model\Schedule;

use App\Constants\AttachEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\System\Attach;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 日程表.
 */
class ScheduleReply extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'schedule_reply';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'uid'        => 'integer',
        'pid'        => 'integer',
        'reply_id'   => 'integer',
        'to_uid'     => 'integer',
        'start_time' => 'datetime:Y-m-d H:i:s',
        'end_time'   => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 父级ID作用域.
     */
    public function scopePid($query, $value)
    {
        is_array($value) ? $query->whereIn('pid', $value) : $query->where('pid', $value);
    }

    /**
     * 回复id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeReplyId($query, $value)
    {
        is_array($value) ? $query->whereIn('reply_id', $value) : $query->where('reply_id', $value);
    }

    /**
     * 时间作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeTimeZone($query, $value)
    {
        [$start,$end] = explode(' - ', $value);
        $query->where('start_time', $start)->where('end_time', $end);
    }

    /**
     * 关联用户.
     * @return HasOne
     */
    public function from_user()
    {
        return $this->hasOne(Admin::class, 'id', 'uid');
    }

    /**
     * 关联用户.
     * @return HasOne
     */
    public function to_user()
    {
        return $this->hasOne(Admin::class, 'id', 'to_uid');
    }

    /**
     * 一对多关联附件.
     * @return HasMany
     */
    public function files()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_SCHEDULE_REPLY]);
    }
}
