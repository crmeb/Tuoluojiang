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

namespace App\Http\Model\Admin;

use App\Http\Model\Company\UserEducation;
use App\Http\Model\Company\UserJobAnalysis;
use App\Http\Model\Company\UserPosition;
use App\Http\Model\Company\UserWork;
use App\Http\Model\Frame\Frame;
use App\Http\Model\Frame\FrameAssist;
use App\Http\Model\Frame\FrameScope;
use App\Http\Model\Position\Job;
use App\Http\Model\Work\WorkMember;
use App\Observers\AdminObserver;
use Tuoluojiang\Open\interfaces\TimeDataInterface;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as AuthUser;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * 用户表.
 */
class Admin extends AuthUser implements JWTSubject, TimeDataInterface
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * 表名.
     *
     * @var string
     */
    protected $table = 'admin';

    /**
     * 主键.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 字段黑名单.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @var string[]
     */
    protected $casts = [
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
        'uid'            => 'string',
        'id'             => 'integer',
        'value'       => 'integer',
        'type'       => 'integer',
        'job'            => 'integer',
        'is_admin'       => 'integer',
        'uni_online'     => 'integer',
        'login_count'    => 'integer',
        'status'         => 'integer',
        'is_init'        => 'integer',
        'work_member_id' => 'integer',
        'deleted_at'     => 'datetime:Y-m-d H:i:s',
        'e_sign'         => 'integer',
    ];

    protected $hidden = ['password', 'mcp_key', 'deleted_at'];

    /**
     * 生成 MCP 工具调用唯一值.
     */
    public static function generateMcpKey(): string
    {
        do {
            $key = bin2hex(random_bytes(24));
        } while (self::where('mcp_key', $key)->exists());

        return $key;
    }

    /**
     * 密码修改器.
     * @param mixed $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ?: password_hash((string) time(), PASSWORD_BCRYPT);
    }

    /**
     * 权限修改器.
     * @param mixed $value
     */
    public function setRolesAttribute($value)
    {
        $roles                     = $this->formatRoles($value);
        $this->attributes['roles'] = $roles ? json_encode($roles) : '';
    }

    /**
     * 权限获取器.
     * @param mixed $value
     */
    public function getRolesAttribute($value)
    {
        return $this->formatRoles($value);
    }

    public static function boot()
    {
        parent::boot();
        static::observe(AdminObserver::class);
    }

    /**
     * 设置主键id.
     *
     * @return mixed|string
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * 自定义声明.
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'entId'      => $this->entid,
            'timestamps' => time(),
        ];
    }

    public function info(): HasOne
    {
        return $this->hasOne(AdminInfo::class, 'uid', 'uid');
    }

    public function user_card(): HasOne
    {
        return $this->hasOne(AdminInfo::class, 'id', 'id');
    }

    public function card(): HasOne
    {
        return $this->hasOne(AdminInfo::class, 'id', 'id');
    }

    public function frameIds(): HasMany
    {
        return $this->hasMany(FrameAssist::class, 'user_id', 'id');
    }

    public function member()
    {
        return $this->hasOne(WorkMember::class, 'id', 'work_member_id');
    }

    /**
     * 一对一关联.
     */
    public function isAdmin(): HasOne
    {
        return $this->hasOne(FrameAssist::class, 'user_id', 'id');
    }

    /**
     * 状态作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopeStatus($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 性别作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopeSex($query, $value)
    {
        return $value !== '' ? $query->where('sex', $value) : null;
    }

    public function scopeNotId($query, $value)
    {
        is_array($value) ? $query->whereNotIn('id', $value) : $query->where('id', '!=', $value);
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    /**
     * 手机号作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopePhoneLike($query, $value)
    {
        $query->where('phone', 'like', "%{$value}%");
    }

    /**
     * 手机号作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopePhone($query, $value)
    {
        $query->where('phone', $value);
    }

    /**
     * 姓名作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopeName($query, $value)
    {
        $query->where('name', 'like', "%{$value}%");
    }

    /**
     * UID作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return mixed
     */
    public function scopeUid($query, $value)
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }

    /**
     * ID不等于作用域
     *
     * @param Builder $query
     * @param mixed $value
     *
     * @return Builder
     */
    public function scopeNotUid($query, $value)
    {
        return $value ? $query->where('uid', '<>', $value) : null;
    }

    /**
     * 岗位.
     * @return HasOne
     */
    public function job()
    {
        return $this->hasOne(Job::class, 'id', 'job');
    }

    /**
     * 远程一对多关联部门.
     * @return HasManyThrough
     */
    public function frames()
    {
        return $this->hasManyThrough(
            Frame::class,
            FrameAssist::class,
            'user_id',
            'id',
            'id',
            'frame_id'
        )->select([
            'frame.id',
            'frame.name',
            'frame.user_count',
            'frame_assist.is_mastart',
            'frame_assist.is_admin',
            'frame_assist.superior_uid',
        ])->orderByDesc('frame_assist.is_mastart');
    }

    /**
     * 远程一对多关联负责部门.
     * @return HasManyThrough
     */
    public function manage_frames()
    {
        return $this->hasManyThrough(
            Frame::class,
            FrameAssist::class,
            'user_id',
            'id',
            'id',
            'frame_id'
        )->select([
            'frame.id',
            'frame.name',
            'frame.user_count',
            'frame_assist.is_mastart',
            'frame_assist.is_admin',
            'frame_assist.superior_uid',
        ])->where('frame_assist.is_admin', 1)->orderByDesc('frame_assist.is_mastart');
    }

    /**
     * 远程一对一关联主部门.
     * @return HasManyThrough
     */
    public function frame()
    {
        return $this->hasOneThrough(
            Frame::class,
            FrameAssist::class,
            'user_id',
            'id',
            'id',
            'frame_id'
        )->select([
            'frame.id',
            'frame.name',
            'frame.user_count',
            'frame_assist.is_mastart',
            'frame_assist.is_admin',
            'frame_assist.superior_uid',
        ])->where('frame_assist.is_mastart', 1);
    }

    /**
     * 远程一对一关联上级.
     * @return HasManyThrough
     */
    public function super()
    {
        return $this->hasOneThrough(
            self::class,
            FrameAssist::class,
            'user_id',
            'id',
            'id',
            'superior_uid'
        );
    }

    /**
     * 工作经历.
     * @return HasMany
     */
    public function works()
    {
        return $this->hasMany(UserWork::class, 'user_id', 'id');
    }

    /**
     * 教育经历.
     * @return HasMany
     */
    public function educations()
    {
        return $this->hasMany(UserEducation::class, 'user_id', 'id');
    }

    /**
     * 任职经历.
     * @return HasMany
     */
    public function positions()
    {
        return $this->hasMany(UserPosition::class, 'user_id', 'id');
    }

    public function scope()
    {
        return $this->hasManyThrough(Frame::class, FrameScope::class, 'uid', 'id', 'id', 'link_id');
    }

    /**
     * name 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameEq($query, $value)
    {
        is_array($value) ? $query->whereIn('name', $value) : $query->where('name', $value);
    }

    /**
     * 一对一关联用户分析.
     * @return HasOne
     */
    public function jobAnalysis()
    {
        return $this->hasOne(UserJobAnalysis::class, 'uid', 'id');
    }

    /**
     * name作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameLike($query, $value): void
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    public function scopeIsWork($query, $value)
    {
        $query->where('work_member_id', '<>', 0);
    }

    public function work()
    {
        return $this->hasOne(WorkMember::class, 'id', 'work_member_id')->where('corp_id', sys_config('wechat_work_corpid'));
    }

    /**
     * 设置时间.
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 规范化用户角色：去掉 0/空值/非法值，并保持原顺序去重.
     *
     * @param mixed $value
     */
    private function formatRoles($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        $roles = [];
        foreach ($value as $roleId) {
            $roleId = $this->parseRoleId($roleId);
            if ($roleId && ! in_array($roleId, $roles, true)) {
                $roles[] = $roleId;
            }
        }

        return $roles;
    }

    /**
     * 解析角色ID，仅接受正整数或纯数字字符串.
     *
     * @param mixed $roleId
     */
    private function parseRoleId($roleId): int
    {
        if (is_int($roleId)) {
            return $roleId > 0 ? $roleId : 0;
        }

        if (is_string($roleId) && ctype_digit(trim($roleId))) {
            return (int) $roleId;
        }

        return 0;
    }
}
