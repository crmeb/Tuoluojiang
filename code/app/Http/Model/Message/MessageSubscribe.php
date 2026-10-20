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

namespace App\Http\Model\Message;

use App\Http\Model\BaseModel;

/**
 * App\Http\Models\message\MessageSubscribe.
 */
class MessageSubscribe extends BaseModel
{
    protected $table = 'message_subscribe';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'           => 'integer',
        'user_id'      => 'integer',
        'is_subscribe' => 'integer',
    ];

    protected function getMessageIdAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    protected function setMessageIdAttribute($value)
    {
        $this->attributes['message_id'] = $value ? json_encode($value) : '';
    }
}
