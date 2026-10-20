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

namespace App\Http\Model\Admin;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminInfo extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * 表名.
     *
     * @var string
     */
    protected $table = 'admin_info';

    protected $casts = [
        'id'         => 'integer',
        'sex'        => 'integer',
        'age'        => 'integer',
        'marriage'   => 'integer',
        'type'       => 'integer',
        'work_years' => 'integer',
        'is_part'    => 'integer',
        'sort'       => 'integer',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 字段黑名单.
     *
     * @var array
     */
    protected $guarded = [];

    public function scopeType($query, $value)
    {
        is_array($value) ? $query->whereIn('type', $value) : $query->where('type', $value);
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }
}
