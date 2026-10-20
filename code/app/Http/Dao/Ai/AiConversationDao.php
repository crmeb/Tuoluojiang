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

namespace App\Http\Dao\Ai;

use App\Http\Model\Ai\AiConversation;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * AI 会话数据访问层.
 *
 * 绑定模型 AiConversation（chat_history）.
 * 提供按用户、应用、访客 token、session_type 等 scope 的列表与 CRUD 查询.
 *
 * @mixin AiConversation
 */
class AiConversationDao extends BaseDao
{
    use ListSearchTrait;

    protected function setModel(): string
    {
        return AiConversation::class;
    }
}
