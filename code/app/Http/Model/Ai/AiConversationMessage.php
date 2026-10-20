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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI 会话消息模型.
 *
 * 表名 chat_record.
 * 主要字段：chat_history_id 会话 ID、chat_record_uuid 消息 UUID、
 * problem_text 提问、answer_text 回答、sql_text 扩展 JSON（引用/工具调用）、
 * details 原始响应、prompt_tokens/completion_tokens/tokens Token 统计.
 * 通过访问器 conversation_id、message_uuid、content、references、tool_calls 映射旧字段.
 */
class AiConversationMessage extends BaseModel
{
    use SoftDeletes;

    protected $table = 'chat_record';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'                => 'integer',
        'chat_history_id'   => 'integer',
        'vote_status'       => 'integer',
        'prompt_tokens'     => 'integer',
        'completion_tokens' => 'integer',
        'tokens'            => 'integer',
        'run_time'          => 'integer',
        'uid'               => 'integer',
        'is_show'           => 'integer',
        'created_at'        => 'datetime:Y-m-d H:i:s',
        'updated_at'        => 'datetime:Y-m-d H:i:s',
        'deleted_at'        => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeConversationId($query, $value): void
    {
        $query->where('chat_history_id', $value);
    }

    public function scopeRole($query, $value): void
    {
        if ($value === 'user') {
            $query->where('problem_text', '<>', '');
        } elseif ($value === 'assistant') {
            $query->where('answer_text', '<>', '');
        }
    }

    public function scopeMessageUuid($query, $value): void
    {
        $query->where('chat_record_uuid', $value);
    }

    public function getConversationIdAttribute(): int
    {
        return (int) ($this->attributes['chat_history_id'] ?? 0);
    }

    public function setConversationIdAttribute($value): void
    {
        $this->attributes['chat_history_id'] = (int) $value;
    }

    public function getMessageUuidAttribute(): string
    {
        return (string) ($this->attributes['chat_record_uuid'] ?? '');
    }

    public function setMessageUuidAttribute($value): void
    {
        $this->attributes['chat_record_uuid'] = (string) $value;
    }

    public function getContentAttribute(): string
    {
        return (string) (($this->attributes['answer_text'] ?? '') ?: ($this->attributes['problem_text'] ?? ''));
    }

    public function setContentAttribute($value): void
    {
        $this->attributes['answer_text'] = (string) $value;
    }

    public function getRawResponseAttribute(): string
    {
        return (string) ($this->attributes['details'] ?? '');
    }

    public function setRawResponseAttribute($value): void
    {
        $this->attributes['details'] = (string) $value;
    }

    public function getReferencesAttribute(): array
    {
        $sql = json_decode((string) ($this->attributes['sql_text'] ?? ''), true);
        return is_array($sql) ? (array) ($sql['references'] ?? []) : [];
    }

    public function setReferencesAttribute($value): void {}

    public function getToolCallsAttribute(): array
    {
        $sql = json_decode((string) ($this->attributes['sql_text'] ?? ''), true);
        return is_array($sql) ? (array) ($sql['tool_calls'] ?? []) : [];
    }

    public function setToolCallsAttribute($value): void {}

    public function getStatusAttribute(): int
    {
        return 1;
    }

    public function setStatusAttribute($value): void {}
}
