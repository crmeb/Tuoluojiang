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

namespace Tuoluojiang\Open\services\wechat\contract;

use EasyWeChat\MiniApp\Contracts\Application as MiniAppApplication;
use EasyWeChat\OfficialAccount\Contracts\Application as ApplicationInterface;
use EasyWeChat\Pay\Contracts\Application;
use EasyWeChat\Work\Contracts\Application as WorkApplication;

/**
 * Interface BaseApplicationInterface.
 */
interface BaseApplicationInterface
{
    /**
     * @email 136327134@qq.com
     * @date 2023/9/14
     */
    public function instance(): self;

    /**
     * @email 136327134@qq.com
     * @date 2023/9/14
     */
    public function application(): Application|ApplicationInterface|MiniAppApplication|WorkApplication;
}
