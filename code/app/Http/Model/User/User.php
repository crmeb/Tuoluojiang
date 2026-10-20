<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model\User;

use Tuoluojiang\Open\interfaces\TimeDataInterface;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as AuthUser;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Webpatser\Uuid\Uuid;

/**
 * 用户表.
 * @deprecated
 */
class User extends AuthUser implements JWTSubject, TimeDataInterface
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * 伪删除字段.
     */
    public const DELETED_AT = 'delete';

    /**
     * 表名.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * 主键.
     *
     * @var string
     */
    protected $primaryKey = 'uid';

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
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
        'uid'         => 'string',
        'age'         => 'integer',
        'entid'       => 'integer',
        'uni_online'  => 'integer',
        'birthday'    => 'datetime:Y-m-d H:i:s',
        'login_count' => 'integer',
        'marriage'    => 'integer',
        'sex'         => 'integer',
        'status'      => 'integer',
        'is_init'     => 'integer',
        'delete'      => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = ['password'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (isset($model->birthday)) {
                $model->age = birthday_to_age($model->birthday);
            }
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = str_replace('-', '', (string) Uuid::generate(4));
            }
        });
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
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'entId'      => $this->entid,
            'timestamps' => time(),
        ];
    }

    /**
     * 密码修改器.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ?: password_hash((string) time(), PASSWORD_BCRYPT);
    }

    /**
     * 密码修改器.
     */
    public function setOnlyPwdAttribute($value)
    {
        $this->attributes['only_pwd'] = md5($value);
    }

    /**
     * 状态作用域
     *
     * @param Builder $query
     *
     * @return mixed
     */
    public function scopeStatus($query, $value)
    {
        return $value !== '' ? $query->where('status', $value) : null;
    }

    /**
     * 性别作用域
     *
     * @param Builder $query
     *
     * @return mixed
     */
    public function scopeSex($query, $value)
    {
        return $value !== '' ? $query->where('sex', $value) : null;
    }

    /**
     * 手机号作用域
     *
     * @param Builder $query
     *
     * @return mixed
     */
    public function scopePhoneLike($query, $value)
    {
        return $value !== '' ? $query->where('phone', 'like', "%{$value}%") : null;
    }

    /**
     * 手机号作用域
     * @param Builder $query
     * @return mixed
     */
    public function scopePhone($query, $value)
    {
        return $value !== '' ? $query->where('phone', $value) : null;
    }

    /**
     * 姓名作用域
     *
     * @param Builder $query
     *
     * @return mixed
     */
    public function scopeName($query, $value)
    {
        return $value !== '' ? $query->where('real_name', 'like', "%{$value}%") : null;
    }

    /**
     * ID作用域
     *
     * @param Builder $query
     *
     * @return mixed
     */
    public function scopeUid($query, $value)
    {
        if (is_array($value) && $value) {
            return $query->whereIn('uid', $value);
        }
        return $value !== '' ? $query->where('uid', $value) : null;
    }

    /**
     * ID不等于作用域
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeNotUid($query, $value)
    {
        return $value ? $query->where('uid', '<>', $value) : null;
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
}
