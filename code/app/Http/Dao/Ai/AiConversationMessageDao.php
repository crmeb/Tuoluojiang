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

use App\Http\Model\Ai\AiConversationMessage;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * AI 会话消息数据访问层.
 *
 * 绑定模型 AiConversationMessage（chat_record）.
 * 提供按会话 ID、消息 UUID、角色（user/assistant）筛选的消息查询.
 */
class AiConversationMessageDao extends BaseDao
{
    use ListSearchTrait;

    protected function setModel(): string
    {
        return AiConversationMessage::class;
    }
}
