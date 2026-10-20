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

namespace App\Http\Requests\system;

use App\Http\Requests\ApiRequest;
use Tuoluojiang\Open\utils\Regex;

class SystemConfigRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'key'         => ['required', 'regex:' . Regex::CONFIG_KEY],
            'key_name'    => 'required',
            'type'        => 'required',
            'cate_id'     => 'integer',
            'parameter'   => 'max:255',
            'upload_type' => 'integer',
            'required'    => 'max:255',
            'width'       => 'integer',
            'high'        => 'integer',
            'value'       => 'max:5000',
            'desc'        => 'max:255',
            'sort'        => 'integer',
            'is_show'     => 'integer',
        ];
    }

    /**
     * 错误提示.
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'key.required'        => '请填写配置英文名称',
            'key.regex'           => '配置英文名称只能包含英文,字母,下划线',
            'key_name.required'   => '请填写配置中文名',
            'type.required'       => '请选择配置类型',
            'cate_id.integer'     => '配置分类必须为数字',
            'parameter.max'       => '配置规则长度不能超出255个字符',
            'upload_type.integer' => '配置上传文件类型必须为数字',
            'required.max'        => '配置规则不能超出255个字符',
            'width.integer'       => '多行文本框的宽度必须为数字',
            'high.integer'        => '多行文本框的高度必须为数字',
            'value.max'           => '配置的值超出5000个字符',
            'desc.max'            => '配置简介超出255个字符',
            'sort.integer'        => '配置排序只能为数字',
            'is_show.integer'     => '配置是否展示字段只能为数字',
        ];
    }
}
