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

namespace App\Http\Service\Message;

use App\Http\Dao\Message\MessageTemplateDao;
use App\Http\Service\BaseService;

/**
 * 消息模板
 * Class MessageTemplateService.
 * @method insert(array $data) 批量新增
 */
class MessageTemplateService extends BaseService
{
    /**
     * MessageTemplateService constructor.
     */
    public function __construct(MessageTemplateDao $dao)
    {
        $this->dao = $dao;
    }
}
