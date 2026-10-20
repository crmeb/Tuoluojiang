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

use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 用户简历.
 */
class UserResume extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'user_resume';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'sex'        => 'integer',
        'age'        => 'integer',
        'marriage'   => 'integer',
        'is_part'    => 'integer',
        'work_years' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public static function boot()
    {
        parent::boot();
        self::updating(function ($model) {
            if (isset($model->birthday) && $model->birthday) {
                $model->age = birthday_to_age($model->birthday);
            }
        });
    }

    /**
     * 工作经历.
     * @return HasMany
     */
    public function works()
    {
        return $this->hasMany(UserWorkHistory::class, 'resume_id', 'id');
    }

    /**
     * 教育经历.
     * @return HasMany
     */
    public function educations()
    {
        return $this->hasMany(UserEducationHistory::class, 'resume_id', 'id');
    }

    /**
     * uid作用域
     * @return mixed
     */
    public function scopeUid($query, $val)
    {
        if ($val !== '') {
            return $query->where('uid', $val);
        }
    }

    /**
     * uid作用域
     * @return mixed
     */
    public function scopeId($query, $val)
    {
        if ($val !== '') {
            return $query->where('id', $val);
        }
    }

    /**
     * NOtId作用域
     * @return mixed
     */
    public function scopeNotId($query, $val)
    {
        if ($val !== '') {
            return $query->where('id', '<>', $val);
        }
    }

    /**
     * id作用域
     * @param mixed $value
     * @return mixed
     */
    public function scopeIds($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('id', $value);
        }
        return $query->where('id', $value);
    }

    /**
     * uid作用域
     */
    public function scopeUids($query, $value)
    {
        $query->whereIn('uid', $value);
    }
}
