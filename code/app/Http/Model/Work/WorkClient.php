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

namespace App\Http\Model\Work;

use App\Observers\WorkClientObserver;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkClient extends BaseModel
{
    use TimeDataTrait;

    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'uid'        => 'integer',
        'type'       => 'integer',
        'gender'     => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $table = 'work_client';

    public function scopeExternalUserid($query, $value)
    {
        is_array($value) ? $query->whereIn('external_userid', $value) : $query->where('external_userid', $value);
    }

    public function scopeUserid($query, $value)
    {
        is_array($value) ? $query->whereIn('userid', $value) : $query->where('userid', $value);
    }

    public function followOne(): HasOne
    {
        return $this->hasOne(WorkClientFollow::class, 'client_id', 'id')->orderBy('createtime');
    }

    public static function boot()
    {
        parent::boot();
        static::observe(WorkClientObserver::class);
    }
}
