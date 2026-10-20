<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Constants\Crud;

use MyCLabs\Enum\Enum;

/**
 * 低代码：字段类型.
 */
final class CrudFormEnum extends Enum
{
    /**
     * 布尔类型.
     */
    public const FORM_SWITCH = 'switch';

    /**
     * 整数类型.
     */
    public const FORM_INPUT_NUMBER = 'input_number';

    /**
     * 精度小数.
     */
    public const FORM_INPUT_FLOAT = 'input_float';

    /**
     * 百分比.
     */
    public const FORM_INPUT_PERCENTAGE = 'input_percentage';

    /**
     * 金额.
     */
    public const FORM_INPUT_PRICE = 'input_price';

    /**
     * 文本.
     */
    public const FORM_INPUT = 'input';

    /**
     * 长文本.
     */
    public const FORM_TEXTAREA = 'textarea';

    /**
     * 富文本.
     */
    public const FORM_RICH_TEXT = 'rich_text';

    /**
     * 单选项.
     */
    public const FORM_RADIO = 'radio';

    /**
     * 级联单选.
     */
    public const FORM_CASCADER_RADIO = 'cascader_radio';

    /**
     * 地址选择.
     */
    public const FORM_CASCADER_ADDRESS = 'cascader_address';

    /**
     * 复选项.
     */
    public const FORM_CHECKBOX = 'checkbox';

    /**
     * 标签组.
     */
    public const FORM_TAG = 'tag';

    /**
     * 级联复选.
     */
    public const FORM_CASCADER = 'cascader';

    /**
     * 日期
     */
    public const FORM_DATE_PICKER = 'date_picker';

    /**
     * 日期时间.
     */
    public const FORM_DATE_TIME_PICKER = 'date_time_picker';

    /**
     * 图片.
     */
    public const FORM_IMAGE = 'image';

    /**
     * 文件.
     */
    public const FORM_FILE = 'file';

    /**
     * 一对一关联.
     */
    public const FORM_INPUT_SELECT = 'input_select';

    /**
     * 下拉.
     */
    public const FORM_SELECT = 'select';

    /**
     * 表格
     */
    public const FORM_TABLE = 'details';

    /**
     * 正则.
     */
    public const FORM_REGEX = [
        CrudFormEnum::FORM_INPUT,
        CrudFormEnum::FORM_TEXTAREA,
        CrudFormEnum::FORM_RICH_TEXT,
        CrudFormEnum::FORM_INPUT_NUMBER,
        CrudFormEnum::FORM_INPUT_FLOAT,
        CrudFormEnum::FORM_INPUT_PRICE,
        CrudFormEnum::FORM_INPUT_PERCENTAGE,
    ];
}
