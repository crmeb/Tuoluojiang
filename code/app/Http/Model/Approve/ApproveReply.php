<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model\Approve;

use App\Constants\AttachEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\System\Attach;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;

/**
 * 审核留言表
 * Class ApproveReply.
 */
class ApproveReply extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'approve_reply';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'card_id'    => 'integer',
        'apply_id'   => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 一对多关联附件.
     * @return HasMany
     */
    public function files()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_APPROVE_REPLY]);
    }

    /**
     * 一对一关联.
     * @return HasOne
     */
    public function approve()
    {
        return $this->hasOne(Approve::class, 'id', 'approve_id');
    }

    /**
     * 一对一关联用户名片.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'name', 'avatar', 'uid']);
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotid($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '<>', $value);
    }

    /**
     * cate_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeCateId($query, $value)
    {
        $query->where('cate_id', $value);
    }

    /**
     * entid作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeEntid($query, $value)
    {
        $query->where('entid', $value);
    }

    /**
     * 职级.
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        if ($value) {
            return $query->where('name', 'like', '%' . $value . '%');
        }
    }
}
