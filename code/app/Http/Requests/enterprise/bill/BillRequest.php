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

namespace App\Http\Requests\enterprise\bill;

use App\Http\Requests\ApiValidate;

/**
 * 企业资金流水
 * Class RankRequest.
 */
class BillRequest extends ApiValidate
{
    /**
     * 自动验证
     * @var bool
     */
    public $authValidate = true;

    /**
     * 错误提醒.
     * @return string[]
     */
    public function message()
    {
        return [
            'types.required'     => '请选择账目类型',
            'cate_id.required'   => '请选择账目分类',
            'cate_id.integer'    => '请选择正确的账目分类',
            'cate_id.gt'         => '请选择正确的账目分类',
            'num.required'       => '请填写账目金额',
            'num.numeric'        => '账目金额必须为数字',
            'num.gt'             => '账目金额必须大于0',
            'type_id.required'   => '请选择支付方式',
            'type_id.integer'    => '请选择正确的支付方式',
            'type_id.gt'         => '请选择正确的支付方式',
            'edit_time.required' => '请选择日期',
            'edit_time.date'     => '请选择正确的日期',
        ];
    }

    /**
     * 规则.
     * @return array|string[]
     */
    protected function rules()
    {
        return [
            'types'     => 'required',
            'cate_id'   => 'required|integer|gt:0',
            'num'       => 'required|numeric|gt:0',
            'type_id'   => 'required|integer|gt:0',
            'edit_time' => 'required|date',
        ];
    }
}
