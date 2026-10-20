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
use Illuminate\Database\Eloquent\Builder;

/**
 * 日程表.
 */
class ScheduleUser extends BaseModel
{
    use TimeDataTrait;

    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'schedule_user';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'uid'         => 'integer',
        'schedule_id' => 'integer',
        'is_master'   => 'integer',
    ];

    public function schedule()
    {
        return $this->hasOne(Schedule::class,'id','schedule_id');
    }
    public function scopeUid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } else {
            $query->where('uid', $value);
        }
    }

    /**
     * schedule_id作用域
     */
    public function scopeScheduleId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('schedule_id', $value);
        } else{
            $query->where('schedule_id', $value);
        }
    }
    public function scopeTodo(Builder $query, $value)
    {
        $query->whereHas('schedule', function ($query) use ($value) {
            $query->whereIn('status', [0,1]);
        });
    }
}
