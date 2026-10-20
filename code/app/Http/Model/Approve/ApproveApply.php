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

namespace App\Http\Model\Approve;

use App\Http\Model\Admin\Admin;
use App\Http\Model\Crud\SystemCrudApprove;
use App\Http\Model\Crud\SystemCrudApproveRule;
use App\Http\Model\Frame\Frame;
use App\Http\Model\Frame\FrameAssist;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * 申请记录表
 * Class ApproveApply.
 */
class ApproveApply extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'approve_apply';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'entid'      => 'integer',
        'card_id'    => 'integer',
        'approve_id' => 'integer',
        'examine'    => 'integer',
        'status'     => 'integer',
        'crud_id'    => 'integer',
        'link_id'    => 'integer',
        'apply_id'   => 'integer',
        'is_recall'  => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * 一对一关联.
     * @return HasOne
     */
    public function approve()
    {
        return $this->hasOne(Approve::class, 'id', 'approve_id')->withTrashed();
    }

    /**
     * 一对一关联.
     * @return HasOne
     */
    public function crudApprove()
    {
        return $this->hasOne(SystemCrudApprove::class, 'id', 'approve_id')->withTrashed();
    }

    /**
     * 审批配置多态访问器：根据crud_id返回对应的审批配置.
     * 需要预加载 approve 和 crudApprove 关联后使用.
     * @return Approve|SystemCrudApprove|null
     */
    public function getApproveConfigAttribute()
    {
        return $this->crud_id ? $this->crudApprove : $this->approve;
    }

    /**
     * 一对一关联用户名片.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    public function frame()
    {
        return $this->hasOneThrough(
            Frame::class,
            FrameAssist::class,
            'user_id',
            'id',
            'user_id',
            'frame_id'
        )->where('frame_assist.is_mastart', 1)
            ->select([
                'frame.id',
                'frame.name',
                'frame.user_count',
                'frame_assist.is_mastart',
            ]);
    }

    /**
     * 一对多关联.
     * @return HasMany
     */
    public function content()
    {
        return $this->hasMany(ApproveContent::class, 'apply_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(ApproveUser::class, 'apply_id', 'id')->groupBy(['node_id']);
    }

    /**
     * 兼容旧版调用：审批人关联.
     * @return HasMany
     */
    public function approve_users()
    {
        return $this->users();
    }

    /**
     * 一对多关联.
     * @return HasMany
     */
    public function form()
    {
        return $this->hasMany(ApproveForm::class, 'approve_id', 'id');
    }

    /**
     * 一对多关联.
     * @return HasMany
     */
    public function reply()
    {
        return $this->hasMany(ApproveReply::class, 'apply_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function rules()
    {
        return $this->hasOne(ApproveRule::class, 'approve_id', 'approve_id');
    }

    /**
     * @return HasOne
     */
    public function crud_rules()
    {
        return $this->hasOne(SystemCrudApproveRule::class, 'approve_id', 'approve_id');
    }

    /**
     * @return HasOne
     */
    public function recall()
    {
        return $this->hasOne(self::class, 'apply_id', 'id')->where('is_recall', 1)->where('status', 0);
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
     * entid作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeEntid($query, $value)
    {
        $query->where('entid', $value);
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
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeCardId($query, $value)
    {
        is_array($value) ? $query->whereIn('card_id', $value) : $query->where('card_id', $value);
    }

    /**
     * user_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeUserId($query, $value)
    {
        is_array($value) ? $query->whereIn('user_id', $value) : $query->where('user_id', $value);
    }

    /**
     * id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotCardId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('card_id', $value) : $query->whereNot('card_id', $value);
    }

    /**
     * status作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value)
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }

    /**
     * node_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNodeId($query, $value)
    {
        $query->where('node_id', $value);
    }

    /**
     * status作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeNotStatus($query, $value)
    {
        $query->where('status', '<>', $value);
    }

    /**
     * approve_id作用域
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeApproveId($query, $value)
    {
        is_array($value) ? $query->whereIn('approve_id', $value) : $query->where('approve_id', $value);
    }

    /**
     * 职级.
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeNameLike($query, $value)
    {
        $query->where(function ($q) use ($value) {
            $q->orWhere('name', 'like', '%' . $value . '%')->orWhere('number', 'like', '%' . $value . '%');
        });
    }
}
