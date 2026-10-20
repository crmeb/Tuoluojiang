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

namespace App\Http\Model\Company;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Assess\AssessPlan;
use App\Http\Model\Assess\AssessSpace;
use App\Http\Model\Assess\AssessTarget;
use App\Http\Model\Frame\Frame;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 考核记录
 * Class UserAssess.
 */
class UserAssess extends BaseModel
{
    use TimeDataTrait;
    use SoftDeletes;

    /**
     * 伪删除字段.
     */
    public const DELETED_AT = 'delete';

    /**
     * 表名.
     * @var string
     */
    protected $table = 'assess';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'            => 'integer',
        'entid'         => 'integer',
        'period'        => 'integer',
        'planid'        => 'integer',
        'frame_id'      => 'integer',
        'number'        => 'integer',
        'check_uid'     => 'integer',
        'test_uid'      => 'integer',
        'start_time'    => 'datetime:Y-m-d H:i:s',
        'make_time'     => 'datetime:Y-m-d H:i:s',
        'make_status'   => 'integer',
        'end_time'      => 'datetime:Y-m-d H:i:s',
        'test_status'   => 'integer',
        'check_end'     => 'datetime:Y-m-d H:i:s',
        'check_status'  => 'integer',
        'verify_time'   => 'datetime:Y-m-d H:i:s',
        'verify_status' => 'integer',
        'score'         => 'decimal:2',
        'total'         => 'decimal:2',
        'grade'         => 'integer',
        'status'        => 'integer',
        'types'         => 'integer',
        'intact'        => 'integer',
        'is_show'       => 'integer',
        'delete'        => 'datetime:Y-m-d H:i:s',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @return HasOne
     */
    public function userEnt()
    {
        return $this->hasOne(Admin::class, 'id', 'test_uid');
    }

    /**
     * 创建时间年查询.
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    public function scopeCreatedAtYear($query, $value)
    {
        return $query->whereRaw(DB::raw('YEAR(created_at) = \'' . $value . '\''));
    }

    /**
     * 部门关联.
     * @return HasOne
     */
    public function frame()
    {
        return $this->hasOne(
            Frame::class,
            'id',
            'frame_id'
        )->select([
            'frame.id',
            'frame.name',
            'frame.user_count',
        ]);
    }

    /**
     * 考核计划关联.
     * @return HasOne
     */
    public function plan()
    {
        return $this->hasOne(AssessPlan::class, 'id', 'planid');
    }

    /**
     * 考核计划详情关联.
     * @return HasOne
     */
    public function planInfo()
    {
        return $this->hasOne(AssessPlan::class, 'id', 'planid');
    }

    /**
     * 考核人.
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeCheckUid($query, $value)
    {
        if (is_array($value)) {
            if (count($value)) {
                return $query->whereIn('check_uid', $value);
            }
        } elseif ($value !== '') {
            return $query->where('check_uid', $value);
        }
    }

    /**
     * 被考核人.
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeTestUid($query, $value)
    {
        is_array($value) ? $query->whereIn('test_uid', $value) : $query->where('test_uid', $value);
    }

    /**
     * 企业ID.
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeEntid($query, $value)
    {
        is_array($value) ? $query->whereIn('entid', $value) : $query->where('entid', $value);
    }

    /**
     * 考核计划ID.
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopePlanid($query, $value)
    {
        is_array($value) ? $query->whereIn('planid', $value) : $query->where('planid', $value);
    }

    /**
     * 考核批次ID.
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNumber($query, $value)
    {
        is_array($value) ? $query->whereIn('number', $value) : $query->where('number', $value);
    }

    /**
     * 级别.
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeGrade($query, $value)
    {
        is_array($value) ? $query->whereIn('grade', $value) : $query->where('grade', $value);
    }

    /**
     * 部门ID筛选.
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeFrameId($query, $value)
    {
        if (is_array($value)) {
            if ($value) {
                $query->whereIn('frame_id', $value);
            }
        } elseif ($value !== '') {
            $query->where('frame_id', $value);
        }
    }

    /**
     * 当前时间筛选/执行期
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeExecTime($query, $value)
    {
        $query->where(function ($que) {
            $que->where('start_time', '<', now()->toDateTimeString())->where('verify_time', '>', now()->toDateTimeString());
        });
    }

    /**
     * 当前时间筛选/执行期
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeEndTime($query, $value)
    {
        $query->where('end_time', '>=', $value);
    }

    /**
     * 当前时间筛选/执行期
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeStartTime($query, $value)
    {
        $query->where('start_time', '>=', $value);
    }

    /**
     * 一对多远程关联考核内容.
     * @return HasManyThrough
     */
    public function info()
    {
        return $this->hasManyThrough(
            AssessTarget::class,
            AssessSpace::class,
            'assessid',
            'spaceid',
            'id',
            'id',
        )->groupBy('spaceid');
    }

    /**
     * 一对一关联考核人.
     * @return HasOne
     */
    public function check()
    {
        return $this->hasOne(Admin::class, 'id', 'check_uid')->select(['id', 'uid', 'name', 'phone', 'avatar', 'job']);
    }

    /**
     * 一对一关联被考核人.
     * @return HasOne
     */
    public function test()
    {
        return $this->hasOne(Admin::class, 'id', 'test_uid')->select(['id', 'uid', 'name', 'phone', 'avatar', 'job'])->with(['job' => fn ($q) => $q->select(['id', 'name'])]);
    }

    public function scopeStatus($query, $value)
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }

    /**
     * 上级评价状态：0、未评价；1、已评价；2、草稿。
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeCheckStatus($query, $value)
    {
        is_array($value) ? $query->whereIn('check_status', $value) : $query->where('check_status', $value);
    }

    /**
     * 过滤状态
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNotStatus($query, $value)
    {
        is_array($value) ? $query->whereNotIn('status', $value) : $query->where('status', '<>', $value);
    }
}
