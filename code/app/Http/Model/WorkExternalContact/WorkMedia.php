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

use App\Jobs\WorkExternalContact\WorkMediaInstantUploadJob;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 素材.
 */
class WorkMedia extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    protected $table = 'work_media';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'uid'         => 'integer',
        'up_type'     => 'integer',
        'link_id'     => 'integer',
        'attach_fail' => 'datetime:Y-m-d H:i:s',
        'fail_time'   => 'datetime:Y-m-d H:i:s',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
        'deleted_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 文件地址.
     * @param mixed $value
     * @return string
     */
    public function getFileUrlAttribute($value)
    {
        return $value ? link_file($value) : '';
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * 正常素材.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNormal($query, $value)
    {
        $failTime = now()->addHour();
        $query->where('link_id', '>', 0)->where(fn ($q) => $q->where('fail_time', '<', $failTime)->orWhereNull('fail_time'));
    }
    public function scopeNotId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->whereNot('id', $value);
    }

    public function scopeJobId($query, $value)
    {
        is_bool($value) ? $query->where('job_id', '<>', '') : $query->where('job_id', $value);
    }

    public function shouldUploadToWork(): bool
    {
        return $this->link_id && (! $this->fail_time || Carbon::make($this->fail_time)->subHour()->isBefore(now()));
    }

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($data) {
            if ($data->shouldUploadToWork()) {
                WorkMediaInstantUploadJob::dispatch($data);
            }
        });
        static::saved(function ($data) {
            if ($data->shouldUploadToWork()) {
                WorkMediaInstantUploadJob::dispatch($data);
            }
        });
    }
}
