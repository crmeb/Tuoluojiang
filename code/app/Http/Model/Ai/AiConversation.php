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
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI 会话模型.
 *
 * 表名 chat_history.
 * 主要字段：user_id 用户 ID、chat_application_id 应用 ID、title 标题、
 * guest_token 访客凭证、session_type 会话类型（0 登录/1 访客）、is_show 显示状态、top_up 置顶时间.
 * 关系：app 所属 AI 应用.
 * 访客会话：user_id=0，guest_token 存 UUID，session_type=1.
 */
class AiConversation extends BaseModel
{
    use SoftDeletes;

    protected $table = 'chat_history';

    protected $primaryKey = 'id';

    protected $casts = [
        'id'              => 'integer',
        'user_id'         => 'integer',
        'session_type'    => 'integer',
        'chat_application_id' => 'integer',
        'is_show'         => 'integer',
        'top_up'          => 'datetime:Y-m-d H:i:s',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
        'deleted_at'      => 'datetime:Y-m-d H:i:s',
    ];

    public function app(): HasOne
    {
        return $this->hasOne(AiApplication::class, 'id', 'chat_application_id');
    }

    /** 按访客 UUID 筛选. */
    public function scopeGuestToken($query, $value): void
    {
        $query->where('guest_token', $value);
    }

    /** 按 session_type 筛选（0=登录用户，1=访客）. */
    public function scopeSessionType($query, $value): void
    {
        is_array($value) ? $query->whereIn('session_type', $value) : $query->where('session_type', $value);
    }

    public function getGuestTokenAttribute(): string
    {
        return (string) ($this->attributes['guest_token'] ?? '');
    }

    public function setGuestTokenAttribute($value): void
    {
        $this->attributes['guest_token'] = (string) $value;
    }

    public function getSessionTypeAttribute(): int
    {
        return (int) ($this->attributes['session_type'] ?? AiConversationEnum::SESSION_TYPE_USER);
    }

    public function setSessionTypeAttribute($value): void
    {
        $this->attributes['session_type'] = (int) $value;
    }

    /** 是否为访客会话. */
    public function isGuestSession(): bool
    {
        return (int) ($this->attributes['session_type'] ?? AiConversationEnum::SESSION_TYPE_USER) === AiConversationEnum::SESSION_TYPE_GUEST;
    }

    public function scopeUid($query, $value): void
    {
        $query->where('user_id', $value);
    }

    public function scopeAppId($query, $value): void
    {
        $query->where('chat_application_id', $value);
    }

    public function scopeStatus($query, $value): void
    {
        is_array($value) ? $query->whereIn('is_show', $value) : $query->where('is_show', $value);
    }

    public function getUidAttribute(): int
    {
        return (int) ($this->attributes['user_id'] ?? 0);
    }

    public function setUidAttribute($value): void
    {
        $this->attributes['user_id'] = (int) $value;
    }

    public function getAppIdAttribute(): int
    {
        return (int) ($this->attributes['chat_application_id'] ?? 0);
    }

    public function setAppIdAttribute($value): void
    {
        $this->attributes['chat_application_id'] = (int) $value;
    }

    public function getStatusAttribute(): int
    {
        return (int) ($this->attributes['is_show'] ?? 1);
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['is_show'] = (int) $value;
    }

    public function getTopAtAttribute()
    {
        return $this->attributes['top_up'] ?? null;
    }

    public function setTopAtAttribute($value): void
    {
        $this->attributes['top_up'] = $value;
    }

    public function getLastMessageAtAttribute()
    {
        return $this->attributes['updated_at'] ?? null;
    }

    public function setLastMessageAtAttribute($value): void
    {
        $this->attributes['updated_at'] = $value;
    }
}
