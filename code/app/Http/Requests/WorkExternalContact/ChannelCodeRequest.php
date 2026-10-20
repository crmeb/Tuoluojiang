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
 * 企微渠道活码请求验证.
 */
class ChannelCodeRequest extends ApiValidate
{
    public function rules()
    {
        return [
            'name'                       => 'required',
            'group_id'                   => 'nullable|integer|min:0',
            'userids'                    => 'required|array',
            'tag_ids'                    => 'nullable|array',
            'skip_verify'                => 'nullable|in:0,1',
            'invite_group'               => 'nullable|in:0,1',
            'code_id'                    => 'nullable|integer|min:0',
            'status'                     => 'nullable|in:0,1',
            'remark'                     => 'nullable|string',
            'welcome'                    => 'nullable|array',
            'welcome.content'            => 'nullable|string',
            'welcome.attach'             => 'nullable|array',
            'welcome.attach.*.types'     => 'nullable|string',
            'welcome.attach.*.file_id'   => 'nullable|integer|min:1',
            'welcome.attach.*.file_url'  => 'nullable|string',
            'welcome.attach.*.media_id'  => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'name.required'                      => '请填写扫码名称',
            'group_id.integer'                   => '活码分组格式错误',
            'group_id.min'                       => '活码分组格式错误',
            'userids.required'                   => '请选择使用员工',
            'userids.array'                      => '使用员工格式错误',
            'tag_ids.array'                      => '客户标签格式错误',
            'skip_verify.in'                     => '客户添加确认方式格式错误',
            'invite_group.in'                    => '邀请加群开关格式错误',
            'code_id.integer'                    => '邀请加群配置格式错误',
            'code_id.min'                        => '邀请加群配置格式错误',
            'status.in'                          => '状态格式错误',
            'remark.string'                      => '备注格式错误',
            'welcome.array'                      => '欢迎语格式错误',
            'welcome.content.string'             => '欢迎语文本格式错误',
            'welcome.attach.array'               => '欢迎语附件格式错误',
            'welcome.attach.*.types.string'      => '欢迎语附件类型格式错误',
            'welcome.attach.*.file_id.integer'   => '欢迎语附件素材格式错误',
            'welcome.attach.*.file_id.min'       => '欢迎语附件素材格式错误',
            'welcome.attach.*.file_url.string'   => '欢迎语附件地址格式错误',
            'welcome.attach.*.media_id.string'   => '欢迎语附件media_id格式错误',
        ];
    }
}
