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

namespace App\Http\Requests\enterprise\program;

use App\Http\Requests\ApiValidate;

/**
 * 项目任务
 * Class ProgramTaskRequest.
 */
class ProgramTaskRequest extends ApiValidate
{
    /**
     * 自动验证
     * @var bool
     */
    public $authValidate = true;

    public function getDefaultRules(): array
    {
        return [];
    }

    public function getRulesByStore(): array
    {
        return [
            'name'       => 'required|max:100',
            'uid'        => 'integer',
            'pid'        => 'integer',
            'program_id' => 'integer|gt:0',
            'plan_start' => 'date_format:Y-m-d',
            'plan_end'   => 'date_format:Y-m-d',
            'status'     => 'integer|in:0,1,2,3,4,5',
            'describe'   => 'text_strlen_confirm_api:2000',
        ];
    }

    public function getRulesByUpdate(): array
    {
        return [
            'name'       => 'max:100',
            'uid'        => 'integer',
            'pid'        => 'integer',
            'program_id' => 'integer|gt:0',
            'plan_start' => 'date_format:Y-m-d',
            'plan_end'   => 'date_format:Y-m-d',
            'status'     => 'integer|in:0,1,2,3,4,5',
            'describe'   => 'text_strlen_confirm_api:2000',
            'members'    => 'array',
        ];
    }

    public function getRulesBySubordinateStore(): array
    {
        return [
            'name' => 'required|max:100',
            'pid'  => 'required|integer',
        ];
    }

    /**
     * 错误提醒.
     * @return string[]
     */
    public function message(): array
    {
        return [
            'name.required'                    => '请填写任务名称',
            'name.max'                         => '任务名称长度超出限制',
            'uid.integer'                      => '负责人类型异常',
            'pid.required'                     => '请选择父级任务',
            'pid.integer'                      => '父级任务型异常',
            'program_id.integer'               => '关联项目型异常',
            'program_id.gt'                    => '请选择关联项目',
            'plan_start.date_format'           => '计划开始时间格式异常',
            'plan_end.date_format'             => '计划结束时间格式异常',
            'status.integer'                   => '请选择正确的状态',
            'status.in'                        => '请选择正确的状态',
            'describe.max'                     => '描述长度超出限制',
            'describe.text_strlen_confirm_api' => '描述长度超出限制',
            'members.array'                    => '任务成员格式异常',
        ];
    }

    /**
     * 规则.
     * @return array|string[]
     */
    protected function rules(): array
    {
        $method = 'getRulesBy' . ucfirst($this->route()->getActionMethod());
        return method_exists($this, $method) ? $this->{$method}() : $this->getDefaultRules();
    }
}
