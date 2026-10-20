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

namespace Tuoluojiang\Open\services\synchro;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Message.
 */
class Message extends TokenService
{
    public function setConfig()
    {
        return $this;
    }

    /**
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function getMessageList()
    {
        return $this->httpRequest('/api/v2/message/lists', [], 'GET');
    }

    /**
     * 获取分类.
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function getCateList()
    {
        return $this->httpRequest('/api/v2/message/cate', [], 'GET', false);
    }
}
