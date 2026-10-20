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

namespace App\Http\Requests\user;

use App\Constants\ScheduleEnum;
use App\Http\Requests\ApiValidate;
use Illuminate\Validation\Rule;

/**
 * 用户日程验证器
 * Class RankRequest.
 */
class ScheduleRequest extends ApiValidate
{
    /**
     * 自动验证
     * @var bool
     */
    public $authValidate = false;

    protected $scene = [
        'create' => ['title', 'content', 'start_time', 'end_time', 'cid', 'period', 'rate'],
        'quick'  => ['title', 'content', 'end_time', 'member', 'remind'],
        'update' => ['title', 'content', 'start_time', 'end_time', 'cid', 'period', 'rate', 'type', 'start', 'end'],
        'delete' => ['type', 'start', 'end'],
        'status' => ['start', 'end'],
    ];

    /**
     * 错误提醒.
     * @return string[]
     */
    public function message()
    {
        return [
            'title.required'      => '请填写日程标题',
            'title.max'           => '日程标题过长',
            'content.max'         => '待办内容长度超出限制最大2000个字符',
            'start_time.required' => '请选择日程开始时间',
            'start_time.date'     => '请选择正确的日程开始时间',
            'end_time.required'   => '请选择日程结束时间',
            'end_time.date'       => '请选择正确的日程结束时间',
            'cid.required'        => '请选择日程类型',
            'period.required'     => '请选择重复方式',
            'period.in'           => '请选择正确的重复方式',
            'rate.required'       => '请填写重复频率',
            'rate.integer'        => '请填写正确的重复频率',
            'rate.min'            => '重复频率最小单位为1',
            'type.required'       => '请选择日程操作类型',
            'type.in'             => '请选择正确的日程操作类型',
            'start.required'      => '请选择日程开始时间',
            'start.date'          => '请选择正确的日程开始时间',
            'end.required'        => '请选择日程结束时间',
            'end.date'            => '请选择正确的日程结束时间',
            'member.array'        => '请选择正确的参与人',
            'member.*.integer'    => '请选择正确的参与人',
            'remind.integer'      => '请选择正确的提醒方式',
            'remind.min'          => '请选择正确的提醒方式',
            'remind.max'          => '请选择正确的提醒方式',
        ];
    }

    /**
     * 规则.
     * @return array|string[]
     */
    protected function rules()
    {
        return [
            'title'      => 'required|max:256',
            'content'    => 'max:2000',
            'start_time' => 'required|date',
            // 快捷创建截止时间可选；完整创建仍必填结束时间
            'end_time'   => $this->currentScene === 'quick' ? 'nullable|date' : 'required|date',
            'cid'        => 'required|integer',
            'period'     => [
                'required',
                Rule::in([ScheduleEnum::REPEAT_NOT, ScheduleEnum::REPEAT_DAY, ScheduleEnum::REPEAT_WEEK, ScheduleEnum::REPEAT_MONTH, ScheduleEnum::REPEAT_YEAR]),
            ],
            'rate'     => 'required|integer|min:1',
            'type'     => [
                'required',
                Rule::in([ScheduleEnum::CHANGE_ALL, ScheduleEnum::CHANGE_NOW, ScheduleEnum::CHANGE_AFTER]),
            ],
            'start'    => 'required|date',
            'end'      => 'required|date',
            'member'   => 'nullable|array',
            'member.*' => 'integer|min:1',
            'remind'   => 'nullable|integer|min:-1|max:8',
        ];
    }
}
