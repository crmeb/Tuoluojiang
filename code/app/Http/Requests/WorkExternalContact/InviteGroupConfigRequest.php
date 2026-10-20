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

namespace App\Http\Requests\WorkExternalContact;

use App\Http\Requests\ApiValidate;

/**
 * 企微渠道活码邀请加群配置请求验证.
 */
class InviteGroupConfigRequest extends ApiValidate
{
    public function rules()
    {
        return [
            'chat_ids'         => 'nullable|array',
            'chat_ids.*'       => 'nullable|string',
            'auto_create_room' => 'nullable|in:0,1',
            'room_base_id'     => 'nullable|integer|min:1',
            'room_base_name'   => 'nullable|string',
            'max_member_num'   => 'nullable|integer|min:1|max:200',
            'scene'            => 'nullable|integer|in:1,2',
            'status'           => 'nullable|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'chat_ids.array'         => '客户群格式错误',
            'chat_ids.*.string'      => '客户群格式错误',
            'auto_create_room.in'    => '自动建群开关格式错误',
            'room_base_id.integer'   => '群聊起始序号格式错误',
            'room_base_id.min'       => '群聊起始序号不能小于1',
            'room_base_name.string'  => '群聊名称格式错误',
            'max_member_num.integer' => '入群人数上限格式错误',
            'max_member_num.min'     => '入群人数上限不能小于1',
            'max_member_num.max'     => '入群人数上限不能大于200',
            'scene.integer'          => '进群方式场景格式错误',
            'scene.in'               => '进群方式场景格式错误',
            'status.in'              => '状态格式错误',
        ];
    }
}
