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

namespace App\Http\Service\User;

use App\Http\Dao\User\UserRemindLogDao;
use App\Http\Service\BaseService;

/**
 * 用户消息提醒日志
 * Class UserRemindLogService.
 * @mixin UserRemindLogDao
 */
class UserRemindLogService extends BaseService
{
    public function __construct(UserRemindLogDao $dao)
    {
        $this->dao = $dao;
    }
}
