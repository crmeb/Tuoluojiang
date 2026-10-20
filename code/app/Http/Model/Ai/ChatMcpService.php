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

namespace App\Http\Model\Ai;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;

/**
 * MCP 服务配置模型（单条 server 扁平 JSON）.
 *
 * 表名 chat_mcp_services.
 * config_json 示例：{ "transport": "sse", "module": "schedule", "headers": [], "timeout": 30 }；
 * is_default=1 为系统内置 MCP，不可修改删除.
 */
class ChatMcpService extends BaseModel
{
    use TimeDataTrait;

    protected $primaryKey = 'id';

    protected $table = 'chat_mcp_services';

    protected $casts = [
        'id'          => 'integer',
        'status'      => 'integer',
        'is_default'  => 'integer',
        'sort'        => 'integer',
        'config_json' => 'array',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
    ];
}
