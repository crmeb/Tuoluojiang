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
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 企微渠道活码客户群配置.
 */
class WorkChannelCodeGroup extends BaseModel
{
    use TimeDataTrait;
    use SoftDeletes;

    protected $table = 'work_channel_code_group';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'       => 'integer',
        'chat_ids' => 'array',
    ];

    public function scopeChatId($query, $value): void
    {
        if (is_array($value)) {
            if (! $value) {
                return;
            }
            $query->where(function ($query) use ($value) {
                foreach ($value as $chatId) {
                    $query->orWhereJsonContains('chat_ids', (string) $chatId);
                }
            });
            return;
        }
        $query->whereJsonContains('chat_ids', (string) $value);
    }

    public function scopeState($query, $value): void
    {
        is_array($value) ? $query->whereIn('state', $value) : $query->where('state', $value);
    }
}
