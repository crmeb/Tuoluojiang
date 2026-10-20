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

/**
 * 企业微信获取应用配置
 * Interface WorkAppConfigHandlerInterface.
 */
interface WorkAppConfigHandlerInterface
{
    /**
     * 获取应用配置.
     * @param string $type 应用标识
     */
    public function getAppConfig(string $corpId, string $type): array;
}
