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

namespace App\Http\Service\WorkExternalContact;

use App\Http\Dao\WorkExternalContact\WorkMassMessagingTaskDao;
use App\Http\Dao\BaseDao;
use App\Http\Service\BaseService;

/**
 * 群发消息发送任务.
 * @mixin BaseDao
 */
class WorkMassMessagingTaskService extends BaseService
{
    public function __construct(WorkMassMessagingTaskDao $dao)
    {
        $this->dao = $dao;
    }
}
