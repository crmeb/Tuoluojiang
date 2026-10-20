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

namespace App\Http\Model\Crud;

use App\Http\Model\BaseModel;

/**
 * 低代码审批数据记录.
 * class SystemCrudApproveRecord
 */
class SystemCrudApproveRecord extends BaseModel
{
    /**
     * 表明.
     * @var string
     */
    protected $table = 'system_crud_approve_record';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'approve_id' => 'integer',
        'crud_id'    => 'integer',
        'data_id'    => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getDataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }


    public function getOriginalDataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function getOriginalScheduleDataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

}
