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

namespace App\Http\Model\User;

use App\Http\Model\BaseModel;

/**
 * 用户Token.
 */
class UserToken extends BaseModel
{
    protected $table = 'user_token';

    protected $primaryKey = 'id';

    protected $casts = [
        'refresh_expires_at'   => 'datetime:Y-m-d H:i:s',
        'refresh_last_used_at' => 'datetime:Y-m-d H:i:s',
        'refresh_revoked_at'   => 'datetime:Y-m-d H:i:s',
        'id'                   => 'integer',
        'fail_time'            => 'datetime:Y-m-d H:i:s',
        'created_at'           => 'datetime:Y-m-d H:i:s',
        'updated_at'           => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeUid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }
}
