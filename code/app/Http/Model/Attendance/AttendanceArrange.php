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

namespace App\Http\Model\Attendance;

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * 考勤排班.
 */
class AttendanceArrange extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'attendance_arrange';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'group_id'   => 'integer',
        'uid'        => 'integer',
        'date'       => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * 一对一关联考勤组.
     * @return HasOne
     */
    public function group()
    {
        $prefix = Config::get('database.connections.mysql.prefix');
        return $this->hasOne(AttendanceGroup::class, 'id', 'group_id')->withTrashed()
            ->select(['attendance_group.id', 'attendance_group.type', 'attendance_group.name'])
            ->selectRaw("IF(`{$prefix}attendance_group`.`deleted_at` is null, 0, 1) as is_delete");
    }

    /**
     * date作用域
     */
    public function scopeAttendDate($query, $value): void
    {
        if ($value !== '') {
            $query->whereBetween(DB::raw("DATE_FORMAT(date,'%Y-%m')"), array_map(function ($item) {
                return str_replace('/', '-', $item);
            }, explode('-', $value)));
        }
    }

    /**
     * 考勤时间.
     */
    public function getDateAttribute($value): string
    {
        return $value && ! str_contains($value, '0000-00-00') ? date('Y-m-d', datetime_timestamp($value)) : '0000-00-00';
    }

    /**
     * date作用域
     */
    public function scopeDate($query, $value): void
    {
        if ($value !== '') {
            $query->whereDate('date', $value);
        }
    }

    /**
     * date作用域
     */
    public function scopeMonth($query, $value): void
    {
        if ($value !== '') {
            $query->where(DB::raw("DATE_FORMAT(date,'%Y-%m')"), $value);
        }
    }

    /**
     * uid作用域
     */
    public function scopeUId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }
}
