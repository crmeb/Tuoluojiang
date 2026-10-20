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
 * 知识库离线评测运行记录.
 *
 * 状态：0pending / 1running / 2done / 3failed。metrics 存 Recall@k / MRR / miss_rate.
 */
class KnowledgeEvalRun extends BaseModel
{
    /**
     * 表名.
     */
    protected $table = 'knowledge_eval_run';

    /**
     * 主键.
     */
    protected $primaryKey = 'id';

    /**
     * 类型转换.
     */
    protected $casts = [
        'id'            => 'integer',
        'feature_flags' => 'array',
        'metrics'       => 'array',
        'status'        => 'integer',
        'created_by'    => 'integer',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * ID 作用域.
     *
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeId($query, $value): void
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * 状态作用域.
     *
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value): void
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }
}
