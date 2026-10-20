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

namespace App\Http\Requests\Customer;

use App\Http\Requests\ApiValidate;

/**
 * 付款提醒
 * Class ClientRequest.
 */
class RemindRequest extends ApiValidate
{
    /**
     * 验证场景.
     * @var string[][]
     */
    protected $scene = [
        'store'  => ['eid', 'cid', 'num', 'types', 'mark', 'time'],
        'update' => ['num', 'types', 'mark', 'time'],
    ];

    /**
     * 错误提醒.
     * @return string[]
     */
    public function message()
    {
        return [
            'eid.required'   => '请填写客户ID',
            'eid.integer'    => '请填写正确的客户ID',
            'cid.required'   => '请填写订单ID',
            'cid.integer'    => '请填写正确的订单ID',
            'num.required'   => '请填写金额',
            'num.numeric'    => '金额必须为数字',
            'num.gt'         => '金额必须大于0',
            'types.required' => '请选择提醒类型',
            'types.integer'  => '类型错误',
            'mark.required'  => '请填写备注信息',
            'time.required'  => '请选择提醒时间',
            'time.date'      => '请选择正确的提醒时间',
        ];
    }

    /**
     * 规则.
     * @return array|string[]
     */
    protected function rules()
    {
        return [
            'eid'   => 'required|integer',
            'cid'   => 'integer|integer',
            'num'   => 'required|numeric|gt:0',
            'types' => 'required|integer',
            'mark'  => 'required',
            'time'  => 'required|date',
        ];
    }
}
