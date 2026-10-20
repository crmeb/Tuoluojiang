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
 * 客户资金记录
 * Class ClientRequest.
 */
class PaymentRequest extends ApiValidate
{
    /**
     * 验证场景.
     * @var string[][]
     */
    protected $scene = [
        'store'   => ['eid', 'cid', 'num', 'types', 'date', 'end_date', 'cate_id', 'type_id'],
        'update'  => ['num', 'types', 'date', 'end_date', 'cate_id', 'type_id'],
        'payment' => ['cid', 'num', 'date', 'type_id', 'bill_cate_id'],
        'renewal' => ['cid', 'num', 'date', 'end_date', 'cate_id', 'type_id', 'bill_cate_id'],
        'expend'  => ['cid', 'num', 'date', 'type_id', 'bill_cate_id'],
    ];

    /**
     * 错误提醒.
     * @return string[]
     */
    public function message()
    {
        return [
            'eid.required'          => '请选择客户ID',
            'eid.integer'           => '请选择正确的客户ID',
            'cid.required'          => '请选择订单ID',
            'cid.integer'           => '请选择正确的订单ID',
            'num.required'          => '请填写金额',
            'num.numeric'           => '金额必须为数字',
            'num.gt'                => '金额必须大于0',
            'types.required'        => '请选择资金类型',
            'date.required'         => '请选择回款日期',
            'date.date'             => '请选择正确的日期',
            'end_date.date'         => '请选择正确的结束日期',
            'cate_id.integer'       => '请选择正确的续费类型',
            'bill_cate_id.required' => '请选择财务收入科目',
            'bill_cate_id.array'    => '请选择正确的财务收入科目',
            'type_id.required'      => '请选择正确的支付方式',
            'type_id.integer'       => '请选择正确的支付方式',
            'type_id.gt'            => '请选择正确的支付方式',
        ];
    }

    /**
     * 规则.
     * @return array|string[]
     */
    protected function rules()
    {
        return [
            'eid'          => 'required|integer',
            'cid'          => 'required|integer',
            'num'          => 'required|numeric|gt:0',
            'types'        => 'required',
            'date'         => 'required|date',
            'end_date'     => 'date',
            'cate_id'      => 'integer',
            'type_id'      => 'required|integer|gt:0',
            'bill_cate_id' => 'required|array',
        ];
    }
}
