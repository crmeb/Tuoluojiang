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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 知识库离线评测用例.
 *
 * 状态：0草稿 / 1确认 / 2停用。仅确认态参与 artisan knowledge:eval 跑批.
 */
class KnowledgeEvalCase extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     */
    protected $table = 'knowledge_eval_case';

    /**
     * 主键.
     */
    protected $primaryKey = 'id';

    /**
     * 类型转换.
     */
    protected $casts = [
        'id'               => 'integer',
        'golden_chunk_ids' => 'array',
        'answer_points'    => 'array',
        'kb_ids'           => 'array',
        'space_type'       => 'integer',
        'tags'             => 'array',
        'status'           => 'integer',
        'created_at'       => 'datetime:Y-m-d H:i:s',
        'updated_at'       => 'datetime:Y-m-d H:i:s',
        'deleted_at'       => 'datetime:Y-m-d H:i:s',
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

    /**
     * 空间类型作用域.
     *
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeSpaceType($query, $value): void
    {
        is_array($value) ? $query->whereIn('space_type', $value) : $query->where('space_type', $value);
    }
}
