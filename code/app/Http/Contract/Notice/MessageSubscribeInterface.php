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

namespace App\Http\Contract\Notice;

/**
 * 消息订阅.
 */
interface MessageSubscribeInterface
{
    /**
     * 保存取消订阅消息列表.
     */
    public function saveSubscribe(int $id, int $status, int $uid): void;

    /**
     * 验证消息是否订阅.
     */
    public function isSend(int|string $userId, int $entId, string $templateType): bool;
}
