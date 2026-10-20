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

use App\Constants\AttachEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\System\Attach;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 知识库文档.
 */
class KnowledgeDocument extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     */
    protected $table = 'knowledge_document';

    /**
     * 主键.
     */
    protected $primaryKey = 'id';

    /**
     * 类型转换.
     */
    protected $casts = [
        'id'           => 'integer',
        'kb_id'        => 'integer',
        'uid'          => 'integer',
        'file_size'    => 'integer',
        'attach_id'    => 'integer',
        'status'       => 'integer',
        'enabled'      => 'integer',
        'chunk_count'  => 'integer',
        'processed_at' => 'datetime:Y-m-d H:i:s',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
        'deleted_at'   => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联知识库.
     */
    public function base(): HasOne
    {
        return $this->hasOne(KnowledgeBase::class, 'id', 'kb_id');
    }

    /**
     * 关联附件.
     */
    public function attach(): HasOne
    {
        return $this->hasOne(Attach::class, 'id', 'attach_id')
            ->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_KNOWLEDGE])
            ->select(['id', 'att_dir', 'real_name', 'name', 'att_size', 'att_type', 'file_ext', 'relation_id', 'relation_type', 'up_type']);
    }

    /**
     * 关联上传人.
     */
    public function user(): HasOne
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    /**
     * 关联分块.
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'doc_id', 'id')->orderBy('chunk_index');
    }

    /**
     * 标题模糊搜索.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeTitleLike($query, $value): void
    {
        $query->where('title', 'like', '%' . $value . '%');
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
     * 上传人作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUid($query, $value): void
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }

    /**
     * 文件类型作用域.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeFileType($query, $value): void
    {
        is_array($value) ? $query->whereIn('file_type', $value) : $query->where('file_type', $value);
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
