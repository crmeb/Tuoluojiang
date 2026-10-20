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

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI 模型配置模型.
 *
 * 表名 chat_models（一行仅存一个模型）.
 * 主要字段：name 配置名称、provider 供应商驱动、config_type 配置类型（1 预设/2 自定义/3 一号通）、
 * base_url 接口地址、is_full_url 是否完整 URL（开启后不拼接 path）、
 * chat_path/embedding_path 接口路径、model_id 模型 ID、model_type 类型（1 会话 / 2 向量）、
 * api_key 密钥、status 启用状态、
 * enable_thinking 思考模式（0 关闭 / 1 开启 / 2 跟随默认）、json 高级配置（上下文窗口、采样等）.
 * 关系：user 创建人.
 */
class AiModel extends BaseModel
{
    use SoftDeletes;

    /**
     * 主键.
     */
    protected $primaryKey = 'id';

    /**
     * 表名.
     */
    protected $table = 'chat_models';

    /**
     * 类型转换.
     */
    protected $casts = [
        'id'              => 'integer',
        'uid'             => 'integer',
        'config_type'     => 'integer',
        'is_full_url'     => 'integer',
        'model_type'      => 'integer',
        'status'          => 'integer',
        'enable_thinking' => 'integer',
        'json'            => 'array',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
        'deleted_at'      => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联创建人.
     */
    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    /**
     * 名称模糊搜索.
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameLike($query, $value): void
    {
        $query->where('name', 'like', "%{$value}%");
    }
}
