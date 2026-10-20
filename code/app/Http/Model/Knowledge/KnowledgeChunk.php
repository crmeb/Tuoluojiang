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
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 知识库分块.
 */
class KnowledgeChunk extends BaseModel
{
    /**
     * 表名.
     */
    protected $table = 'knowledge_chunk';

    /**
     * 主键.
     */
    protected $primaryKey = 'id';

    /**
     * 类型转换.
     */
    protected $casts = [
        'id'                  => 'integer',
        'kb_id'               => 'integer',
        'doc_id'              => 'integer',
        'chunk_index'         => 'integer',
        'source_type'         => 'integer',
        'embedding'           => 'array',
        'embedding_model_id'  => 'integer',
        'embedding_dimension' => 'integer',
        'metadata'            => 'array',
        'image_urls'          => 'array',
        'status'              => 'integer',
        'enabled'             => 'integer',
        'created_at'          => 'datetime:Y-m-d H:i:s',
        'updated_at'          => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联文档.
     */
    public function document(): HasOne
    {
        return $this->hasOne(KnowledgeDocument::class, 'id', 'doc_id');
    }

    /**
     * 内容模糊搜索.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeContentLike($query, $value): void
    {
        $query->where('content', 'like', '%' . $value . '%');
    }

    /**
     * ID作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeId($query, $value): void
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * 知识库ID作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeKbId($query, $value): void
    {
        is_array($value) ? $query->whereIn('kb_id', $value) : $query->where('kb_id', $value);
    }

    /**
     * 文档ID作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeDocId($query, $value): void
    {
        is_array($value) ? $query->whereIn('doc_id', $value) : $query->where('doc_id', $value);
    }

    /**
     * 状态作用域（向量化状态）.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value): void
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }

    /**
     * 来源类型作用域（1自动/2手动）.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeSourceType($query, $value): void
    {
        is_array($value) ? $query->whereIn('source_type', $value) : $query->where('source_type', $value);
    }

    /**
     * 启停作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeEnabled($query, $value): void
    {
        is_array($value) ? $query->whereIn('enabled', $value) : $query->where('enabled', $value);
    }
}
