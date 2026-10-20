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

namespace App\Constants\Work;

use MyCLabs\Enum\Enum;

/**
 * 素材枚举.
 */
final class MediaEnum extends Enum
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public const TYPE_FILE = 'file';

    public const TYPE_VOICE = 'voice';

    /**
     * 模板类型: 小程序.
     */
    public const TEMP_MINI_PROGRAM = 'mini_program';

    /**
     * 模板类型: 文本.
     */
    public const TEMP_TEXT = 'text';

    /**
     * 模板类型: 视频.
     */
    public const TEMP_VIDEO = 'video';

    /**
     * 模板类型: 链接.
     */
    public const TEMP_LINK = 'link';

    /**
     * 模板类型: 文件.
     */
    public const TEMP_FILE = 'file';

    /**
     * 模板类型: 图片.
     */
    public const TEMP_IMAGE = 'image';

    /**
     * 关联类型: 回复.
     */
    public const LINK_TYPE_REPLY = 'work_reply_temp';

    /**
     * 关联类型: 群发.
     */
    public const LINK_TYPE_MASS = 'work_mass_messaging_temp';

    /**
     * 关联类型: 渠道活码欢迎语.
     */
    public const LINK_TYPE_CHANNEL_CODE = 'work_channel_code';
}
