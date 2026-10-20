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

use App\Constants\Ai\AiConversationEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Service\Knowledge\KnowledgeBindingParser;
use App\Http\Service\Ai\Agent\AgentConfig;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI 应用模型.
 *
 * 表名 chat_applications.
 * 主要字段：name 名称、models_id 绑定模型、enable_thinking 是否启用模型深度思考、
 * source_type 插件类型（SQL/MCP/知识库）、
 * app_type 应用类型（0 内部/1 外部）、share_key/share_enabled 外部分享、auth_ids/edit_ids 权限.
 * 关系：user 创建人、model 绑定模型；knowledge_ids 存关联知识库 ID 数组.
 * 外部应用（app_type=1）支持 share_key 分享、guest_json 访客配置与 Widget 嵌入.
 * draft_json 为未发布草稿；正式会话只读已发布列，调试请求才覆盖为本字段.
 */
class AiApplication extends BaseModel
{
    use SoftDeletes;

    protected $table = 'chat_applications';

    protected $primaryKey = 'id';

    protected $appends = [
        'source_types',
    ];

    protected $casts = [
        'id'            => 'integer',
        'uid'           => 'integer',
        'models_id'     => 'integer',
        'enable_thinking' => 'integer',
        'use_limit'     => 'integer',
        'count_number'  => 'integer',
        'app_type'      => 'integer',
        'share_enabled' => 'integer',
        'guest_use_limit' => 'integer',
        'guest_json'    => 'array',
        'share_expire_at' => 'datetime:Y-m-d H:i:s',
        'status'        => 'integer',
        'sort'          => 'integer',
        'auth_ids'      => 'array',
        'edit_ids'      => 'array',
        'tables'        => 'array',
        'prologue_list' => 'array',
        'json'          => 'array',
        'keyword'       => 'array',
        'mcp_json'        => 'array',
        'mcp_service_ids' => 'array',
        'knowledge_ids'   => 'array',
        'agent_json'    => 'array',
        'draft_json'    => 'array',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
        'deleted_at'    => 'datetime:Y-m-d H:i:s',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    public function model(): HasOne
    {
        return $this->hasOne(AiModel::class, 'id', 'models_id');
    }

    public function getSourceTypesAttribute(): array
    {
        $types = $this->normalizeSourceTypes($this->attributes['source_type'] ?? null);
        if (
            KnowledgeBindingParser::hasIds($this->knowledge_ids ?? null)
            && ! in_array(AiConversationEnum::SOURCE_KNOWLEDGE, $types, true)
        ) {
            $types[] = AiConversationEnum::SOURCE_KNOWLEDGE;
        }

        return $types;
    }

    public function setSourceTypesAttribute($value): void
    {
        $this->attributes['source_type'] = json_encode(
            $this->normalizeSourceTypes($value),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function sourceTypeMask(): int
    {
        return AiConversationEnum::sourceTypeValue($this->source_types);
    }

    public function agentConfig(): AgentConfig
    {
        return AgentConfig::fromApplication($this);
    }

    public function scopeNameLike($query, $value): void
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    private function normalizeSourceTypes(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value   = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            $value = AiConversationEnum::sourceTypes((int) $value);
        }

        $types = array_values(array_unique(array_intersect(
            array_map('intval', $value),
            AiConversationEnum::SOURCE_TYPES
        )));

        return $types ?: [AiConversationEnum::SOURCE_NORMAL];
    }
}
