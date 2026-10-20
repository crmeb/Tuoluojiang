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

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 知识库.
 */
class KnowledgeBase extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     */
    protected $table = 'knowledge_base';

    /**
     * 主键.
     */
    protected $primaryKey = 'id';

    /**
     * 类型转换.
     */
    protected $casts = [
        'id'                 => 'integer',
        'uid'                => 'integer',
        'space_type'         => 'integer',
        'visibility'         => 'array',
        'embedding_model_id' => 'integer',
        'chunk_mode'         => 'integer',
        'chunk_size'         => 'integer',
        'chunk_overlap'      => 'integer',
        'doc_count'          => 'integer',
        'chunk_count'        => 'integer',
        'status'             => 'integer',
        'sort'               => 'integer',
        'created_at'         => 'datetime:Y-m-d H:i:s',
        'updated_at'         => 'datetime:Y-m-d H:i:s',
        'deleted_at'         => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联创建人.
     */
    public function user(): HasOne
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    /**
     * 关联文档.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class, 'kb_id', 'id');
    }

    /**
     * 名称模糊搜索.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameLike($query, $value): void
    {
        $query->where('name', 'like', '%' . $value . '%');
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
     * 创建人作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value): void
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }

    /**
     * 空间类型作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeSpaceType($query, $value): void
    {
        is_array($value) ? $query->whereIn('space_type', $value) : $query->where('space_type', $value);
    }

    /**
     * 状态作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value): void
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }
}
