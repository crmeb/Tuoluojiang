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

namespace App\Support;

use Crmeb\Yihaotong\Factory;

/**
 * 一号通 Factory 静态状态重置.
 *
 * Factory::$drivers 以类名为键缓存 Client 实例，且 Client 在构造时就绑定了当时的
 * AccessToken。Swoole 常驻进程下该静态属性跨请求存活，AppId/AppSecret 改了之后
 * Factory::setAccessToken() 只换掉 self::$accessToken，已缓存的 Client 仍持有旧凭证，
 * 导致一直用旧 Key 请求上游（表现为保存了正确配置仍报凭证错误）。
 */
class YihaotongFactory
{
    /**
     * 清空已缓存的 Client 与 AccessToken，下次调用重新按当前配置构建.
     */
    public static function reset(): void
    {
        try {
            $ref = new \ReflectionClass(Factory::class);

            foreach (['drivers' => [], 'accessToken' => null] as $property => $value) {
                if (! $ref->hasProperty($property)) {
                    continue;
                }
                $prop = $ref->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue(null, $value);
            }
        } catch (\Throwable) {
            // 反射失败不影响主流程
        }
    }
}
