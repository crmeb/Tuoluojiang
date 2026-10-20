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

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;

class WorkMember extends BaseModel
{
    use TimeDataTrait;

    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'uid'             => 'integer',
        'gender'          => 'integer',
        'enable'          => 'integer',
        'is_leader'       => 'integer',
        'hide_mobile'     => 'integer',
        'main_department' => 'integer',
        'status'          => 'integer',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
    ];

    protected $table = 'work_member';

    public function scopeUserid($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('userid', $value);
            } else {
                $query->where('userid', $value);
            }
        }
    }
}
