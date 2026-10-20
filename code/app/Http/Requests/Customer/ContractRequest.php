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

class ContractRequest extends ApiValidate
{
    /**
     * 验证场景.
     * @var string[][]
     */
    protected $scene = [
        'store'  => ['doc_name', 'eid', 'sign_type', 'term_type', 'start_date', 'end_date', 'date_count', 'sign_file', 'file_id', 'mark', 'cid', 'link_type', 'signatory', 'processInfo', 'productInfo'],
        'update' => ['doc_name', 'start_date', 'end_date', 'date_count', 'sign_file', 'file_id', 'mark', 'cid', 'link_type', 'signatory', 'processInfo', 'productInfo'],
    ];

    /**
     * 错误提醒.
     * @return string[]
     */
    public function message()
    {
        return [
            'doc_name.required'    => '请填写合同名称',
            'doc_name.regex'       => '合同名称不符合规范',
            'doc_name.max'         => '合同名称过长',
            'eid.required'         => '请选择客户',
            'start_date.required'  => '请选择合同开始日期',
            'start_date.date'      => '请选择正确的日期',
            'end_date.required'    => '请选择合同结束日期',
            'end_date.date'        => '请选择正确的结束日期',
            'end_date.after'       => '结束日期必须在开始日期之后',
            'sign_type.required'   => '请选择签约类型',
            'term_type.required'   => '请选择合同期限类型',
        ];
    }

    /**
     * 规则.
     * @return array|string[]
     */
    protected function rules()
    {
        $termType = request()->post('term_type');
        $rules = [
            'doc_name'    => 'required|max:255',
            'eid'         => 'required',
            'sign_type'   => 'required|integer|in:1,2',
            'term_type'   => 'required|integer|in:0,1,2,3',
            'date_count'  => 'nullable|integer',
            'sign_file'   => 'nullable|array',
            'file_id'     => 'nullable',
            'mark'        => 'nullable|string',
            'cid'         => 'nullable|array',
            'link_type'   => 'nullable|integer',
            'signatory'   => 'nullable|array',
            'processInfo' => 'nullable|array',
            'productInfo' => 'nullable|array',
        ];

        // term_type=1 表示固定日期，需要开始和结束日期
        if ($termType == 1) {
            $rules['start_date'] = 'required|date';
            $rules['end_date']   = 'required|date|after:start_date';
        } else {
            // 其他情况（签约日起算、无期限），日期可选
            $rules['start_date'] = 'nullable|date';
            $rules['end_date']   = 'nullable|date';
        }

        return $rules;
    }
}
