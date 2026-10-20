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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 消息模板
 * Class MessageTemplate.
 */
class MessageTemplate extends BaseModel
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'message_template';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'relation_id'     => 'integer',
        'message_id'      => 'integer',
        'type'            => 'integer',
        'status'          => 'integer',
        'relation_status' => 'integer',
        'push_rule'       => 'integer',
        'minute'          => 'integer',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
        'deleted_at'      => 'datetime:Y-m-d H:i:s',
        'crud_event_id'   => 'integer',
    ];
}
