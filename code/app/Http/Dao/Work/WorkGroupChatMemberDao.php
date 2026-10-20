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

namespace App\Http\Dao\Work;

use App\Http\Model\Work\WorkGroupChatMember;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;

class WorkGroupChatMemberDao extends BaseDao
{
    use BatchSearchTrait;

    protected function setModel()
    {
        return WorkGroupChatMember::class;
    }
}
