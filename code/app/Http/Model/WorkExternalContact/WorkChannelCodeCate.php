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

namespace App\Http\Model\WorkExternalContact;

use App\Http\Model\BaseModel;

/**
 * 企微渠道活码分组.
 */
class WorkChannelCodeCate extends BaseModel
{
    protected $table = 'work_channel_code_cate';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'pid'        => 'integer',
        'uid'        => 'integer',
        'sort'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeNameLike($query, $value): void
    {
        $query->where('name', 'like', '%' . $value . '%');
    }
}
