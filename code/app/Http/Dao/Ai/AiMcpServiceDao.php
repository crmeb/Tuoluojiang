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

use App\Http\Model\Ai\ChatMcpService;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * AI MCP 服务配置数据访问层.
 *
 * 绑定模型 ChatMcpService（chat_mcp_services）.
 * 提供 MCP 服务列表、状态筛选及 CRUD 基础查询.
 */
class AiMcpServiceDao extends BaseDao
{
    use ListSearchTrait;

    protected function setModel(): string
    {
        return ChatMcpService::class;
    }
}
