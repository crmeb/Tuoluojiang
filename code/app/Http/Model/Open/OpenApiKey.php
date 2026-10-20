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

namespace App\Http\Model\Open;

use Tuoluojiang\Open\interfaces\TimeDataInterface;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webpatser\Uuid\Uuid;

class OpenApiKey extends AuthUser implements JWTSubject, TimeDataInterface
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'openapi_key';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 字段黑名单.
     * @var array
     */
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'id'         => 'integer',
        'uid'        => 'integer',
        'status'     => 'integer',
        'last_time'  => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->ak = str_replace('-', '', (string)Uuid::generate(4));
            $model->sk = md5($model->ak . time());
            $model->created_at = now();
            $model->updated_at = now();
        });

        static::updating(function ($model) {
            $model->updated_at = now();
        });
    }

    public function setAuthAttribute($value)
    {
        $this->attributes['auth'] = json_encode($value);
    }

    public function getAuthAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setCrudAuthAttribute($value)
    {
        $this->attributes['crud_auth'] = json_encode($value);
    }

    public function getCrudAuthAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function scopeNameLike($query, $value)
    {
        $query->where(fn($q) => $q->where('title', 'like', '%' . $value . '%')->orWhere('info', 'like', '%' . $value . '%')->orWhere('ak', 'like', '%' . $value . '%'));
    }

    public function scopeAk($query, $value)
    {
        $query->where('ak', $value);
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
            'timestamps' => time(),
        ];
    }
}
