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

namespace App\Events\Config;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * 一号通 AppId/AppSecret 配置变更事件.
 *
 * 仅由 SystemConfigObserver 在对应配置 created/updated 时派发；
 * 监听器负责同步电子签与 AI 模型，失败不阻断配置保存接口.
 */
class YihaotongConfigChangedEvent
{
    use Dispatchable;
}
