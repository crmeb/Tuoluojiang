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

namespace App\Http\Model\Knowledge;

use App\Http\Model\BaseModel;

/**
 * 知识库问答日志.
 */
class KnowledgeQuestionLog extends BaseModel
{
    public const UPDATED_AT = null;

    /**
     * 表名.
     */
    protected $table = 'knowledge_question_log';

    /**
     * 主键.
     */
    protected $primaryKey = 'id';

    /**
     * 类型转换.
     */
    protected $casts = [
        'id'         => 'integer',
        'kb_id'      => 'integer',
        'app_id'     => 'integer',
        'uid'        => 'integer',
        'hit_chunks' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
}
