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

namespace App\Constants\ProgramEnum;

use MyCLabs\Enum\Enum;

final class DynamicEnum extends Enum
{
    /**
     * 动态类型：项目.
     */
    public const PROGRAM = 1;

    /**
     * 动态类型：任务.
     */
    public const TASK = 2;

    /**
     * 动作类型：添加.
     */
    public const CREATED_ACTION = 1;

    /**
     * 动作类型：修改.
     */
    public const UPDATE_ACTION = 2;

    /**
     * 动作类型：删除.
     */
    public const DELETE_ACTION = 3;

    public static function getProgramFieldText(string $field): string
    {
        return match ($field) {
            'name'       => '项目名称',
            'uid'        => '负责人',
            'members'    => '项目成员',
            'start_date' => '计划开始',
            'end_date'   => '计划结束',
            'eid'        => '关联客户',
            'cid'        => '关联订单',
            'status'     => '项目状态',
            'describe'   => '项目描述',
            default      => '未知',
        };
    }

    public static function getTaskFieldText(string $field): string
    {
        return match ($field) {
            'pid'        => '父级任务',
            'name'       => '名称',
            'program_id' => '关联项目',
            'version_id' => '关联版本',
            'plan_start' => '计划开始',
            'plan_end'   => '计划结束',
            'members'    => '协作者',
            'uid'        => '负责人',
            'status'     => '状态',
            'priority'   => '优先级',
            'describe'   => '描述',
            default      => '未知数据',
        };
    }
}
